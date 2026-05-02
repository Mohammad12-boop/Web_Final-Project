<?php
// This file handles the multi-step form to create a new service listing

require_once "Service.php";
// Check session and connect to db
session_start();
require_once "db.php.inc";

// Only freelancers can create new services
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Freelancer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$errors = [];

if (!isset($_SESSION['service_data'])) {
    $_SESSION['service_data'] = [];
}

// Ensure we start at step 1 if no data is present
if ($step > 1 && empty($_SESSION['service_data']['title'])) {
    header("Location: create-service.php?step=1");
    exit();
}

// Array with all categories and subcategories
$categories = [
    "Web Development" => [
        "Frontend Development",
        "Backend Development",
        "Full Stack Development",
        "WordPress Development",
        "E-commerce Development",
        "Bug Fixes"
    ],
    "Graphic Design" => [
        "Logo Design",
        "Brand Identity",
        "Print Design",
        "Illustration",
        "UI/UX Design",
        "Web Design"
    ],
    "Writing & Translation" => [
        "Article Writing",
        "Copywriting",
        "Proofreading",
        "Translation",
        "Technical Writing"
    ],
    "Digital Marketing" => [
        "Social Media Marketing",
        "SEO",
        "Email Marketing",
        "Content Marketing",
        "PPC Advertising"
    ],
    "Video & Animation" => [
        "Video Editing",
        "Animation",
        "Whiteboard Animation",
        "Video Production"
    ],
    "Music & Audio" => [
        "Voice Over",
        "Mixing & Mastering",
        "Producers & Composers"
    ],
    "Business Consulting" => [
        "Business Planning",
        "Financial Consulting",
        "Legal Consulting",
        "HR Consulting"
    ],
    "Tutoring & Education" => [
        "Online Tutoring",
        "Career Counseling",
        "Language Lessons"
    ]
];

$title = isset($_SESSION['service_data']['title']) ? $_SESSION['service_data']['title'] : "";
$category = isset($_SESSION['service_data']['category']) ? $_SESSION['service_data']['category'] : "";
$subcategory = isset($_SESSION['service_data']['subcategory']) ? $_SESSION['service_data']['subcategory'] : "";
$description = isset($_SESSION['service_data']['description']) ? $_SESSION['service_data']['description'] : "";
$price = isset($_SESSION['service_data']['price']) ? $_SESSION['service_data']['price'] : "";
$delivery_time = isset($_SESSION['service_data']['delivery_time']) ? $_SESSION['service_data']['delivery_time'] : "";
$revisions = isset($_SESSION['service_data']['revisions']) ? $_SESSION['service_data']['revisions'] : "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 1) {

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE freelancer_id = :uid AND status = 'Active'");
    $countStmt->bindValue(':uid', $user_id);
    $countStmt->execute();
    if ($countStmt->fetchColumn() >= 50) {
        $errors['general'] = "You have reached the maximum limit of 50 active services.";
    }

    $title = isset($_POST['title']) ? trim($_POST['title']) : "";
    $category = isset($_POST['category']) ? $_POST['category'] : "";
    $subcategory = isset($_POST['subcategory']) ? $_POST['subcategory'] : "";
    $description = isset($_POST['description']) ? trim($_POST['description']) : "";
    $price = isset($_POST['price']) ? trim($_POST['price']) : "";
    $delivery_time = isset($_POST['delivery_time']) ? trim($_POST['delivery_time']) : "";
    $revisions = isset($_POST['revisions']) ? trim($_POST['revisions']) : "";

    if (strlen($title) < 10 || strlen($title) > 100) {
        $errors['title'] = "Title must be 10-100 characters.";
    } else {
        $stmt = $pdo->prepare("SELECT service_id FROM services WHERE title = :title AND freelancer_id = :uid");
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':uid', $user_id);
        $stmt->execute();
        if ($stmt->fetch()) {
            $errors['title'] = "You already have a service with this title.";
        }
    }

    if (empty($category)) {
        $errors['category'] = "Category is required.";
    } elseif (!array_key_exists($category, $categories)) {
        $errors['category'] = "Must be valid category from predefined list";
    }

    if (empty($subcategory)) {
        $errors['subcategory'] = "Subcategory is required.";
    } elseif (empty($category) || !in_array($subcategory, $categories[$category])) {
        $errors['subcategory'] = "Must be valid subcategory for selected category";
    }

    if (strlen($description) < 100 || strlen($description) > 2000) {
        $errors['description'] = "Description must be 100-2000 characters.";
    }

    if ($delivery_time === "") {
        $errors['delivery_time'] = "Delivery time is required";
    } elseif (!preg_match("/^[0-9]+$/", $delivery_time) || $delivery_time < 1 || $delivery_time > 90) {
        $errors['delivery_time'] = "Delivery time must be 1-90 days";
    }

    if ($revisions === "") {
        $errors['revisions'] = "Number of revisions is required";
    } elseif (!preg_match("/^[0-9]+$/", $revisions) || $revisions < 0 || $revisions > 999) {
        $errors['revisions'] = "Revisions must be 0-999";
    }

    if ($price === "") {
        $errors['price'] = "Price is required";
    } elseif (!is_numeric($price) || $price < 5 || $price > 10000) {
        $errors['price'] = "Price must be between \$5 and \$10,000";
    }

    if (empty($errors)) {
        $_SESSION['service_data']['title'] = $title;
        $_SESSION['service_data']['category'] = $category;
        $_SESSION['service_data']['subcategory'] = $subcategory;
        $_SESSION['service_data']['description'] = $description;
        $_SESSION['service_data']['price'] = $price;
        $_SESSION['service_data']['delivery_time'] = $delivery_time;
        $_SESSION['service_data']['revisions'] = $revisions;

        header("Location: create-service.php?step=2");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 2) {

    $main_image_val = isset($_POST['main_image']) ? (int) $_POST['main_image'] : 1;
    $temp_images = isset($_SESSION['service_data']['images']) ? $_SESSION['service_data']['images'] : [];

    $step2_errors = [];

    for ($i = 1; $i <= 3; $i++) {
        $input_name = "image" . $i;

        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
            $fname = $_FILES[$input_name]['name'];
            $ftmp = $_FILES[$input_name]['tmp_name'];
            $fsize = $_FILES[$input_name]['size'];

            // Regex for JPG/JPEG/PNG
            if (!preg_match("/\.(jpg|jpeg|png)$/i", $fname)) {
                $step2_errors[$input_name] = "Only JPG, JPEG, PNG allowed.";
            } elseif ($fsize > 5 * 1024 * 1024) {
                $step2_errors[$input_name] = "Max file size is 5MB.";
            } else {
                $check = getimagesize($ftmp);
                if ($check) {
                    if ($check[0] < 800 || $check[1] < 600) {
                        $step2_errors[$input_name] = "Dimensions must be at least 800x600px.";
                    } else {
                        // Valid
                        $ext = pathinfo($fname, PATHINFO_EXTENSION);
                        // Temp Storage Flat Path
                        $temp_path = "uploads/temp_srv_" . $user_id . "_" . $i . "_" . time() . "." . $ext;
                        if (move_uploaded_file($ftmp, $temp_path)) {
                            $temp_images[$i] = $temp_path;
                        } else {
                            $step2_errors[$input_name] = "Upload failed.";
                        }
                    }
                } else {
                    $step2_errors[$input_name] = "Invalid image file.";
                }
            }
        }
    }

    $errors = $step2_errors;

    if (count($temp_images) < 1) {
        $errors['general'] = "Minimum 1 image required in Step 2";
    }

    if (empty($errors)) {

        if (!array_key_exists($main_image_val, $temp_images)) {
            $main_image_val = array_key_first($temp_images);
        }

        $_SESSION['service_data']['images'] = $temp_images;
        $_SESSION['service_data']['main_image_idx'] = $main_image_val;
        header("Location: create-service.php?step=3");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 3 && (isset($_POST['confirm_service']) || isset($_POST['save_draft']))) {

    $service_status = isset($_POST['save_draft']) ? 'Inactive' : 'Active';

    $unique = false;
    $service_id = "";
    while (!$unique) {
        $service_id = mt_rand(1000000000, 9999999999);
        $stmt = $pdo->prepare("SELECT 1 FROM services WHERE service_id = :sid");
        $stmt->execute([':sid' => $service_id]);
        if (!$stmt->fetch())
            $unique = true;
    }

    $sData = $_SESSION['service_data'];
    $images = $sData['images'];              // temp paths keyed by 1..3
    $main_idx = $sData['main_image_idx'];

    $ordered = [];
    if (isset($images[$main_idx])) {
        $ordered[] = $images[$main_idx];
    }
    foreach ($images as $k => $p) {
        if ($k != $main_idx)
            $ordered[] = $p;
    }

    $dir = "uploads/services/" . $service_id . "/";

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $img1 = null;
    $img2 = null;
    $img3 = null;

    for ($i = 0; $i < count($ordered) && $i < 3; $i++) {
        $temp_path = $ordered[$i];
        if (!file_exists($temp_path))
            continue;

        $ext = strtolower(pathinfo($temp_path, PATHINFO_EXTENSION));
        $dest = $dir . sprintf("image_%02d.%s", $i + 1, $ext);

        if (rename($temp_path, $dest)) {
            if ($i == 0)
                $img1 = $dest;
            if ($i == 1)
                $img2 = $dest;
            if ($i == 2)
                $img3 = $dest;
        }
    }

    if (empty($img1)) {
        $errors['general'] = "Image upload failed. Please try again.";
    } else {

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO services
            (service_id, freelancer_id, title, category, subcategory, description, price, delivery_time, revisions_included,
            image_1, image_2, image_3, status, featured_status)
            VALUES
            (:sid, :uid, :title, :cat, :subcat, :desc, :price, :del, :rev, :img1, :img2, :img3, :status, 'No')";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':sid', $service_id);
            $stmt->bindValue(':uid', $user_id);
            $stmt->bindValue(':title', $sData['title']);
            $stmt->bindValue(':cat', $sData['category']);
            $stmt->bindValue(':subcat', $sData['subcategory']);
            $stmt->bindValue(':desc', $sData['description']);
            $stmt->bindValue(':price', $sData['price']);
            $stmt->bindValue(':del', $sData['delivery_time']);
            $stmt->bindValue(':rev', $sData['revisions']);
            $stmt->bindValue(':img1', $img1);
            $stmt->bindValue(':img2', $img2);
            $stmt->bindValue(':img3', $img3);
            $stmt->bindValue(':status', $service_status);
            $stmt->execute();

            $pdo->commit();

            unset($_SESSION['service_data']);
            if ($service_status == 'Inactive') {
                $_SESSION['success_msg'] = "Service saved as draft! ID: " . $service_id;
            } else {
                $_SESSION['success_msg'] = "Service published successfully! ID: " . $service_id;
            }
            header("Location: my-services.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = "Database Error: " . $e->getMessage();
        }
    }

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create New Service</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">
        <?php include("nav.php"); ?>
        <main class="main-content">

            <div class="breadcrumb">
                <a href="index.php">Home</a> > <a href="my-services.php">My Services</a> > <span>Create New
                    Service</span>
            </div>

            <h2 class="container-h1">Create New Service</h2>

            <div class="step-indicator-container">
                <div class="step <?php echo ($step == 1) ? 'active-step' : ''; ?>">1. Overview</div>
                <div class="step <?php echo ($step == 2) ? 'active-step' : ''; ?>">2. Gallery</div>
                <div class="step <?php echo ($step == 3) ? 'active-step' : ''; ?>">3. Publish</div>
            </div>

            <?php if (isset($errors['general']))
                echo '<div class="error-msg mb-20 text-center">' . $errors['general'] . '</div>'; ?>

            <?php if ($step == 1): ?>
                <!-- STEP 1 FORM -->
                <form action="create-service.php?step=1" method="POST" class="service-form">
                    <h3 class="form-section-title">Service Overview</h3>

                    <div class="form-group">
                        <label for="title">Service Title <span class="required">*</span></label>
                        <input type="text" name="title" id="title" class="form-control"
                            value="<?php echo htmlspecialchars($title); ?>" placeholder="I will do something amazing...">
                        <?php if (isset($errors['title']))
                            echo '<span class="error-msg">' . $errors['title'] . '</span>'; ?>
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
                            <?php if (isset($errors['category']))
                                echo '<span class="error-msg">' . $errors['category'] . '</span>'; ?>
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
                            <?php if (isset($errors['subcategory']))
                                echo '<span class="error-msg">' . $errors['subcategory'] . '</span>'; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea name="description" id="description" rows="6"
                            class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                        <?php if (isset($errors['description']))
                            echo '<span class="error-msg">' . $errors['description'] . '</span>'; ?>
                    </div>

                    <h3 class="form-section-title mt-30">Pricing & Delivery</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($) <span class="required">*</span></label>
                            <input type="number" name="price" id="price" class="form-control"
                                value="<?php echo htmlspecialchars($price); ?>" min="5" max="10000" step="0.01">
                            <?php if (isset($errors['price']))
                                echo '<span class="error-msg">' . $errors['price'] . '</span>'; ?>
                        </div>
                        <div class="form-group">
                            <label for="delivery_time">Delivery Time (Days) <span class="required">*</span></label>
                            <input type="number" name="delivery_time" id="delivery_time" class="form-control"
                                value="<?php echo htmlspecialchars($delivery_time); ?>" min="1" max="90">
                            <?php if (isset($errors['delivery_time']))
                                echo '<span class="error-msg">' . $errors['delivery_time'] . '</span>'; ?>
                        </div>
                        <div class="form-group">
                            <label for="revisions">Revisions <span class="required">*</span></label>
                            <input type="number" name="revisions" id="revisions" class="form-control"
                                value="<?php echo htmlspecialchars($revisions); ?>" min="0" max="999">
                            <?php if (isset($errors['revisions']))
                                echo '<span class="error-msg">' . $errors['revisions'] . '</span>'; ?>
                        </div>
                    </div>

                    <input type="submit" class="btn-submit mt-20" value="Save & Continue">
                </form>
            <?php endif; ?>

            <?php if ($step == 2): ?>
                <!-- STEP 2 FORM -->
                <form action="create-service.php?step=2" method="POST" enctype="multipart/form-data" class="service-form">
                    <h3 class="form-section-title">Upload Gallery</h3>
                    <p class="mb-20">Upload 1-3 images. Select the radio button to choose your Main Image.<br>
                        <span class="text-muted">(JPG/PNG, Max 5MB, Min 800x600px)</span>
                    </p>

                    <div class="form-group image-upload-row">
                        <label class="radio-label">
                            <input type="radio" name="main_image" value="1" checked> Main
                        </label>
                        <label>Service Image 1 (required)</label>
                        <input type="file" name="image1" class="form-control" accept="image/jpeg, image/png">
                        <?php if (isset($errors['image1']))
                            echo '<div class="error-msg">' . $errors['image1'] . '</div>'; ?>
                    </div>

                    <div class="form-group image-upload-row mt-10">
                        <label class="radio-label">
                            <input type="radio" name="main_image" value="2"> Main
                        </label>
                        <label>Service Image 2 (optional)</label>
                        <input type="file" name="image2" class="form-control" accept="image/jpeg, image/png">
                        <?php if (isset($errors['image2']))
                            echo '<div class="error-msg">' . $errors['image2'] . '</div>'; ?>
                    </div>

                    <div class="form-group image-upload-row mt-10">
                        <label class="radio-label">
                            <input type="radio" name="main_image" value="3"> Main
                        </label>
                        <label>Service Image 3 (optional)</label>
                        <input type="file" name="image3" class="form-control" accept="image/jpeg, image/png">
                        <?php if (isset($errors['image3']))
                            echo '<div class="error-msg">' . $errors['image3'] . '</div>'; ?>
                    </div>

                    <div class="form-buttons mt-30">
                        <a href="create-service.php?step=1" class="btn-cancel">Back</a>
                        <input type="submit" class="btn-submit" value="Save & Continue">
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($step == 3): ?>
                <!-- STEP 3 FORM -->
                <form action="create-service.php?step=3" method="POST" class="service-form">
                    <h3 class="form-section-title">Review & Publish</h3>

                    <div class="review-section">
                        <h4><?php echo htmlspecialchars($title); ?></h4>
                        <div class="mb-10">
                            <span class="badge"><?php echo htmlspecialchars($category); ?></span>
                            <span class="text-muted"> &gt; <?php echo htmlspecialchars($subcategory); ?></span>
                        </div>

                        <div class="review-gallery">
                            <?php
                            if (isset($_SESSION['service_data']['images'])) {
                                foreach ($_SESSION['service_data']['images'] as $k => $path) {
                                    $cls = ($k == $_SESSION['service_data']['main_image_idx']) ? "review-image-container main" : "review-image-container";
                                    echo '<div class="' . $cls . '"><img src="' . $path . '" class="review-image"></div>';
                                }
                            }
                            ?>
                        </div>

                        <div class="review-description">
                            <?php echo nl2br(htmlspecialchars($description)); ?>
                        </div>

                        <div class="review-pricing">
                            <ul>
                                <li><strong>Price:</strong> $<?php echo htmlspecialchars($price); ?></li>
                                <li><strong>Delivery:</strong> <?php echo htmlspecialchars($delivery_time); ?> Days</li>
                                <li><strong>Revisions:</strong> <?php echo htmlspecialchars($revisions); ?></li>
                            </ul>
                        </div>
                    </div>

                    <a href="create-service.php?step=2" class="btn-cancel">Back</a>
                    <input type="submit" name="save_draft" class="btn-secondary" value="Save as Draft">
                    <input type="submit" name="confirm_service" class="btn-submit" value="Publish Service">
        </div>
        </form>
    <?php endif; ?>

    </main>
    </div>
    <?php include("footer.php"); ?>
</body>

</html>