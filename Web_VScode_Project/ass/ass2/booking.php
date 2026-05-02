<?php

    require_once("db_config.php");

    $errorMsg = "";
    $trip = null;

    $trip_id = 0;

     if (!isset($_GET['id'])) {
        $errorMsg = "Invalid or missing trip ID !";
    }else {

        $trip_id = (int) $_GET['id'];
        if($trip_id <= 0) {

            $errorMsg = "Invalid or missing trip ID !";
        }else {

             try {

                // Retrieve trip details from the database using trip_id in the URL.
                // If trip_id is missing/invalid, show an appropriate error message.
                $sql = "SELECT trip_id, trip_name, destination, duration_days, price, start_date, end_date, available_seats
                        FROM trips WHERE trip_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt -> bindValue(1, $trip_id);
                $stmt -> execute();
                $trip = $stmt->fetch();

                if (empty($trip)){

                    $errorMsg = "Trip not found !";
                }

            }catch (PDOException $e) {

                $errorMsg = "Unable to load booking page at the moment. 
                            Please try again later!";
            }
        }
    }

    $duration = "";
    if($errorMsg == "") {

        $days = $trip["duration_days"];
        $nights = max(0, $days - 1);
        if ($nights == 0) {

            $duration = $days . " day";
        }else {

            $duration = $days . " days / " . $nights . " nights";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Rihla Travel - Booking</title>
</head>
<body>
    <header>
        <h1>Al-Rihla Travel - Booking</h1>
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
        
        <?php if ($errorMsg != ""): ?>
            <section>
                <h2>Booking</h2>
                <p> <?php echo htmlspecialchars($errorMsg); ?> </p>
                <p> <a href="./tour-packages.php">Back to Tour Packages</a> </p>
            </section>
        <?php else: ?>
            <section>
                <h2>Trip Information</h2>
                <ul>
                    <li> <strong>Trip:</strong> <?php echo htmlspecialchars($trip["trip_name"]); ?> — <?php echo htmlspecialchars($trip["destination"]); ?> </li>
                    <li> <strong>Dates:</strong> <time><?php echo htmlspecialchars($trip["start_date"]); ?></time> to <time><?php echo htmlspecialchars($trip["end_date"]); ?></time> </li>
                    <li> <strong>Duration:</strong> <?php echo htmlspecialchars($duration); ?> </li>
                    <li> <strong>Price per person (USD):</strong> <?php echo "$" .  htmlspecialchars($trip["price"]); ?> </li>
                    <li> <strong>Available seats:</strong> <?php echo htmlspecialchars($trip["available_seats"]); ?> </li>
                </ul>
            </section>

            <hr>

            <section>
                <h2>Booking Form</h2>

                <form action="process-booking.php" method="post">

                    <!-- Hidden fields store trip data so process-booking.php can receive it with POST
                    (trip_id, trip_name, trip_price) as required in the assignment -->
                    
                    <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($trip["trip_id"]); ?>">
                    <input type="hidden" name="trip_name" value="<?php echo htmlspecialchars($trip["trip_name"]); ?>">
                    <input type="hidden" name="trip_price" value="<?php echo htmlspecialchars($trip["price"]); ?>">

                    <fieldset>
                        <legend>Customer Information</legend>

                        <p>
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </p>

                        <p>
                            <label for="email">Email Address:</label>
                            <input type="email" id="email" name="email" 
                            placeholder="Ex. example@mail.com"
                            required>
                        </p>

                        <p>
                            <label for="phone">Phone Number:</label>
                            <input type="tel" id="phone" name="phone" required>
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend>Booking Details</legend>

                        <p>
                            <label for="num_travelers">Number of Travelers:</label>
                            <input type="number" id="num_travelers" name="num_travelers" min="1" required>
                        </p>

                        <p>
                            <label for="special_requests">Special Requests:</label> <br>
                            <textarea id="special_requests" name="special_requests" rows="4" cols="40"></textarea>
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend>Payment Information</legend>

                        <p>
                            <label>Payment Method:</label><br>
                            <input type="radio" id="visa" name="payment_method" value="Visa Card" required>
                            <label for="visa">Visa Card</label><br>

                            <input type="radio" id="master" name="payment_method" value="Master Card" required>
                            <label for="master">Master Card</label>
                        </p>

                        <p>
                            <label for="card_number">Card Number:</label>
                            <input type="text" id="card_number" name="card_number" 
                                    pattern="[0-9]{16}"
                                    minlength="16" maxlength="16" 
                                    placeholder="Ex. 1234567890123456 (16 digits)"
                                    required>
                        </p>

                        <p>
                            <label for="cardholder_name">Cardholder Name:</label>
                            <input type="text" id="cardholder_name" name="cardholder_name" 
                                    pattern="[A-Za-z ]+" 
                                    placeholder="Ex. Name on Card"
                                    required>
                        </p>

                        <p>
                            <label for="expiry_date">Expiry Date:</label>
                            <input type="text" id="expiry_date" name="expiry_date" 
                                    placeholder="MM/YYYY" 
                                    pattern="(0[1-9]|1[0-2])\/[0-9]{4}"
                                    required>
                        </p>
                    </fieldset>

                    <p>
                        <input type="submit" name="submit" value="Submit Booking">
                    </p>
                </form>
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