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

// Fetch user details for the logged-in user
$sql = "SELECT Username, Email, Photo FROM user WHERE user.UserId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$user = null; // Initialize user variable
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc(); // Get user details
}

$username = $user ? $user['Username'] : 'Guest'; // Get the full name
$email = $user ? $user['Email'] : 'No Email'; // Get the email from the database or use the fallback
$photo = $user && $user['Photo'] ? $user['Photo'] : 'https://t4.ftcdn.net/jpg/00/64/67/27/360_F_64672736_U5kpdGs9keUll8CRQ3p3YaEv2M6qkVY5.jpg'; // Set default photo if null

// Prepare SQL SELECT statement for borrowed transactions
$borrowedSql = "SELECT Borrow_Date, Amount, Name, Payment_Amount, Balance, Due_Date FROM borrowed WHERE User_Id = ? ORDER BY Due_Date ASC"; // Changed to sort by Due_Date
$borrowedStmt = $conn->prepare($borrowedSql);
$borrowedStmt->bind_param("i", $userId);
$borrowedStmt->execute();
$borrowedResult = $borrowedStmt->get_result();

// Store borrowed transactions to display later
$transactions = [];
while ($row = $borrowedResult->fetch_assoc()) {
    $transactions[] = $row;
}

// Sort transactions by balance (0 balances last)
usort($transactions, function($a, $b) {
    if ($a['Balance'] == 0 && $b['Balance'] != 0) return 1;
    if ($a['Balance'] != 0 && $b['Balance'] == 0) return -1;
    return $a['Due_Date'] <=> $b['Due_Date']; // Secondary sort by Due Date
});

// Close the statement
$borrowedStmt->close();

// Calculate the total amount borrowed, only including transactions with a balance > 0
$totalAmount = 0;
foreach ($transactions as $transaction) {
    if ($transaction['Balance'] > 0) {
        $totalAmount += $transaction['Amount']; // Only include amounts where balance > 0
    }
}

// Calculate the repaid amount and outstanding amount
$repaidAmount = 0;
$outstandingAmount = 0;

foreach ($transactions as $transaction) {
    if ($transaction['Balance'] > 0) {
        $repaidAmount += $transaction['Payment_Amount']; // Only consider payments where balance > 0
        $outstandingAmount += $transaction['Balance'];   // Only consider balances where balance > 0
    }
}

// Calculate the payable amount (which is the outstanding amount)
$payableAmount = $outstandingAmount; // This is the outstanding amount after filtering

// Calculate the progress percentage
$progressPercentage = $totalAmount > 0 ? ($payableAmount / $totalAmount) * 100 : 0;

// Close statement and connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Sans+Caption:wght@700&display=swap" />
    <link rel="stylesheet" href="./css/lent-and-borrow.css" />
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
            <a href="../account/accounts.php" class="account">Account</a>
            <a href="transactions.php" class="transactions" href="#" onclick="return false;">Transactions</a>
            <div class="additional-links">
                <a href="../lent/lent-page.php" class="text1">Money Lent</a>
                <a href="./borrow-page.php" class="text2">Money Borrowed</a>
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
            <span class="home-page-1">Borrow Page</span>
            <div class="group-2">
                <div class="notifications"><div class="notif-icon"></div></div>
                <div class="settings"><div class="setting-icon"></div></div>
        </div> <!--header end-->
        <div class="box"> <!-- white container start -->
            <div class="stat-container"> <!-- stat container start -->
                <!-- Total Money Lent Box -->
                <div class="card-header">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="box-total-money-lent">
                    <p class="box-title">Total Money Borrowed</p>
                    <p class="total-amount">₱ <?php echo number_format($totalAmount, 2); ?></p> <!-- Total Money Borrowed -->
                </div>

                <!-- Repaid and Outstanding Box -->
                <div class="repaid-n-outstanding-box">
                    <div class="box-2">
                        <p class="mini-box-title">Repaid Amount</p>
                        <p class="mini-box-total-amount">₱ <?php echo number_format($repaidAmount, 2); ?></p> <!-- Repaid Amount -->
                    </div>
                    <div class="box-2">
                        <p class="mini-box-title">Outstanding Amount</p>
                        <p class="mini-box-total-amount">₱ <?php echo number_format($outstandingAmount, 2); ?></p> <!-- Outstanding Amount -->
                    </div>
                </div>
                
                <!-- Bar Chart Box Placeholder -->
                <div class="box-for-bar">
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo round($progressPercentage); ?>%;"></div> <!-- Set width here -->
                    </div>
                    <div class="text-bar">
                        <p>Payable: ₱<?php echo number_format($payableAmount, 2); ?> / ₱<?php echo number_format($totalAmount, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="transaction-section">
                <h2>TRANSACTIONS</h2>
            </div>
            <div class="table-bg">
            <div class="table-container">
                <div class="table-scroll"> <!-- Scrollable container -->
                    <table>
                        <thead class="table-header">
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Lender's Name</th>
                                <th>Payment</th>
                                <th>Balance</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Define the maximum number of rows
                            $maxRows = 7;

                            // Count the number of transactions
                            $transactionCount = count($transactions);

                            // Loop through the transactions and display them
                            foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['Borrow_Date']); ?></td>
                                    <td>₱ <?php echo number_format($transaction['Amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Name']); ?></td>
                                    <td>₱ <?php echo number_format($transaction['Payment_Amount'], 2); ?></td>
                                    <td>₱ <?php echo number_format($transaction['Balance'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Due_Date']); ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Generate empty rows if there are fewer than maxRows -->
                            <?php for ($i = $transactionCount; $i < $maxRows; $i++): ?>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div> <!-- End of scrollable container -->

                <div class="button-container">
                    <a href="./new-transact-borrow.php" class="btn btn-rec">
                        <img src="../assets/images/plus-icon.png" alt="Add Icon" class="btn-icon"> Record
                    </a>
                    <a href="./upd-transact-borrow.php" class="btn btn-upd">
                        <img src="../assets/images/update-icon.png" alt="Update Icon" class="btn-icon"> Update
                    </a>
                </div>
            </div>
        </div>

                </div>
            </div>
        </div> <!-- white container end -->
    </div> <!--parent section of box end-->

    <!--script tags-->
    <script>
        const links = document.querySelectorAll('.sidebar a');
        const additionalLinksContainer = document.querySelector('.additional-links');

        // Set the "Money Borrowed" link as active
        const moneyBorrowedLink = document.querySelector('.text2');
        moneyBorrowedLink.classList.add('active');

        // Set the "Transactions" link as active
        const transactionsLink = document.querySelector('.transactions');
        transactionsLink.classList.add('active');

        // Initially show the additional links
        additionalLinksContainer.style.display = 'block';

        links.forEach(link => {
            link.addEventListener('click', function(event) {
                // If the clicked link is the Transactions link
                if (this.classList.contains('transactions')) {
                    event.preventDefault(); // Prevent default action
                    // No toggle since we want it always visible
                } else {
                    // Remove active class from all links
                    this.classList.add('active'); // Set active class on clicked link
                }
            });
        });

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
    </script>
</body>
</html>