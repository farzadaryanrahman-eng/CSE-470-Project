<?php
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_SESSION['username'];
    $pet = $_POST['pet_name'];
    $service = $_POST['service'];
    $date = $_POST['date'];
	$status = "Pending";
    $sql = "INSERT INTO appointments (username, pet_name, service_type, app_date, status) 
        VALUES ('$user', '$pet', '$service', '$date', '$status')";
  
  
  
    if (mysqli_query($conn, $sql)) {
        $message = "<p style='color: green;'>Appointment booked successfully!</p>";
    } else {
        $message = "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vet Clinic - Pet Care Zone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #ADD8E6;
            --accent-pink: #ffb6c1;
            --text-dark: #444;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-dark);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .vet-profile {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .vet-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #ccc;
        }

        .booking-form {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        input, select, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        button {
            background-color: var(--accent-pink);
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover { background-color: #ff8fa3; }

        .back-link {
            display: block;
            margin-top: 20px;
            text-decoration: none;
            color: #555;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="vet-profile">
        <div class="vet-image">
            <i class="fas fa-user-nurse"></i>
        </div>
        <div>
            <h2>Dr. Farzad</h2>
            <p><strong>Specialty:</strong> Small Animal Surgery & Vaccinations</p>
            <p><strong>Experience:</strong> 12+ Years</p>
            <p><i class="fas fa-star" style="color: gold;"></i> 4.9/5 Patient Rating</p>
        </div>
    </div>

    <div class="booking-form">
        <h3>Book an Appointment</h3>
        <?php echo $message; ?>
        <form action="vet.php" method="POST">
            <label>Pet Name:</label>
            <input type="text" name="pet_name" placeholder="Enter your pet's name" required>

            <label>Service Needed:</label>
            <select name="service" required>
                <option value="General Checkup">General Checkup</option>
                <option value="Vaccination">Vaccination</option>
                <option value="Surgery">Surgery</option>
                <option value="Dental Cleaning">Dental Cleaning</option>
            </select>

            <label>Preferred Date:</label>
            <input type="date" name="date" required>

            <button type="submit">Confirm Appointment</button>
        </form>
    </div>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

</body>
</html>