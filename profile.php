<?php
// Feature 2: Profile & Care Management
// Lets the user update their own contact info and manage pet profiles.
// NOTE: this app stores $_SESSION['username'] as the consumer's Name
// (see login.php), so profile updates below match on Name. If two users
// ever register with the same Name this will affect both rows -- worth
// switching the session to store Email or a user id instead, long-term.
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}

$user = $_SESSION['username'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $phone = trim($_POST['Phone']);
        $address = trim($_POST['Address']);
        $stmt = $conn->prepare("UPDATE consumer SET Phone = ?, Address = ? WHERE Name = ?");
        $stmt->bind_param("sss", $phone, $address, $user);
        $message = $stmt->execute()
            ? "<p style='color:green;font-weight:bold;'>Profile updated.</p>"
            : "<p style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
        $stmt->close();
    } elseif (isset($_POST['add_pet'])) {
        $petName = trim($_POST['pet_name']);
        $species = trim($_POST['species']);
        $breed = trim($_POST['breed']);
        $age = ($_POST['age'] !== '') ? (int)$_POST['age'] : null;
        $notes = trim($_POST['notes']);
        $stmt = $conn->prepare("INSERT INTO pets (username, pet_name, species, breed, age, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $user, $petName, $species, $breed, $age, $notes);
        $message = $stmt->execute()
            ? "<p style='color:green;font-weight:bold;'>Pet profile added.</p>"
            : "<p style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
        $stmt->close();
    } elseif (isset($_POST['update_pet'])) {
        $id = (int)$_POST['pet_id'];
        $petName = trim($_POST['pet_name']);
        $species = trim($_POST['species']);
        $breed = trim($_POST['breed']);
        $age = ($_POST['age'] !== '') ? (int)$_POST['age'] : null;
        $notes = trim($_POST['notes']);
        $stmt = $conn->prepare("UPDATE pets SET pet_name=?, species=?, breed=?, age=?, notes=? WHERE id=? AND username=?");
        $stmt->bind_param("sssissi", $petName, $species, $breed, $age, $notes, $id, $user);
        $message = $stmt->execute()
            ? "<p style='color:green;font-weight:bold;'>Pet profile updated.</p>"
            : "<p style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
        $stmt->close();
    } elseif (isset($_POST['delete_pet'])) {
        $id = (int)$_POST['pet_id'];
        $stmt = $conn->prepare("DELETE FROM pets WHERE id=? AND username=?");
        $stmt->bind_param("is", $id, $user);
        $stmt->execute();
        $stmt->close();
        $message = "<p style='color:green;font-weight:bold;'>Pet profile removed.</p>";
    }
}

$stmt = $conn->prepare("SELECT Name, Email, Phone, Address FROM consumer WHERE Name = ? LIMIT 1");
$stmt->bind_param("s", $user);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT id, pet_name, species, breed, age, notes FROM pets WHERE username = ? ORDER BY pet_name");
$stmt->bind_param("s", $user);
$stmt->execute();
$petsRes = $stmt->get_result();
$pets = [];
while ($row = $petsRes->fetch_assoc()) $pets[] = $row;
$stmt->close();

$editPet = null;
if (isset($_GET['edit'])) {
    foreach ($pets as $p) {
        if ($p['id'] == (int)$_GET['edit']) { $editPet = $p; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:800px; margin:0 auto; }
    .card { background:#fff; padding:20px; border-radius:15px; box-shadow:0 4px 8px rgba(0,0,0,0.1); margin-bottom:20px; }
    input, textarea, button { width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; font-family:inherit; }
    button { background:#ffb6c1; font-weight:bold; cursor:pointer; border:none; }
    button:hover { background:#ff8fa3; }
    .pet-card { background:#f8fafc; border-radius:10px; padding:12px 16px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; }
    .pet-actions { display:flex; gap:6px; }
    .pet-actions a, .pet-actions button { width:auto; padding:6px 10px; font-size:0.85em; }
    .btn-edit { background:#3498db; color:#fff; text-decoration:none; border-radius:6px; }
    .btn-delete { background:#ef4444; color:#fff; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
    .empty { color:#888; font-style:italic; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-id-card"></i> My Profile</h1>
    <?php echo $message; ?>

    <div class="card">
        <h3>Account Info</h3>
        <form method="POST">
            <label>Name</label>
            <input type="text" value="<?php echo htmlspecialchars($profile['Name']); ?>" disabled>
            <label>Email</label>
            <input type="text" value="<?php echo htmlspecialchars($profile['Email']); ?>" disabled>
            <label>Phone</label>
            <input type="text" name="Phone" value="<?php echo htmlspecialchars($profile['Phone']); ?>" required>
            <label>Address</label>
            <input type="text" name="Address" value="<?php echo htmlspecialchars($profile['Address']); ?>" required>
            <button type="submit" name="update_profile" value="1">Save Profile</button>
        </form>
    </div>

    <div class="card">
        <h3><?php echo $editPet ? 'Edit Pet' : 'Add a Pet'; ?></h3>
        <form method="POST">
            <?php if ($editPet): ?>
                <input type="hidden" name="pet_id" value="<?php echo $editPet['id']; ?>">
            <?php endif; ?>
            <input type="text" name="pet_name" placeholder="Pet's Name" value="<?php echo $editPet ? htmlspecialchars($editPet['pet_name']) : ''; ?>" required>
            <input type="text" name="species" placeholder="Species (Dog, Cat, etc.)" value="<?php echo $editPet ? htmlspecialchars($editPet['species']) : ''; ?>">
            <input type="text" name="breed" placeholder="Breed" value="<?php echo $editPet ? htmlspecialchars($editPet['breed']) : ''; ?>">
            <input type="number" name="age" placeholder="Age (years)" min="0" value="<?php echo $editPet ? htmlspecialchars($editPet['age']) : ''; ?>">
            <textarea name="notes" rows="2" placeholder="Notes (allergies, temperament, etc.)"><?php echo $editPet ? htmlspecialchars($editPet['notes']) : ''; ?></textarea>
            <button type="submit" name="<?php echo $editPet ? 'update_pet' : 'add_pet'; ?>" value="1">
                <?php echo $editPet ? 'Save Changes' : 'Add Pet'; ?>
            </button>
        </form>
        <?php if ($editPet): ?>
            <p style="text-align:center;"><a href="profile.php">Cancel edit</a></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>My Pets</h3>
        <?php if (empty($pets)): ?>
            <p class="empty">No pets added yet.</p>
        <?php else: ?>
            <?php foreach ($pets as $p): ?>
                <div class="pet-card">
                    <div>
                        <strong><?php echo htmlspecialchars($p['pet_name']); ?></strong>
                        <?php if ($p['species']): ?> &middot; <?php echo htmlspecialchars($p['species']); ?><?php endif; ?>
                        <?php if ($p['breed']): ?> &middot; <?php echo htmlspecialchars($p['breed']); ?><?php endif; ?>
                        <?php if ($p['age'] !== null): ?> &middot; <?php echo (int)$p['age']; ?> yr(s)<?php endif; ?>
                        <?php if ($p['notes']): ?><br><small><?php echo htmlspecialchars($p['notes']); ?></small><?php endif; ?>
                    </div>
                    <div class="pet-actions">
                        <a class="btn-edit" href="profile.php?edit=<?php echo $p['id']; ?>">Edit</a>
                        <form method="POST" onsubmit="return confirm('Remove this pet profile?');">
                            <input type="hidden" name="pet_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" name="delete_pet" value="1" class="btn-delete">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
