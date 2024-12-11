<?php
include_once(__DIR__ . '/../config.php'); // Ensure this file connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the user ID from the session
    $userId = $_SESSION["user"]["ID"] ?? null;

    if ($userId === null) {
        $_SESSION['error'] = 'User  not logged in!';
        header("Location: ./borrow-page.php");
        exit();
    }

    // Get form data
    $lender_name = $_POST['lender_name'];
    $due_date = $_POST['due_date']; // Ensure this matches your form input's name
    $payment_amount = $_POST['payment'] ? floatval($_POST['payment']) : 0; // Convert to float or set to 0

    // Validate lender name and check for non-zero balance
    $stmt = $conn->prepare("SELECT * FROM borrowed WHERE Name = ? AND User_Id = ? AND Balance > 0");
    $stmt->bind_param("si", $lender_name, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Lender exists with a non-zero balance, proceed with the update
        $row = $result->fetch_assoc();
        $current_payment_amount = $row['Payment_Amount']; // Fetch the current payment amount
        $balance = $row['Balance']; // Fetch the current balance

        // Calculate the new total payment amount
        $new_payment_amount = $current_payment_amount + $payment_amount;

        // Calculate the new balance
        $new_balance = $balance - $payment_amount; // Deduct the payment amount from the balance

        // Check if the new balance is not negative
        if ($new_balance < 0) {
            $_SESSION['error'] = 'Payment exceeds the balance!';
        } else {
            // Update the due date, new payment amount, and new balance
            $update_stmt = $conn->prepare("UPDATE borrowed SET Due_Date = ?, Payment_Amount = ?, Balance = ? WHERE ID = ?");
            $update_stmt->bind_param("sdii", $due_date, $new_payment_amount, $new_balance, $row['ID']); // Adjusted types

            if ($update_stmt->execute()) {
                $_SESSION['message'] = 'Transaction updated successfully!';
            } else {
                $_SESSION['error'] = 'Error updating transaction: ' . $update_stmt->error;
            }
            $update_stmt->close();
        }
    } else {
        $_SESSION['error'] = 'Lender not found!';
    }

    // Close the statement
    $stmt->close();
    header("Location: ./borrow-page.php"); // Redirect to the borrow page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPDATE TRANSACTION MODAL</title>
    <link rel="stylesheet" href="./css/upd-modal.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background-content">
        <?php include __DIR__ . '/borrow-page.php'; ?>
    </div>
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="container">
        <div class="modal-header"> <!-- Modal Header -->
            <div class="rectangle">UPDATE TRANSACTION</div>
        </div>
        <!-- Modal Content -->
        <div class="modal">
            <form id="transaction-form" action="" method="POST">
                <!-- Lender's Name -->
                <div class="input-group">
                    <label for="lender-name">Lender’s Name</label>
                    <input type="text" id="lender-name" name="lender_name" placeholder="Enter Lender's Name" autocomplete="off" required>
                    <div id="suggestions"></div> <!-- Suggestions for lender names -->
                </div>
                <!-- Remaining Balance and Date -->
                <div class="form-row">
                    <!-- Remaining Balance -->
                    <div class="input-group">
                        <label for="remaining-balance">Remaining Balance</label>
                        <div class="remaining-balance-group">
                            <span class="currency">₱</span>
                            <input type="text" id="remaining-balance" name="remaining_balance" value="0" readonly>
                        </div>
                    </div>
                    <!-- Date -->
                    <div class="date-group">
                        <div class="input-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="due_date" required>
                        </div>
                    </div>
                </div>

                <!-- Payment -->
                <div class="input-group">
                    <label for="payment">Payment</label>
                    <input type="text" id="payment" name="payment" placeholder="Enter amount" readonly required>
                </div>

                <!-- Keypad -->
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
    </div> <!--container end-->

    <script>
        // Get the payment input field
        const paymentInput = document.getElementById('payment');
    
        // Get all number buttons
        const numberButtons = document.querySelectorAll('.keypad .number');

        // Add click event listeners to each number button
        numberButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Append the button's text (the number) to the payment input value
                paymentInput.value += button.textContent;
            });
        });

        // Clear button functionality
        document.querySelector('.clear').addEventListener('click', () => {
            paymentInput.value = ''; // Clear the input field
        });


        // Wait for the DOM to fully load
        document.addEventListener('DOMContentLoaded', function() {
            // Get the modal backdrop element
            const modalBackdrop = document.getElementById('modalBackdrop');

            // Add click event listener
            modalBackdrop.addEventListener('click', function() {
                // Redirect to borrow-page.php
                window.location.href = './borrow-page.php';
            });
        });


    // Get references to the input fields
    const lenderInput = document.getElementById('lender-name');
    const remainingBalanceInput = document.getElementById('remaining-balance');
    const suggestionsContainer = document.getElementById('suggestions'); // Make sure to have a container for suggestions

    // Function to fetch suggestions based on user input
    lenderInput.addEventListener('input', function() {
        const query = this.value;

        if (query.length > 0) {
            fetch(`./get-lenders.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(suggestions => {
                    clearSuggestions(); // Clear previous suggestions
                    suggestions.forEach(suggestion => {
                        const item = document.createElement('div');
                        item.classList.add('suggestion-item');
                        item.textContent = suggestion.name;

                        // Add click event to the suggestion item
                        item.addEventListener('click', () => handleSuggestionClick(suggestion));

                        suggestionsContainer.appendChild(item);
                    });
                })
                .catch(error => console.error('Error fetching suggestions:', error));
        } else {
            clearSuggestions(); // Clear suggestions if input is empty
        }
    });

    // Function to handle when a suggestion is clicked
    function handleSuggestionClick(suggestion) {
        lenderInput.value = suggestion.name; // Set the input value to the selected name
        remainingBalanceInput.value = formatBalance(suggestion.balance); // Update the balance input

        clearSuggestions(); // Clear suggestions
    }

    // Add keydown event listener to the lender input
    lenderInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            const firstSuggestion = suggestionsContainer.querySelector('.suggestion-item');
            if (firstSuggestion) {
                // Trigger the click event on the first suggestion
                firstSuggestion.click();
                event.preventDefault(); // Prevent the default form submission
            }
        }
    });

    // Function to format the balance as a string with commas
    function formatBalance(balance) {
        return balance.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","); // Format balance with commas
    }

    // Function to clear suggestions
    function clearSuggestions() {
        suggestionsContainer.innerHTML = ''; // Clear the suggestions container
    }

    // Optional: Close suggestions when clicking outside
    document.addEventListener('click', function(event) {
        if (!suggestionsContainer.contains(event.target) && event.target !== lenderInput) {
            clearSuggestions(); // Clear suggestions if clicked outside
        }
    });

    // Initialize remaining balance input to 0 by default
    remainingBalanceInput.value = "0";
    </script>
</body>
</html>