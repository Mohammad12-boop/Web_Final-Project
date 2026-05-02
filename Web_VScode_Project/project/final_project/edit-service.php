<?php
session_start();
require_once "db.php.inc";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Freelancer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$service_id = isset($_GET['id']) ? $_GET['id'] : "";
$error_msg = "";
$success_msg = "";

$stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = :sid AND freelancer_id = :uid");
$stmt->bindValue(':sid', $service_id);
$stmt->bindValue(':uid', $user_id);
$stmt->execute();
$service = $stmt->fetch();

if (!$service) {
    // Service not found or access denied
    header("Location: my-services.php");
    exit();
}

$title = $service['title'];
$category = $service['category'];
$subcategory = $service['subcategory'];
$description = $service['description'];
$price = $service['price'];
$delivery_time = $service['delivery_time'];
$revisions = $service['revisions_included'];
$status = $service['status'];
$featured_status = $service['featured_status'];

$categories = [
    "Web Development" => ["Frontend Development", "Backend Development", "Full Stack Development", "WordPress Development", "E-commerce Development", "Bug Fixes"],
    "Graphic Design" => ["Logo Design", "Brand Identity", "Print Design", "Illustration", "UI/UX Design", "Web Design"],
    "Writing & Translation" => ["Article Writing", "Copywriting", "Proofreading", "Translation", "Technical Writing"],
    "Digital Marketing" => ["Social Media Marketing", "SEO", "Email Marketing", "Content Marketing", "PPC Advertising"],
    "Video & Animation" => ["Video Editing", "Animation", "Whiteboard Animation", "Video Production"],
    "Music & Audio" => ["Voice Over", "Mixing & Mastering", "Producers & Composers"],
    "Business Consulting" => ["Business Planning", "Financial Consulting", "Legal Consulting", "HR Consulting"],
    "Tutoring & Education" => ["Online Tutoring", "Career Counseling", "Language Lessons"]
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = isset($_POST['title']) ? trim($_POST['title']) : "";
    $category = isset($_POST['category']) ? $_POST['category'] : "";
    $subcategory = isset($_POST['subcategory']) ? $_POST['subcategory'] : "";
    $description = isset($_POST['description']) ? trim($_POST['description']) : "";
    $price = isset($_POST['price']) ? trim($_POST['price']) : "";
    $delivery_time = isset($_POST['delivery_time']) ? trim($_POST['delivery_time']) : "";
    $revisions = isset($_POST['revisions']) ? trim($_POST['revisions']) : "";
    $status = isset($_POST['status']) ? $_POST['status'] : "Inactive";
    $featured_input = isset($_POST['featured']) ? "Yes" : "No";

    if (strlen($title) < 10 || strlen($title) > 100)
        $error_msg = "Title must be 10-100 characters.";
    elseif (empty($category))
        $error_msg = "Category is required.";
    elseif (empty($subcategory))
        $error_msg = "Subcategory is required.";
    elseif (strlen($description) < 100 || strlen($description) > 2000)
        $error_msg = "Description must be 100-2000 characters.";
    elseif (!is_numeric($price) || $price < 5 || $price > 10000)
        $error_msg = "Price must be between $5 and $10,000.";
    elseif (!preg_match("/^[0-9]+$/", $delivery_time) || $delivery_time < 1 || $delivery_time > 90)
        $error_msg = "Delivery time must be 1-90 days.";
    elseif (!preg_match("/^[0-9]+$/", $revisions) || $revisions < 0 || $revisions > 999)
        $error_msg = "Revisions must be 0-999.";

    if (empty($error_msg)) {
        $chk = $pdo->prepare("SELECT service_id FROM services WHERE title = :t AND freelancer_id = :uid AND service_id != :sid");
        $chk->execute([':t' => $title, ':uid' => $user_id, ':sid' => $service_id]);
        if ($chk->fetch())
            $error_msg = "You already have a service with this title.";
    }

    if ($status == 'Inactive') {
        $featured_input = 'No';
    } elseif (empty($error_msg) && $featured_input == 'Yes') {
        $cntSql = "SELECT COUNT(*) FROM services WHERE freelancer_id = :uid AND featured_status = 'Yes' AND service_id != :sid";
        $cntStmt = $pdo->prepare($cntSql);
        $cntStmt->execute([':uid' => $user_id, ':sid' => $service_id]);
        if ($cntStmt->fetchColumn() >= 3) {
            $error_msg = "You have reached the maximum of 3 featured services.";
            $featured_input = 'No';
        }
    }

    $img1_path = $service['image_1'];
    $img2_path = $service['image_2'];
    $img3_path = $service['image_3'];

    if (empty($error_msg)) {
        $upload_dir = "uploads/services/" . $service_id . "/";
        if (!is_dir($upload_dir) && !file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        function handleUpload($inputName, $currentPath, $upload_dir, &$error)
        {
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] == 0) {
                $fname = $_FILES[$inputName]['name'];
                $ftmp = $_FILES[$inputName]['tmp_name'];
                $fsize = $_FILES[$inputName]['size'];

                if (!preg_match("/\.(jpg|jpeg|png)$/i", $fname)) {
                    $error = "Only JPG, PNG allowed.";
                    return $currentPath;
                }
                if ($fsize > 5 * 1024 * 1024) {
                    $error = "Max file size 5MB.";
                    return $currentPath;
                }

                $check = getimagesize($ftmp);
                if (!$check || $check[0] < 800 || $check[1] < 600) {
                    $error = "Min dimensions 800x600px.";
                    return $currentPath;
                }

                $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                $suffix = ($inputName == 'image1') ? '01' : (($inputName == 'image2') ? '02' : '03');
                $newPath = $upload_dir . "image_" . $suffix . "_" . time() . "." . $ext;

                if (move_uploaded_file($ftmp, $newPath)) {
                    if (!empty($currentPath) && file_exists($currentPath)) {
                        unlink($currentPath);
                    }
                    return $newPath;
                } else {
                    $error = "Upload failed.";
                    return $currentPath;
                }
            }
            return $currentPath;
        }

        $img1_path = handleUpload('image1', $img1_path, $upload_dir, $error_msg);
        if (empty($error_msg))
            $img2_path = handleUpload('image2', $img2_path, $upload_dir, $error_msg);
        if (empty($error_msg))
            $img3_path = handleUpload('image3', $img3_path, $upload_dir, $error_msg);

        if (empty($img1_path) && empty($img2_path) && empty($img3_path)) {
            $error_msg = "At least one image is required.";
        }
    }

    if (empty($error_msg)) {
        try {
            $sql = "UPDATE services SET 
                    title = :title, category = :cat, subcategory = :sub, description = :desc, 
                    price = :price, delivery_time = :del, revisions_included = :rev,
                    status = :status, featured_status = :feat,
                    image_1 = :img1, image_2 = :img2, image_3 = :img3
                    WHERE service_id = :sid AND freelancer_id = :uid";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':cat', $category);
            $stmt->bindValue(':sub', $subcategory);
            $stmt->bindValue(':desc', $description);
            $stmt->bindValue(':price', $price);
            $stmt->bindValue(':del', $delivery_time);
            $stmt->bindValue(':rev', $revisions);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':feat', $featured_input);
            $stmt->bindValue(':img1', $img1_path);
            $stmt->bindValue(':img2', $img2_path);
            $stmt->bindValue(':img3', $img3_path);
            $stmt->bindValue(':sid', $service_id);
            $stmt->bindValue(':uid', $user_id);

            $stmt->execute();

            $_SESSION['success_msg'] = "Service updated successfully!";
            // Requirement 8.2: Redirect to My Services page
            header("Location: my-services.php");
            exit();

        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Service - Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">
        <?php include("nav.php"); ?>
        <main class="main-content">

            <div class="breadcrumb">
                <a href="main.php">Home</a> > <a href="my-services.php">My Services</a> > <span>Edit Service</span>
            </div>

            <h2 class="container-h1">Edit Service</h2>

            <!-- Errors display blocks, Success redirects away -->
            <?php if (!empty($error_msg)): ?>
                <div class="error-msg text-center mb-20"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="edit-service.php?id=<?php echo htmlspecialchars($service_id); ?>" method="POST"
                enctype="multipart/form-data" class="service-form">

                <h3 class="form-section-title">Service Overview</h3>

                <div class="form-group">
                    <label for="title">Service Title <span class="required">*</span></label>
                    <input type="text" name="title" id="title" class="form-control"
                        value="<?php echo htmlspecialchars($title); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select name="category" id="category" class="form-control" onchange="this.form.submit()">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $catName => $subs): ?>
                                <option value="<?php echo htmlspecialchars($catName); ?>" <?php if ($category == $catName)
                                       echo 'selected'; ?>><?php echo htmlspecialchars($catName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subcategory">Subcategory <span class="required">*</span></label>
                        <select name="subcategory" id="subcategory" class="form-control">
                            <option value="">Select Subcategory</option>
                            <?php foreach ($categories as $catName => $subs): ?>
                                <optgroup label="<?php echo htmlspecialchars($catName); ?>">
                                    <?php foreach ($subs as $sub): ?>
                                        <option value="<?php echo htmlspecialchars($sub); ?>" <?php if ($subcategory == $sub)
                                               echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea name="description" id="description" rows="6"
                        class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <h3 class="form-section-title mt-30">Pricing & Delivery</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($) <span class="required">*</span></label>
                        <input type="number" name="price" id="price" class="form-control"
                            value="<?php echo htmlspecialchars($price); ?>" min="5" max="10000" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="delivery_time">Delivery Time (Days) <span class="required">*</span></label>
                        <input type="number" name="delivery_time" id="delivery_time" class="form-control"
                            value="<?php echo htmlspecialchars($delivery_time); ?>" min="1" max="90">
                    </div>
                    <div class="form-group">
                        <label for="revisions">Revisions <span class="required">*</span></label>
                        <input type="number" name="revisions" id="revisions" class="form-control"
                            value="<?php echo htmlspecialchars($revisions); ?>" min="0" max="999">
                    </div>
                </div>

                <h3 class="form-section-title mt-30">Status & Featured</h3>
                <div class="form-group">
                    <label>Service Status</label>
                    <div class="status-radio-group">
                        <label class="radio-label mr-20">
                            <input type="radio" name="status" value="Active" <?php if ($status == 'Active')
                                echo 'checked'; ?>> Active
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="status" value="Inactive" <?php if ($status == 'Inactive')
                                echo 'checked'; ?>> Inactive
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" value="Yes" <?php if ($featured_status == 'Yes')
                            echo 'checked'; ?>>
                        <strong>Feature this Service</strong> <span class="gold-text">★</span>
                    </label>
                    <p class="text-muted-small">
                        * Only Active services can be featured. Max 3 per freelancer.
                    </p>
                </div>


                <h3 class="form-section-title mt-30">Manage Images</h3>
                <p class="mb-20 text-muted">Upload new images to replace existing ones. At least one image must remain.
                </p>

                <div class="form-group image-upload-box">
                    <label>Image 1 (Main)</label>
                    <div class="image-preview-wrapper">
                        <?php if (!empty($service['image_1'])): ?>
                            <img src="<?php echo htmlspecialchars($service['image_1']); ?>" alt="Img 1"
                                class="image-preview-img">
                        <?php else: ?>
                            <div class="image-preview-placeholder">No Img</div>
                        <?php endif; ?>
                        <input type="file" name="image1" class="form-control image-file-input"
                            accept="image/jpeg, image/png">
                    </div>
                </div>

                <div class="form-group mt-10 image-upload-box">
                    <label>Image 2</label>
                    <div class="image-preview-wrapper">
                        <?php if (!empty($service['image_2'])): ?>
                            <img src="<?php echo htmlspecialchars($service['image_2']); ?>" alt="Img 2"
                                class="image-preview-img">
                        <?php else: ?>
                            <div class="image-preview-placeholder">No Img</div>
                        <?php endif; ?>
                        <input type="file" name="image2" class="form-control image-file-input"
                            accept="image/jpeg, image/png">
                    </div>
                </div>

                <div class="form-group mt-10 image-upload-box">
                    <label>Image 3</label>
                    <div class="image-preview-wrapper">
                        <?php if (!empty($service['image_3'])): ?>
                            <img src="<?php echo htmlspecialchars($service['image_3']); ?>" alt="Img 3"
                                class="image-preview-img">
                        <?php else: ?>
                            <div class="image-preview-placeholder">No Img</div>
                        <?php endif; ?>
                        <input type="file" name="image3" class="form-control image-file-input"
                            accept="image/jpeg, image/png">
                    </div>
                </div>

                <div class="form-buttons mt-30">
                    <a href="my-services.php" class="btn-cancel">Cancel</a>
                    <input type="submit" class="btn-submit" value="Update Service">
                </div>

            </form>
        </main>
    </div>
    <?php include("footer.php"); ?>
</body>

</html>