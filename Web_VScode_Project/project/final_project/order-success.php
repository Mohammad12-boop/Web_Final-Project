<?php
// Strict Requirement: Service class loaded BEFORE session_start
require_once "Service.php";
session_start();
require_once "db.php.inc";

// Precondition: Order success
if (!isset($_SESSION['last_orders']) || empty($_SESSION['last_orders'])) {
    header("Location: browse-services.php");
    exit();
}

$order_ids = $_SESSION['last_orders'];
// Optional: Clear the session variable if you only want it shown once. 
// But "Back" button usage might desire persistence until nav away. 
// We will simply display them.

// Fetch Order Details
// Fetch Order Details
// We need to fetch details for all these IDs
$params = [];
$clauses = [];
foreach ($order_ids as $index => $id) {
    $key = ":oid_" . $index;
    $clauses[] = $key;
    $params[$key] = $id;
}
$in_str = implode(',', $clauses);

// Fetch with JOIN to get service/freelancer info
$sql = "SELECT o.*, s.title as service_title, u.first_name, u.last_name, u.user_id as f_id 
        FROM orders o
        JOIN services s ON o.service_id = s.service_id
        JOIN users u ON o.freelancer_id = u.user_id
        WHERE o.order_id IN ($in_str)";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Placed Successfully</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">
        <?php include("nav.php"); ?>
        <main class="main-content">

            <div class="success-container">
                <div class="success-icon">✓</div>
                <h1 class="success-title">Orders Placed Successfully!</h1>
                <?php
                $total_sum = 0;
                foreach ($orders as $o)
                    $total_sum += $o['total_amount'];
                ?>
                <p class="success-subtitle">You have placed <strong><?php echo count($orders); ?></strong> orders</p>
                <div class="success-total-banner">
                    Total Amount: <span>$<?php echo number_format($total_sum, 2); ?></span>
                </div>

                <div class="order-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-id">Order #
                                    <?php echo $order['order_id']; ?>
                                </span>
                                <span class="status-badge">
                                    <?php echo $order['status']; ?>
                                </span>
                            </div>
                            <div class="order-body">
                                <h3 class="order-body-title">
                                    <?php echo htmlspecialchars($order['service_title']); ?>
                                </h3>
                                <p class="order-meta-text">
                                    Freelancer:
                                    <a href="profile.php?id=<?php echo $order['f_id']; ?>">
                                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                    </a>
                                </p>
                                <p class="order-meta-text">
                                    Expected Delivery: <strong>
                                        <?php echo date('M d, Y', strtotime($order['expected_delivery'])); ?>
                                    </strong>
                                </p>
                                <div class="order-card-footer">
                                    <span class="order-total-price">$
                                        <?php echo number_format($order['total_amount'], 2); ?>
                                    </span>
                                    <button class="btn-submit secondary-btn btn-small"
                                        onclick="window.location.href='order-details.php?id=<?php echo $order['order_id']; ?>'">View
                                        Order Details</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-actions">
                    <a href="my-orders.php" class="btn-submit">View All Orders</a>
                    <a href="browse-services.php" class="btn-submit secondary-btn">Browse More Services</a>
                </div>
            </div>

        </main>
    </div>

    <?php include("footer.php"); ?>
</body>

</html>