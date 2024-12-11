<?php
include_once(__DIR__ . '/../config.php'); // Start the session

// Check if the user is logged in
if (!isset($_SESSION["user"])) {
    header("Location: ../index.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION["user"]["ID"] ?? null; // Access the user ID from the session
    $budgetAmount = $_POST['totalBudget'] ?? 0; // Get the budget amount from the form input
    $startDate = $_POST['startDate'] ?? null; // Get the start date from the form input
    $endDate = $_POST['endDate'] ?? null; // Get the end date from the form input

    // Check if startDate and endDate are valid
    if (empty($startDate) || empty($endDate)) {
        $_SESSION['error'] = 'Please provide valid start and end dates.';
        header("Location: ./home-page.php");
        exit();
    }

    // Convert dates
    $rDate = date("Y-m-d H:i:s", strtotime($startDate));
    $duration = date("Y-m-d H:i:s", strtotime($endDate));

    // Get the current month and year
    $currentMonth = date("n", strtotime($startDate)); // Numeric representation of the month (1-12)
    $currentYear = date("Y", strtotime($startDate)); // Full numeric representation of the year

    // Check if a budget entry already exists for the user for the current month and year
    $checkSql = "SELECT * FROM budget WHERE User_Id = ? AND Month = ? AND Year = ?";
    $checkStmt = $conn->prepare($checkSql);
    
    if (!$checkStmt) {
        $_SESSION['error'] = "SQL preparation failed: " . $conn->error;
        header("Location: ./home-page.php");
        exit();
    }

    $checkStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // If a budget entry exists, update it
        $row = $result->fetch_assoc();
        $newTotalBudget = $row['TotalBudget'] + $budgetAmount; // Calculate the new total budget

        $updateSql = "UPDATE budget SET Budget = Budget + ?, TotalBudget = ?, RDATE = ?, Duration = ? WHERE User_Id = ? AND Month = ? AND Year = ?";
        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {
            $_SESSION['error'] = "SQL preparation failed: " . $conn->error;
            header("Location: ./home-page.php");
            exit();
        }

        // Bind parameters and execute
        $updateStmt->bind_param("ddssiii", $budgetAmount, $newTotalBudget, $rDate, $duration, $userId, $currentMonth, $currentYear);
        if ($updateStmt->execute()) {
            $_SESSION['message'] = 'Budget updated successfully!';
        } else {
            $_SESSION['error'] = 'Error updating budget: ' . $updateStmt->error;
        }

        $updateStmt->close();
    } else {
        // If no budget entry exists, insert a new one
        $insertSql = "INSERT INTO budget (User_Id, Budget, TotalBudget, RDATE, Duration, Month, Year) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            $_SESSION['error'] = "SQL preparation failed: " . $conn->error;
            header("Location: ./home-page.php");
            exit();
        }

        // Bind parameters and execute
        $insertStmt->bind_param("iddsiii", $userId, $budgetAmount, $budgetAmount, $rDate, $duration, $currentMonth, $currentYear);

        if ($insertStmt->execute()) {
            $_SESSION['message'] = 'Balance added successfully!';
        } else {
            $_SESSION['error'] = 'Error adding budget: ' . $insertStmt->error;
        }

        $insertStmt->close();
    }

    $checkStmt->close();

    // Redirect to dashboard after processing
    header("Location: ./home-page.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Financial Link</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Konkhmer+Sleokchher:wght@400&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700&display=swap" />
    <link rel="stylesheet" href="./css/balance.css" />
</head>
<body>
    <div class="background-content">
        <?php include __DIR__ . '/home-page.php'; ?>
    </div>
    <div class="modal-backdrop" onclick="redirectToPage()"></div> 
    <div class="modal">
        <div class="modal-content">
            <span class="add-balance">ADD BALANCE</span>
            <div class="line"></div>
            <div class="flex-row-aa">
                <span class="balance-1">Balance</span>
                <span class="duration-1">Duration</span>
            </div>
            <form method="post" action="">
              <div class="flex-row-aa-1">
                  <div class="rectangle-input">
                      <input id="totalBudget" name="totalBudget" placeholder="Enter Amount" type="number" readonly required />
                  </div>
                  <div class="rectangle-input">
                      <input id="startDate" name="startDate" type="date" required/>
                  </div>
                  <div class="rectangle-input">
                      <input id="endDate" name="endDate" type="date" required/>
                  </div>
                  <span class="to">TO</span>
              </div>
              <div class="button-grid">
                  <div class="flex-row-e">
                      <button type="button" class="rectangle-button" onclick="addToBudget(50)">₱ 50</button>
                      <button type="button" class="rectangle-button" onclick="addToBudget(100)">₱ 100</button>
                  </div>
              </div>
              <div class="flex-row-fa">
                  <button type="button" class="rectangle-button" onclick="addToBudget(200)">₱ 200</button>
                  <button type="button" class="rectangle-button" onclick="addToBudget(500)">₱ 500</button>
              </div>
              <div class="flex-row-db">
                  <button type="button" class="rectangle-button" onclick="addToBudget(1000)">₱ 1,000</button>
                  <button type="button" class="rectangle-button" onclick="addToBudget(5000)">₱ 5,000</button>
              </div>
              <div class="flex-row-fae">
                  <button type="button" class="rectangle-button-clear" onclick="clearBudget()"><span class="span-clear">CLEAR</span></button>
                  <button class="rectangle-button-done" type="submit"><span class="span-done">DONE</span></button>
              </div>
          </form>
        </div>
    </div>

    <script>
        function addToBudget(amount) {
            const totalBudgetInput = document.getElementById('totalBudget');
            const currentBudget = parseInt(totalBudgetInput.value) || 0; // Get current budget or default to 0
            totalBudgetInput.value = currentBudget + amount; // Update the input value
        }

        function clearBudget() {
            document.getElementById('totalBudget').value = ''; // Clear the input field
        }

        // Function to redirect when modal backdrop is clicked
        function redirectToPage() {
            window.location.href = './home-page.php'; // Change this to your desired URL
        }
    </script>
</body>
</html>