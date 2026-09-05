<?php
// Feature 9: Appointment Management
// Lets a logged-in user view all their vet + grooming appointments,
// cancel a pending one, or reschedule its date.
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}

$user = $_SESSION['username'];
$message = "";

// Whitelist so we never interpolate a raw user-supplied table name into SQL
$validSources = [
    'vet' => 'appointments',
    'grooming' => 'grooming_appointments'
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['source'], $_POST['id'], $_POST['action'])) {
    $source = $_POST['source'];
    $id = (int)$_POST['id'];
    $action = $_POST['action'];

    if (isset($validSources[$source])) {
        $table = $validSources[$source];

        if ($action === 'cancel') {
            $stmt = $conn->prepare("UPDATE $table SET status = 'Cancelled' WHERE id = ? AND username = ? AND status = 'Pending'");
            $stmt->bind_param("is", $id, $user);
            $message = $stmt->execute()
                ? "<p style='color:green;font-weight:bold;'>Appointment cancelled.</p>"
                : "<p style='color:red;'>Error cancelling appointment.</p>";
            $stmt->close();
        } elseif ($action === 'reschedule' && isset($_POST['new_date'])) {
            $newDate = $_POST['new_date'];
            $stmt = $conn->prepare("UPDATE $table SET app_date = ? WHERE id = ? AND username = ? AND status = 'Pending'");
            $stmt->bind_param("sis", $newDate, $id, $user);
            $message = $stmt->execute()
                ? "<p style='color:green;font-weight:bold;'>Appointment rescheduled.</p>"
                : "<p style='color:red;'>Error rescheduling appointment.</p>";
            $stmt->close();
        }
    }
}

function fetchAppointments($conn, $table, $user, $sourceKey, $label) {
    $stmt = $conn->prepare("SELECT id, pet_name, service_type, app_date, status FROM $table WHERE username = ? ORDER BY app_date DESC");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['source'] = $sourceKey;
        $row['type_label'] = $label;
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

$allAppointments = array_merge(
    fetchAppointments($conn, 'appointments', $user, 'vet', 'Vet'),
    fetchAppointments($conn, 'grooming_appointments', $user, 'grooming', 'Grooming')
);
usort($allAppointments, fn($a, $b) => strtotime($b['app_date']) <=> strtotime($a['app_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Appointments - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:900px; margin:0 auto; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:15px; overflow:hidden; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
    th, td { padding:12px; text-align:left; border-bottom:1px solid #eee; }
    th { background:#ffb6c1; color:#fff; }
    .status-Pending { color:#e67e22; font-weight:bold; }
    .status-Cancelled { color:#e74c3c; font-weight:bold; }
    .status-Confirmed, .status-Completed { color:#27ae60; font-weight:bold; }
    .badge { padding:3px 10px; border-radius:12px; font-size:0.8em; color:#fff; }
    .badge-vet { background:#3498db; }
    .badge-grooming { background:#ff8fa3; }
    form.inline { display:inline-block; margin:4px 4px 0 0; }
    input[type=date] { padding:5px; border-radius:5px; border:1px solid #ccc; }
    button { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; }
    .btn-cancel { background:#e74c3c; color:#fff; }
    .btn-reschedule { background:#3498db; color:#fff; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
    .empty { text-align:center; color:#888; padding:30px; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-calendar-check"></i> My Appointments</h1>
    <?php echo $message; ?>

    <?php if (empty($allAppointments)): ?>
        <p class="empty">You have no appointments yet. Book one from your <a href="page2.php">dashboard</a>.</p>
    <?php else: ?>
    <table>
        <tr><th>Type</th><th>Pet</th><th>Service</th><th>Date</th><th>Status</th><th>Actions</th></tr>
        <?php foreach ($allAppointments as $a): ?>
        <tr>
            <td><span class="badge badge-<?php echo $a['source']; ?>"><?php echo $a['type_label']; ?></span></td>
            <td><?php echo htmlspecialchars($a['pet_name']); ?></td>
            <td><?php echo htmlspecialchars($a['service_type']); ?></td>
            <td><?php echo htmlspecialchars($a['app_date']); ?></td>
            <td class="status-<?php echo htmlspecialchars($a['status']); ?>"><?php echo htmlspecialchars($a['status']); ?></td>
            <td>
                <?php if ($a['status'] === 'Pending'): ?>
                    <form class="inline" method="POST">
                        <input type="hidden" name="source" value="<?php echo $a['source']; ?>">
                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn-cancel" onclick="return confirm('Cancel this appointment?');">Cancel</button>
                    </form>
                    <form class="inline" method="POST">
                        <input type="hidden" name="source" value="<?php echo $a['source']; ?>">
                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                        <input type="hidden" name="action" value="reschedule">
                        <input type="date" name="new_date" required>
                        <button type="submit" class="btn-reschedule">Reschedule</button>
                    </form>
                <?php else: ?>
                    <em>No actions</em>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
