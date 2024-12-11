<?php
// Include database connection
include_once(__DIR__ . '/../config.php'); // Ensure this file connects to your database

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION["user"]["ID"] ?? null; // Get the user ID from the session
    $amount = $_POST['amount'] ?? 0; // Get the amount from the form
    $description = $_POST['description'] ?? ''; // Get the description from the form
    $category = $_POST['category'] ?? ''; // Get the category from the form
    $date = date("Y-m-d H:i:s"); // Get the current date

    // Check if a category is selected
    if (empty($category)) {
        $_SESSION['error'] = 'Please select a category.';
        header("Location: ./expense-history.php");
        exit();
    }

    // Prepare SQL INSERT statement for expense including category
    $insertSql = "INSERT INTO expense (User_Id, Description, Cost, Date, Category) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);

    if (!$insertStmt) {
        $_SESSION['error'] = "SQL preparation failed: " . $conn->error;
        header("Location: ./expense-history.php");
        exit();
    }

    // Bind parameters and execute
    $insertStmt->bind_param("issss", $userId, $description, $amount, $date, $category);

    if ($insertStmt->execute()) {
        // Get the current month and year
        $currentMonth = date("n"); // Numeric representation of the month (1-12)
        $currentYear = date("Y"); // Full numeric representation of the year

        // Fetch the current budget for the user for the current month and year
        $budgetSql = "SELECT Budget FROM budget WHERE User_Id = ? AND Month = ? AND Year = ?";
        $budgetStmt = $conn->prepare($budgetSql);
        $budgetStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
        $budgetStmt->execute();
        $budgetStmt->bind_result($currentBudget);
        $budgetStmt->fetch();
        $budgetStmt->close();

        // Check if the user has a budget for the current month and year
        if ($currentBudget !== null) {
            // Deduct the expense from the current budget
            $newBudget = $currentBudget - $amount;

            // Update the budget in the database
            $updateBudgetSql = "UPDATE budget SET Budget = ? WHERE User_Id = ? AND Month = ? AND Year = ?";
            $updateBudgetStmt = $conn->prepare($updateBudgetSql);
            $updateBudgetStmt->bind_param("diis", $newBudget, $userId, $currentMonth, $currentYear);

            if ($updateBudgetStmt->execute()) {
                $_SESSION['message'] = 'Expense added successfully!';
            } else {
                $_SESSION['error'] = 'Error updating budget: ' . $updateBudgetStmt->error;
            }

            $updateBudgetStmt->close();
        } else {
            $_SESSION['error'] = 'No budget found for this user for the current month and year.';
        }

        header("Location: ./expense-history.php"); // Redirect after processing
        exit();
    } else {
        $_SESSION['error'] = 'Error: ' . $insertStmt->error;
    }

    $insertStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Expense</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Konkhmer+Sleokchher&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/expense-add.css"> <!-- Link to the external CSS file -->
</head>
<body>
    <div class="background-content">
        <?php include __DIR__ . '/home-page.php'; ?>
    </div>
    <div class="modal-backdrop"></div>
    <div class="modal">
        <div class="back-button">
            <a href="./expense-history.php"><i class="fas fa-arrow-left"></i></a>
        </div>
        <h1>DAILY EXPENSE</h1>
        <form id="expense-form" method="POST" action="">
            <input type="number" id="amount" name="amount" placeholder="Amount" required>
            <input type="text" id="description" name="description" placeholder="Description">
            <input type="hidden" id="category" name="category" required> <!-- Hidden input for selected category -->
            <div class="category-buttons">
                <button type="button" class="category-button" onclick="setCategory('FOOD')"> <i class="fas fa-utensils"></i> FOOD</button>
                <button type="button" class="category-button" onclick="setCategory('MATERIAL')"> <i class="fas fa-box"></i> MATERIAL</button>
                <button type="button" class="category-button" onclick="setCategory('ENTERTAINMENT')"> <i class="fas fa-film"></i> ENTERTAINMENT</button>
                <button type="button" class="category-button" onclick="setCategory('MISCELLANEOUS')"> <i class="fas fa-ellipsis-h"></i> MISCELLANEOUS</button>
                <button type="button" class="category-button" onclick="setCategory('TRANSPORTATION')"> <i class="fas fa-bus"></i> TRANSPORTATION</button>
            </div>
            <button type="submit" class="add-expense-button" id="add-expense-button">ADD EXPENSE</button>
        </form>
    </div>

    <script>
        // JavaScript to handle button click and set category
        const categoryButtons = document.querySelectorAll('.category-button');
        let selectedCategory = null;

        function setCategory(category) {
            // Set the selected category in the hidden input
            document.getElementById('category').value = category;

            // Remove active class from all buttons
            categoryButtons.forEach(button => button.classList.remove('active'));

            // Add active class to the clicked button
            const button = Array.from(categoryButtons).find(btn => btn.textContent.trim().startsWith(category));
            if (button) {
                button.classList.add('active');
            }
        }

        // Handle form submission to ensure a category is selected
        document.getElementById('expense-form').addEventListener('submit', function(event) {
            if (!document.getElementById('category').value) {
                event.preventDefault(); // Prevent form submission
            }
        });
    </script>
</body>
</html>