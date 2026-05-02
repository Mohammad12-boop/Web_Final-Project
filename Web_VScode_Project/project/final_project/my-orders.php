<?php
require_once "header.php";
require_once "db.php.inc";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';
$valid_statuses = ['All', 'Pending', 'In Progress', 'Delivered', 'Completed', 'Cancelled'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'All';
}

$sql = "SELECT o.*, u.first_name, u.last_name, u.profile_photo 
        FROM orders o ";

if ($role == 'Client') {
    $sql .= "JOIN users u ON o.freelancer_id = u.user_id WHERE o.client_id = :user_id ";
} else {
    $sql .= "JOIN users u ON o.client_id = u.user_id WHERE o.freelancer_id = :user_id ";
}

if ($status_filter != 'All') {
    $sql .= "AND o.status = :status ";
}

$sql .= "ORDER BY o.order_date DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id);
    if ($status_filter != 'All') {
        $stmt->bindValue(':status', $status_filter);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='error-msg'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    $orders = [];
}

function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'Pending':
            return 'badge-pending';
        case 'In Progress':
            return 'badge-progress';
        case 'Delivered':
            return 'badge-delivered';
        case 'Completed':
            return 'badge-completed';
        case 'Revision Requested':
            return 'badge-revision';
        case 'Cancelled':
            return 'badge-cancelled';
        default:
            return '';
    }
}
?>

<link rel="stylesheet" href="styles.css" />
<div class="page-layout">
    <?php include("nav.php"); ?>
    <main class="main-content">
        <div class="my-services">
            <h1 class="container-h1">My Orders</h1>

            <!-- Filter Form -->
            <div class="order-filter-container">
                <form action="my-orders.php" method="get" class="order-filter-form">
                    <select name="status" class="form-control order-filter-select">
                        <?php foreach ($valid_statuses as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo ($status_filter == $s) ? 'selected' : ''; ?>>
                                <?php echo $s; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" value="Apply Filter" class="order-filter-btn">
                </form>
            </div>

            <?php if (empty($orders)): ?>
                <p class="text-center">No orders found.</p>
            <?php else: ?>
                <table class="my-services-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Service</th>
                            <th>
                                <?php echo ($role == 'Client') ? 'Freelancer' : 'Client'; ?>
                            </th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Expected Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="primary-link">
                                        #
                                        <?php echo htmlspecialchars($order['order_id']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($order['service_title']); ?>
                                </td>
                                <td>
                                    <div class="table-user-info">
                                        <?php
                                        $photo = !empty($order['profile_photo']) ? $order['profile_photo'] : 'https://via.placeholder.com/40';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Profile"
                                            class="table-user-photo">
                                        <span>
                                            <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>$
                                    <?php echo number_format($order['price'], 2); ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['expected_delivery'])); ?></td>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="action-btn">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once "footer.php"; ?>