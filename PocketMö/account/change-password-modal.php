<?php
include_once(__DIR__ . '/../config.php'); // Include your database connection file

// Redirect to login if user is not logged in
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit();
}

// Handle form submission for changing password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $userId = $_SESSION["user"]["ID"];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Fetch the current password from the database
    $sql = "SELECT Password FROM user WHERE UserId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    // Verify the current password
    if (password_verify($currentPassword, $hashedPassword)) {
        // Check if new password and confirm password match
        if ($newPassword === $confirmPassword) {
            // Hash the new password
            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update the password in the database
            $updateSql = "UPDATE user SET Password = ? WHERE UserId = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $newHashedPassword, $userId);
            if ($updateStmt->execute()) {
                $_SESSION['message'] = "Password changed successfully!";
                header("Location: ./accounts.php");
            } else {
                $_SESSION['error'] = "Error updating password. Please try again.";
                header("Location: ./change-password-modal.php");
            }
            $updateStmt->close();
        } else {
            $_SESSION['error'] = "New password and confirmation do not match.";
            header("Location: ./change-password-modal.php");
        }
    } else {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: ./change-password-modal.php");
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="./css/change-pass.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background-content">
        <?php include __DIR__ . '/accounts.php'; ?>
    </div>
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="modal-container">
        <div class="modal-header"> <!-- Modal Header -->
            <div class="rectangle">CHANGE PASSWORD</div>
        </div>
        <!-- Modal Content -->
        <div class="container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="success-message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <form id="transaction-form" method="POST" action="">
                <div class="input-group">
                    <label for="current-password">Current Password</label>
                    <input type="password" name="current_password" id="current-password" placeholder="Please enter current password" required>
                </div>

                <div class="input-group">
                    <label for="new-password">New Password</label>
                    <input type="password" name="new_password" id="new-password" placeholder="Please enter new password" required>
                </div>

                <div class="input-group">
                    <label for="confirm-password">Re-type New Password</label>
                    <input type="password" name="confirm_password" id="confirm-password" placeholder="Please re-enter new password" required>
                </div>

                <div class="keypad">
                    <button type="reset" class="reset" onclick="resetForm()">RESET</button>
                    <button type="submit" name="change_password" class="save">SAVE</button>
                </div>
            </form>
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

        // Function to reset the form
        function resetForm() {
            document.getElementById('transaction-form').reset();
        }

    </script>
</body>
</html>