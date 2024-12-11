<?php
// Include database connection
include_once(__DIR__ . '/../config.php');

// Redirect to login if user is not logged in
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit();
}

// Check for messages
$message = '';
$error = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']); // Clear the error after displaying
}

// Determine the alert type
$alertType = !empty($error) ? 'error' : 'success';
$alertMessage = !empty($error) ? $error : $message;

// Fetch user details for the logged-in user
$userId = $_SESSION["user"]["ID"]; // Get the user ID from the session

$sql = "SELECT Username, Email, Photo, Name, ContactNumber, DateOfBirth FROM user WHERE UserId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$user = null; // Initialize user variable
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc(); // Get user details
}

// Use fallback values if user data is not available
$username = $user['Username'] ?? 'Guest';
$email = $user['Email'] ?? 'No Email';
$name = $user['Name'] ?? 'N/A';
$contactNumber = $user['ContactNumber'] ?? 'N/A';
$dateOfBirth = !empty($user['DateOfBirth']) && $user['DateOfBirth'] !== '0000-00-00' ? date('Y-m-d', strtotime($user['DateOfBirth'])) : ''; // Format the date of birth for input
$photo = !empty($user['Photo']) ? $user['Photo'] : 'https://t4.ftcdn.net/jpg/00/64/67/27/360_F_64672736_U5kpdGs9keUll8CRQ3p3YaEv2M6qkVY5.jpg'; // Set default photo if null

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? null;
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null; // Get the email from the form
    $contactNumber = $_POST['contact-number'] ?? null;
    $dateOfBirth = $_POST['date-of-birth'] ?? null;

    // Check if the email already exists in the database
    if ($email !== $user['Email']) {
        $emailCheckQuery = "SELECT COUNT(*) FROM user WHERE Email = ? AND UserId != ?";
        $emailCheckStmt = $conn->prepare($emailCheckQuery);
        $emailCheckStmt->bind_param("si", $email, $userId);
        $emailCheckStmt->execute();
        $emailCheckStmt->bind_result($emailCount);
        $emailCheckStmt->fetch();
        $emailCheckStmt->close();

        if ($emailCount > 0) {
            $_SESSION['error'] = "This email address is already in use. Please choose another one.";
            header("Location: ./accounts.php"); // Redirect to the same page to show the error
            exit();
        }
    }

    // Prepare the update query dynamically
    $updateFields = [];
    $params = [];
    $types = '';

    if (!empty($name)) {
        $updateFields[] = "Name = ?";
        $params[] = $name;
        $types .= 's';
    }
    if (!empty($username)) {
        $updateFields[] = "Username = ?";
        $params[] = $username;
        $types .= 's';
    }
    if (!empty($email)) {
        $updateFields[] = "Email = ?";
        $params[] = $email;
        $types .= 's';
    }
    if (!empty($contactNumber)) {
        $updateFields[] = "ContactNumber = ?";
        $params[] = $contactNumber;
        $types .= 's';
    }
    if (!empty($dateOfBirth)) {
        $updateFields[] = "DateOfBirth = ?";
        $params[] = $dateOfBirth;
        $types .= 's';
    }

    // Only proceed if there are fields to update
    if (!empty($updateFields)) {
        $updateQuery = "UPDATE user SET " . implode(', ', $updateFields) . " WHERE UserId = ?";
        $params[] = $userId;
        $types .= 'i';
    
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param($types, ...$params);
    
        if ($updateStmt->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
            header("Location: ./accounts.php"); // Redirect to the same page to show the message
            exit();
        } else {
            $_SESSION['error'] = "Error updating profile: " . $conn->error;
            header("Location: ./accounts.php"); // Redirect to the same page to show the error
            exit();
        }
    } else {
        $_SESSION['message'] = "No changes made.";
        header("Location: ./accounts.php"); // Redirect to the same page to show the message
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="./css/edit-profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background-content">
        <?php include __DIR__ . '/accounts.php'; ?>
    </div>
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="modal-container">
        <div class="modal-header"> <!-- Modal Header -->
            <div class="rectangle">EDIT PERSONAL INFO.</div>
        </div>
        <!-- Modal Content -->
        <div class="modal">
            <?php if ($alertMessage): ?>
                <div class="alert <?php echo $alertType; ?>">
                    <?php echo htmlspecialchars($alertMessage); ?>
                </div>
            <?php endif; ?>
            <form id="transaction-form" method="POST">
                <!-- User's Name -->
                <div class="input-group">
                    <label for="recipient-name">Name</label>
                    <input type="text" id="recipient-name" name="name" placeholder="<?php echo htmlspecialchars($name); ?>">
                </div>
                <!-- User's Username -->
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="<?php echo htmlspecialchars($username); ?>">
                </div>
                <!-- User's Contact Number -->
                <div class="input-group">
                    <label for="contact-number">Contact Number</label>
                    <input type="text" id="contact-number" name="contact-number" placeholder="<?php echo htmlspecialchars($contactNumber); ?>" 
                        pattern="(09|03)\d{9}" maxlength="11" title="Please enter a valid contact number starting with 09 or 03, followed by 9 digits." 
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>
                <!-- User's Email Address -->
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>
                <!-- User's Date of Birth -->
                <div class="input-group">
                    <label for="date-of-birth">Date of Birth</label>
                    <input type="date" id="date-of-birth" name="date-of-birth" placeholder="<?php echo htmlspecialchars($dateOfBirth); ?>">
                </div>
                <!-- Submit Button -->
                <div class="keypad">
                    <button type="button" class="reset" onclick="resetForm()">RESET</button>
                    <button type="submit" class="save">SAVE</button>
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