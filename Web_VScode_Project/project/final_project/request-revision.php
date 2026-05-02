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

$sql = "SELECT * FROM orders WHERE order_id = :order_id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':order_id', $order_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || $order['client_id'] != $user_id) {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Access Denied</h2></div></main>";
    require_once "footer.php";
    exit();
}

if ($order['status'] != 'Delivered') {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Revisions can only be requested for Delivered orders.</h2></div></main>";
    require_once "footer.php";
    exit();
}

$rev_count_sql = "SELECT COUNT(*) FROM revision_requests WHERE order_id = :order_id AND request_status IN ('Accepted', 'Rejected')";
$rc_stmt = $pdo->prepare($rev_count_sql);
$rc_stmt->bindValue(':order_id', $order_id);
$rc_stmt->execute();
$used_revisions = $rc_stmt->fetchColumn();

if ($order['revisions_included'] < 999 && $used_revisions >= $order['revisions_included']) {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Revision Limit Reached</h2><p class='container-p'>You have used all allowed revisions for this order.</p><p><a href='order-details.php?id=$order_id'>Back to Order</a></p></div></main>";
    require_once "footer.php";
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $notes = isset($_POST['revision_notes']) ? trim($_POST['revision_notes']) : '';

    if (strlen($notes) < 50 || strlen($notes) > 500) {
        $error = "Description must be between 50 and 500 characters.";
    } elseif (!isset($_POST['confirm_revision'])) {
        $error = "You must acknowledge the revision count.";
    } else {
        $revision_file_path = null;
        if (isset($_FILES['revision_file']) && $_FILES['revision_file']['error'] == 0) {
            $upload_dir = "uploads/orders/" . $order_id . "/revisions/";
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            $f_name = $_FILES['revision_file']['name'];
            $f_tmp = $_FILES['revision_file']['tmp_name'];
            $f_size = $_FILES['revision_file']['size'];

            if ($f_size <= 10 * 1024 * 1024) {
                $ext = pathinfo($f_name, PATHINFO_EXTENSION);
                $new_name = uniqid() . "_" . basename($f_name);
                $dest = $upload_dir . $new_name;

                if (move_uploaded_file($f_tmp, $dest)) {
                    $revision_file_path = $dest;
                }
            }
        }

        $ins_sql = "INSERT INTO revision_requests (order_id, revision_notes, request_status, request_date, revision_file) VALUES (:order_id, :notes, 'Pending', NOW(), :file)";

        $ins = $pdo->prepare($ins_sql);
        $ins->bindValue(':order_id', $order_id);
        $ins->bindValue(':notes', $notes);
        $ins->bindValue(':file', $revision_file_path);

        if ($ins->execute()) {
            if ($revision_file_path) {
                $f_sql = "INSERT INTO file_attachments (order_id, file_path, original_filename, file_size, file_type) VALUES (:oid, :path, :name, :size, 'revision')";
                $f_stmt = $pdo->prepare($f_sql);
                $f_stmt->bindValue(':oid', $order_id);
                $f_stmt->bindValue(':path', $revision_file_path);
                $f_stmt->bindValue(':name', $_FILES['revision_file']['name']);
                $f_stmt->bindValue(':size', $_FILES['revision_file']['size']);
                $f_stmt->execute();
            }

            $up_sql = "UPDATE orders SET status = 'Revision Requested' WHERE order_id = :order_id";
            $up = $pdo->prepare($up_sql);
            $up->bindValue(':order_id', $order_id);
            $up->execute();

            header("Location: order-details.php?id=" . $order_id);
            exit();
        } else {
            $error = "Database error.";
        }
    }
}
?>

<link rel="stylesheet" href="styles.css" />
<div class="page-layout">
    <?php include("nav.php"); ?>
    <main class="main-content">
        <div class="container text-left">
            <h1 class="container-h1">Request Revision</h1>
            <p class="container-p">Please describe the changes you need.</p>

            <?php if ($error): ?>
                <p class="error-msg">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <form action="request-revision.php?id=<?php echo $order_id; ?>" method="post" enctype="multipart/form-data"
                class="auth-form form-full-width">

                <div class="form-group">
                    <label for="notes">Revision Description (50-500 characters) *</label>
                    <textarea name="revision_notes" id="notes" class="form-control" rows="5" required minlength="50"
                        maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label>Reference File (Optional)</label>
                    <input type="file" name="revision_file" class="form-control">
                    <p class="form-help-text">Max 10MB.</p>
                </div>

                <div class="form-group">
                    <label class="checkbox-label checkbox-flex-label">
                        <input type="checkbox" name="confirm_revision" required>
                        <span>I acknowledge that this request counts toward my revision limit (
                            <?php echo ($order['revisions_included'] >= 999) ? 'Unlimited' : $used_revisions . '/' . $order['revisions_included']; ?>
                            used).
                        </span>
                    </label>
                </div>

                <div class="form-row">
                    <input type="submit" value="Submit Request" class="btn-submit">
                    <a href="order-details.php?id=<?php echo $order_id; ?>"
                        class="action-btn btn-cancel-link">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once "footer.php"; ?>