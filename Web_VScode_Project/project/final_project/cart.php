<?php
// This file manages the shopping cart and shows recently viewed services

require_once "Service.php";
// Start session and connect to database
session_start();
require_once "db.php.inc";

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (!($item instanceof Service)) {
            // Corrupted session detected
            $_SESSION['cart'] = [];
            $_SESSION['error_msg'] = "Cart session corrupted. Please re-add services.";
            // Redirect to browse or clear and stay
            header("Location: browse-services.php");
            exit();
        }
    }
}

$success_msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
$error_msg = isset($_SESSION['error_msg']) ? $_SESSION['error_msg'] : '';

unset($_SESSION['success_msg']);
unset($_SESSION['error_msg']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if (($action == 'add' || $action == 'order_now')) {
        $service_id = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;

        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php?error=Please login to add services to cart");
            exit();
        }
        $user_id = $_SESSION['user_id'];

        $sql = "SELECT s.*, u.first_name, u.last_name, u.user_id as f_id 
                FROM services s 
                JOIN users u ON s.freelancer_id = u.user_id 
                WHERE s.service_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $service_id);
        $stmt->execute();
        $service_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service_data) {
            $_SESSION['error_msg'] = "Service not found.";
            header("Location: service-detail.php?id=$service_id");
            exit();
        }
        if ($service_data['status'] != 'Active') {
            $_SESSION['error_msg'] = "Cannot add inactive service to cart.";
            header("Location: service-detail.php?id=$service_id");
            exit();
        }
        if ($_SESSION['role'] == 'Freelancer' && $service_data['freelancer_id'] == $user_id) {
            $_SESSION['error_msg'] = "You cannot add your own service to cart.";
            header("Location: service-detail.php?id=$service_id");
            exit();
        }
        foreach ($_SESSION['cart'] as $item) {
            if ($item->getServiceId() == $service_id) {
                $_SESSION['error_msg'] = "Service already in cart";
                header("Location: service-detail.php?id=$service_id");
                exit();
            }
        }

        $f_name = $service_data['first_name'] . " " . $service_data['last_name'];
        $timestamp = date("Y-m-d H:i:s");
        $img = $service_data['image_1'];
        $new_service = new Service(
            $service_data['service_id'],
            $service_data['title'],
            $service_data['category'],
            $service_data['subcategory'],
            $service_data['price'],
            $service_data['delivery_time'],
            $service_data['revisions_included'],
            $service_data['freelancer_id'],
            $f_name,
            $img,
            $timestamp
        );

        $_SESSION['cart'][] = $new_service;

        if ($action == 'order_now') {
            header("Location: checkout.php");
            exit();
        } else {
            $_SESSION['success_msg'] = "Service added to cart successfully!";
            header("Location: service-detail.php?id=$service_id");
            exit();
        }
    } elseif ($action == 'remove') {
        $remove_id = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;

        $temp_cart = [];
        foreach ($_SESSION['cart'] as $item) {
            if ($item->getServiceId() != $remove_id) {
                $temp_cart[] = $item;
            }
        }
        $_SESSION['cart'] = $temp_cart;
        $_SESSION['success_msg'] = "Service removed from cart.";
        header("Location: cart.php");
        exit();
    } elseif ($action == 'checkout') {
        if (empty($_SESSION['cart'])) {
            $_SESSION['error_msg'] = "Your cart is empty.";
            header("Location: browse-services.php");
            exit();
        }

        $removed_titles = [];
        $valid_cart = [];

        foreach ($_SESSION['cart'] as $item) {
            $check_id = $item->getServiceId();
            $sql_check = "SELECT status FROM services WHERE service_id = :id";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindValue(':id', $check_id);
            $stmt_check->execute();
            $status_row = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($status_row && $status_row['status'] == 'Active') {
                $valid_cart[] = $item;
            } else {
                $removed_titles[] = $item->getTitle();
            }
        }

        $_SESSION['cart'] = $valid_cart;

        if (!empty($removed_titles)) {
            $titles_str = implode(", ", $removed_titles);
            $_SESSION['error_msg'] = "Service '{$titles_str}' is no longer available and has been removed";
            header("Location: cart.php");
            exit();
        }

        header("Location: checkout.php");
        exit();
    }
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    // "Redirect to login page with error"
    header("Location: login.php?error=Please login as client to view cart");
    exit();
}

$cart_items = $_SESSION['cart'];

$subtotal = 0;
foreach ($cart_items as $item) {
    if ($item instanceof Service) {
        $subtotal += $item->getPrice();
    }
}
$service_fee = $subtotal * 0.05;
$total = $subtotal + $service_fee;

$recent_services = [];
if (empty($cart_items)) {
    if (isset($_COOKIE['recent_viewed'])) {
        $recent_ids = explode(",", $_COOKIE['recent_viewed']);
        if (!empty($recent_ids)) {

            foreach ($recent_ids as $r_id) {
                if ($r_id != '') {
                    $sql_r = "SELECT s.service_id, s.title, s.price, s.image_1, u.first_name, u.last_name 
                              FROM services s 
                              JOIN users u ON s.freelancer_id = u.user_id 
                              WHERE s.service_id = :id AND s.status = 'Active'";
                    $stmt_r = $pdo->prepare($sql_r);
                    $stmt_r->bindValue(':id', $r_id);
                    $stmt_r->execute();
                    $r_row = $stmt_r->fetch(PDO::FETCH_ASSOC);
                    if ($r_row) {
                        $recent_services[] = $r_row;
                        if (count($recent_services) >= 4)
                            break;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">
        <?php include("nav.php"); ?>

        <main class="main-content">

            <h1 class="cart-title">Shopping Cart</h1>

            <?php if ($success_msg): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>

                <div class="empty-cart-container">
                    <img src="https://via.placeholder.com/100?text=Bag" alt="Empty Cart" class="empty-cart-img">
                    <div class="empty-cart-msg">Your cart is empty</div>
                    <a href="browse-services.php" class="btn-submit">Browse Services</a>
                </div>

                <?php if (!empty($recent_services)): ?>
                    <div class="recently-viewed-section">
                        <h3 class="recently-viewed-title">Recently Viewed Services</h3>
                        <div class="services-grid">
                            <?php foreach ($recent_services as $recent): ?>
                                <div class="service-card">
                                    <a href="service-detail.php?id=<?php echo $recent['service_id']; ?>" class="card-link">
                                        <div class="card-image">
                                            <?php $img = $recent['image_1'] ? $recent['image_1'] : 'https://via.placeholder.com/300x200'; ?>
                                            <img src="<?php echo htmlspecialchars($img); ?>"
                                                alt="<?php echo htmlspecialchars($recent['title']); ?>">
                                        </div>
                                        <div class="card-content">
                                            <div class="card-title"><?php echo htmlspecialchars($recent['title']); ?></div>
                                            <div class="card-freelancer">
                                                By
                                                <?php echo htmlspecialchars($recent['first_name'] . ' ' . $recent['last_name']); ?>
                                            </div>
                                            <div class="card-price">
                                                Starting at $<?php echo number_format($recent['price'], 2); ?>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>

                <!-- CART WITH ITEMS -->
                <div class="cart-layout">

                    <!-- Main Area: Table -->
                    <div class="cart-main">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th colspan="2">Service</th>
                                    <th>Ref</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td class="col-thumb">
                                            <?php
                                            $thumb = $item->getMainImagePath() ? $item->getMainImagePath() : 'https://via.placeholder.com/100x75';
                                            ?>
                                            <a href="service-detail.php?id=<?php echo $item->getServiceId(); ?>">
                                                <img src="<?php echo htmlspecialchars($thumb); ?>" class="cart-thumb"
                                                    alt="Service">
                                            </a>
                                        </td>
                                        <td>
                                            <a href="service-detail.php?id=<?php echo $item->getServiceId(); ?>"
                                                class="cart-item-title">
                                                <?php echo htmlspecialchars($item->getTitle()); ?>
                                            </a>
                                            <div class="cart-item-meta">
                                                Category: <?php echo htmlspecialchars($item->getCategory()); ?><br>
                                                Freelancer: <?php echo htmlspecialchars($item->getFreelancerName()); ?><br>
                                                Delivery: <?php echo $item->getFormattedDelivery(); ?> | Revisions:
                                                <?php echo $item->getRevisionsIncluded(); ?>
                                            </div>
                                        </td>
                                        <td>#<?php echo $item->getServiceId(); ?></td>
                                        <td>
                                            <strong><?php echo $item->getFormattedPrice(); ?></strong>
                                        </td>
                                        <td>
                                            <form action="cart.php" method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="service_id"
                                                    value="<?php echo $item->getServiceId(); ?>">
                                                <button type="submit" class="cart-remove-btn" title="Remove">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Sidebar: Summary -->
                    <div class="cart-sidebar">
                        <div class="summary-title">ORDER SUMMARY</div>

                        <div class="summary-row">
                            <span>Services Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Service Fee (5%):</span>
                            <span>$<?php echo number_format($service_fee, 2); ?></span>
                        </div>

                        <div class="summary-row summary-total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>

                        <form action="cart.php" method="POST" class="mt-30">
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="btn-submit w-100">Proceed to Checkout</button>
                        </form>
                    </div>

                </div>

            <?php endif; ?>

        </main>
    </div>

    <?php include("footer.php"); ?>
</body>

</html>