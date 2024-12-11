<?php
include_once(__DIR__ . '/../config.php'); // Include your database connection

// Check if a file was uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profilePicture'])) {
    $file = $_FILES['profilePicture'];

    // Validate the file (you can add more validation as needed)
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Define the upload directory using __DIR__
        $uploadDir = __DIR__ . '/../assets/uploads/'; // Correctly set the upload directory
        $fileName = basename($file['name']);
        $uniqueFileName = uniqid() . '-' . $fileName; // Create a unique file name
        $filePath = $uploadDir . $uniqueFileName; // Full path for moving the file

        // Move the uploaded file to the desired directory
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Prepare the relative path to store in the database
            $relativePath = '../assets/uploads/' . $uniqueFileName;

            // Update the user's profile picture in the database
            $userId = $_SESSION["user"]["ID"]; // Get the user ID from the session
            $sql = "UPDATE user SET Photo = ? WHERE UserId = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $relativePath, $userId); // Store the relative path
            $stmt->execute();

            // Return success response
            echo json_encode(['success' => true, 'filePath' => $relativePath]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File upload error.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
}
?>