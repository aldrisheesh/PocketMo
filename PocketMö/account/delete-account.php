<?php
// Include database connection
include_once(__DIR__ . '/../config.php'); 

// Redirect to login if user is not logged in
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit();
}

// Initialize messages
$message = '';
$error = '';

// Check for messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']); // Clear the error after displaying
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    $userId = $_SESSION["user"]["ID"]; // Get the user ID from the session

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Prepare deletion queries for all relevant tables
        $deleteBorrowed = "DELETE FROM borrowed WHERE User_Id = ?";
        $deleteBudget = "DELETE FROM budget WHERE User_Id = ?";
        $deleteExpense = "DELETE FROM expense WHERE User_Id = ?";
        $deleteLent = "DELETE FROM lent WHERE User_Id = ?";
        $deleteUser  = "DELETE FROM user WHERE UserId = ?";

        // Prepare and execute the statement to delete borrowed records
        $stmt = $conn->prepare($deleteBorrowed);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // Prepare and execute the statement to delete budget records
        $stmt = $conn->prepare($deleteBudget);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // Prepare and execute the statement to delete expense records
        $stmt = $conn->prepare($deleteExpense);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // Prepare and execute the statement to delete lent records
        $stmt = $conn->prepare($deleteLent);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // Prepare and execute the statement to delete the user profile
        $stmt = $conn->prepare($deleteUser );
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Optionally, destroy the session and redirect the user
        session_destroy();
        $_SESSION['message'] = "Your account has been deleted successfully.";
        header("Location: ../index.php"); // Redirect to a goodbye page or similar
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if something goes wrong
        $conn->rollback();
        $_SESSION['error'] = "Error deleting account: " . $e->getMessage();
        header("Location: ./accounts.php"); // Redirect back to accounts page
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .background-content {
            display: flex; /* Center the content inside this div */
            justify-content: center;
            align-items: center;
            height: 100%; /* Take full height of the body */
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999; 
        }

        .modal {
            position: absolute; 
            background-color: white;
            border-radius: 25px;
            width: 100%; 
            max-width: 500px; 
            padding: 40px;
            text-align: center;
            overflow: hidden;
            box-sizing: border-box; 
            z-index: 1000; 
        }

        .modal-header {
            font-size: 32px; /* Increased font size */
            font-weight: 700;
            margin-bottom: 15px; /* Increased margin */
        }

        .modal-body {
            font-size: 20px; /* Increased font size */
            margin-bottom: 30px; /* Increased margin */
        }

        .modal-footer {
            display: flex;
            justify-content: space-between;
        }

        .btn {
            padding: 15px 30px; /* Increased padding */
            border: none;
            border-radius: 6px; /* Increased border radius */
            cursor: pointer;
            font-size: 20px; /* Increased font size */
            width: 190px; /* Increased width */
        }

        .btn-yes {
            background-color: #2ecc71;
            color: white;
        }

        .btn-no {
            background-color: #e74c3c;
            color: white;
        }

        .btn-yes:hover {
            background-color: #27ae60;
        }

        .btn-no:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="background-content">
        <?php include __DIR__ . '/accounts.php'; ?>
    </div>
    <!-- Confirmation Modal -->
    <div class="modal-backdrop" id="modalBackdrop"></div> 
    <div class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i> Confirm Deletion
            </div>
            <div class="modal-body">
                Are you sure you want to delete your account? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button class="btn btn-no" onclick="redirectToPage()">No</button>
                <form action="" method="POST" style="display: inline;">
                    <input type="hidden" name="delete_account" value="1">
                    <button type="submit" class="btn btn-yes">Yes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Wait for the DOM to fully load
                document.addEventListener('DOMContentLoaded', function() {
            // Get the modal backdrop element
            const modalBackdrop = document.getElementById('modalBackdrop');

            // Add click event listener
            modalBackdrop.addEventListener('click', function() {
                // Redirect to borrow-page.php
                window.location.href = './accounts.php';
            });
        });

        function redirectToPage() {
            window.location.href = "./accounts.php"; // Replace with your desired URL
        }
    </script>
</body>
</html>