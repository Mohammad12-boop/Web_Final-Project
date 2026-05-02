-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 08:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travel_company`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `num_travelers` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `card_number` varchar(20) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `special_requests` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `trip_id`, `customer_name`, `customer_email`, `customer_phone`, `num_travelers`, `total_amount`, `payment_method`, `card_number`, `booking_date`, `special_requests`) VALUES
(1, 4, 'Mohammed', 'mohakinggam987@gmail.com', '0592456355', 5, 3000.00, 'Visa Card', '3456', '2025-12-15 18:39:49', ''),
(2, 5, 'Mohammed', 'mohakinggam987@gmail.com', '0592456355', 5, 2400.00, 'Visa Card', '3456', '2025-12-15 18:41:42', 'Thanks');

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `trip_id` int(11) NOT NULL,
  `trip_name` varchar(200) NOT NULL,
  `destination` varchar(200) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `available_seats` int(11) NOT NULL,
  `description` text NOT NULL,
  `itinerary` text NOT NULL,
  `inclusions` text NOT NULL,
  `exclusions` text NOT NULL,
  `requirements` text NOT NULL,
  `image_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`trip_id`, `trip_name`, `destination`, `duration_days`, `price`, `start_date`, `end_date`, `available_seats`, `description`, `itinerary`, `inclusions`, `exclusions`, `requirements`, `image_url`) VALUES
(1, 'Jerusalem Spiritual & Heritage Weekend', 'Jerusalem', 2, 350.00, '2026-01-16', '2026-01-17', 25, 'A short spiritual and heritage getaway in the Old City and surrounding landmarks.', 'Day 1: Old City walk (gates, markets) and heritage sites|Day 2: Religious landmarks visit and cultural museum stop', 'Hotel 1 night|Breakfast|Local guide|Transportation within city', 'Lunch meals|Personal expenses|Tips', 'Valid ID/passport|Comfortable walking shoes|Respectful dress code', 'images/jerusalem_weekend.jpg'),
(2, 'Bethlehem & Jericho Culture Escape', 'Bethlehem, Jericho', 3, 520.00, '2026-02-10', '2026-02-12', 30, 'A cultural trip combining Bethlehem’s heritage and Jericho’s nature and history.', 'Day 1: Bethlehem old town and heritage walk|Day 2: Jericho city tour and nearby sites|Day 3: Local markets and departure', 'Hotel 2 nights|Breakfast|Guide|Transportation|Entrance fees (as applicable)', 'International flights|Optional activities|Personal expenses', 'Valid passport (6 months)|Moderate fitness|Comfortable shoes', 'images/bethlehem_jericho.jpg'),
(3, 'Ramallah City & Cuisine Tour', 'Ramallah', 2, 300.00, '2026-03-06', '2026-03-07', 40, 'City vibes, local cuisine, and cultural highlights in Ramallah.', 'Day 1: City center, museums, and cafe culture|Day 2: Food tasting tour and artisan shops', 'Hotel 1 night|Breakfast|Food tasting (fixed items)|Local guide', 'Extra meals|Personal shopping|Tips', 'Valid ID|Comfortable walking shoes', 'images/ramallah_cuisine.jpg'),
(4, 'Hebron Heritage & Handicrafts', 'Hebron', 3, 600.00, '2026-04-14', '2026-04-16', 17, 'Explore historic Hebron and its famous craftsmanship and markets.', 'Day 1: Old city and heritage introduction|Day 2: Handicrafts workshops and market tour|Day 3: Cultural sites and departure', 'Hotel 2 nights|Breakfast|Guide|Workshop entry|Transportation', 'Lunch meals|Personal purchases|Tips', 'Valid ID/passport|Respect local customs|Comfortable shoes', 'images/hebron_handicrafts.jpg'),
(5, 'Nablus History & Sweets Journey', 'Nablus', 3, 480.00, '2026-05-05', '2026-05-07', 3, 'Historical sites and the famous Nablus sweets experience.', 'Day 1: Old city and historical walking tour|Day 2: Cultural heritage sites and soap factory visit|Day 3: Local cuisine and departure', 'Hotel 2 nights|Breakfast|Guide|Transportation', 'Extra meals|Personal expenses|Tips', 'Valid ID|Moderate fitness', 'images/nablus_sweets.jpg'),
(6, 'Gaza Coast (Virtual Heritage Focus)', 'Gaza', 4, 900.00, '2026-06-09', '2026-06-12', 18, 'A heritage-focused itinerary emphasizing culture and history (program details subject to local conditions).', 'Day 1: Cultural introduction and heritage overview|Day 2: Historical narrative sites|Day 3: Community stories and museums|Day 4: Wrap-up and departure', 'Hotel 3 nights|Breakfast|Guide|Transportation (as possible)', 'International flights|Personal expenses|Optional activities', 'Valid passport/ID|Follow safety guidance|Flexible schedule', 'images/gaza_heritage.jpg'),
(7, 'West Bank Highlights Explorer', 'Jerusalem, Bethlehem, Ramallah', 7, 1450.00, '2026-07-03', '2026-07-09', 20, 'A full-week highlights tour across major West Bank destinations.', 'Day 1: Arrival and Jerusalem overview|Day 2: Old City deep tour|Day 3: Bethlehem heritage day|Day 4: Ramallah culture and markets|Day 5: Local crafts and community visits|Day 6: Optional nature stop and free exploration|Day 7: Departure', '6 nights hotel|Daily breakfast|Professional guide|Air-conditioned transport|Entrance fees (as applicable)', 'International flights|Lunch meals|Personal expenses|Tips', 'Valid passport (6 months)|Travel insurance|Comfortable shoes|Moderate fitness', 'images/westbank_highlights.jpg'),
(8, 'Palestine Nature & Desert Adventure', 'Jericho, Dead Sea', 5, 1100.00, '2026-08-18', '2026-08-22', 16, 'A nature-focused journey featuring desert scenery and relaxation spots.', 'Day 1: Jericho welcome and local sites|Day 2: Desert trails and viewpoint stops|Day 3: Dead Sea relaxation day|Day 4: Cultural visits and local cuisine|Day 5: Departure', '4 nights hotel|Breakfast|Guide|Transportation', 'International flights|Optional activities|Personal expenses', 'Valid passport/ID|Sun protection|Comfortable walking shoes', 'images/nature_desert.jpg'),
(9, 'Palestinian Cities Deep Dive', 'Hebron, Nablus, Bethlehem', 10, 1750.00, '2026-09-01', '2026-09-10', 14, 'A longer itinerary exploring multiple cities with detailed cultural immersion.', 'Day 1: Arrival and orientation|Day 2: Bethlehem heritage tour|Day 3: Community visits|Day 4: Hebron old city|Day 5: Crafts and markets|Day 6: Transfer and Nablus heritage|Day 7: Soap factory and old town|Day 8: Free exploration and optional stops|Day 9: Wrap-up|Day 10: Departure', '9 nights hotel|Daily breakfast|Guide|Transport between cities', 'International flights|Lunch and dinner|Personal expenses|Tips', 'Valid passport (6 months)|Travel insurance|Moderate fitness|Comfortable shoes', 'images/cities_deep_dive.jpg'),
(10, 'Complete Palestine Experience', 'Jerusalem, Bethlehem, Hebron, Nablus, Ramallah', 14, 2000.00, '2026-11-05', '2026-11-18', 12, 'A comprehensive 14-day experience covering major destinations and cultural themes.', 'Day 1: Arrival and Jerusalem intro|Day 2: Old City landmarks|Day 3: Museums and heritage walk|Day 4: Bethlehem and local culture|Day 5: Community engagement|Day 6: Hebron heritage sites|Day 7: Crafts and markets|Day 8: Transfer and Nablus tour|Day 9: Historical sites|Day 10: Ramallah city life|Day 11: Optional nature/culture|Day 12: Free day and shopping|Day 13: Closing cultural event|Day 14: Departure', '13 nights hotel|Daily breakfast and selected dinners|Professional guide|Transport|Entrance fees (as applicable)', 'International flights|Some meals|Personal expenses|Optional activities|Tips', 'Valid passport (6 months)|Travel insurance|Comfortable shoes|Respect local customs|Flexible schedule', 'images/complete_experience.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `trip_id` (`trip_id`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`trip_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `trip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`trip_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
