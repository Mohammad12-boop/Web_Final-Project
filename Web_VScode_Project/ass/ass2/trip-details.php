<?php

    require_once("db_config.php");

    $trip = null;
    $errorMsg = "";
    $trip_id = null;

    if (!isset($_GET['id'])) {
        $errorMsg = "Invalid or missing trip ID !";
    }else {

        // Read trip ID from URL and validate it before querying the database
        $trip_id = (int) $_GET['id'];
        if($trip_id <= 0) {

            $errorMsg = "Invalid or missing trip ID !";
        }else {
            try {

                // Get the selected trip using a prepared statement to prevent SQL injection
                $sql = "SELECT * FROM trips WHERE trip_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt -> bindValue(1, $trip_id);
                $stmt -> execute();
                $trip = $stmt->fetch();

                if (empty($trip)){

                    $errorMsg = "Trip not found !";
                }

            }catch (PDOException $e) {

                $errorMsg = "Unable to load trip details at the moment. 
                Please try again later !";
            }
        } 
    }

    function splitPipe(string $text) {

        if (!isset($text) || empty($text)) {
            return [];
        }

        $parts = explode("|", $text);
        return $parts;
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Rihla Travel - Trip Details</title>
</head>
<body>
    <header>
        <h1>Al-Rihla Travel - Trip Details</h1>
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
                <h2>Trip Details</h2>
                <p> <?php echo htmlspecialchars($errorMsg); ?> </p>
                <p> <a href="./tour-packages.php">Back to Tour Packages</a> </p>
            </section>
        <?php else: ?>

            <?php 
                $days = $trip["duration_days"];
                $nights = max(0, $days - 1);
                $duration = "";
                if ($nights == 0) {

                    $duration = $days . " day";
                }else {

                    $duration = $days . " days / " . $nights . " nights";
                }

                // Split the pipe-separated text from the database into list items
                // Example stored format: "Day 1: ... | Day 2: ... | Day 3: ..."
                $itineraries = splitPipe($trip["itinerary"]);
                $inclusions = splitPipe($trip["inclusions"]);
                $exclusions = splitPipe($trip["exclusions"]);
                $requirements = splitPipe($trip["requirements"]);
            ?>

            <section>

                <h2> <?php echo htmlspecialchars($trip["trip_name"]); ?> </h2>

                <ul>
                    <li> <strong>Destination:</strong> <?php echo htmlspecialchars($trip["destination"]); ?> </li>
                    <li> <strong>Duration:</strong> <?php echo $duration; ?> </li>
                    <li> <strong>Price (USD):</strong> <?php echo "$" . htmlspecialchars($trip["price"]); ?> </li>
                    <li> <strong>Dates:</strong> <time> <?php echo htmlspecialchars($trip["start_date"]); ?> </time>
                        to <time> <?php echo htmlspecialchars($trip["end_date"]); ?> </time>
                    </li>
                    <li> <strong>Available Seats:</strong> <?php echo htmlspecialchars($trip["available_seats"]); ?> </li>
                </ul>    

                <figure>
                    <img src="<?php echo htmlspecialchars($trip["image_url"]); ?>" 
                    alt="<?php echo htmlspecialchars($trip["trip_name"]); ?>" 
                    width="350">
                    <figcaption> 
                        <?php echo htmlspecialchars($trip['trip_name']); ?>
                        - 
                        <?php echo htmlspecialchars($trip['destination']); ?> 
                    </figcaption>
                </figure>

                <p> <?php echo htmlspecialchars($trip["description"]); ?> </p>
            </section>

            <hr>

            <section>
                <h2>Package Information</h2>

                <!-- Display detailed package info using the same structure required by the assignment:
                <details> + <summary> + <ul><li>...</li></ul> -->

                <details>
                    <summary>Day-by-Day Itinerary</summary>
                    <?php if (!empty($itineraries)) : ?>
                        <ul>
                            <?php foreach($itineraries as $itinerary) : ?>
                                <li> <?php echo htmlspecialchars($itinerary); ?> </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <ul>
                            <li>No itinerary information available.</li>
                        </ul>
                    <?php endif; ?>
                </details>

                <details>
                    <summary>Included Services</summary>
                    <?php if (!empty($inclusions)) : ?>
                        <ul>
                            <?php foreach($inclusions as $inclusion) : ?>
                                <li> <?php echo htmlspecialchars($inclusion); ?> </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <ul>
                            <li>No inclusion information available.</li>
                        </ul>
                    <?php endif; ?>
                </details>

                <details>
                    <summary>Not Included</summary>
                    <?php if (!empty($exclusions)) : ?>
                        <ul>
                            <?php foreach($exclusions as $exclusion) : ?>
                                <li> <?php echo htmlspecialchars($exclusion); ?> </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <ul>
                            <li>No (Not inclusion) information available.</li>
                        </ul>
                    <?php endif; ?>
                </details>

                <details>
                    <summary>Requirements</summary>
                    <?php if (!empty($requirements)) : ?>
                        <ul>
                            <?php foreach($requirements as $requirement) : ?>
                                <li> <?php echo htmlspecialchars($requirement); ?> </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <ul>
                            <li>No Requirement information available.</li>
                        </ul>
                    <?php endif; ?>
                </details>

                <hr>

                <p>
                    <a href="./booking.php?id=<?php echo $trip["trip_id"] ?>"
                            target="_blank">  
                        <h3>Book This Trip</h3>
                    </a>
                </p>

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