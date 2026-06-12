<?php 
date_default_timezone_set('Asia/Kolkata');


// $conn = new mysqli($host, $user, $pass, $dbname);
$conn = mysqli_connect("localhost","u444388293_workforce","AVav@@2026","u444388293_workforce");
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
// echo "Connected succesfull";
   
}


$con2 = $conn;
?>