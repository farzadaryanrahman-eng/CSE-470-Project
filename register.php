<?php
require_once('DBconnect.php');

if(isset($_POST['Name']) && isset($_POST['Email']) && isset($_POST['Password']) && isset($_POST['Phone']) && isset($_POST['Address'])) {
    $n = $_POST['Name'];
    $e = $_POST['Email'];
	$p = $_POST['Password'];
	$ph = $_POST['Phone'];
	$a = $_POST['Address'];

    $sql = "Insert into  consumer (Name,Email,Password,Phone,Address) values ('$n','$e','$p','$ph','$a')";
    $result = mysqli_query($conn, $sql);

    if($result){
        echo "Registration Successful";
    } else {
        echo "Error" .mysqli_error($conn);
    }
}
?>