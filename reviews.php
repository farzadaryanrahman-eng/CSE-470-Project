<?php
// Feature 14: Product Reviews & Ratings
// Access as reviews.php?item=Item+Name  (linked from pawmart.php)
session_start();
require_once('DBconnect.php');

if (!isset($_GET['item']) || trim($_GET['item']) === '') {
    header("Location: pawmart.php");
    exit();
}
$item = $_GET['item'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_review'])) {
    if (!isset($_SESSION['username'])) {
        header("Location: register.html");
        exit();
    }
    $user = $_SESSION['username'];
    $rating = max(1, min(5, (int)$_POST['rating']));
    $text = trim($_POST['review_text']);
    $stmt = $conn->prepare("INSERT INTO reviews (username, item_name, rating, review_text) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $user, $item, $rating, $text);
    $message = $stmt->execute()
        ? "<p style='color:green;font-weight:bold;'>Review submitted!</p>"
        : "<p style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
    $stmt->close();
}

$stmt = $conn->prepare("SELECT username, rating, review_text, created_at FROM reviews WHERE item_name = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $item);
$stmt->execute();
$res = $stmt->get_result();
$reviews = [];
$totalRating = 0;
while ($row = $res->fetch_assoc()) {
    $reviews[] = $row;
    $totalRating += $row['rating'];
}
$stmt->close();
$avgRating = count($reviews) > 0 ? round($totalRating / count($reviews), 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($item); ?> Reviews - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:700px; margin:0 auto; }
    .card { background:#fff; padding:20px; border-radius:15px; box-shadow:0 4px 8px rgba(0,0,0,0.1); margin-bottom:20px; }
    .stars { color:#facc15; }
    .avg { font-size:1.5em; font-weight:bold; }
    select, textarea, button { width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; font-family:inherit; }
    button { background:#ffb6c1; font-weight:bold; cursor:pointer; border:none; }
    button:hover { background:#ff8fa3; }
    .review { border-bottom:1px solid #eee; padding:10px 0; }
    .review small { color:#888; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-star"></i> Reviews: <?php echo htmlspecialchars($item); ?></h1>
    <?php echo $message; ?>

    <div class="card" style="text-align:center;">
        <div class="avg"><?php echo $avgRating; ?> / 5</div>
        <div class="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="<?php echo $i <= round($avgRating) ? 'fas' : 'far'; ?> fa-star"></i>
            <?php endfor; ?>
        </div>
        <p><?php echo count($reviews); ?> review(s)</p>
    </div>

    <div class="card">
        <h3>Write a Review</h3>
        <?php if (isset($_SESSION['username'])): ?>
            <form method="POST">
                <select name="rating" required>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Okay</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Bad</option>
                </select>
                <textarea name="review_text" rows="3" placeholder="Share your experience..." required></textarea>
                <button type="submit" name="add_review" value="1">Submit Review</button>
            </form>
        <?php else: ?>
            <p>Please <a href="register.html">login</a> to write a review.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>All Reviews</h3>
        <?php if (empty($reviews)): ?>
            <p>No reviews yet. Be the first!</p>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
                <div class="review">
                    <strong><?php echo htmlspecialchars($r['username']); ?></strong>
                    <span class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?php echo $i <= $r['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </span>
                    <br><small><?php echo htmlspecialchars($r['created_at']); ?></small>
                    <p><?php echo nl2br(htmlspecialchars($r['review_text'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="pawmart.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Paw Mart</a>
</div>
</body>
</html>
