<?php
// Include database connection
include_once(__DIR__ . '/../config.php'); // Ensure this file connects to your database

// Fetch today's expenses
$userId = $_SESSION["user"]["ID"] ?? null; // Get the user ID from the session
$today = date("Y-m-d"); // Get today's date

// Prepare SQL SELECT statement for today's expenses, ordered by Date DESC
$expenseSql = "SELECT Description, Cost, Date, Category FROM expense WHERE User_Id = ? AND DATE(Date) = ? ORDER BY Date DESC";
$expenseStmt = $conn->prepare($expenseSql);
$expenseStmt->bind_param("is", $userId, $today);
$expenseStmt->execute();
$expenseResult = $expenseStmt->get_result();

// Define category to icon mapping
$categoryIcons = [
    'FOOD' => 'fas fa-utensils',
    'MATERIAL' => 'fas fa-book',
    'ENTERTAINMENT' => 'fas fa-film',
    'MISCELLANEOUS' => 'fas fa-ellipsis-h',
    'TRANSPORTATION' => 'fas fa-bus',
];

// Store expenses to display later
$expenses = [];
while ($row = $expenseResult->fetch_assoc()) {
    $category = $row['Category'];
    $iconClass = $categoryIcons[$category] ?? 'fas fa-question'; // Default icon if category not found
    $expenses[] = [
        'icon' => $iconClass,
        'title' => strtoupper($category),
        'cost' => number_format($row['Cost'], 2),
        'description' => htmlspecialchars($row['Description']),
        'category' => strtoupper($category) // Store the category in uppercase for consistency
    ];
}

// Close the statement
$expenseStmt->close();

// Check if there are no expenses
$noExpensesMessage = empty($expenses) ? "No items to display." : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Overview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="./css/expense-history.css"> 
    <style>
        .expenses-container {
            max-height: 300px; /* Set max height for scrolling */
            overflow-y: auto; /* Enable vertical scrolling */
            border: 1px solid #ccc; /* Optional: Add a border for better visibility */
            padding: 10px; /* Optional: Add some padding */
            margin-bottom: 20px; /* Space below the container */
        }
        .expense-item {
            display: flex;
            margin-bottom: 10px; /* Space between items */
        }
        .no-expenses {
            text-align: center; /* Center the message */
            color: #888; /* Light gray color for the message */
            font-size: 16px; /* Adjust font size */
        }
    </style>
</head>
<body>
    <div class="background-content">
        <?php include __DIR__ . '/home-page.php'; ?>
    </div>
    <div class="modal-backdrop"></div>
    <div class="modal">
        <div class="header">EXPENSE OVERVIEW</div>
        <div class="tabs">
            <div class="tab active" data-category="ALL">ALL</div>
            <div class="tab" data-category="FOOD">FOOD</div>
            <div class="tab" data-category="MATERIAL">MATERIAL</div>
            <div class="tab" data-category="ENTERTAINMENT">ENTERTAINMENT</div>
            <div class="tab" data-category="MISCELLANEOUS">MISCELLANEOUS</div>
            <div class="tab" data-category="TRANSPORTATION">TRANSPORTATION</div>
        </div>

        <div class="expenses-container" style="border: none;">
            <div class="no-expenses" style="display: none;"></div> <!-- Placeholder for no items message -->
            <?php foreach ($expenses as $expense): ?>
                <div class="expense-item" data-category="<?php echo $expense['title']; ?>">
                    <div class="icon-container">
                        <div class="circle">
                            <i class="<?php echo $expense['icon']; ?>"></i> <!-- Icon inside the circle -->
                        </div>
                    </div>
                    <div class="expense-details">
                        <div class="title"><?php echo $expense['title']; ?></div>
                        <div class="cost">₱ <?php echo $expense['cost']; ?></div>
                    </div>
                    <div class="expense-note"><?php echo $expense['description']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="buttons">
            <button class="button exit" onclick="location.href='./home-page.php'">EXIT</button>
            <button class="button add" onclick="location.href='./expense-add.php'">ADD</button>
        </div>
    </div>

    <script>
    // Get all tabs
    const tabs = document.querySelectorAll('.tab');
    const expenseItems = document.querySelectorAll('.expense-item');
    const noExpensesMessage = document.querySelector('.no-expenses');

    // Function to update the display of expense items
    function updateExpenseDisplay(selectedCategory) {
        let hasItems = false;

        expenseItems.forEach(item => {
            const itemCategory = item.getAttribute('data-category');
                        // Show all items if 'ALL' is selected, otherwise filter by category
                        if (selectedCategory === 'ALL' || itemCategory === selectedCategory) {
                item.style.display = 'flex'; // Show item
                hasItems = true; // Mark that we have at least one item
            } else {
                item.style.display = 'none'; // Hide item
            }
        });

        // Show or hide the no items message based on whether there are items
        if (hasItems) {
            noExpensesMessage.style.display = 'none'; // Hide message if there are items
        } else {
            noExpensesMessage.textContent = "No items to display."; // Set message text
            noExpensesMessage.style.display = 'block'; // Show message if no items
        }
    }

    // Add click event listener to each tab
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove 'active' class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add 'active' class to the clicked tab
            tab.classList.add('active');

            // Get the selected category
            const selectedCategory = tab.getAttribute('data-category');

            // Update the display based on the selected category
            updateExpenseDisplay(selectedCategory);
        });
    });

    // Initial setup to show all items
    updateExpenseDisplay('ALL');
    </script>
</body>
</html>