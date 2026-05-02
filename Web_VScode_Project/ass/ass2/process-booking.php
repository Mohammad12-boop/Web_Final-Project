<?php

    require_once("db_config.php");

    $errorMsg = "";
    $success = false;
      
    // Helper validation functions (server-side validation):
    // We validate inputs on the server even if HTML pattern/required exists,
    // because client-side validation can be bypassed.
    function isAllDigits($value) {

        $value = trim($value);
        if ($value == "") {

            return false;
        }

        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {

            $char = $value[$i];
            if ($char < '0' || $char > '9') {

                return false;
            } 
        }

        return true;
    }

    function isAlphaSpace($value) {

        $value = trim($value);
        if ($value == "") {
            return false;
        } 

        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {

            $char = $value[$i];

            $isLetter = false;
            if(($char >= 'A' && $char <= 'Z') 
            || ($char >= 'a' && $char <= 'z')) {

                $isLetter = true;
            }
            
            $isSpace = false;
            if($char == ' ') {

                $isSpace = true;
            }
            if (!($isLetter || $isSpace)) {

                return false;
            } 
        }

        return true;
    }

    function isValidExpiry($value) {

        $value = trim($value);
        if (strlen($value) != 7) {
            return false;
        } 

        if ($value[2] != '/') {
            return false;
        }

        $mm = substr($value, 0, 2);
        $yy = substr($value, 3, 4);

        if (!isAllDigits($mm) || !isAllDigits($yy)) {
            return false;
        }

        $month = (int)$mm;
        if($month >= 1 && $month <= 12) {
            return true;
        }

        return false;
    }

    function isValidEmail($email) {

        $email = trim($email);

        if($email == ""){
            return false;
        }

        $at  = strpos($email, '@');
        $dot = strrpos($email, '.');

        if (($at != false) && ($at > 0) 
            && ($dot != false) && ($dot > $at + 1) 
            && ($dot < strlen($email) - 1)) {

            return true;
        } 
        
        return false;
    }

    $trip_id = 0;
    $trip_name = "";
    $trip_price = 0.0;

    $customer_name = "";
    $email = "";
    $phone = "";

    $num_travelers = 0;
    $special_requests = "";

    $payment_method = "";
    $card_number_full = "";
    $cardholder_name = "";
    $expiry_date = "";

    // Receive booking form data sent by POST (including hidden fields)
    if(!isset($_POST["submit"])) {

        $errorMsg = "Invalid request. Please submit the booking form.";
    }else {

        if(isset($_POST["trip_id"])) {

            $trip_id = (int)$_POST["trip_id"];
        }
        if(isset($_POST["trip_name"])) {

            $trip_name = trim($_POST["trip_name"]);
        }
        if(isset($_POST["trip_price"])) {

            $trip_price = (float)$_POST["trip_price"];
        } 
        if(isset($_POST["full_name"])) {

            $customer_name = trim($_POST["full_name"]);
        } 
        if(isset($_POST["email"])) {

            $email = trim($_POST["email"]);
        } 
        if(isset($_POST["phone"])) {

            $phone = trim($_POST["phone"]);
        } 
        if(isset($_POST["num_travelers"])) {

            $num_travelers = (int)$_POST["num_travelers"];
        } 
        if(isset($_POST["special_requests"])) {

            $special_requests = trim($_POST["special_requests"]);
        } 
        if(isset($_POST["payment_method"])) {

            $payment_method = trim($_POST["payment_method"]);
        } 
        if(isset($_POST["card_number"])) {

            $card_number_full = trim($_POST["card_number"]);
        } 
        if(isset($_POST["cardholder_name"])) {

            $cardholder_name = trim($_POST["cardholder_name"]);
        } 
        if(isset($_POST["expiry_date"])) {

            $expiry_date = trim($_POST["expiry_date"]);
        } 

        $booking_id = null;
        $destination = "";
        $total_amount = 0.00;

        // Validate required fields and formats before any database operation
        // - email format
        // - positive number of travelers
        // - card fields format (16 digits, name letters, expiry MM/YYYY)
        if ($trip_id <= 0 || $trip_name == "" || $trip_price <= 0) {
            $errorMsg = "Invalid trip data. Please go back and try again.";
        } elseif ($customer_name == "" || $email == "" || $phone == "") {
            $errorMsg = "Please fill all required customer fields.";
        } elseif (!isValidEmail($email)) {
            $errorMsg = "Invalid email format.";
        } elseif ($num_travelers <= 0) {
            $errorMsg = "Number of travelers must be a positive number.";
        } elseif ($payment_method != "Visa Card" && $payment_method != "Master Card") {
            $errorMsg = "Invalid payment method. Please Choose a method.";
        } elseif (!isAllDigits($card_number_full) || strlen($card_number_full) != 16) {
            $errorMsg = "Card number must be exactly 16 digits.";
        } elseif (!isAlphaSpace($cardholder_name)) {
            $errorMsg = "Cardholder name must contain alphabetical characters only.";
        } elseif (!isValidExpiry($expiry_date)) {
            $errorMsg = "Expiry date must be in MM/YYYY format.";
        } else {

            $card_last4 = substr($card_number_full, -4);

            $total_amount = $trip_price * $num_travelers;

            try {

                // Use a transaction to ensure the booking insert and seat update happen together.
                // If any step fails, roll back to keep database consistent.
                $pdo->beginTransaction();

                // Check current available seats from the database to prevent overbooking
                // (Do not rely only on values sent from the form)
                $sqlTrip = "SELECT trip_name, destination, available_seats, price
                        FROM trips
                        WHERE trip_id = ?";
                
                $stmtTrip = $pdo->prepare($sqlTrip);
                $stmtTrip->bindValue(1, $trip_id);
                $stmtTrip->execute();

                $tripRow = $stmtTrip->fetch();

                if (empty($tripRow)) {
                    throw new Exception("Trip not found.");
                }

                $trip_name = $tripRow["trip_name"];
                $trip_price = $tripRow["price"];
                $destination = $tripRow["destination"];
                $available_seats = (int)$tripRow["available_seats"];

                if ($num_travelers > $available_seats) {
                    throw new Exception("Not enough available seats for this trip.");
                }

                // Insert the booking into bookings table using a prepared statement
                // Store only the last 4 digits of the card number (display only) as required
                $sqlInsert = "INSERT INTO bookings
                (trip_id, customer_name, customer_email, customer_phone, num_travelers, total_amount, payment_method, card_number, special_requests)
                VALUES
                (:trip_id, :customer_name, :customer_email, :customer_phone, :num_travelers, :total_amount, :payment_method, :card_number, :special_requests)";

                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->bindValue(":trip_id", $trip_id);
                $stmtInsert->bindValue(":customer_name", $customer_name);
                $stmtInsert->bindValue(":customer_email", $email);
                $stmtInsert->bindValue(":customer_phone", $phone);
                $stmtInsert->bindValue(":num_travelers", $num_travelers);
                $stmtInsert->bindValue(":total_amount", $total_amount);
                $stmtInsert->bindValue(":payment_method", $payment_method);
                $stmtInsert->bindValue(":card_number", $card_last4);
                $stmtInsert->bindValue(":special_requests", $special_requests);
                $stmtInsert->execute();

                $booking_id = $pdo->lastInsertId();

                // Reduce available seats by the number of travelers booked
                // Extra condition (available_seats >= :n) prevents negative seats in concurrent bookings
                $sqlUpdate = "UPDATE trips
                          SET available_seats = available_seats - :n
                          WHERE trip_id = :trip_id AND available_seats >= :n";

                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->bindValue(":n", $num_travelers);
                $stmtUpdate->bindValue(":trip_id", $trip_id);
                $stmtUpdate->execute();

                if ($stmtUpdate->rowCount() != 1) {
                    throw new Exception("Booking failed due to seat availability update.");
                }

                $pdo->commit();
                $success = true;

            }catch (Exception $ex) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMsg = "Unable to process booking at the moment. Please try again later.";
            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMsg = "Unable to process booking at the moment. Please try again later.";
            }
        }
    }
?>

 <!-- Display confirmation details required by the assignment:
         Booking ID, customer name, trip name & destination, travelers, total amount -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Rihla Travel - Booking Confirmation</title>
</head>
<body>
    <header>
        <h1>Al-Rihla Travel - Booking Confirmation</h1>
        <figure>
            <img src="./images/Logo_Al-Rihla.jpg"
                alt="Al-Rihla Travel logo showing Dome of the Rock and Palestinian colors"
                width="220" height="220">
            <figcaption>Where Every Journey Tells a Palestinian Story</figcaption>
        </figure>

        <nav>
            <ul>
                <li><a href="./index.php">Home</a></li>
                <li><a href="../ass1/destinations.html">Destinations</a></li>
                <li><a href="./tour-packages.php">Tour Packages</a></li>
                <li><a href="./search-trips.php">Search Trips</a></li>
                <li><a href="../ass1/about.php">About Us</a></li>
                <li><a href="../ass1/gallery.php">Gallery</a></li>
                <li><a href="../ass1/faq.php">FAQ</a></li>
            </ul>
        </nav>
    </header>

    <hr>

    <main>
         <!-- Display confirmation details required by the assignment:
         Booking ID, customer name, trip name & destination, travelers, total amount -->
        <?php if ($success): ?>
            <section>
                <h2>Booking Confirmed</h2>
                <ul>
                    <li> <strong>Booking ID:</strong> <?php echo htmlspecialchars($booking_id); ?> </li>
                    <li> <strong>Customer Name:</strong> <?php echo htmlspecialchars($customer_name); ?> </li>
                    <li> <strong>Trip:</strong> <?php echo htmlspecialchars($trip_name); ?> — <?php echo htmlspecialchars($destination); ?> </li>
                    <li> <strong>Number of Travelers:</strong> <?php echo htmlspecialchars($num_travelers); ?> </li>
                    <li> <strong>Total Amount Paid (USD):</strong> <?php echo "$" .  htmlspecialchars($total_amount); ?> </li>
                </ul>

                <p>
                    <a href="./tour-packages.php">Back to Tour Packages</a>
                </p>
            </section>
        <?php else: ?>
            <section>
                <h2>Booking Failed</h2>
                <p><?php echo htmlspecialchars($errorMsg); ?></p>
                <p><a href="./tour-packages.php">Back to Tour Packages</a></p>
            </section>
        <?php endif; ?>
    </main>

    <hr>

   <footer>
        <p>
            &copy; 2025 Al-Rihla Travel - Ramallah, Palestine
            <br>
            Email: <a href="mailto:1220951@student.birzeit.edu">1220951@student.birzeit.edu</a>
            <br>
            Phone: +970-59-2456355
            <br>
            Links: 
            <a href="../ass1/about.html">About Us</a> |
            <a href="../ass1/gallery.html">Gallery</a> |
            <a href="../ass1/faq.html">FAQ</a>  
        </p>
    </footer>

</body>
</html>