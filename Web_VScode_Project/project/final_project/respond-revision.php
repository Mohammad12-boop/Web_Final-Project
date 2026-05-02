<?php
require_once "header.php";
require_once "db.php.inc";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

function redirectBack($id)
{
    header("Location: order-details.php?id=" . $id);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'accept' && isset($_GET['id'])) {
    $order_id = $_GET['id'];

    // Validate
    $sql = "SELECT freelancer_id, status FROM orders WHERE order_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['freelancer_id'] != $user_id || $order['status'] != 'Revision Requested') {
        redirectBack($order_id);
    }

    $rev_sql = "SELECT revision_id FROM revision_requests WHERE order_id = :id AND request_status = 'Pending' ORDER BY request_date DESC LIMIT 1";
    $rev_stmt = $pdo->prepare($rev_sql);
    $rev_stmt->bindValue(':id', $order_id);
    $rev_stmt->execute();
    $rev_id = $rev_stmt->fetchColumn();

    if ($rev_id) {
        $up_rev = "UPDATE revision_requests SET request_status = 'Accepted', response_date = NOW() WHERE revision_id = :rid";
        $up = $pdo->prepare($up_rev);
        $up->bindValue(':rid', $rev_id);
        $up->execute();

        header("Location: upload-delivery.php?id=" . $order_id);
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject_revision'])) {
    $order_id = $_POST['order_id'];
    $reason = trim($_POST['rejection_reason']);

    if (strlen($reason) < 50 || strlen($reason) > 500) {
        redirectBack($order_id);
    }

    // Validate
    $sql = "SELECT freelancer_id, status FROM orders WHERE order_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['freelancer_id'] != $user_id || $order['status'] != 'Revision Requested') {
        redirectBack($order_id);
    }

    $rev_sql = "SELECT revision_id FROM revision_requests WHERE order_id = :id AND request_status = 'Pending' ORDER BY request_date DESC LIMIT 1";
    $rev_stmt = $pdo->prepare($rev_sql);
    $rev_stmt->bindValue(':id', $order_id);
    $rev_stmt->execute();
    $rev_id = $rev_stmt->fetchColumn();

    if ($rev_id) {
        $up_rev = "UPDATE revision_requests SET request_status = 'Rejected', response_date = NOW(), freelancer_response = :reason WHERE revision_id = :rid";
        $up = $pdo->prepare($up_rev);
        $up->bindValue(':reason', $reason);
        $up->bindValue(':rid', $rev_id);
        $up->execute();

        $up_ord = "UPDATE orders SET status = 'Delivered' WHERE order_id = :oid";
        $up_o = $pdo->prepare($up_ord);
        $up_o->bindValue(':oid', $order_id);
        $up_o->execute();

        redirectBack($order_id);
    }
}

header("Location: my-orders.php");
exit();
?>