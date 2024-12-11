<?php
include_once(__DIR__ . '/../config.php'); // Ensure this file connects to your database

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $userId = $_SESSION["user"]["ID"] ?? null; // Get user ID from session

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("SELECT DISTINCT Name, balance FROM lent WHERE Name LIKE ? AND User_Id = ? AND balance > 0");
    $searchTerm = $query . '%'; // Search for names starting with the input
    $stmt->bind_param("si", $searchTerm, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'name' => $row['Name'], 
            'balance' => $row['balance'] // Collect balance as well
        ];
    }

    // Return suggestions as a JSON response
    echo json_encode($suggestions);
}
?>