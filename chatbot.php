<?php
// Feature 8: AI Chatbot with RAG
//
// HOW THIS WORKS RIGHT NOW:
// This is genuine retrieval (the "R" in RAG) -- each incoming message is
// keyword-scored against a small local knowledge base of pet-care topics,
// and the best-matching entry's answer is returned. It runs with zero
// external dependencies or API keys, so it works out of the box.
//
// TO UPGRADE TO FULL RAG (retrieve + generate with a real LLM):
// After the retrieval step below picks the top-matching $kb entries,
// instead of returning their answer text directly, send those entries as
// context in a prompt to an LLM API (e.g. POST to /v1/messages) along
// with the user's message, and return the model's generated reply. The
// retrieval logic (findBestMatches) does not need to change -- only the
// "generate" step at the bottom of handleMessage() does.
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}
$user = $_SESSION['username'];

// ---- Local knowledge base ----
$kb = [
    [
        'keywords' => ['bath', 'bathing', 'wash', 'shampoo'],
        'answer' => "Most dogs and cats only need a bath every 4-6 weeks unless they get visibly dirty -- over-bathing can dry out their skin. You can book a professional bath any time from the Grooming page."
    ],
    [
        'keywords' => ['feed', 'feeding', 'food', 'diet', 'how much', 'meal'],
        'answer' => "Feeding amounts depend on your pet's age, weight, and activity level -- the guideline on your pet food's packaging is a good starting point. For a diet plan tailored to your pet, it's best to check with your vet."
    ],
    [
        'keywords' => ['vaccine', 'vaccination', 'shots', 'immunization'],
        'answer' => "Puppies and kittens typically need a vaccination series starting around 6-8 weeks old, with boosters every few weeks until ~16 weeks, then annual or triennial boosters as adults. You can book a Vaccination visit from the Vet page for an exact schedule."
    ],
    [
        'keywords' => ['worm', 'deworm', 'parasite', 'flea', 'tick'],
        'answer' => "Routine deworming is usually recommended every 1-3 months for young pets and every 3-6 months for adults, alongside monthly flea/tick prevention. We carry Worming Tablets in Paw Mart, but please confirm dosing with your vet based on your pet's weight."
    ],
    [
        'keywords' => ['teeth', 'dental', 'brush teeth', 'breath'],
        'answer' => "Brushing your pet's teeth a few times a week with pet-safe toothpaste helps prevent tartar buildup. You can also book a professional Teeth Brush service from the Grooming page."
    ],
    [
        'keywords' => ['nail', 'nails', 'claw', 'trim'],
        'answer' => "Nails usually need trimming every 3-4 weeks if you don't hear them clicking on the floor. Nail Trimming is available as a grooming service if you'd rather leave it to a professional."
    ],
    [
        'keywords' => ['appointment', 'book', 'booking', 'schedule visit', 'reschedule', 'cancel appointment'],
        'answer' => "You can book a Vet or Grooming appointment from your Dashboard, and manage, reschedule, or cancel any pending appointment from the My Appointments page."
    ],
    [
        'keywords' => ['order', 'cart', 'wishlist', 'payment', 'checkout'],
        'answer' => "You can add products to your cart or wishlist in Paw Mart, then check out from the cart panel. Your past orders and payments are available under My Orders and Payment History."
    ],
    [
        'keywords' => ['hello', 'hi', 'hey', 'help'],
        'answer' => "Hi! I can help with general questions about bathing, feeding, vaccinations, deworming, dental care, nail trims, and booking appointments. What would you like to know?"
    ],
];

// Emergency keywords get a fixed, direct response instead of retrieval --
// this is not the place to guess or look clever.
$emergencyWords = ['bleeding', 'seizure', 'seizing', 'poison', 'poisoned', 'unconscious',
                    'not breathing', 'collapsed', 'choking', 'hit by car', 'emergency'];

function findBestMatch($message, $kb) {
    $message = strtolower($message);
    $bestScore = 0;
    $bestEntry = null;
    foreach ($kb as $entry) {
        $score = 0;
        foreach ($entry['keywords'] as $kw) {
            if (str_contains($message, $kw)) $score++;
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestEntry = $entry;
        }
    }
    return $bestEntry;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_message'])) {
    $userMsg = trim($_POST['user_message']);
    if ($userMsg !== '') {
        $lower = strtolower($userMsg);
        $isEmergency = false;
        foreach ($emergencyWords as $w) {
            if (str_contains($lower, $w)) { $isEmergency = true; break; }
        }

        if ($isEmergency) {
            $reply = "This sounds urgent. Please contact your nearest emergency vet clinic right away, or book an Emergency Care vet visit immediately. I'm not able to give emergency medical guidance here.";
        } else {
            $match = findBestMatch($userMsg, $kb);
            $reply = $match ? $match['answer']
                : "I don't have a specific answer for that yet, but you can book a Vet appointment for a proper answer from a professional, or try asking about bathing, feeding, vaccinations, deworming, dental care, nail trims, or booking appointments.";
        }

        $stmt = $conn->prepare("INSERT INTO chat_messages (username, role, message) VALUES (?, 'user', ?)");
        $stmt->bind_param("ss", $user, $userMsg);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO chat_messages (username, role, message) VALUES (?, 'bot', ?)");
        $stmt->bind_param("ss", $user, $reply);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: chatbot.php");
    exit();
}

if (isset($_GET['clear'])) {
    $stmt = $conn->prepare("DELETE FROM chat_messages WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->close();
    header("Location: chatbot.php");
    exit();
}

$stmt = $conn->prepare("SELECT role, message, created_at FROM chat_messages WHERE username = ? ORDER BY created_at ASC");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();
$history = [];
while ($row = $res->fetch_assoc()) $history[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Pet Care Assistant - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:650px; margin:0 auto; }
    .chat-box { background:#fff; border-radius:15px; box-shadow:0 4px 8px rgba(0,0,0,0.1); padding:16px; height:420px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; margin-bottom:14px; }
    .bubble { max-width:80%; padding:10px 14px; border-radius:16px; line-height:1.4; }
    .bubble-user { align-self:flex-end; background:#ffb6c1; color:#333; border-bottom-right-radius:4px; }
    .bubble-bot { align-self:flex-start; background:#e6f4ff; color:#333; border-bottom-left-radius:4px; }
    .bubble small { display:block; color:#888; font-size:0.7em; margin-top:4px; }
    form.chat-form { display:flex; gap:8px; }
    form.chat-form input { flex:1; padding:12px; border-radius:8px; border:1px solid #ccc; }
    form.chat-form button { padding:12px 18px; border:none; border-radius:8px; background:#ffb6c1; font-weight:bold; cursor:pointer; }
    form.chat-form button:hover { background:#ff8fa3; }
    .clear-link { display:block; text-align:right; font-size:0.85em; color:#888; margin-bottom:6px; text-decoration:none; }
    .empty { color:#888; font-style:italic; text-align:center; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-robot"></i> AI Pet Care Assistant</h1>
    <a class="clear-link" href="chatbot.php?clear=1" onclick="return confirm('Clear conversation?');">Clear chat</a>

    <div class="chat-box" id="chatBox">
        <?php if (empty($history)): ?>
            <p class="empty">Ask me about bathing, feeding, vaccinations, deworming, dental care, nail trims, or booking appointments.</p>
        <?php else: ?>
            <?php foreach ($history as $m): ?>
                <div class="bubble bubble-<?php echo $m['role']; ?>">
                    <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                    <small><?php echo htmlspecialchars($m['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form class="chat-form" method="POST">
        <input type="text" name="user_message" placeholder="Type a question..." autocomplete="off" required>
        <button type="submit">Send</button>
    </form>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script>
    var box = document.getElementById('chatBox');
    box.scrollTop = box.scrollHeight;
</script>
</body>
</html>
