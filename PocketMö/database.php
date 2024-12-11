<?php
// Database connection configuration
$hostName = "localhost";
$dbUser  = "root";
$dbPassword = "";
$dbName = "expense_manager";

// Create a connection
$conn = mysqli_connect($hostName, $dbUser , $dbPassword, $dbName);

// Check connection and handle errors
if (!$conn) {
    error_log("Connection failed: " . mysqli_connect_error()); // Log error to server log
    die("Connection failed. Please try again later."); // User-friendly error message
}

// Set character set to utf8mb4 for better compatibility with emojis and special characters
mysqli_set_charset($conn, "utf8mb4");
?>