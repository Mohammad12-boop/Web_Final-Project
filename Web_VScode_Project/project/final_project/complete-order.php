<?php
require_once "header.php";
require_once "db.php.inc";

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

$sql = "SELECT * FROM orders WHERE order_id = :order_id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':order_id', $order_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || $order['client_id'] != $user_id) {
    header("Location: my-orders.php");
    exit();
}

if ($order['status'] != 'Delivered') {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Order is not in Delivered status.</h2><p><a href='order-details.php?id=$order_id'>Back to Order</a></p></div></main>";
    require_once "footer.php";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_complete'])) {
    $up_sql = "UPDATE orders SET status = 'Completed', completion_date = NOW() WHERE order_id = :order_id";
    $up = $pdo->prepare($up_sql);
    $up->bindValue(':order_id', $order_id);

    if ($up->execute()) {
        header("Location: order-details.php?id=" . $order_id);
        exit();
    }
}
?>

<link rel="stylesheet" href="styles.css" />
<div class="page-layout">
    <?php include("nav.php"); ?>
    <main class="main-content">
        <div class="container">
            <h1 class="container-h1">Mark Order as Completed</h1>
            <p class="container-p">Are you sure you want to mark this order as completed? This action cannot be undone.
            </p>

            <div class="success-msg-box order-summary-box">
                <p><strong>Order:</strong> #
                    <?php echo htmlspecialchars($order['order_id']); ?>
                </p>
                <p><strong>Service:</strong>
                    <?php echo htmlspecialchars($order['service_title']); ?>
                </p>
            </div>

            <form action="complete-order.php" method="post">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <input type="hidden" name="confirm_complete" value="1">

                <div class="form-group text-center-mb-20">
                    <label class="checkbox-label">
                        <input type="checkbox" required> I confirm that I have reviewed the delivery and accept it.
                    </label>
                </div>

                <div class="form-row flex-center">
                    <input type="submit" value="Confirm Completion" class="btn-submit btn-success-wide">
                    <a href="order-details.php?id=<?php echo $order_id; ?>"
                        class="action-btn btn-cancel-line">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once "footer.php"; ?>