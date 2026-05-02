<?php
require_once "header.php";
require_once "db.php.inc";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Freelancer') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM orders WHERE order_id = :order_id AND freelancer_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':order_id', $order_id);
$stmt->bindValue(':user_id', $user_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Order not found</h2></div></main>";
    require_once "footer.php";
    exit();
}

if ($order['status'] != 'In Progress' && $order['status'] != 'Revision Requested') {
    echo "<main class='main-content'><div class='container'><h2 class='container-h2'>Cannot upload delivery for this order status.</h2><p><a href='order-details.php?id=$order_id'>Back to Order</a></p></div></main>";
    require_once "footer.php";
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = isset($_POST['delivery_message']) ? trim($_POST['delivery_message']) : '';

    if (strlen($message) < 50 || strlen($message) > 500) {
        $error = "Delivery message must be between 50 and 500 characters.";
    } elseif (empty($_FILES['delivery_files']['name'][0])) {
        $error = "You must upload at least one file.";
    } else {
        $file_count = count($_FILES['delivery_files']['name']);
        if ($file_count > 5) {
            $error = "Maximum 5 files allowed.";
        } else {
            $upload_dir = "uploads/orders/" . $order_id . "/deliverables/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $success_count = 0;

            for ($i = 0; $i < $file_count; $i++) {
                $file_name = $_FILES['delivery_files']['name'][$i];
                $file_tmp = $_FILES['delivery_files']['tmp_name'][$i];
                $file_size = $_FILES['delivery_files']['size'][$i];
                $file_type = $_FILES['delivery_files']['type'][$i];

                // continue;

                $new_filename = uniqid() . "_" . basename($file_name);
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $attachment_type = ($order['status'] == 'Revision Requested') ? 'revision' : 'deliverable';
                    $ins_sql = "INSERT INTO file_attachments (order_id, file_path, original_filename, file_size, file_type) VALUES (:order_id, :path, :name, :size, :type)";
                    $ins = $pdo->prepare($ins_sql);
                    $ins->bindValue(':order_id', $order_id);
                    $ins->bindValue(':path', $destination);
                    $ins->bindValue(':name', $file_name);
                    $ins->bindValue(':size', $file_size);
                    $ins->bindValue(':type', $attachment_type);
                    $ins->execute();
                    $success_count++;
                }
            }

            if ($success_count > 0) {
                $up_sql = "UPDATE orders SET status = 'Delivered', deliverable_notes = :msg WHERE order_id = :order_id";
                $up = $pdo->prepare($up_sql);
                $up->bindValue(':msg', $message);
                $up->bindValue(':order_id', $order_id);
                $up->execute();

                header("Location: order-details.php?id=" . $order_id);
                exit();
            } else {
                $error = "Failed to upload files.";
            }
        }
    }
}
?>

<link rel="stylesheet" href="styles.css" />
<div class="page-layout">
    <?php include("nav.php"); ?>
    <main class="main-content">
        <div class="container text-left">
            <h1 class="container-h1">Upload Delivery</h1>

            <?php if ($error): ?>
                <p class="error-msg">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <form action="upload-delivery.php?id=<?php echo $order_id; ?>" method="post" enctype="multipart/form-data"
                class="auth-form form-full-width">

                <div class="form-group">
                    <label for="msg">Delivery Message (50-500 characters) *</label>
                    <textarea name="delivery_message" id="msg" class="form-control" rows="5" required minlength="50"
                        maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label>Upload Files (Max 5 files, 50MB each) *</label>
                    <input type="file" name="delivery_files[]" class="form-control" multiple required>
                    <p class="form-help-text">Supported formats: Any. Max size:
                        50MB.</p>
                </div>

                <div class="form-row">
                    <input type="submit" value="Submit Delivery" class="btn-submit">
                    <a href="order-details.php?id=<?php echo $order_id; ?>"
                        class="action-btn btn-cancel-link">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once "footer.php"; ?>