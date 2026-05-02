<?php
require_once "Service.php";
session_start();
require_once "db.php.inc";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Freelancer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $service_id = $_GET['id'];

    if (preg_match('/^\d+$/', $service_id)) {
        if ($action == 'activate') {
            $stmt = $pdo->prepare("UPDATE services SET status = 'Active' WHERE service_id = :sid AND freelancer_id = :uid");
            $stmt->bindValue(':sid', $service_id);
            $stmt->bindValue(':uid', $user_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $success_msg = "Service activated successfully.";
            }
        } elseif ($action == 'deactivate') {
            $stmt = $pdo->prepare("UPDATE services SET status = 'Inactive', featured_status = 'No' WHERE service_id = :sid AND freelancer_id = :uid");
            $stmt->bindValue(':sid', $service_id);
            $stmt->bindValue(':uid', $user_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $success_msg = "Service deactivated successfully.";
            }
        }
    }
}

$qry = "SELECT COUNT(*) FROM services WHERE freelancer_id = :uid";
$s1 = $pdo->prepare($qry);
$s1->bindValue(':uid', $user_id);
$s1->execute();
$cnt_total = $s1->fetchColumn();

$qry = "SELECT COUNT(*) FROM services WHERE freelancer_id = :uid AND status = 'Active'";
$s2 = $pdo->prepare($qry);
$s2->bindValue(':uid', $user_id);
$s2->execute();
$cnt_active = $s2->fetchColumn();

$qry = "SELECT COUNT(*) FROM services WHERE freelancer_id = :uid AND featured_status = 'Yes' AND status = 'Active'";
$s3 = $pdo->prepare($qry);
$s3->bindValue(':uid', $user_id);
$s3->execute();
$cnt_featured = $s3->fetchColumn();

$qry = "SELECT COUNT(*) FROM orders WHERE freelancer_id = :uid AND status = 'Completed'";
$s4 = $pdo->prepare($qry);
$s4->bindValue(':uid', $user_id);
$s4->execute();
$cnt_completed = $s4->fetchColumn();


$sql = "SELECT service_id, title, category, price, status, featured_status, image_1, 
        DATE_FORMAT(created_date, '%b %d, %Y') as formatted_date 
        FROM services WHERE freelancer_id = :uid ORDER BY created_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $user_id);
$stmt->execute();
$services = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Services - Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">

        <?php include("nav.php"); ?>

        <main class="main-content">

            <div class="breadcrumb">
                <a href="main.php">Home</a> > <span>My Services</span>
            </div>

            <h2 class="container-h1">My Services</h2>

            <?php if (!empty($success_msg)): ?>
                <div class="success-msg-box"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <div class="profile-container mb-20">
                <div class="profile-sidebar-card w-100">
                    <h3 class="container-h2 text-center mt-0">Service Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $cnt_total; ?></span>
                            <span class="stat-label">Total Services</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number success-text"><?php echo $cnt_active; ?></span>
                            <span class="stat-label">Active Services</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number <?php echo ($cnt_featured >= 3) ? 'gold-text' : ''; ?>">
                                <?php echo $cnt_featured; ?>/3
                            </span>
                            <span class="stat-label">Featured</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $cnt_completed; ?></span>
                            <span class="stat-label">Completed Orders</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="create-btn-container">
                <a href="create-service.php" class="btn-submit btn-create">+ Create New Service</a>
            </div>

            <div class="my-services">
                <table class="my-services-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Service Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($services) > 0): ?>
                            <?php foreach ($services as $svc): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $img = !empty($svc['image_1']) ? $svc['image_1'] : "https://via.placeholder.com/100";
                                        ?>
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="Service"
                                            class="service-thumb service-thumb-sm">
                                    </td>
                                    <td class="text-left">
                                        <strong><a href="service-details.php?service_id=<?php echo $svc['service_id']; ?>"
                                                class="sort-link"><?php echo htmlspecialchars($svc['title']); ?></a></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($svc['category']); ?></td>
                                    <td>$<?php echo sprintf("%.2f", $svc['price']); ?></td>
                                    <td>
                                        <?php if ($svc['status'] == 'Active'): ?>
                                            <span class="success-text">Active</span>
                                        <?php else: ?>
                                            <span class="status-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($svc['featured_status'] == 'Yes'): ?>
                                            <span class="featured-indicator"></span> Yes
                                        <?php else: ?>
                                            No
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($svc['formatted_date']); ?></td>
                                    <td>
                                        <a href="edit-service.php?id=<?php echo $svc['service_id']; ?>"
                                            class="action-btn">Edit</a>

                                        <?php if ($svc['status'] == 'Active'): ?>
                                            <a href="my-services.php?action=deactivate&id=<?php echo $svc['service_id']; ?>"
                                                class="action-btn danger"
                                                onclick="return confirm('Are you sure you want to deactivate this service?');">Deactivate</a>
                                        <?php else: ?>
                                            <a href="my-services.php?action=activate&id=<?php echo $svc['service_id']; ?>"
                                                class="action-btn success">Activate</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">You have no services yet. <a
                                        href="create-service.php">Create one!</a></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <?php include("footer.php"); ?>
</body>

</html>