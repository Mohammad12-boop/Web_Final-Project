<?php
// This file allows clients to cancel a pending order

require_once "header.php";
// Database connection
require_once "db.php.inc";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) && !isset($_POST['order_id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = isset($_GET['id']) ? $_GET['id'] : $_POST['order_id'];
$user_id = $_SESSION['user_id'];

// Fetch order details from database
$sql = "SELECT * FROM orders WHERE order_id = :order_id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':order_id', $order_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Order not found</h2></div></main>";
    require_once "footer.php";
    exit();
}

// Only the client who placed the order can cancel it
if ($order['client_id'] != $user_id) {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Access Denied</h2></div></main>";
    require_once "footer.php";
    exit();
}

if ($order['status'] != 'Pending') {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Order cannot be cancelled. Status is " . htmlspecialchars($order['status']) . "</h2><p class='container-p'><a href='order-details.php?id=$order_id'>Back to Order</a></p></div></main>";
    require_once "footer.php";
    exit();
}

$error = '';

// Handle form submission for cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $confirm = isset($_POST['confirm_cancel']);
    $reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : '';

    if (!$confirm) {
        $error = "You must confirm the cancellation.";
    } else {
        $update_sql = "UPDATE orders SET status = 'Cancelled', completion_date = NOW(), deliverable_notes = :reason WHERE order_id = :order_id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindValue(':reason', "Cancellation Reason: " . $reason);
        $update_stmt->bindValue(':order_id', $order_id);

        if ($update_stmt->execute()) {
            header("Location: my-orders.php");
            exit();
        } else {
            $error = "Database error. Please try again.";
        }
    }
}
?>

<link rel="stylesheet" href="styles.css" />
<div class="page-layout">
    <?php include("nav.php"); ?>
    <main class="main-content">
        <div class="container text-left">
            <h1 class="container-h1">Cancel Order #
                <?php echo htmlspecialchars($order['order_id']); ?>
            </h1>
            <p class="container-p">Are you sure you want to cancel this order? This action cannot be undone.</p>

            <div class="order-details-card">
                <h3 class="order-section-title">Order Summary</h3>
                <p><strong>Service:</strong>
                    <?php echo htmlspecialchars($order['service_title']); ?>
                </p>
                <p><strong>Price:</strong> $
                    <?php echo number_format($order['price'], 2); ?>
                </p>
            </div>

            <?php if ($error): ?>
                <p class="error-msg">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <form action="cancel-order.php" method="post" class="auth-form form-full-width-no-margin">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">

                <div class="form-group">
                    <label for="reason">Cancellation Reason (Optional)</label>
                    <textarea name="cancellation_reason" id="reason" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-label checkbox-flex-label">
                        <input type="checkbox" name="confirm_cancel" required>
                        I confirm that I want to cancel this order.
                    </label>
                </div>

                <div class="form-row">
                    <input type="submit" value="Submit Request" class="btn-submit btn-danger-custom">
                    <a href="order-details.php?id=<?php echo $order_id; ?>" class="action-btn btn-cancel-link">Go
                        Back</a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once "footer.php"; ?>