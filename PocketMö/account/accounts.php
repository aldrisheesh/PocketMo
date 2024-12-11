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

// Fetch borrowed transactions for the specific user
$userId = $_SESSION["user"]["ID"] ?? null; // Get the user ID from the session

// Fetch user details for the logged-in user, including the password length
$sql = "SELECT Username, Email, Photo, Name, ContactNumber, DateOfBirth, PasswordLength FROM user WHERE UserId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$user = null; // Initialize user variable
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc(); // Get user details
}

// Use fallback values if user data is not available
$username = $user['Username'] ?? 'Guest'; // Get the username or use 'Guest'
$email = $user['Email'] ?? 'No Email'; // Get the email or use 'No Email'
$name = $user['Name'] ?? 'N/A'; // Get the name or use 'No Name'
$contactNumber = $user['ContactNumber'] ?? 'N/A'; // Get the contact number or use 'No Contact Number'
$dateOfBirth = !empty($user['DateOfBirth']) && $user['DateOfBirth'] !== '0000-00-00' ? date('F j, Y', strtotime($user['DateOfBirth'])) : 'N/A'; // Format the date of birth or use 'No Date of Birth'
$photo = !empty($user['Photo']) ? $user['Photo'] : 'https://t4.ftcdn.net/jpg/00/64/67/27/360_F_64672736_U5kpdGs9keUll8CRQ3p3YaEv2M6qkVY5.jpg'; // Set default photo if null

// Get the password length
$passwordLength = $user['PasswordLength'] ?? 0; // Get the password length or use 0
$asterisks = str_repeat('*', $passwordLength); // Generate asterisks based on the password length

// Function to apply styles based on value presence
function styleText($value) {
    return empty($value) || $value === 'N/A' || $value === 'N/A' || $value === 'N/A' ? 'style="color: lightgray;"' : '';
}

// Close statement and connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Sans+Caption:wght@700&display=swap" />
    <link rel="stylesheet" href="./css/account.css" />
</head>
<body>
    <div class="main-sidebar"> <!--sidebar start-->
    <img alt="User profile picture" height="80" src="<?php echo htmlspecialchars($photo); ?>">
        <div class="group-d">
            <span class="name-text"><?php echo htmlspecialchars($username); ?></span>
            <span class="email-text"><?php echo htmlspecialchars($email); ?></span>
        </div>
        <div class="sidebar">
            <a href="../homepage/home-page.php" class="home-page-12">Home Page</a>
            <a href="./accounts.php" class="account">Account</a>
            <a class="transactions" href="#" onclick="return false;">Transactions</a>
            <div class="additional-links" style="display: none;">
                <a href="../lent/lent-page.php" class="text1">Money Lent</a>
                <a href="../borrow/borrow-page.php" class="text2">Money Borrowed</a>
            </div>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
        <div class="rectangle-13"></div>
        <div class="simple-app-logo"></div>
    </div> <!--sidebar end-->

    <div class="main-container"> <!--parent section of box-->
        <div id="alert" class="alert" style="display: none;">
            <span id="alert-message"></span>
        </div>
        <div class="home"> <!--header start-->
                <div class="home-icon"></div>
            </div>
            <span class="home-page-1">Account Page</span>
            <div class="group-2">
                <div class="notifications"><div class="notif-icon"></div></div>
                <div class="settings"><div class="setting-icon"></div></div>
        </div> <!--header end-->

        <div class="box"> <!-- white container start -->
            <div class="head-divider"></div>

            <div class="account-container">
                <!-- Left Section -->
                <div class="left-section">
                    <div class="section-titles"><h2>MANAGE ACCOUNT</h2></div>
                    <div class="profile-pic-container">
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Profile" class="profile-pic">
                        <button class="edit-pic-button" id="editPicButton">
                            <img src="../assets/images/edit-pfp.png" alt="Edit Profile">
                        </button>
                        <input type="file" id="fileInput" accept="image/*" style="display: none;">
                    </div>
                    
                    <div class="personal-info">
                        <div class="subsec-personal-info">
                            <div class="section-titles"><h2>PERSONAL INFORMATION</h2></div>
                            <a href="./edit-profile-modal.php" class="btn btn-upd">
                                <img src="../assets/images/update-icon.png" alt="Update Icon" class="btn-icon"> Edit
                            </a>
                        </div>
  
                        <div class="table-bg">
                            <table>
                                <tr>
                                    <th>Name</th>
                                    <td style="<?php echo empty($name) || $name === 'N/A' ? 'color: lightgray;' : ''; ?>">
                                        <?php echo htmlspecialchars($name); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Username</th>
                                    <td style="<?php echo empty($username) || $username === 'N/A' ? 'color: lightgray;' : ''; ?>">
                                        <?php echo htmlspecialchars($username); ?>
                                    </td>
                                </tr>
                                <tr>
                                <th>Contact Number</th>
                                <td style="<?php echo empty($contactNumber) || $contactNumber === 'N/A' ? 'color: lightgray;' : ''; ?>">
                                    <?php 
                                    if (!empty($contactNumber) && $contactNumber !== 'N/A') {
                                        // Ensure the contact number is exactly 11 digits
                                        if (preg_match('/^0\d{10}$/', $contactNumber)) { // Check if it starts with 0 and has 11 digits
                                            // Remove the first digit (0) and format the contact number
                                            $contactNumberWithoutZero = substr($contactNumber, 1); // Remove the first character
                                            $formattedNumber = '+63 ' . substr($contactNumberWithoutZero, 0, 3) . ' ' . 
                                                            substr($contactNumberWithoutZero, 3, 3) . ' ' . 
                                                            substr($contactNumberWithoutZero, 6, 4);
                                            echo htmlspecialchars($formattedNumber);
                                        } else {
                                            echo htmlspecialchars($contactNumber); // Output the raw value if not valid
                                        }
                                    } else {
                                        echo htmlspecialchars($contactNumber); // This will output 'N/A' or an empty string
                                    }
                                    ?>
                                </td>
                                </tr>
                                <tr>
                                    <th>Email Address</th>
                                    <td style="<?php echo empty($email) || $email === 'N/A' ? 'color: lightgray;' : ''; ?>">
                                        <?php echo htmlspecialchars($email); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td style="<?php echo empty($dateOfBirth) || $dateOfBirth === 'N/A' ? 'color: lightgray;' : ''; ?>">
                                        <?php echo htmlspecialchars($dateOfBirth); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Password</th>
                                    <td class="password-cell">
                                        <?php echo htmlspecialchars($asterisks); // Display asterisks ?>
                                        <a href="./change-password-modal.php" class="set-pass-button">
                                            <img src="../assets/images/set-pass.png" alt="Change Password Icon" class="set-pass-icon">
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div> <!-- Left Section end -->

                
                <!-- Right Section -->
                <div class="right-section">
                  <div class="section-titles"><h2>ACCOUNT PREFERENCES</h2></div>
                  
                  <div class="preferences-container">
                    <!-- Notifications -->
                    <div class="preference-row">
                      <span class="main-label">Notifications</span>
                      <span class="sub-label">Push Notifications</span>
                      <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider round"></span>
                      </label>
                    </div>
                  
                    <div class="preference-row">
                      <span class="main-label"></span> <!-- Empty for alignment -->
                      <span class="sub-label">Email Notifications</span>
                      <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider round"></span>
                      </label>
                    </div>
                  
                    <div class="preference-row">
                      <span class="main-label"></span> <!-- Empty for alignment -->
                      <span class="sub-label">SMS Alerts</span>
                      <label class="switch">
                        <input type="checkbox">
                        <span class="slider round"></span>
                      </label>
                    </div>
                  
                    <!-- Language -->
                    <div class="preference-row">
                      <span class="main-label">Language</span>
                      <span class="sub-label"></span> <!-- Empty for alignment -->
                      <select class="dropdown" style="border: none">
                        <option>English</option>
                        <option>Filipino</option>
                      </select>
                    </div>
                  
                    <!-- Default Page -->
                    <div class="preference-row">
                      <span class="main-label">Default Page</span>
                      <span class="sub-label"></span> <!-- Empty for alignment -->
                      <select class="dropdown" style="border: none">
                        <option>Dashboard</option>
                      </select>
                    </div>
                  
                    <!-- Default Currency -->
                    <div class="preference-row">
                      <span class="main-label">Default Currency</span>
                      <span class="sub-label"></span> <!-- Empty for alignment -->
                      <select class="dropdown" style="border: none">
                        <option>Philippine Peso</option>
                      </select>
                    </div>
                  
                    <!-- Payment Methods -->
                    <div class="preference-row">
                      <span class="main-label">Payment Method</span>
                      <span class="sub-label">Bank Transfer</span>
                      <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider round"></span>
                      </label>
                    </div>
                  
                    <div class="preference-row">
                      <span class="main-label"></span> <!-- Empty for alignment -->
                      <span class="sub-label">Card</span>
                      <label class="switch">
                        <input type="checkbox">
                        <span class="slider round"></span>
                      </label>
                    </div>
                  
                    <div class="preference-row">
                      <span class="main-label"></span> <!-- Empty for alignment -->
                      <span class="sub-label">Digital Wallet</span>
                      <label class="switch">
                        <input type="checkbox">
                        <span class="slider round"></span>
                      </label>
                    </div>

                    <div class="preference-row">
                        <span class="main-label">Data Backup</span>
                        <span class="sub-label">Download your information</span>
                        <button class="backup-button"><img src="../assets/images/dl-icon.png" alt="Download Icon"></button>
                    </div>
                      
                  
                    <!-- Delete Account Button -->
                    <button class="delete-account" onclick="redirectToDeleteAccount()">
                        <i class="fas fa-trash-alt"></i>Delete Account
                    </button>
                  
                    </div>

                  </div>
                </div><!-- Right Section end-->
              </div>
        </div> <!-- white container end -->
    </div> <!--parent section of box end-->

        <!--script tags-->
      <script>
        // Get all the links in the sidebar
        const links = document.querySelectorAll('.sidebar a');
        const additionalLinksContainer = document.querySelector('.additional-links');

        // Initially hide the additional links
        additionalLinksContainer.style.display = 'none'; // Ensure it's hidden initially

        // Add click event listener to each link
        links.forEach(link => {
            link.addEventListener('click', function(event) {
                // If the clicked link is the Transactions link, prevent redirection
                if (this.classList.contains('transactions')) {
                    event.preventDefault(); // Prevent default action (navigation)
                    
                    // Toggle the visibility of additional links
                    additionalLinksContainer.style.display = additionalLinksContainer.style.display === 'none' ? 'block' : 'none';
                }
                // If the clicked link is Money Lent or Money Borrowed, do not hide additional links
                else if (this.classList.contains('money-lent') || this.classList.contains('money-borrowed')) {
                    // Just add active class, do nothing else
                    links.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    // Ensure additional links are visible
                    additionalLinksContainer.style.display = 'block'; 
                } 
                // For all other links, hide additional links
                else {
                    // Remove 'active' class from all links
                    links.forEach(l => l.classList.remove('active'));
                    // Add 'active' class to the clicked link
                    this.classList.add('active');
                    
                    // Hide additional links if any other link is clicked
                    additionalLinksContainer.style.display = 'none';
                }
            });
        });

        // Optionally, set the first link as active on page load
        document.querySelector('.sidebar a:nth-child(2)').classList.add('active');

        function redirectToDeleteAccount() {
            window.location.href = "./delete-account.php"; // Replace with your desired URL
        }
        // Function to show the alert
        function showAlert(message) {
            const alertBox = document.getElementById('alert');
            const alertMessage = document.getElementById('alert-message');
            alertMessage.textContent = message; // Set the alert message
            alertBox.style.display = 'flex'; // Use flex to align items

            // Automatically hide the alert after 3 seconds
            setTimeout(() => {
                alertBox.classList.add('hide');
                setTimeout(() => {
                    alertBox.style.display = 'none';
                    alertBox.classList.remove('hide');
                }, 500); // Wait for the fade-out transition
            }, 3000);
        }

        // Show alert if there is a message
        window.onload = function() {
            const alertBox = document.getElementById('alert');
            const alertMessage = document.getElementById('alert-message');
            const alertType = "<?php echo !empty($error) ? 'error' : 'success'; ?>"; // Determine alert type
            const messageContent = "<?php echo addslashes($alertMessage); ?>"; // Get the alert message

            if (messageContent) { // Check if there is a message
                alertMessage.textContent = messageContent; // Set the alert message
                alertBox.classList.add(alertType); // Add the appropriate class for styling
                alertBox.style.display = 'flex'; // Use flex to align items
                alertBox.classList.add('show'); // Show the alert with animation

                // Automatically hide the alert after 3 seconds
                setTimeout(() => {
                    alertBox.classList.add('hide');
                    setTimeout(() => {
                        alertBox.style.display = 'none';
                        alertBox.classList.remove('hide');
                        alertBox.classList.remove(alertType); // Remove the alert type class
                    }, 500); // Wait for the fade-out transition
                }, 3000);
            } else {
                alertBox.style.display = 'none'; // Ensure the alert box is hidden if there's no message
            }
        };

        document.getElementById('editPicButton').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });

        document.getElementById('fileInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('profilePicture', file);

                // Send the file to the server
                fetch('./upload.php', { // Make sure to create this PHP file
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the profile picture on the page
                        document.querySelector('.profile-pic').src = data.filePath; // Update the image source
                        showAlert('Profile picture updated successfully!'); // Show success message

                        // Reload the page after a short delay to allow the user to see the success message
                        setTimeout(() => {
                            location.reload(); // Reload the page
                        });
                    } else {
                        showAlert('Error updating profile picture: ' + data.message); // Show error message
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    showAlert('An error occurred while uploading the image.');
                });
            }
        });
    </script>
</body>
</html>