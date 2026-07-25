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
    $sql = "INSERT INTO grooming_appointments (username, pet_name, service_type, app_date, status) 
            VALUES ('$user', '$pet', '$service', '$date', '$status')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "<p style='color: green; font-weight: bold;'>Appointment for $service booked!</p>";
    } else {
        $message = "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grooming - Pet Care Zone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #e6f4ff;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .service-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        .service-card {
            background: white;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 250px;
        }
        .booking-box {
            background: white;
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        input, select, button {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background: #ffb6c1;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }
        button:hover { background: #ff8fa3; }
        .back-link { display: block; margin-top: 20px; text-decoration: none; color: #555; }
    </style>
</head>
<body> 
    
    <h1><i class="fas fa-cut"></i> Pet Grooming Center</h1>
    <?php echo $message; ?>

    <div class="service-container">
        <div class="service-card">
            <img src="bathtub.png" height="150" width="150">
            <h3>Bathing</h3>
            <p>Fresh & Clean</p>
        </div>

        <div class="service-card">
            <img src="haircut.png" height="150" width="150">
            <h3>Hair Trimming</h3>
            <p>Professional Cuts</p>
        </div>

        <div class="service-card">
            <img src="teethbrush.png" height="150" width="150">
            <h3>Teeth Brush</h3>
            <p>Sparkling Smiles</p>
        </div>
    </div>

    <div class="booking-box">
        <h3>Book an Appointment</h3>
        <form action="groomer.php" method="POST">
            <input type="text" name="pet_name" placeholder="Pet's Name" required>
            
            <select name="service" required>
                <option value="">Select Service</option>
                <option value="Bathing">Bathing</option>
                <option value="Nail Trimming">Nail Trimming</option>
                <option value="Hair Trimming">Hair Trimming</option>
                <option value="Brushing">Brushing</option>
                <option value="Teeth Brush">Teeth Brush</option>
            </select>

            <input type="date" name="date" required>
            <button type="submit">Confirm Appointment</button>
        </form>
    </div>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  
</body>
</html>