<?php
require_once "db.php.inc";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Freelancer') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT status FROM orders WHERE order_id = :order_id AND freelancer_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':order_id', $order_id);
    $stmt->bindValue(':user_id', $user_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order && $order['status'] == 'Pending') {
        $update = "UPDATE orders SET status = 'In Progress' WHERE order_id = :order_id";
        $u_stmt = $pdo->prepare($update);
        $u_stmt->bindValue(':order_id', $order_id);
        $u_stmt->execute();
    }
}

header("Location: order-details.php?id=" . $order_id);
exit();
