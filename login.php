<?php
require_once('DBconnect.php');
session_start(); 

if (isset($_POST['Email']) && isset($_POST['Password'])) {
    $e = $_POST['Email'];
    $p = $_POST['Password']; 
    $sql = "SELECT Name FROM consumer WHERE Email = '$e' AND Password = '$p'";
    $result = mysqli_query($conn, $sql); 

    if (mysqli_num_rows($result) > 0) { 
        $row = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $row['Name']; 

        header("Location: page2.php"); 
        exit(); 
    } else {
        echo "Login Failed. Please check your email and password."; 
        echo "<br><a href='register.html'>Try Again</a>"; 
    }
} else {
    header("Location: register.html"); 
    exit(); 
}
?>