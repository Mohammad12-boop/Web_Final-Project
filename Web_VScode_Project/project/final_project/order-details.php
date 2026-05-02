<?php
require_once "header.php";
require_once "db.php.inc";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$sql = "SELECT o.*, 
        s.title as service_title, s.category, s.image_1,
        uc.first_name as client_fname, uc.last_name as client_lname, uc.profile_photo as client_photo,
        uf.first_name as free_fname, uf.last_name as free_lname, uf.profile_photo as free_photo
        FROM orders o
        JOIN services s ON o.service_id = s.service_id
        JOIN users uc ON o.client_id = uc.user_id
        JOIN users uf ON o.freelancer_id = uf.user_id
        WHERE o.order_id = :order_id";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':order_id', $order_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Order not found</h2></div></main>";
    require_once "footer.php";
    exit();
}

if ($order['client_id'] != $user_id && $order['freelancer_id'] != $user_id) {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Access Denied</h2></div></main>";
    require_once "footer.php";
    exit();
}

$files_sql = "SELECT * FROM file_attachments WHERE order_id = :order_id ORDER BY upload_timestamp DESC";
$files_stmt = $pdo->prepare($files_sql);
$files_stmt->bindValue(':order_id', $order_id);
$files_stmt->execute();
$all_files = $files_stmt->fetchAll(PDO::FETCH_ASSOC);

$req_files = [];
$del_files = [];
$rev_files = [];

foreach ($all_files as $f) {
    if ($f['file_type'] == 'requirement')
        $req_files[] = $f;
    elseif ($f['file_type'] == 'deliverable')
        $del_files[] = $f;
    elseif ($f['file_type'] == 'revision')
        $rev_files[] = $f;
}

$rev_sql = "SELECT * FROM revision_requests WHERE order_id = :order_id ORDER BY request_date DESC";
$rev_stmt = $pdo->prepare($rev_sql);
$rev_stmt->bindValue(':order_id', $order_id);
$rev_stmt->execute();
$revisions = $rev_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_rev = count($revisions);
$accepted_rev = 0;
$rejected_rev = 0;
foreach ($revisions as $r) {
    if ($r['request_status'] == 'Accepted')
        $accepted_rev++;
    if ($r['request_status'] == 'Rejected')
        $rejected_rev++;
}
$remaining_rev = $order['revisions_included'] - $accepted_rev;
if ($order['revisions_included'] >= 999)
    $remaining_rev = "Unlimited";
if ($remaining_rev < 0)
    $remaining_rev = 0;

function getBadge($status)
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
        <div class="main-body order-main-body">
            <div class="order-header-info">
                <h1 class="order-id-title">Order #
                    <?php echo htmlspecialchars($order['order_id']); ?>
                </h1>
                <span class="status-badge order-status-badge-lg <?php echo getBadge($order['status']); ?>">
                    <?php echo htmlspecialchars($order['status']); ?>
                </span>
            </div>

            <div class="order-info-grid mb-10">
                <div>
                    <div class="order-details-card">
                        <h3 class="order-section-title">Order Information</h3>
                        <div class="order-info-grid">
                            <div class="info-item">
                                <label>Service</label>
                                <span>
                                    <?php echo htmlspecialchars($order['service_title']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Category</label>
                                <span>
                                    <?php echo htmlspecialchars($order['category']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Order Date</label>
                                <span>
                                    <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Expected Delivery</label>
                                <span>
                                    <?php echo date('M d, Y', strtotime($order['expected_delivery'])); ?>
                                </span>
                            </div>
                            <?php
                            $actual_delivery = null;
                            if (!empty($del_files) || !empty($rev_files)) {
                                $latest_timestamp = 0;
                                foreach (array_merge($del_files, $rev_files) as $f) {
                                    $ts = strtotime($f['upload_timestamp']);
                                    if ($ts > $latest_timestamp) {
                                        $latest_timestamp = $ts;
                                    }
                                }
                                if ($latest_timestamp > 0) {
                                    $actual_delivery = date('M d, Y', $latest_timestamp);
                                }
                            }
                            if ($actual_delivery):
                                ?>
                                <div class="info-item">
                                    <label>Actual Delivery</label>
                                    <span>
                                        <?php echo $actual_delivery; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <label>Service Price</label>
                                <span>$<?php echo number_format($order['price'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Service Fee</label>
                                <span>$<?php echo number_format($order['service_fee'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Total Price</label>
                                <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Revisions Included</label>
                                <span>
                                    <?php echo $order['revisions_included']; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['status'] == 'Cancelled'): ?>
                        <div class="order-details-card">
                            <h3 class="order-section-title">Cancellation Details</h3>
                            <div class="order-info-grid">
                                <div class="info-item">
                                    <label>Cancellation Date</label>
                                    <span>
                                        <?php echo isset($order['completion_date']) ? date('M d, Y', strtotime($order['completion_date'])) : 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <label>Reason</label>
                                    <span>
                                        <?php
                                        $reason = str_replace("Cancellation Reason: ", "", $order['deliverable_notes'] ?? '');
                                        echo !empty($reason) ? htmlspecialchars($reason) : 'No reason provided.';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="order-details-card">
                        <h3 class="order-section-title">Requirements</h3>
                        <p>
                            <?php echo nl2br(htmlspecialchars($order['requirements'])); ?>
                        </p>

                        <?php if (!empty($req_files)): ?>
                            <h4 class="attachment-header">Attached Files</h4>
                            <ul class="file-list">
                                <?php foreach ($req_files as $file): ?>
                                    <li class="file-item"
                                        data-ext="<?php echo strtolower(pathinfo($file['original_filename'], PATHINFO_EXTENSION)); ?>">
                                        <div class="file-icon"></div>
                                        <div class="file-details">
                                            <span class="file-name">
                                                <?php echo htmlspecialchars($file['original_filename']); ?>
                                            </span>
                                            <span class="file-meta">
                                                <?php echo round($file['file_size'] / 1024, 2); ?> KB
                                            </span>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" download
                                            class="file-download-link">Download</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($revisions) || $order['status'] == 'Revision Requested'): ?>
                        <div class="history-card">
                            <h3 class="order-section-title">Revision History</h3>

                            <div class="revision-summary">
                                <div class="summary-stat">
                                    <span class="stat-value"><?php echo $total_rev; ?></span>
                                    <span class="stat-label">Total</span>
                                </div>
                                <div class="summary-stat">
                                    <span
                                        class="stat-value text-secondary"><?php echo ($total_rev - $accepted_rev - $rejected_rev); ?></span>
                                    <span class="stat-label">Pending</span>
                                </div>
                                <div class="summary-stat">
                                    <span class="stat-value text-success"><?php echo $accepted_rev; ?></span>
                                    <span class="stat-label">Accepted</span>
                                </div>
                                <div class="summary-stat">
                                    <span class="stat-value text-danger"><?php echo $rejected_rev; ?></span>
                                    <span class="stat-label">Rejected</span>
                                </div>
                                <div class="summary-stat">
                                    <span class="stat-value"><?php echo $remaining_rev; ?></span>
                                    <span class="stat-label">Remaining</span>
                                </div>
                            </div>

                            <table class="my-services-table revision-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Freelancer Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($revisions as $rev): ?>
                                        <tr>
                                            <td><?php echo date('M d', strtotime($rev['request_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($rev['request_status']); ?></td>
                                            <td>
                                                <?php if ($rev['request_status'] == 'Rejected'): ?>
                                                    <span class="text-danger">Rejected</span>
                                                <?php elseif ($rev['request_status'] == 'Accepted'): ?>
                                                    <span class="text-success">Accepted</span>
                                                <?php else: ?>
                                                    <span class="text-secondary">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="revision-response-content">
                                                <strong>Request:</strong>
                                                <?php echo htmlspecialchars($rev['revision_notes']); ?>
                                                <?php if (!empty($rev['freelancer_response'])): ?>
                                                    <div class="freelancer-response-block">
                                                        <strong>Response:</strong>
                                                        <?php echo htmlspecialchars($rev['freelancer_response']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="timeline-card">
                        <h3 class="order-section-title">Order Progress</h3>
                        <div class="timeline-container">

                            <div class="timeline-item <?php echo 'completed'; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-line"></div>
                                <div class="timeline-content">
                                    <div class="timeline-step">Order Placed</div>
                                    <div class="timeline-date">
                                        <?php echo date('M d, H:i', strtotime($order['order_date'])); ?>
                                    </div>
                                </div>
                            </div>

                            <?php
                            $step2_class = '';
                            $step2_date = '';
                            if ($order['status'] == 'Pending') {
                                $step2_class = '';
                            } elseif ($order['status'] == 'Cancelled') {
                                $step2_class = '';
                            } else {
                                if ($order['status'] == 'In Progress' || $order['status'] == 'Revision Requested') {
                                    $step2_class = 'active';
                                } else {
                                    $step2_class = 'completed';
                                }
                            }
                            ?>
                            <div class="timeline-item <?php echo $step2_class; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-line"></div>
                                <div class="timeline-content">
                                    <div class="timeline-step">In Progress</div>
                                </div>
                            </div>

                            <?php
                            $step3_class = '';
                            $step3_date = '';
                            if ($order['status'] == 'Delivered') {
                                $step3_class = 'active';
                                if ($actual_delivery)
                                    $step3_date = $actual_delivery;
                            } elseif ($order['status'] == 'Completed') {
                                $step3_class = 'completed';
                                if ($actual_delivery)
                                    $step3_date = $actual_delivery;
                            }
                            ?>
                            <div class="timeline-item <?php echo $step3_class; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-line"></div>
                                <div class="timeline-content">
                                    <div class="timeline-step">Delivered</div>
                                    <?php if ($step3_date): ?>
                                        <div class="timeline-date"><?php echo $step3_date; ?></div><?php endif; ?>
                                </div>
                            </div>

                            <?php
                            $step4_class = '';
                            if ($order['status'] == 'Completed') {
                                $step4_class = 'completed';
                            }
                            ?>
                            <div class="timeline-item <?php echo $step4_class; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-step">Completed</div>
                                    <?php if ($order['status'] == 'Completed' && isset($order['completion_date'])): ?>
                                        <div class="timeline-date">
                                            <?php echo date('M d, H:i', strtotime($order['completion_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($order['status'] == 'Cancelled'): ?>
                                <div class="timeline-item cancelled">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-step">Cancelled</div>
                                        <div class="timeline-date">
                                            <?php echo date('M d', strtotime($order['completion_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <div class="order-details-card">
                        <h3 class="order-section-title">
                            <?php echo ($role == 'Client') ? 'Freelancer' : 'Client'; ?>
                        </h3>
                        <div class="profile-card profile-card-minimal">
                            <?php
                            if ($role == 'Client') {
                                $p_name = $order['free_fname'] . " " . $order['free_lname'];
                                $p_photo = !empty($order['free_photo']) ? $order['free_photo'] : 'https://via.placeholder.com/60';
                                $p_role = 'Freelancer';
                            } else {
                                $p_name = $order['client_fname'] . " " . $order['client_lname'];
                                $p_photo = !empty($order['client_photo']) ? $order['client_photo'] : 'https://via.placeholder.com/60';
                                $p_role = 'Client';
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($p_photo); ?>" class="profile-img profile-img-lg">
                            <div class="profile-info">
                                <span class="profile-name profile-name-lg">
                                    <?php echo htmlspecialchars($p_name); ?>
                                </span>
                                <span class="profile-role">
                                    <?php echo $p_role; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="order-details-card order-actions-centered">
                        <h3 class="order-section-title">Actions</h3>

                        <?php if ($role == 'Client'): ?>
                            <!-- CLIENT ACTIONS -->
                            <?php if ($order['status'] == 'Pending'): ?>
                                <a href="cancel-order.php?id=<?php echo $order_id; ?>" class="action-btn danger btn-bold">Cancel
                                    Order</a>

                            <?php elseif ($order['status'] == 'In Progress'): ?>
                                <p class="container-p">Freelancer is working on your order.</p>

                            <?php elseif ($order['status'] == 'Delivered'): ?>
                                <a href="complete-order.php?id=<?php echo $order_id; ?>"
                                    class="action-btn success btn-bold">Mark as
                                    Completed</a>
                                <br><br>
                                <a href="request-revision.php?id=<?php echo $order_id; ?>"
                                    class="action-btn btn-request-revision">Request Revision</a>

                            <?php elseif ($order['status'] == 'Completed'): ?>
                                <p class="success-msg-box">Order Completed. <a href="#">Leave a Review</a></p>

                            <?php endif; ?>

                        <?php else: ?>
                            <!-- FREELANCER ACTIONS -->
                            <?php if ($order['status'] == 'Pending'): ?>
                                <form action="start-order.php" method="post">
                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                    <button type="submit" class="btn-submit btn-auto-width">Start Working</button>
                                </form>

                            <?php elseif ($order['status'] == 'In Progress' || $order['status'] == 'Revision Requested'): ?>
                                <?php if ($order['status'] == 'Revision Requested'): ?>
                                    <p class="container-p">Client has requested a revision.</p>
                                    <a href="respond-revision.php?id=<?php echo $order_id; ?>&action=accept"
                                        class="action-btn success btn-bold">Accept & Upload</a>

                                    <form action="respond-revision.php" method="post" class="revision-response-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                        <input type="hidden" name="reject_revision" value="1">
                                        <textarea name="rejection_reason" class="form-control revision-reason-input"
                                            placeholder="Reason for rejection (50-500 chars)" rows="2" required minlength="50"
                                            maxlength="500"></textarea>
                                        <button type="submit" class="action-btn danger">Reject Request</button>
                                    </form>
                                <?php else: ?>
                                    <a href="upload-delivery.php?id=<?php echo $order_id; ?>"
                                        class="btn-submit inline-block-btn">Upload
                                        Delivery</a>
                                <?php endif; ?>

                            <?php elseif ($order['status'] == 'Delivered'): ?>
                                <p class="container-p">Waiting for client response.</p>

                            <?php endif; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($del_files)): ?>
                <div class="order-details-card">
                    <h3 class="order-section-title">Deliveries</h3>
                    <ul class="file-list">
                        <?php foreach ($del_files as $file): ?>
                            <li class="file-item"
                                data-ext="<?php echo strtolower(pathinfo($file['original_filename'], PATHINFO_EXTENSION)); ?>">
                                <div class="file-icon"></div>
                                <div class="file-details">
                                    <span class="file-name">
                                        <?php echo htmlspecialchars($file['original_filename']); ?>
                                    </span>
                                    <span class="file-meta">
                                        Uploaded:
                                        <?php echo date('M d, Y', strtotime($file['upload_timestamp'])); ?> |
                                        Size:
                                        <?php echo round($file['file_size'] / 1024, 2); ?> KB
                                    </span>
                                </div>
                                <a href="<?php echo htmlspecialchars($file['file_path']); ?>" download
                                    class="file-download-link">Download</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once "footer.php"; ?>