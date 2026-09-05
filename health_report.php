<?php
// Feature 10: AI Health Report Summary
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}

$user = $_SESSION['username'];
$message = "";
$summary = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_record'])) {
        $pet = trim($_POST['pet_name']);
        $note = trim($_POST['record_text']);
        $date = $_POST['record_date'];
        $stmt = $conn->prepare("INSERT INTO health_records (username, pet_name, record_text, record_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user, $pet, $note, $date);
        $message = $stmt->execute()
            ? "<p style='color:green;font-weight:bold;'>Health record added.</p>"
            : "<p style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
        $stmt->close();
    } elseif (isset($_POST['generate_summary'])) {
        $pet = $_POST['summary_pet'];
        $stmt = $conn->prepare("SELECT record_text, record_date FROM health_records WHERE username = ? AND pet_name = ? ORDER BY record_date ASC");
        $stmt->bind_param("ss", $user, $pet);
        $stmt->execute();
        $res = $stmt->get_result();
        $records = [];
        while ($row = $res->fetch_assoc()) $records[] = $row;
        $stmt->close();
        $summary = generateHealthSummary($pet, $records);
    }
}

/**
 * IMPORTANT NOTE FOR THE TEAM:
 * This is a lightweight, rule-based (extractive) summarizer that runs
 * entirely in PHP, so this feature works right now with zero external
 * dependencies or API keys.
 *
 * To upgrade this to a real AI/RAG-based summary (matching README item #8,
 * the AI Chatbot with RAG), replace the body of this function with a call
 * to your LLM provider's API: send the concatenated $records text as
 * context in the prompt, and return the model's generated text instead of
 * the keyword-based summary below. Keep the function signature the same
 * so nothing else on this page needs to change.
 */
function generateHealthSummary($pet, $records) {
    if (empty($records)) {
        return "No health records found for " . htmlspecialchars($pet) . " yet. Add a record below to generate a summary.";
    }

    $watchWords = ['vomit','fever','limp','cough','allergy','allergic','infection','pain',
                   'lethargic','diarrhea','rash','swelling','injury','surgery','vaccine',
                   'vaccinated','checkup','healthy','recovered'];
    $fullText = strtolower(implode(' ', array_column($records, 'record_text')));
    $keywordCounts = [];
    foreach ($watchWords as $w) {
        $count = substr_count($fullText, $w);
        if ($count > 0) $keywordCounts[$w] = $count;
    }
    arsort($keywordCounts);
    $topFindings = array_slice(array_keys($keywordCounts), 0, 5);

    $first = $records[0];
    $last = $records[count($records) - 1];

    $summary = "<strong>Health Summary for " . htmlspecialchars($pet) . "</strong><br>";
    $summary .= count($records) . " record(s) on file, from " . htmlspecialchars($first['record_date']) .
                " to " . htmlspecialchars($last['record_date']) . ".<br><br>";

    $summary .= "<strong>Recurring themes noticed:</strong> ";
    $summary .= !empty($topFindings) ? htmlspecialchars(implode(', ', $topFindings)) : "none of the common flags detected";
    $summary .= "<br><br>";

    $summary .= "<strong>Most recent note (" . htmlspecialchars($last['record_date']) . "):</strong><br>" .
                nl2br(htmlspecialchars($last['record_text']));
    return $summary;
}

$petsStmt = $conn->prepare("SELECT DISTINCT pet_name FROM health_records WHERE username = ?");
$petsStmt->bind_param("s", $user);
$petsStmt->execute();
$petsRes = $petsStmt->get_result();
$pets = [];
while ($row = $petsRes->fetch_assoc()) $pets[] = $row['pet_name'];
$petsStmt->close();

$allStmt = $conn->prepare("SELECT pet_name, record_text, record_date FROM health_records WHERE username = ? ORDER BY record_date DESC");
$allStmt->bind_param("s", $user);
$allStmt->execute();
$allRes = $allStmt->get_result();
$allRecords = [];
while ($row = $allRes->fetch_assoc()) $allRecords[] = $row;
$allStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Health Report - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:800px; margin:0 auto; }
    .card { background:#fff; padding:20px; border-radius:15px; box-shadow:0 4px 8px rgba(0,0,0,0.1); margin-bottom:20px; }
    input, select, textarea, button { width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; font-family:inherit; }
    button { background:#ffb6c1; font-weight:bold; cursor:pointer; border:none; }
    button:hover { background:#ff8fa3; }
    .summary-box { background:#eafaf1; border:2px dashed #27ae60; border-radius:10px; padding:15px; margin-top:15px; }
    .record { border-bottom:1px solid #eee; padding:10px 0; }
    .record small { color:#888; }
    .ai-tag { background:#8e44ad; color:#fff; padding:2px 8px; border-radius:10px; font-size:0.75em; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-heartbeat"></i> AI Health Report Summary</h1>
    <?php echo $message; ?>

    <div class="card">
        <h3>Add a Health Record</h3>
        <form method="POST">
            <input type="text" name="pet_name" placeholder="Pet's Name" required>
            <textarea name="record_text" rows="3" placeholder="Vet visit notes, symptoms, medication, etc." required></textarea>
            <input type="date" name="record_date" required>
            <button type="submit" name="add_record" value="1">Add Record</button>
        </form>
    </div>

    <div class="card">
        <h3><span class="ai-tag">AI</span> Generate Summary</h3>
        <?php if (empty($pets)): ?>
            <p>Add at least one health record above to generate a summary.</p>
        <?php else: ?>
            <form method="POST">
                <select name="summary_pet" required>
                    <?php foreach ($pets as $p): ?>
                        <option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="generate_summary" value="1">Generate AI Summary</button>
            </form>
        <?php endif; ?>
        <?php if ($summary): ?>
            <div class="summary-box"><?php echo $summary; ?></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>All Records</h3>
        <?php if (empty($allRecords)): ?>
            <p>No records yet.</p>
        <?php else: ?>
            <?php foreach ($allRecords as $r): ?>
                <div class="record">
                    <strong><?php echo htmlspecialchars($r['pet_name']); ?></strong> —
                    <small><?php echo htmlspecialchars($r['record_date']); ?></small>
                    <p><?php echo nl2br(htmlspecialchars($r['record_text'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
