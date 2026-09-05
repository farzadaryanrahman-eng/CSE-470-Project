<?php
session_start();
require_once __DIR__ . '/DBconnect.php';
require_once __DIR__ . '/payment_log.php';

if (!isset($_SESSION['username'])) {
    header('Location: register.html');
    exit();
}

$user = $_SESSION['username'];
$message = '';
$join = null;
$tableMissing = !pawmart_table_exists($conn, 'video_consultations');

if (!$tableMissing && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_call'])) {
    $pet = trim($_POST['pet_name'] ?? '');
    $reason = trim($_POST['reason'] ?? 'Video consultation');
    $slug = preg_replace('/[^A-Za-z0-9]/', '', $user);
    if ($slug === '') {
        $slug = 'Guest';
    }
    $room = 'PawMart' . $slug . uniqid();
    $meetUrl = 'https://meet.jit.si/' . $room;

    $stmt = $conn->prepare(
        'INSERT INTO video_consultations (username, pet_name, reason, room_name, meet_url, status) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $status = 'scheduled';
    $stmt->bind_param('ssssss', $user, $pet, $reason, $room, $meetUrl, $status);
    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        header('Location: video_consultation.php?join=' . (int) $newId);
        exit();
    }
    $message = 'Could not create the video room: ' . htmlspecialchars($stmt->error);
    $stmt->close();
}

$pets = [];
$petStmt = $conn->prepare('SELECT pet_name FROM pets WHERE username = ? ORDER BY pet_name');
if ($petStmt) {
    $petStmt->bind_param('s', $user);
    $petStmt->execute();
    $petRes = $petStmt->get_result();
    while ($row = $petRes->fetch_assoc()) {
        $pets[] = $row['pet_name'];
    }
    $petStmt->close();
}

$history = [];
if (!$tableMissing) {
    $stmt = $conn->prepare(
        'SELECT id, pet_name, reason, room_name, meet_url, status, created_at
         FROM video_consultations WHERE username = ? ORDER BY created_at DESC'
    );
    $stmt->bind_param('s', $user);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}

if (isset($_GET['join'])) {
    $joinId = (int) $_GET['join'];
    foreach ($history as $row) {
        if ((int) $row['id'] === $joinId) {
            $join = $row;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Video Consultation - Pet Care Zone</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #e6f4ff; margin: 0; padding: 20px; }
    h1 { text-align: center; color: #333; }
    .container { max-width: 960px; margin: 0 auto; }
    .card { background: #fff; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
    input, select, button { width: 100%; padding: 10px; margin: 8px 0; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; }
    button, .join-btn { background: #ffb6c1; font-weight: bold; cursor: pointer; border: none; }
    button:hover { background: #ff8fa3; }
    .join-btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; color: #333; }
    .join-btn.primary { background: #2e86de; color: #fff; }
    .warn { background: #fff3cd; color: #856404; padding: 12px; border-radius: 10px; margin-bottom: 16px; }
    .share { background: #f8fafc; padding: 10px; border-radius: 8px; word-break: break-all; }
    #jitsi-container { height: 560px; border-radius: 12px; overflow: hidden; background: #111; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
    .back-link { display: block; text-align: center; margin-top: 12px; text-decoration: none; color: #555; }
    .row-actions { display: flex; gap: 8px; flex-wrap: wrap; }
  </style>
</head>
<body>
<div class="container">
  <h1><i class="fas fa-video"></i> Video Consultation</h1>
  <p style="text-align:center;color:#555;">Free Jitsi meeting — no API key. Share the link with your vet so you both join the same room.</p>

  <?php if ($tableMissing): ?>
    <p class="warn">The <code>video_consultations</code> table is missing. Import <code>schema_additions_video_stripe.sql</code> in phpMyAdmin, then refresh.</p>
  <?php endif; ?>
  <?php echo $message; ?>

  <?php if ($join): ?>
  <div class="card">
    <h3>Live room</h3>
    <p><strong>Reason:</strong> <?php echo htmlspecialchars($join['reason']); ?>
      <?php if ($join['pet_name']): ?> · <strong>Pet:</strong> <?php echo htmlspecialchars($join['pet_name']); ?><?php endif; ?>
    </p>
    <p class="share">Share this link: <a href="<?php echo htmlspecialchars($join['meet_url']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($join['meet_url']); ?></a></p>
    <p class="row-actions">
      <a class="join-btn primary" href="<?php echo htmlspecialchars($join['meet_url']); ?>" target="_blank" rel="noopener">Open video call in a new tab</a>
    </p>
    <div id="jitsi-container"></div>
  </div>
  <script src="https://meet.jit.si/external_api.js"></script>
  <script>
    (function () {
      var room = <?php echo json_encode($join['room_name']); ?>;
      var displayName = <?php echo json_encode($user); ?>;
      if (typeof JitsiMeetExternalAPI !== 'function') {
        return;
      }
      var api = new JitsiMeetExternalAPI('meet.jit.si', {
        roomName: room,
        parentNode: document.querySelector('#jitsi-container'),
        width: '100%',
        height: 560,
        userInfo: { displayName: displayName },
        configOverwrite: { startWithAudioMuted: true, prejoinPageEnabled: true }
      });
    })();
  </script>
  <?php endif; ?>

  <div class="card">
    <h3>Start a new video call</h3>
    <form method="POST">
      <label>Pet</label>
      <?php if (!empty($pets)): ?>
        <select name="pet_name">
          <option value="">Select a pet (optional)</option>
          <?php foreach ($pets as $petName): ?>
            <option value="<?php echo htmlspecialchars($petName); ?>"><?php echo htmlspecialchars($petName); ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input type="text" name="pet_name" placeholder="Pet name (optional)">
      <?php endif; ?>
      <label>Reason</label>
      <select name="reason" required>
        <option value="General Checkup">General Checkup</option>
        <option value="Follow-up">Follow-up</option>
        <option value="Emergency consult">Emergency consult</option>
        <option value="Prescription review">Prescription review</option>
      </select>
      <button type="submit" name="start_call" value="1" <?php echo $tableMissing ? 'disabled' : ''; ?>>
        Generate video room
      </button>
    </form>
  </div>

  <div class="card">
    <h3>Your rooms</h3>
    <?php if (empty($history)): ?>
      <p style="color:#888;">No video rooms yet.</p>
    <?php else: ?>
      <table>
        <tr><th>Created</th><th>Pet</th><th>Reason</th><th></th></tr>
        <?php foreach ($history as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
            <td><?php echo htmlspecialchars($row['pet_name'] ?: '—'); ?></td>
            <td><?php echo htmlspecialchars($row['reason']); ?></td>
            <td><a href="video_consultation.php?join=<?php echo (int) $row['id']; ?>">Rejoin</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
