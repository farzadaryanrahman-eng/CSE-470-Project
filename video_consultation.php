<?php
// Feature 3: Video Consultation
// Uses Jitsi Meet (meet.jit.si), a free public video-call service that
// needs NO API key or account -- each booking gets a unique, hard-to-guess
// room name, and "Join" opens a real working video call in a new tab.
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}

$user = $_SESSION['username'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['book'])) {
        $pet = trim($_POST['pet_name']);
        $date = $_POST['session_date'];
        $time = $_POST['session_time'];
        $roomName = 'pawmart-vet-' . bin2hex(random_bytes(6));

        $stmt = $conn->prepare("INSERT INTO video_consultations (username, pet_name, session_date, session_time, status, room_name) VALUES (?, ?, ?, ?, 'Scheduled', ?)");
        $stmt->bind_param("sssss", $user, $pet, $date, $time, $roomName);
        $message = $stmt->execute()
            ? "<p style='color:green;font-weight:bold;'>Video consultation booked!</p>"
            : "<p style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
        $stmt->close();
    } elseif (isset($_POST['cancel'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE video_consultations SET status='Cancelled' WHERE id=? AND username=? AND status='Scheduled'");
        $stmt->bind_param("is", $id, $user);
        $stmt->execute();
        $stmt->close();
        $message = "<p style='color:green;font-weight:bold;'>Consultation cancelled.</p>";
    }
}

$stmt = $conn->prepare("SELECT id, pet_name, session_date, session_time, status, room_name FROM video_consultations WHERE username = ? ORDER BY session_date DESC, session_time DESC");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();
$sessions = [];
while ($row = $res->fetch_assoc()) $sessions[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Video Consultation - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:800px; margin:0 auto; }
    .card { background:#fff; padding:20px; border-radius:15px; box-shadow:0 4px 8px rgba(0,0,0,0.1); margin-bottom:20px; }
    input, select, button { width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; font-family:inherit; }
    button { background:#ffb6c1; font-weight:bold; cursor:pointer; border:none; }
    button:hover { background:#ff8fa3; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:10px; text-align:left; border-bottom:1px solid #eee; }
    th { background:#ffb6c1; color:#fff; }
    .status-Scheduled { color:#2980b9; font-weight:bold; }
    .status-Cancelled { color:#e74c3c; font-weight:bold; }
    .status-Completed { color:#27ae60; font-weight:bold; }
    .btn-join { display:inline-block; background:#10b981; color:#fff; padding:6px 12px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; }
    .btn-cancel { width:auto; padding:6px 12px; font-size:0.9em; background:#ef4444; color:#fff; }
    .empty { text-align:center; color:#888; padding:20px; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-video"></i> Video Consultation</h1>
    <?php echo $message; ?>

    <div class="card">
        <h3>Book a Video Consultation</h3>
        <form method="POST">
            <input type="text" name="pet_name" placeholder="Pet's Name" required>
            <input type="date" name="session_date" required>
            <input type="time" name="session_time" required>
            <button type="submit" name="book" value="1">Book Session</button>
        </form>
    </div>

    <div class="card">
        <h3>My Sessions</h3>
        <?php if (empty($sessions)): ?>
            <p class="empty">No video consultations booked yet.</p>
        <?php else: ?>
        <table>
            <tr><th>Pet</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr>
            <?php foreach ($sessions as $s): ?>
            <tr>
                <td><?php echo htmlspecialchars($s['pet_name']); ?></td>
                <td><?php echo htmlspecialchars($s['session_date']); ?></td>
                <td><?php echo htmlspecialchars($s['session_time']); ?></td>
                <td class="status-<?php echo htmlspecialchars($s['status']); ?>"><?php echo htmlspecialchars($s['status']); ?></td>
                <td>
                    <?php if ($s['status'] === 'Scheduled'): ?>
                        <a class="btn-join" target="_blank" rel="noopener" href="https://meet.jit.si/<?php echo urlencode($s['room_name']); ?>">
                            <i class="fas fa-video"></i> Join
                        </a>
                        <form method="POST" style="display:inline-block; width:auto;" onsubmit="return confirm('Cancel this session?');">
                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="cancel" value="1" class="btn-cancel">Cancel</button>
                        </form>
                    <?php else: ?>
                        <em>—</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
