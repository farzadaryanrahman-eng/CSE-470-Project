<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}

// Check admin role so the admin link only shows for admins
require_once('DBconnect.php');
$isAdmin = false;
$stmt = $conn->prepare("SELECT role FROM consumer WHERE Name = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $roleRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($roleRow && $roleRow['role'] === 'admin') {
        $isAdmin = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pet Care Zone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #ADD8E6;
            --accent-pink: #ffb6c1;
            --hover-pink: #ff8fa3;
            --star-gold: #ffd700;
            --text-dark: #444;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-dark);
            text-align: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }

        h1 {
            margin-top: 60px;
            font-size: 3.5rem;
            color: #333;
            text-transform: capitalize;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .stars-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .star {
            position: absolute;
            width: 25px;
            height: 25px;
            background-color: var(--star-gold);
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            opacity: 0.6;
            animation: twinkle 3s infinite ease-in-out;
        }

        @keyframes twinkle {
            0%, 100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.2); opacity: 1; }
        }

        .circle-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 50px;
            margin-top: 60px;
            padding: 20px;
        }

        .circle {
            background: linear-gradient(135deg, var(--accent-pink), #ff9aa2);
            border-radius: 50%;
            width: 200px;
            height: 200px;
            display: flex;
            flex-direction: column; 
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            text-decoration: none; 
            color: inherit;
        }

        .circle:hover {
            transform: translateY(-15px) scale(1.05);
            background: linear-gradient(135deg, var(--hover-pink), var(--accent-pink));
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        
        .circle i {
            font-size: 3rem;
            color: white;
            margin-bottom: 10px;
        }

        .circle-label {
            background-color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1em;
            color: #555;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
		.logout-btn {
		 position: absolute;
		 top: 20px;
		 right: 20px;
		 background-color: #ff4d4d;
		 color: white;
		 padding: 10px 20px;
		 border-radius: 25px;
		 text-decoration: none;
		 font-weight: bold;
		 font-size: 0.9em;
		 box-shadow: 0 4px 6px rgba(0,0,0,0.1);
		 transition: background 0.3s;
		 z-index: 10; 
}

		.logout-btn:hover {
			background-color: #cc0000;
		}

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
            max-width: 700px;
        }
        .quick-links a {
            background: #fff;
            color: #555;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            box-shadow: 0 3px 6px rgba(0,0,0,0.08);
            transition: 0.2s;
        }
        .quick-links a:hover {
            background: var(--accent-pink);
            color: #fff;
        }
        .quick-links a.admin-link {
            background: #2c3e50;
            color: #fff;
        }
        .quick-links a.admin-link:hover {
            background: #1a252f;
        }

		@media (max-width: 600px) {
			.logout-btn {
				position: static;
				margin-top: 10px;
				display: inline-block;
			}
		}
    </style>
</head>
<body>
	<h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

    <div class="stars-container">
        <div class="star" style="top: 10%; left: 10%; animation-delay: 0s;"></div>
        <div class="star" style="top: 15%; right: 15%; animation-delay: 0.5s;"></div>
        <div class="star" style="bottom: 20%; left: 20%; animation-delay: 1s;"></div>
        <div class="star" style="bottom: 10%; right: 10%; animation-delay: 1.5s;"></div>
        <div class="star" style="top: 40%; left: 50%; animation-delay: 2s;"></div>
    </div>

    <h1>Pet Care Zone</h1>

    <div class="circle-container">
        <a href="vet.php" class="circle">
            <i class="fas fa-user-md"></i>
            <span class="circle-label">Vet</span>
        </a>

        <a href="groomer.php" class="circle">
            <i class="fas fa-cut"></i>
            <span class="circle-label">Groom</span>
        </a>

        <a href="pawmart.php" class="circle">
            <i class="fas fa-shopping-basket"></i>
            <span class="circle-label">Paw Mart</span>
        </a>

        <a href="video_consultation.php" class="circle">
            <i class="fas fa-video"></i>
            <span class="circle-label">Video</span>
        </a>

        <a href="payment_history.php" class="circle">
            <i class="fas fa-file-invoice-dollar"></i>
            <span class="circle-label">Payments</span>
        </a>
    </div>

    <div class="quick-links">
        <a href="profile.php"><i class="fas fa-id-card"></i> My Profile & Pets</a>
        <a href="video_consultation.php"><i class="fas fa-video"></i> Video Consultation</a>
        <a href="chatbot.php"><i class="fas fa-robot"></i> AI Pet Assistant</a>
        <a href="appointment_management.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
        <a href="health_report.php"><i class="fas fa-heartbeat"></i> AI Health Report</a>
        <a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
        <a href="order_management.php"><i class="fas fa-receipt"></i> My Orders</a>
        <a href="payment_history.php"><i class="fas fa-file-invoice-dollar"></i> Payment History</a>
        <?php if ($isAdmin): ?>
            <a href="admin_inventory.php" class="admin-link"><i class="fas fa-boxes"></i> Inventory Admin</a>
        <?php endif; ?>
    </div>

	<img src="panda.jpg" height="250" width="250" style="display: block; margin: 0 auto 10px auto; border-radius: 30%;">
	<a href="logout.php" class="logout-btn">
    <i class="fas fa-sign-out-alt"></i> Log Out
</a>

</body>
</html>
