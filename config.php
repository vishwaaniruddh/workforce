<?php
date_default_timezone_set('Asia/Kolkata');


$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, 'localhost:') === 0) {
    // Local credentials
    $conn = mysqli_connect("localhost", "root", "", "u444388293_workforce");
} else {
    // Server credentials
    $conn = mysqli_connect("localhost", "u444388293_workforce", "AVav@@2026", "u444388293_workforce");
}
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    // echo "Connected succesfull";

}


$con2 = $conn;
?>