<?php

    try {

        require_once("db_config.php");

        $trips = [];
        $errorMsg = "";

        // Retrieve all trips from the database using a prepared statement
        $sql = "SELECT trip_id, trip_name, destination, 
        duration_days, price, start_date, available_seats FROM trips";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        // Fetch rows one by one and store them in an array for display
        while ($row = $stmt->fetch()) {

            $trips[] = $row;
        }

    }catch (PDOException $e) {
        // Show a user-friendly message instead of DB error details
        $errorMsg = "Unable to load trips at the moment. 
        Please try again later !!";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Rihla Travel - Tour Packages</title>
</head>
<body>
    
    <header>
        <h1>Al-Rihla Travel - Tour Packages</h1>
        <figure>
            <img src="./images/Logo_Al-Rihla.jpg" alt="Al-Rihla Travel logo showing Dome of the Rock and Palestinian colors" width="220" height="220" >
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
        <section>
            <h2>Our Tour Packages</h2>
            <p>
                Al-Rihla Travel offers carefully designed tour packages that combine culture, history, 
                and everyday Palestinian life. Choose the package that best matches your interests 
                and explore Palestine with local expert guides.
            </p>
        </section>

        <hr>

        <section>
            <h2>Overview of Available Packages</h2>

            <?php if ($errorMsg != ""): ?>
                <p> <?php echo htmlspecialchars($errorMsg); ?> </p>
            <?php else: ?>

                <table border = 7>
                    <caption>Available Tour Packages</caption>
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Destinations</th>
                            <th scope="col">Duration (days/nights)</th>
                            <th scope="col">Price (USD)</th>
                            <th scope="col">Start Date</th>
                            <th scope="col">Available Seats</th>
                        </tr>
                    </thead>

                    <tbody>
                        
                        <?php if (empty($trips)): ?>
                            <tr>
                                <td colspan="6">
                                    <p>No trips available at the moment.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trips as $trip): ?>

                                <?php
                                    $days = $trip["duration_days"];
                                    $nights = max(0, $days - 1);
                                    $duration = "";
                                    if ($nights == 0) {

                                        $duration = $days . " day";
                                    }else {

                                        $duration = $days . " days / " . $nights . " nights";
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <a href="./trip-details.php?id=<?php echo $trip["trip_id"]; ?>">
                                            <?php echo htmlspecialchars($trip["trip_name"]); ?>
                                        </a>
                                    </td>
                                    <td> <?php echo htmlspecialchars($trip["destination"]); ?> </td>
                                    <td> <?php echo $duration; ?> </td>
                                    <td> <?php echo "$" . htmlspecialchars($trip["price"]); ?> </td>
                                    <td> <time> <?php echo htmlspecialchars($trip["start_date"]); ?> </time> </td>
                                    <td> <?php echo htmlspecialchars($trip["available_seats"]); ?> </td>
                                </tr>

                            <?php endforeach; ?>
                        <?php endif; ?>

                    </tbody>
                    
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                All prices include accommodation and guided tours. Seasonal discounts may apply.
                                For custom or private packages, please contact Al-Rihla Travel at
                                <a href="mailto:1220951@student.birzeit.edu">1220951@student.birzeit.edu</a>
                                or call +970-59-2456355 for a tailored offer.
                            </td>
                        </tr>
                    </tfoot>

                </table>
            <?php endif; ?>
        </section>
    
        <hr>

        <section>
            <h2>Package Comparison</h2>

            <table>
                <caption>Comparison of Key Features Across Tour Packages</caption>

                <thead>
                <tr>
                    <th scope="col">Feature</th>
                    <th scope="col">Holy Land Heritage Tour</th>
                    <th scope="col">Palestinian Culinary Journey</th>
                    <th scope="col">Historical Cities Explorer</th>
                    <th scope="col">Complete Palestine Experience</th>
                    <th scope="col">Weekend Getaway</th>
                    <th scope="col">Nature and Adventure Tour</th>
                    <th scope="col">Cultural Immersion Program</th>
                </tr>
                </thead>

                <tbody>
                <tr>
                    <th scope="row">Daily breakfast included</th>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                </tr>

                <tr>
                    <th scope="row">Some dinners included</th>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>No</td>
                    <td>Yes</td>
                    <td>No</td>
                    <td>Yes</td>
                    <td>Yes</td>
                </tr>

                <tr>
                    <th scope="row">Professional local guide</th>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                </tr>

                <tr>
                    <th scope="row">Private transportation included</th>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                </tr>

                <tr>
                    <th scope="row">Accommodation level</th>
                    <td>3–4 star hotels</td>
                    <td>Guesthouses</td>
                    <td>Hotels/guesthouses</td>
                    <td>3–4 star hotels</td>
                    <td>Local hotel</td>
                    <td>Lodges/guesthouses</td>
                    <td>Homestay</td>
                </tr>

                <tr>
                    <th scope="row">Suitable for families</th>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>Yes</td>
                    <td>No</td>
                    <td>Yes (older children)</td>
                </tr>

                <tr>
                    <th scope="row">Physical activity level</th>
                    <td>Moderate</td>
                    <td>Easy</td>
                    <td>Moderate</td>
                    <td>Active</td>
                    <td>Easy</td>
                    <td>Moderate</td>
                    <td>Easy–Moderate</td>
                </tr>
                </tbody>
            </table>
        </section>

        <hr>

        <aside>
            <h2>Special Offers</h2>
            <ul>
                <li>Seasonal discounts available during spring and autumn tours.</li>
                <li>Group booking benefits for groups of 8 or more travelers.</li>
                <li>Early bird offers for bookings made at least 3 months in advance.</li>
                <li>Student and senior discounts on selected packages.</li>
            </ul>
        </aside>
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