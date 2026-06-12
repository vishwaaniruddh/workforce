<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    // IMPORTANT: Do not echo anything here, it will break JSON API responses!
    // echo "Connected succesfull";
}

$con2 = $conn;
?>