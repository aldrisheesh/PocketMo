<?php
include_once(__DIR__ . '/../../database.php'); // Include your database connection
session_start();

// Fetch user ID from the session
$user_id = $_SESSION["user"]["ID"];

// Get the current date in 'Y-m-d' format
$current_date = date('Y-m-d');

// Fetch today's expenses grouped by category for the logged-in user
$sql = "SELECT Category, SUM(Cost) as TotalCost FROM expense WHERE User_Id = ? AND DATE(Date) = ? GROUP BY Category";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $current_date); // bind user_id as integer and current_date as string
$stmt->execute();
$result = $stmt->get_result();

$expenses = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
}

$stmt->close(); // Close the prepared statement
$conn->close(); // Close the database connection

// Encode the data as JSON for use in JavaScript
header('Content-Type: application/json'); // Set the content type to JSON
echo json_encode($expenses);
?>