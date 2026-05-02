<?php
// This file displays the full details for a chosen service

require_once "Service.php";
// Start session for login check and Cart
session_start();
require_once "db.php.inc";

$service_id = isset($_GET['id']) ? $_GET['id'] : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Get the service and freelancer information from the database
$sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.profile_photo, u.registration_date, u.email 
        FROM services s 
        JOIN users u ON s.freelancer_id = u.user_id 
        WHERE s.service_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $service_id);
$stmt->execute();
$service = $stmt->fetch();

$service_exists = $service ? true : false;
$is_owner = ($user_id && $service && $user_id == $service['freelancer_id']);
$is_active = ($service && $service['status'] == 'Active');

$error_message = "";
if (!$service_exists) {
    $error_message = "Service not found.";
} elseif (!$is_active && !$is_owner) {
    $error_message = "Service no longer available.";
}

// Increment the view count if it's an active service
if ($service_exists && $is_active) {
    $sql_view = "UPDATE services SET view_count = view_count + 1 WHERE service_id = :id";
    $stmt_view = $pdo->prepare($sql_view);
    $stmt_view->bindValue(':id', $service_id);
    $stmt_view->execute();
}

// Handle cookie logic for recently viewed services
if ($service_exists && $is_active) {
    $cookie_name = "recent_viewed";
    $recent = [];
    if (isset($_COOKIE[$cookie_name])) {
        // Explode
        $recent = explode(",", $_COOKIE[$cookie_name]);
    }

    $temp_recent = [];
    foreach ($recent as $val) {
        if ($val != $service_id) {
            $temp_recent[] = $val;
        }
    }
    $recent = $temp_recent;

    $recent[] = $service_id;

    if (count($recent) > 4) {
        $count = count($recent);
        $temp_recent_limit = [];
        for ($i = 1; $i < $count; $i++) {
            $temp_recent_limit[] = $recent[$i];
        }
        $recent = $temp_recent_limit;
    }

    setcookie($cookie_name, implode(",", $recent), time() + (30 * 86400), "/");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo $service_exists ? htmlspecialchars($service['title']) : 'Service Not Found'; ?> - Freelance
        Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">
        <?php include("nav.php"); ?>

        <main class="main-content">

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="success-message">
                    <?php
                    echo htmlspecialchars($_SESSION['success_msg']);
                    unset($_SESSION['success_msg']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="error-message">
                    <?php
                    echo htmlspecialchars($_SESSION['error_msg']);
                    unset($_SESSION['error_msg']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message != ''): ?>
                <div class="detail-error">
                    <h2>Error</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <a href="browse-services.php" class="btn-submit">Back to Services</a>
                </div>
            <?php else: ?>

                <?php if (!$is_active && $is_owner): ?>
                    <div class="owner-warning">
                        This service is currently inactive and not visible to clients.
                    </div>
                <?php endif; ?>

                <div class="service-detail-container">

                    <div class="detail-left">

                        <div class="gallery-container">
                            <div class="gallery-stack">
                                <?php
                                $img1 = ($service['image_1'] != '') ? $service['image_1'] : "https://via.placeholder.com/600x400";
                                $img2 = ($service['image_2'] != '') ? $service['image_2'] : null;
                                $img3 = ($service['image_3'] != '') ? $service['image_3'] : null;
                                ?>

                                <img id="img1" src="<?php echo htmlspecialchars($img1); ?>"
                                    class="gallery-main-img default-img">
                                <?php if ($img2): ?>
                                    <img id="img2" src="<?php echo htmlspecialchars($img2); ?>"
                                        class="gallery-main-img overlay-img">
                                <?php endif; ?>
                                <?php if ($img3): ?>
                                    <img id="img3" src="<?php echo htmlspecialchars($img3); ?>"
                                        class="gallery-main-img overlay-img">
                                <?php endif; ?>
                            </div>

                            <div class="gallery-thumbs">
                                <a href="#img1" class="thumb-link">
                                    <img src="<?php echo htmlspecialchars($img1); ?>" class="thumb-img">
                                </a>
                                <?php if ($img2): ?>
                                    <a href="#img2" class="thumb-link">
                                        <img src="<?php echo htmlspecialchars($img2); ?>" class="thumb-img">
                                    </a>
                                <?php endif; ?>
                                <?php if ($img3): ?>
                                    <a href="#img3" class="thumb-link">
                                        <img src="<?php echo htmlspecialchars($img3); ?>" class="thumb-img">
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="detail-breadcrumbs">
                            <?php echo htmlspecialchars($service['category']); ?> &gt;
                            <?php echo htmlspecialchars($service['subcategory']); ?>
                        </div>

                        <h1 class="detail-title"><?php echo htmlspecialchars($service['title']); ?></h1>

                        <div class="freelancer-info-card">
                            <?php
                            $f_photo = ($service['profile_photo'] != '') ? $service['profile_photo'] : "https://via.placeholder.com/60";
                            ?>
                            <img src="<?php echo htmlspecialchars($f_photo); ?>" class="freelancer-detail-photo"
                                alt="Freelancer">
                            <div class="freelancer-text">
                                <div class="freelancer-name">
                                    <?php echo htmlspecialchars($service['first_name'] . ' ' . $service['last_name']); ?>
                                    <a href="mailto:<?php echo $service['email'] ?>"> <?php echo $service['email'] ?> </a>
                                </div>
                                <div class="freelancer-since">
                                    Member since <?php echo date("M Y", strtotime($service['registration_date'])); ?>
                                </div>
                                <a href="profile.php?id=<?php echo $service['freelancer_id']; ?>" class="profile-link">View
                                    Profile</a>
                            </div>
                        </div>

                        <div class="service-description">
                            <h3>About This Service</h3>
                            <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                        </div>

                    </div>

                    <div class="detail-right">
                        <div class="booking-card">
                            <div class="booking-header">
                                Starting at <span
                                    class="booking-price">$<?php echo number_format($service['price'], 2); ?></span>
                            </div>

                            <ul class="booking-features">
                                <li>
                                    <strong><?php echo $service['delivery_time']; ?> Days</strong> Delivery
                                </li>
                                <li>
                                    <strong><?php echo $service['revisions_included']; ?></strong> Revisions
                                </li>
                            </ul>

                            <div class="booking-actions">

                                <?php if (!$user_id): ?>
                                    <a href="login.php" class="booking-btn secondary-btn">Login to Order</a>

                                <?php elseif ($is_owner): ?>
                                    <a href="edit-service.php?id=<?php echo $service_id; ?>"
                                        class="booking-btn secondary-btn">Edit Service</a>

                                <?php elseif ($role == 'Client'): ?>
                                    <form action="cart.php" method="POST" class="mb-10">
                                        <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">

                                        <button type="submit" name="action" value="order_now"
                                            class="booking-btn primary-btn">Order Now</button>
                                        <button type="submit" name="action" value="add"
                                            class="booking-btn secondary-btn btn-mt-10">Add to Cart</button>
                                    </form>

                                <?php else: ?>
                                    <form action="cart.php" method="POST" class="mb-10">
                                        <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">

                                        <button type="submit" name="action" value="order_now"
                                            class="booking-btn primary-btn">Order Now</button>
                                        <button type="submit" name="action" value="add"
                                            class="booking-btn secondary-btn btn-mt-10">Add to Cart</button>
                                    </form>
                                <?php endif; ?>

                                <div class="contact-freelancer">
                                    <a href="#" class="contact-link">Contact Freelancer</a>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

        </main>
    </div>

    <?php include("footer.php"); ?>
</body>

</html>