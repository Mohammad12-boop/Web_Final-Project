<?php

    require_once("db_config.php");

    $errorMsg = "";
    $trips = [];

    $search_on = false;

    $destination = "";
    $start_date  = "";
    $end_date    = "";
    $min_price   = "";
    $max_price   = "";
    $min_days    = "";

    if (isset($_GET["submit"])) {

        $search_on = true;

        if (isset($_GET["destination"])) {

            $destination = trim($_GET["destination"]);
        }
        if (isset($_GET["start_date"])) {

            $start_date = trim($_GET["start_date"]);
        }
        if (isset($_GET["end_date"])) {

            $end_date = trim($_GET["end_date"]);
        }
        if (isset($_GET["min_price"])) {

            $min_price = trim($_GET["min_price"]);
        }
        if (isset($_GET["max_price"])) {

            $max_price = trim($_GET["max_price"]);
        }
        if (isset($_GET["min_days"])) {

            $min_days = trim($_GET["min_days"]);
        }

        try {

            // Build one SQL query where each filter works independently:
            // If a filter is empty, its condition becomes TRUE and does not affect the result.
            // This allows combining filters (destination + date + price + duration) in one search.
            $sql = "SELECT trip_id, trip_name, destination, 
                        duration_days, price, start_date FROM trips
                    WHERE (:destination = '' OR destination LIKE :des_like)
                        AND (:start_date= '' OR start_date >= :start_date)
                        AND (:end_date = '' OR start_date <= :end_date)
                        AND (:min_price = '' OR price >= :min_price)
                        AND (:max_price = '' OR price <= :max_price)
                        AND (:min_days = '' OR duration_days >= :min_days)
                    ";
            
            // Bind user inputs to prepared statement parameters to prevent SQL injection
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":destination", $destination);
            $stmt->bindValue(":des_like", '%' . $destination . '%');
            $stmt->bindValue(":start_date", $start_date);
            $stmt->bindValue(":end_date", $end_date);
            $stmt->bindValue(":min_price", $min_price);
            $stmt->bindValue(":max_price", $max_price);
            $stmt->bindValue(":min_days", $min_days);
            $stmt->execute();

            while($row = $stmt->fetch()) {

                $trips[] = $row;
            }

        }catch (PDOException $e) {

            $errorMsg = "Unable to perform search at the moment. 
            Please try again later!";
        }

    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Rihla Travel - Search Trips</title>
</head>
<body>
    <header>
        <h1>Al-Rihla Travel - Search Trips</h1>
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
        <section>
            <h2>Search Trips</h2>

            <form action="search-trips.php" method="get">
                <fieldset>
                    <legend>Filter Criteria</legend>

                    <p>
                        <label for="destination">Destination:</label>
                        <input type="text" name="destination" id="destination" 
                        value="<?php echo htmlspecialchars($destination); ?>" />
                    </p>

                    <p>
                        <label for="start_date">Start Date:</label>
                        <input type="date" name="start_date" id="start_date" 
                        value="<?php echo htmlspecialchars($start_date); ?>" />

                        <label for="end_date">End Date:</label>
                        <input type="date" name="end_date" id="end_date" 
                        value="<?php echo htmlspecialchars($end_date); ?>" />
                    </p>

                    <p>
                        <label for="min_price">Min Price:</label>
                        <input type="number" name="min_price" id="min_price" min="0"
                        value="<?php echo htmlspecialchars($min_price); ?>" />

                        <label for="max_price">Max Price:</label>
                        <input type="number" name="max_price" id="max_price" min="0"
                        value="<?php echo htmlspecialchars($max_price); ?>" />
                    </p>

                    <p>
                        <label for="min_days">Min Duration (Days):</label>
                        <input type="number" name="min_days" id="min_days" min="1"
                        value="<?php echo htmlspecialchars($min_days); ?>" />
                    </p>

                    <input type="submit" name="submit" value="Search" />
                </fieldset>
            </form>
        </section>

        <hr>

        <section>
            <h2>Search Results</h2>

            <?php if ($search_on && $errorMsg != "") :?>

                <p> <?php echo htmlspecialchars($errorMsg); ?> </p>
            <?php elseif ($search_on && empty($trips)) :?>

                <p>No Trips match the search criteria !</p>
            <?php elseif ($search_on) :?>

                <table border="7">
                    <thead>
                        <tr>
                            <th scope="col">Trip Name</th>
                            <th scope="col">Destination</th>
                            <th scope="col">Duration</th>
                            <th scope="col">Price (USD)</th>
                            <th scope="col">Start Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($trips as $trip) :?>
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
                                    <!-- Make trip name clickable and open details in a new tab (target="_blank") as required -->
                                    <a href="./trip-details.php?id=<?php echo $trip["trip_id"]; ?>" target="_blank">
                                        <?php echo htmlspecialchars($trip["trip_name"]); ?>
                                    </a>
                                </td>
                                <td> <?php echo htmlspecialchars($trip["destination"]); ?> </td>
                                <td> <?php echo $duration; ?> </td>
                                <td> <?php echo "$" . htmlspecialchars($trip["price"]); ?> </td>
                                <td> <time> <?php echo htmlspecialchars($trip["start_date"]); ?> </time> </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <p>Use the form above to search for trips.</p>
            <?php endif; ?>
        </section>
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