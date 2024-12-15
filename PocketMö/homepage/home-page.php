<?php
include_once(__DIR__ . '/../config.php'); // Start the session

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

// Fetch user details and budget for the logged-in user
$user_id = $_SESSION["user"]["ID"];
$currentMonth = date("n"); // Get the current month (1-12)
$currentYear = date("Y"); // Get the current year (e.g., 2023)

// Fetch user details for the logged-in user
$sqlUser  = "SELECT Username, Email, Photo FROM user WHERE UserId = ?";
$stmtUser  = $conn->prepare($sqlUser );
$stmtUser ->bind_param("i", $user_id);
$stmtUser ->execute();
$resultUser  = $stmtUser ->get_result();

$user = null; // Initialize user variable
if ($resultUser ->num_rows > 0) {
    $user = $resultUser ->fetch_assoc(); // Get user details
}

// Fetch budget details for the logged-in user for the current month and year
$sqlBudget = "SELECT Budget FROM budget WHERE User_Id = ? AND Month = ? AND Year = ?";
$stmtBudget = $conn->prepare($sqlBudget);
$stmtBudget->bind_param("iii", $user_id, $currentMonth, $currentYear);
$stmtBudget->execute();
$resultBudget = $stmtBudget->get_result();

$budget = 0; // Initialize budget variable
if ($resultBudget->num_rows > 0) {
    $budgetRow = $resultBudget->fetch_assoc();
    $budget = $budgetRow['Budget']; // Get the budget from the result
}

$username = $user ? $user['Username'] : 'Guest'; // Get the full name
$email = $user ? $user['Email'] : 'No Email'; // Get the email from the database or use the fallback
$photo = $user && $user['Photo'] ? $user['Photo'] : 'https://t4.ftcdn.net/jpg/00/64/67/27/360_F_64672736_U5kpdGs9keUll8CRQ3p3YaEv2M6qkVY5.jpg'; // Set default photo if null
// Fetch today's expenses and calculate total
$totalExpenses = 0;
$currentDate = date("Y-m-d"); // Get current date in Y-m-d format
$expenseSql = "SELECT SUM(Cost) as Total FROM expense WHERE User_Id = ? AND DATE(Date) = ?";
$expenseStmt = $conn->prepare($expenseSql);
$expenseStmt->bind_param("is", $user_id, $currentDate);
$expenseStmt->execute();
$expenseResult = $expenseStmt->get_result();

if ($expenseResult->num_rows > 0) {
    $expenseRow = $expenseResult->fetch_assoc();
    $totalExpenses = $expenseRow['Total'] ? $expenseRow['Total'] : 0; // Get total or set to 0 if null
}

$expenseStmt->close();

// Fetch borrowed transactions for the specific user
$borrowedSql = "SELECT SUM(Amount) AS TotalBorrowed, SUM(Payment_Amount) AS TotalRepaid, SUM(Balance) AS TotalOutstanding FROM borrowed WHERE User_Id = ?";
$borrowedStmt = $conn->prepare($borrowedSql);
$borrowedStmt->bind_param("i", $user_id);
$borrowedStmt->execute();
$borrowedResult = $borrowedStmt->get_result();

$borrowedData = $borrowedResult->fetch_assoc();
$totalBorrowed = $borrowedData['TotalBorrowed'] ?? 0; // Total amount borrowed
$totalRepaid = $borrowedData['TotalRepaid'] ?? 0; // Total amount repaid
$totalOutstandingBorrow = $borrowedData['TotalOutstanding'] ?? 0; // Total outstanding amount

// Close the statement
$borrowedStmt->close();

// Fetch lent transactions for the specific user
$lentSql = "SELECT SUM(Amount) AS TotalLent, SUM(Payment_Amount) AS TotalRepaid, SUM(Balance) AS TotalOutstanding FROM lent WHERE User_Id = ?";
$lentStmt = $conn->prepare($lentSql);
$lentStmt->bind_param("i", $user_id);
$lentStmt->execute();
$lentResult = $lentStmt->get_result();

$lentData = $lentResult->fetch_assoc();
$totalLent = $lentData['TotalLent'] ?? 0; // Total amount lent
$totalRepaid = $lentData['TotalRepaid'] ?? 0; // Total amount repaid
$totalOutstandingLent = $lentData['TotalOutstanding'] ?? 0; // Total outstanding amount

// Close the statement
$lentStmt->close();

// Get the current month and year
$currentMonth = date('n'); 
$currentYear = date('Y');
$currentMonth_S = date('M');

// Prepare the SQL queries
// 1. Get Total Budget (Total Balance) for the logged-in user
$totalBudgetQuery = "SELECT SUM(TotalBudget) AS TotalBalance FROM budget WHERE User_Id = ? AND Month <= ? AND Year = ?";
$stmtBalance = $conn->prepare($totalBudgetQuery);
$stmtBalance->bind_param("iii", $user_id, $currentMonth, $currentYear);
$stmtBalance->execute();
$resultBalance = $stmtBalance->get_result();
$totalBalance = $resultBalance->fetch_assoc()['TotalBalance'] ?? 0;

// 2. Get Total Expense for the current month for the logged-in user
$totalExpenseQuery = "SELECT SUM(Cost) AS TotalExpense FROM expense WHERE User_Id = ? AND MONTH(Date) = ? AND YEAR(Date) = ?";
$stmtExpense = $conn->prepare($totalExpenseQuery);
$stmtExpense->bind_param("iii", $user_id, $currentMonth, $currentYear);
$stmtExpense->execute();
$resultExpense = $stmtExpense->get_result();
$totalExpense = $resultExpense->fetch_assoc()['TotalExpense'] ?? 0;

// Prepare data for the past and future months
$months = [];
$balanceData = [];
$expenseData = [];

// Loop to get data for the months
for ($i = -2; $i <= 2; $i++) { // From two months ago to two months ahead
    $month = ($currentMonth + $i - 1) % 12 + 1; // Adjust month for looping
    $year = $currentYear + floor(($currentMonth + $i - 1) / 12); // Adjust year if necessary

    // Get the total budget for the month for the logged-in user
    $budgetQuery = "SELECT SUM(TotalBudget) AS TotalBalance FROM budget WHERE User_Id = ? AND Month = ? AND Year = ?";
    $stmtBudget = $conn->prepare($budgetQuery);
    $stmtBudget->bind_param("iii", $user_id, $month, $year);
    $stmtBudget->execute();
    $resultBudget = $stmtBudget->get_result();
    $balance = $resultBudget->fetch_assoc()['TotalBalance'] ?? 0;

    // Get the total expenses for the month for the logged-in user
    $expenseQuery = "SELECT SUM(Cost) AS TotalExpense FROM expense WHERE User_Id = ? AND MONTH(Date) = ? AND YEAR(Date) = ?";
    $stmtExpense = $conn->prepare($expenseQuery);
    $stmtExpense->bind_param("iii", $user_id, $month, $year);
    $stmtExpense->execute();
    $resultExpense = $stmtExpense->get_result();
    $expense = $resultExpense->fetch_assoc()['TotalExpense'] ?? 0;

    // Store the month name and data
    $months[] = date('F', mktime(0, 0, 0, $month, 1)); // Get month name
    $balanceData[] = $balance;
    $expenseData[] = $expense;
}

// Close statements
$stmtBalance->close();
$stmtExpense->close();
$stmtBudget->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Financial Link</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Sans+Caption:wght@700&display=swap" />
    <link rel="stylesheet" href="./css/home-page.css" />
  </head>
  <body>
    <div class="main-sidebar">
      <img alt="User profile picture" height="80" src="<?php echo htmlspecialchars($photo); ?>">
      <div class="group-d">
        <span class="name-text"><?php echo htmlspecialchars($username); ?></span
        ><span class="email-text"><?php echo htmlspecialchars($email); ?></span>
      </div>
      <div class="sidebar">
        <a href="./home-page.php" class="home-page-12">Home Page</a>
        <a href="../account/accounts.php" class="account">Account</a>
        <a class="transactions" href="#" onclick="return false;">Transactions</a>
        <div class="additional-links" style="display: none;">
          <a href="../lent/lent-page.php" class="text1">Money Lent</a>
          <a href="../borrow/borrow-page.php" class="text2">Money Borrowed</a>
        </div>
        <a href="../logout.php" class="logout">Logout</a>
      </div>
      <div class="rectangle-13"></div>
      <div class="simple-app-logo"></div>
    </div>
    <div class="main-container">
        <div id="alert" class="alert" style="display: none;">
            <span id="alert-message"></span>
        </div>
        <div class="background"></div>
        <div class="home"><div class="icon-4"></div></div>
        <span class="home-page-1">Home Page</span>
        <div class="group-2">
          <div class="notifications"><div class="icon"></div></div>
          <div class="notification-box" id="notificationBox" style="display: none;">No Notifications</div>
          <div class="settings"><div class="icon-3"></div></div>
        </div>
        <div class="rectangle"></div>
        <div class="rectangle-5"></div>
        <span class="balance">Balance</span
        ><span class="total-expense">Expense</span>
        <div class="money"></div>
        <div class="group-6">
          <div class="wallet"><div class="icon-7"></div></div>
        </div>
        <a class="click" href="./balance-add.php">
          <div class="ellipse"></div>
          <div class="plus">
            <div class="icon-9"></div>
          </div>
        </a>
        <a class="click-2" href="expense-history.php">
          <div class="ellipse-8"></div>
          <div class="plus-a"><div class="icon-b"></div></div>
        </a>
        <span class="money-text">₱ <?php echo number_format($budget, 2); ?></span
        ><span class="money-text-c">₱ <?php echo number_format($totalExpenses, 2); ?></span>
        <div class="rectangle-e"></div>
        <div class="rectangle-f"></div>
        <span class="total-borrowed">Money Borrowed</span>
        <span class="total-lent">Money Lent</span>
        <div class="coin-hand"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="subscription-cashflow"><div class="icon-11"></div></div>
        <span class="money-amount">₱ <?php echo number_format($totalOutstandingBorrow, 2); ?></span>
        <span class="money-amount-14">₱ <?php echo number_format($totalOutstandingLent, 2); ?></span>
        <div class="rectangle-15">
          <div class="pie-container">
            <div class="chart" id="chart"></div>
            <div class="legend" id="legend"></div>
          </div>
        </div>
        <div class="rectangle-16">
          <div class="graph">
              <div class="graph-container">
                  <canvas id="myChart"></canvas>
              </div>
          </div>
        </div>
        <span class="daily-expense">Daily Expense</span>
        <span class="monthly-expense">Monthly Expense</span>
        <div class="money-17"></div>
        <div class="month-date"></div><div class="year-date"></div>
        <span class="month"><?php echo $currentMonth_S; ?></span><span class="year"><?php echo $currentYear; ?></span>
    </div>

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

        // Function to show the alert
        function showAlert(message) {
            const alertBox = document.getElementById('alert');
            alertBox.textContent = message; // Set the alert message
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
            const message = "<?php echo addslashes($message); ?>"; // Get the message from PHP
            if (message) {
                showAlert(message);
            }
        };

        // Optionally, set the first link as active on page load
        document.querySelector('.sidebar a:first-child').classList.add('active');

      // Fetch expense data from the server
      fetch('./graph/fetch-expenses.php')
          .then(response => response.json())
          .then(data => {
              const categories = ['FOOD', 'MATERIAL', 'ENTERTAINMENT', 'MISCELLANEOUS', 'TRANSPORTATION'];
              const colors = ['#9B2227', '#BB3F03', '#CA6702', '#EE9B00', '#E9D7A5'];
              let totalCosts = [0, 0, 0, 0, 0];

              // Check if there is data
              if (data.length === 0) {
                  // Set default values
                  totalCosts = [0, 0, 0, 0, 0];
              } else {
                  // Calculate total costs for each category
                  data.forEach(expense => {
                      const index = categories.indexOf(expense.Category);
                      if (index !== -1) {
                          totalCosts[index] += parseFloat(expense.TotalCost);
                      }
                  });
              }

              // Update the chart background with conic-gradient
              const total = totalCosts.reduce((a, b) => a + b, 0);
              let gradientStops = '';
              let startAngle = 0;

              totalCosts.forEach((cost, index) => {
                  if (total > 0 && cost > 0) {
                      const percentage = (cost / total) * 100;
                      const endAngle = startAngle + percentage;
                      gradientStops += `${colors[index]} ${startAngle}% ${endAngle}%, `;
                      startAngle = endAngle;
                  }
              });

              // If there are no costs, set a default gradient
              if (total === 0) {
                  gradientStops = colors.map((color, index) => `${color} ${index * 20}% ${(index + 1) * 20}%`).join(', ');
              }

              // Remove the last comma and space
              gradientStops = gradientStops.slice(0, -2);
              document.getElementById('chart').style.background = `conic-gradient(${gradientStops})`;

              // Populate the legend
              const legend = document.getElementById('legend');
              legend.innerHTML = ''; // Clear previous legend items
              totalCosts.forEach((cost, index) => {
                  const legendItem = document.createElement('div');
                  legendItem.className = 'legend-item';
                  legendItem.innerHTML = `
                      <div class="legend-color ${categories[index].toLowerCase()}"></div>
                      <div class="legend-text">
                          <span>${categories[index]}</span>
                          <span>₱ ${cost.toFixed(2)}</span>
                      </div>
                  `;
                  legend.appendChild(legendItem);
              });
          })
          .catch(error => console.error('Error fetching data:', error));

          //chart
          var ctx = document.getElementById('myChart').getContext('2d');
          var myChart = new Chart(ctx, {
              type: 'bar',
              data: {
                  labels: <?php echo json_encode($months); ?>,
                  datasets: [
                      {
                          label: 'Total Balance',
                          data: <?php echo json_encode($balanceData); ?>,
                          backgroundColor: '#0048B5',
                          borderColor: 'rgba(31, 119, 180, 1)',
                          borderWidth: 1,
                          borderRadius: {
                              topLeft: 10,
                              topRight: 10,
                              bottomLeft: 0,
                              bottomRight: 0
                          }
                      },
                      {
                          label: 'Total Expense',
                          data: <?php echo json_encode($expenseData); ?>,
                          backgroundColor: '#0A2471',
                          borderColor: 'rgba(11, 61, 145, 1)',
                          borderWidth: 1,
                          borderRadius: {
                              topLeft: 10,
                              topRight: 10,
                              bottomLeft: 0,
                              bottomRight: 0
                          }
                      }
                  ]
              },
              options: {
                  animation: false, // Disable animation
                  plugins: {
                      legend: {
                          position: 'bottom', // Move legend to the bottom of the x-axis
                          labels: {
                              color: 'rgba(1, 28, 53, 1)', // Set legend text color
                              font: {
                                  size: 16, // Set font size for legend
                                  family: 'Inter', // Set font family for legend
                                  weight: 'bold' // Optional: Set font weight for legend
                              },
                              padding: 30
                          }
                      }
                  },
                  scales: {
                      x: {
                          grid: {
                              display: false // Disable vertical grid lines
                          },
                          border: {
                              display: false // Disable x-axis border
                          },
                          ticks: {
                              color: 'rgba(1, 28, 53, 1)',
                              font: {
                                  size: 14, // Set font size
                                  family: 'Inter', // Set font family
                                  weight: 'bold' // Optional: Set font weight
                              },
                          },
                      },
                      y: {
                          ticks: {
                              color: 'rgba(1, 28, 53, 1)',
                              font: {
                                  size: 14, // Set font size
                                  family: 'Inter', // Set font family
                                  weight: 'bold' // Optional: Set font weight
                              },
                              padding: 10
                          },
                          border: {
                              display: false // Disable y-axis border
                          },
                      }
                  }
              }
          });
    </script>
  </body>
</html>
