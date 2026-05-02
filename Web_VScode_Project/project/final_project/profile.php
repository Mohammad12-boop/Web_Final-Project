<?php
session_start();
require_once "db.php.inc";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$error_msg = "";
$success_msg = "";

$email = "";
$first_name = "";
$last_name = "";
$phone = "";
$country = "";
$city = "";

// Freelancer Fields
$title = "";
$bio = "";
$skills = "";
$years_experience = "";

$sql = "SELECT *, DATE_FORMAT(registration_date, '%M %Y') as formatted_date FROM users WHERE user_id = :uid";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $user_id);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$first_name = $user['first_name'];
$last_name = $user['last_name'];
$email = $user['email'];
$phone = $user['phone'];
$country = $user['country'];
$city = $user['city'];
$title = isset($user['title']) ? $user['title'] : "";
$bio = isset($user['bio']) ? $user['bio'] : "";
$skills = isset($user['skills']) ? $user['skills'] : "";
$years_experience = isset($user['years_experience']) ? $user['years_experience'] : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : "";
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : "";
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : "";
    $country = isset($_POST['country']) ? $_POST['country'] : "";
    $city = isset($_POST['city']) ? trim($_POST['city']) : "";

    if ($role == 'Freelancer') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : "";
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : "";
        $skills = isset($_POST['skills']) ? trim($_POST['skills']) : "";
        $years_experience = isset($_POST['years_experience']) ? trim($_POST['years_experience']) : "";
    }
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : "";
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : "";
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : "";
    if (empty($first_name) || empty($last_name)) {
        $error_msg = "Name fields are required.";
    } elseif (!preg_match("/^[a-zA-Z]{2,50}$/", $first_name)) {
        $error_msg = "First Name must be 2-50 alphabetic characters.";
    } elseif (!preg_match("/^[a-zA-Z]{2,50}$/", $last_name)) {
        $error_msg = "Last Name must be 2-50 alphabetic characters.";
    } elseif (empty($email)) {
        $error_msg = "Email is required.";
    } elseif (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/", $email)) {
        $error_msg = "Invalid email format.";
    } elseif (empty($phone)) {
        $error_msg = "Phone is required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error_msg = "Phone number must be exactly 10 digits.";
    } elseif (empty($country)) {
        $error_msg = "Country is required.";
    } elseif (empty($city)) {
        $error_msg = "City is required.";
    } elseif (strlen($city) < 2 || strlen($city) > 50) {
        $error_msg = "City must be 2-50 characters.";
    }
    if (empty($error_msg) && $role == 'Freelancer') {
        if (empty($title)) {
            $error_msg = "Professional Title is required.";
        } elseif (strlen($title) < 10 || strlen($title) > 100) {
            $error_msg = "Professional Title must be 10-100 characters.";
        } elseif (empty($bio)) {
            $error_msg = "Bio is required.";
        } elseif (strlen($bio) < 50 || strlen($bio) > 500) {
            $error_msg = "Bio must be 50-500 characters.";
        } elseif (strlen($skills) > 200) {
            $error_msg = "Skills must not exceed 200 characters.";
        } elseif ($years_experience != "") {
            if (!preg_match("/^[0-9]+$/", $years_experience) || $years_experience < 0 || $years_experience > 50) {
                $error_msg = "Years of Experience must be an integer between 0 and 50.";
            }
        }
    }
    if (empty($error_msg) && $email != $user['email']) {
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :uid");
        $checkStmt->bindValue(':email', $email);
        $checkStmt->bindValue(':uid', $user_id);
        $checkStmt->execute();
        if ($checkStmt->fetch()) {
            $error_msg = "Email is already taken.";
        }
    }
    $password_update = false;
    if (empty($error_msg) && !empty($new_password)) {
        if (empty($current_password)) {
            $error_msg = "Please enter current password to change it.";
        } elseif (md5($current_password) != $user['password']) {
            $error_msg = "Current password is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $error_msg = "New password must be at least 8 characters.";
        } elseif (!preg_match("/[A-Z]/", $new_password)) {
            $error_msg = "Password must contain at least 1 uppercase letter.";
        } elseif (!preg_match("/[a-z]/", $new_password)) {
            $error_msg = "Password must contain at least 1 lowercase letter.";
        } elseif (!preg_match("/[0-9]/", $new_password)) {
            $error_msg = "Password must contain at least 1 number.";
        } elseif (!preg_match("/[!@#$%^&*]/", $new_password)) {
            $error_msg = "Password must contain at least 1 special character (!@#$%^&*).";
        } elseif ($new_password != $confirm_password) {
            $error_msg = "New passwords do not match.";
        } else {
            $password_update = true;
        }
    }

    $photo_path = $user['profile_photo'];
    if (empty($error_msg) && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $filename = $_FILES['profile_photo']['name'];
        $tmp_name = $_FILES['profile_photo']['tmp_name'];
        $file_size = $_FILES['profile_photo']['size'];
        if (!preg_match("/\.(jpg|jpeg|png)$/i", $filename)) {
            $error_msg = "Only JPG, JPEG, and PNG allowed.";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $error_msg = "Image size must be less than 2MB.";
        } else {
            $check = getimagesize($tmp_name);
            if ($check) {
                $width = $check[0];
                $height = $check[1];
                if ($width < 300 || $height < 300) {
                    $error_msg = "Image dimensions must be at least 300x300px.";
                }
            } else {
                $error_msg = "Invalid image file.";
            }
        }
        if (empty($error_msg)) {
            $target_path = "uploads/profiles/" . $user_id . "/profile_photo.jpg";
            if (move_uploaded_file($tmp_name, $target_path)) {
                $photo_path = $target_path;
            } else {
                $error_msg = "Failed to upload image.";
            }
        }
    }

    if (empty($error_msg)) {
        $sql = "UPDATE users SET first_name = :fname, last_name = :lname, email = :email, phone = :phone, 
                country = :country, city = :city, bio = :bio, profile_photo = :photo";
        if ($role == 'Freelancer') {
            $sql .= ", title = :title, skills = :skills, years_experience = :ye";
        }
        if ($password_update) {
            $sql .= ", password = :pass";
        }
        $sql .= " WHERE user_id = :uid";
        $updateStmt = $pdo->prepare($sql);
        $updateStmt->bindValue(':fname', $first_name);
        $updateStmt->bindValue(':lname', $last_name);
        $updateStmt->bindValue(':email', $email);
        $updateStmt->bindValue(':phone', $phone);
        $updateStmt->bindValue(':country', $country);
        $updateStmt->bindValue(':city', $city);
        $updateStmt->bindValue(':bio', $bio);
        $updateStmt->bindValue(':photo', $photo_path);
        if ($role == 'Freelancer') {
            $updateStmt->bindValue(':title', $title);
            $updateStmt->bindValue(':skills', $skills);
            $ye_val = ($years_experience == "") ? NULL : $years_experience;
            $updateStmt->bindValue(':ye', $ye_val);
        }
        if ($password_update) {
            $updateStmt->bindValue(':pass', md5($new_password));
        }
        $updateStmt->bindValue(':uid', $user_id);
        try {
            $updateStmt->execute();
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['profile_photo'] = $photo_path;
            $success_msg = "Profile updated successfully!";

            $stmt->execute();
            $user = $stmt->fetch();
            $first_name = $user['first_name'];
            $last_name = $user['last_name'];
            $email = $user['email'];
            $phone = $user['phone'];
            $country = $user['country'];
            $city = $user['city'];
            $bio = isset($user['bio']) ? $user['bio'] : "";
            if ($role == 'Freelancer') {
                $title = isset($user['title']) ? $user['title'] : "";
                $skills = isset($user['skills']) ? $user['skills'] : "";
                $years_experience = isset($user['years_experience']) ? $user['years_experience'] : "";
            }

        } catch (PDOException $e) {
            $error_msg = "Database Error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile - Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">

        <?php include("nav.php"); ?>

        <main class="main-content">
            <h2 class="container-h1">My Profile</h2>

            <?php if (!empty($success_msg)): ?>
                <div class="success-msg-box"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="error-msg text-center"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="profile-container">
                <aside class="profile-sidebar">
                    <div class="profile-sidebar-card text-center">
                        <?php
                        $imgSrc = !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : "images/user.png";
                        if (empty($user['profile_photo'])) {
                            $imgSrc = "https://via.placeholder.com/150";
                        }
                        ?>
                        <img src="<?php echo $imgSrc; ?>" alt="Profile Photo" class="profile-large-img">

                        <h3><?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></h3>
                        <p class="role-badge <?php echo ($role == 'Client') ? 'role-client' : 'role-freelancer'; ?>">
                            <?php echo htmlspecialchars($role); ?>
                        </p>

                        <p class="email-text">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>

                        <p class="join-date">Member since <?php echo htmlspecialchars($user['formatted_date']); ?></p>

                        <?php if ($role == 'Freelancer'): ?>
                            <?php
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
                            ?>
                            <div class="freelancer-stats">
                                <hr>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <span class="stat-number"><?php echo $cnt_total; ?></span>
                                        <span class="stat-label">Total Services</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number success-text"><?php echo $cnt_active; ?></span>
                                        <span class="stat-label">Active</span>
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
                        <?php endif; ?>
                    </div>
                </aside>

                <!-- Right Column (70%) -->
                <section class="profile-edit-section">
                    <h3 class="container-h2 form-section-title">Edit Profile</h3>

                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="profile-form">

                        <h4 class="container-h2 form-section-title">Account Information</h4>
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="text" name="email" id="email" class="form-control"
                                value="<?php echo htmlspecialchars($email); ?>">
                        </div>

                        <div class="form-group">
                            <label for="current_password">Current Password (Required to change password)</label>
                            <input type="password" name="current_password" id="current_password" class="form-control">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password (Min 8 chars)</label>
                                <input type="password" name="new_password" id="new_password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password"
                                    class="form-control">
                            </div>
                        </div>

                        <h4 class="container-h2 form-section-title mt-30">Personal Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" id="first_name" class="form-control"
                                    value="<?php echo htmlspecialchars($first_name); ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" id="last_name" class="form-control"
                                    value="<?php echo htmlspecialchars($last_name); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number <span class="required">*</span></label>
                                <input type="text" name="phone" id="phone" class="form-control"
                                    value="<?php echo htmlspecialchars($phone); ?>">
                            </div>

                            <div class="form-group">
                                <label for="country">Country <span class="required">*</span></label>
                                <select name="country" id="country" class="form-control">
                                    <option value="">Select Country</option>
                                    <option value="USA" <?php if ($country == 'USA')
                                        echo 'selected'; ?>>USA</option>
                                    <option value="UK" <?php if ($country == 'UK')
                                        echo 'selected'; ?>>UK</option>
                                    <option value="Germany" <?php if ($country == 'Germany')
                                        echo 'selected'; ?>>Germany
                                    </option>
                                    <option value="France" <?php if ($country == 'France')
                                        echo 'selected'; ?>>France
                                    </option>
                                    <option value="Palestine" <?php if ($country == 'Palestine')
                                        echo 'selected'; ?>>
                                        Palestine</option>
                                    <option value="Other" <?php if ($country == 'Other')
                                        echo 'selected'; ?>>Other
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" name="city" id="city" class="form-control"
                                value="<?php echo htmlspecialchars($city); ?>">
                        </div>

                        <div class="form-group">
                            <label for="profile_photo">Profile Photo (Max 2MB, 300x300+)</label>
                            <input type="file" name="profile_photo" id="profile_photo" class="form-control">
                        </div>

                        <?php if ($role == 'Freelancer'): ?>
                            <h4 class="container-h2 form-section-title mt-30">Professional Information</h4>

                            <div class="form-group">
                                <label for="title">Professional Title <span class="required">*</span></label>
                                <input type="text" name="title" id="title" class="form-control"
                                    value="<?php echo htmlspecialchars($title); ?>" placeholder="e.g. Senior Web Developer">
                            </div>

                            <div class="form-group">
                                <label for="bio">Bio / Description (50-500 chars) <span class="required">*</span></label>
                                <textarea name="bio" id="bio" rows="4"
                                    class="form-control"><?php echo htmlspecialchars($bio); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="skills">Skills (Comma separated, max 200 chars)</label>
                                <input type="text" name="skills" id="skills" class="form-control"
                                    value="<?php echo htmlspecialchars($skills); ?>" placeholder="PHP, MySQL, HTML, CSS">
                            </div>

                            <div class="form-group">
                                <label for="years_experience">Years of Experience (0-50)</label>
                                <input type="number" name="years_experience" id="years_experience" class="form-control"
                                    min="0" max="50" value="<?php echo htmlspecialchars($years_experience); ?>">
                            </div>
                        <?php endif; ?>

                        <input type="submit" class="btn-submit mt-20" value="Save Changes" />
                        <a href="profile.php" class="btn-cancel mt-10 w-100">Cancel</a>
                    </form>
                </section>
            </div>
        </main>
    </div>

    <?php include("footer.php"); ?>
</body>

</html>