<?php
session_start();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form input
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate the input
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        require_once "database.php"; // Include database connection

        // Check if the username or email already exists
        $checkSql = "SELECT * FROM user WHERE Username = ? OR Email = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Calculate the length of the password
            $passwordLength = strlen($password);

            // Proceed with the insertion
            $sql = "INSERT INTO user (Username, Email, Password, PasswordLength) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hash the password

            if ($stmt) {
                $stmt->bind_param("sssi", $username, $email, $hashedPassword, $passwordLength);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Registration successful! You can now log in.";
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "SQL preparation failed: " . $conn->error;
            }
        }
        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/registration.css"> 
</head>
<body>
    <div class="container">
        <div class="left"></div>
        <div class="middle"></div>
        <div class="right">
            <div class="logo">
                <img alt="App logo" height="100" src="./assets/images/logo-1.png" width="100"/>
                <h2>CREATE ACCOUNT</h2>
            </div>
            <form class="login-form" action="registration.php" method="post">
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <i class="bi bi-person-circle"></i>
                    <input type="text" placeholder="Username" name="username" required/>
                </div>
                <div class="form-group">
                    <i class="bi bi-envelope-fill"></i>
                    <input type="email" placeholder="Email Address" name="email" required/>
                </div>
                <div class="form-group">
                    <i class="bi bi-keyboard"></i>
                    <input type="password" placeholder="Password" name="password" required/>
                </div>
                <div class="form-group">
                    <i class="fas fa-exclamation-circle"></i>
                    <input type="password" placeholder="Re-enter Password" name="confirmPassword" required/>
                </div>
                <button class="btn" type="submit">SIGN UP</button>
                <div class="signup">
                    Already have an account? <a href="./index.php">SIGN IN</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>