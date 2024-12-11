<?php
// Include database connection
include_once(__DIR__ . '/../config.php'); // Ensure this file connects to your database

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the user ID from the session
    $userId = $_SESSION["user"]["ID"] ?? null;
    
    // Get form data
    $lent_date = $_POST['initial_date']; // This will be retained for new records
    $due_date = $_POST['due_date'];
    $recipient_name = $_POST['recipient_name'];
    $amount = $_POST['amount'];
    
    // Initialize Payment_Amount to 0
    $payment_amount = 0;

    // Check for existing record with non-zero balance
    $stmt = $conn->prepare("SELECT ID, Balance FROM lent WHERE Name = ? AND User_Id = ? AND Balance > 0");
    $stmt->bind_param("si", $recipient_name, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Existing record found, update it
        $row = $result->fetch_assoc();
        $existing_id = $row['ID'];
        $existing_balance = $row['Balance'];

        // Calculate new balance
        $new_balance = $existing_balance + $amount;

        // Prepare and bind the SQL UPDATE statement
        $update_stmt = $conn->prepare("UPDATE lent SET Balance = ?, Due_Date = ? WHERE ID = ?");
        $update_stmt->bind_param("ssi", $new_balance, $due_date, $existing_id);

        // Execute the update statement
        if ($update_stmt->execute()) {
            $_SESSION['message'] = 'Lent record updated successfully!';
        } else {
            $_SESSION['error'] = 'Error updating record: ' . $update_stmt->error;
        }

        // Close the update statement
        $update_stmt->close();
    } else {
        // No existing record found, insert a new one
        $balance = $amount; // Set balance to the amount lent

        // Prepare and bind the SQL INSERT statement
        $insert_stmt = $conn->prepare("INSERT INTO lent (User_Id, Lent_Date, Amount, Name, Payment_Amount, Balance, Due_Date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("issssss", $userId, $lent_date, $amount, $recipient_name, $payment_amount, $balance, $due_date);

        // Execute the insert statement
        if ($insert_stmt->execute()) {
            $_SESSION['message'] = 'Lent record added successfully!';
        } else {
            $_SESSION['error'] = 'Error: ' . $insert_stmt->error;
        }

        // Close the insert statement
        $insert_stmt->close();
    }

    // Close the initial statement
    $stmt->close();

    // Redirect to another page after successful submission
    header("Location: ./lent-page.php"); // Change this to your desired redirect page
    exit(); // Make sure to exit after the redirect
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Konkhmer+Sleokchher&family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/new-transact.css" />
</head>
<body>
    <div class="background-content">
        <?php include __DIR__ . '/lent-page.php'; ?>
    </div>
    <div class="modal-header">
        <div class="rectangle">NEW TRANSACTION</div>
    </div>
    <div class="modal-backdrop" id="modalBackdrop"></div> 
    <div class="modal">
        <form id="transaction-form" action="" method="POST" onsubmit="return handleFormSubmit();">
            <div class="header"></div>
            <div class="date-group">
                <div class="input-group">
                    <label for="initial-date">Initial Date</label>
                    <input type="date" id="initial-date" name="initial_date" placeholder="DD/MM/YYYY" required>
                </div>
                <div class="input-group">
                    <label for="due-date">Due Date</label>
                    <input type="date" id="due-date" name="due_date" placeholder="DD/MM/YYYY" required>
                </div>
            </div>
            <div class="input-group">
                <label for="recipient-name">Recipient’s Name</label>
                <input type="text" id="recipient-name" name="recipient_name" placeholder="Enter recipient’s name" required>
            </div>
            <div class="input-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" placeholder="Enter Amount" readonly required>
            </div>
            <div class="keypad">
                <button type="button" class="number">1</button>
                <button type="button" class="number">2</button>
                <button type="button" class="number">3</button>
                <button type="button" class="number">4</button>
                <button type="button" class="number">5</button>
                <button type="button" class="number">6</button>
                <button type="button" class="number">7</button>
                <button type="button" class="number">8</button>
                <button type="button" class="number">9</button>
                <button type="button" class="clear">CLEAR</button>
                <button type="button" class="number">0</button>
                <button type="submit" class="done">DONE</button>
            </div>
        </form>
    </div>

    <script>
        // Wait for the DOM to fully load
        document.addEventListener('DOMContentLoaded', function() {
            // Get the modal backdrop element
            const modalBackdrop = document.getElementById('modalBackdrop');

            // Add click event listener
            modalBackdrop.addEventListener('click', function() {
                // Redirect to lent-page.php
                window.location.href = './lent-page.php';
            });
        });

        // Get the amount input field
        const amountInput = document.getElementById('amount');

        // Get all number buttons
        const numberButtons = document.querySelectorAll('.keypad .number');

        // Add click event listeners to each number button
        numberButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Append the button's text (the number) to the amount input value
                amountInput.value += button.textContent;
            });
        });

        // Clear button functionality
        document.querySelector('.clear').addEventListener('click', () => {
            amountInput.value = ''; // Clear the input field
        });

        function handleFormSubmit() {
        // Optionally, you can perform validation here
        // If everything is okay, redirect after submission
        setTimeout(() => {
            window.location.href = "./lent-page.php"; // Change this to your desired redirect page
        }, 1000); // Delay for 1 second to let the form submission process
        return true; // Allow the form to submit
    }
    </script>
</body>
</html>