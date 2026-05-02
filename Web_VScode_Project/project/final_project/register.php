<?php
require_once "Service.php";
session_start();
require_once "db.php.inc";

$errors = [];
$full_name = "";
$first_name = "";
$last_name = "";
$email = "";
$phone = "";
$bio = "";
$city = "";
$role = "";
$age_verification = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $role = isset($_POST['role']) ? $_POST['role'] : "";
    $full_name = isset($_POST['full_name']) ? $_POST['full_name'] : "";

    $name_parts = explode(" ", $full_name);

    $first_name = "";
    $last_name = "";

    if (count($name_parts) > 0) {
        $first_name = $name_parts[0];
        unset($name_parts[0]);
        $last_name = implode(" ", $name_parts);
    }

    $email = isset($_POST['email']) ? $_POST['email'] : "";
    $phone = isset($_POST['phone']) ? $_POST['phone'] : "";
    $city = isset($_POST['city']) ? $_POST['city'] : "";
    $bio = isset($_POST['bio']) ? $_POST['bio'] : "";
    $password = isset($_POST['password']) ? $_POST['password'] : "";
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : "";
    $age_verification = isset($_POST['age_verification']) ? $_POST['age_verification'] : "";

    if (empty($role) || !in_array($role, ['Client', 'Freelancer'])) {
        $errors['role'] = "Please select a valid account type.";
    }

    if (empty($full_name)) {
        $errors['full_name'] = "Full Name is required.";
    } elseif (strlen($full_name) < 2 || strlen($full_name) > 50) {
        $errors['full_name'] = "Full Name must be between 2 and 50 characters.";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $full_name)) {
        $errors['full_name'] = "Full Name must contain only letters and spaces.";
    } elseif (strpos($full_name, ' ') == false) {
        $errors['full_name'] = "Please enter both First and Last Name separated by a space.";
    } elseif (empty($first_name) || empty($last_name)) {
        $errors['full_name'] = "Please enter a valid full name.";
    }

    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/", $email)) {
        $errors['email'] = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $errors['email'] = "Email is already registered.";
        }
    }

    if (empty($phone)) {
        $errors['phone'] = "Phone Number is required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors['phone'] = "Phone Number must be exactly 10 digits.";
    }

    if (empty($city)) {
        $errors['city'] = "Please select a city.";
    }

    if ($role == 'Freelancer' && empty($bio)) {
        $errors['bio'] = "Bio is required for Freelancers.";
    } elseif (strlen($bio) > 500) {
        $errors['bio'] = "Bio must not exceed 500 characters.";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters.";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors['password'] = "Password must contain at least 1 uppercase letter.";
    } elseif (!preg_match("/[a-z]/", $password)) {
        $errors['password'] = "Password must contain at least 1 lowercase letter.";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors['password'] = "Password must contain at least 1 number.";
    } elseif (!preg_match("/[!@#$%^&*]/", $password)) {
        $errors['password'] = "Password must contain at least 1 special character (!@#$%^&*).";
    }

    if ($password != $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (empty($age_verification)) {
        $errors['age_verification'] = "You must confirm you are 18+ years old.";
    }

    if (empty($errors)) {
        $unique = false;
        $user_id = "";
        while (!$unique) {
            $user_id = (string) rand(1000000000, 9999999999);
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = :id");
            $stmt->bindValue(':id', $user_id);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $unique = true;
            }
        }

        $hashed_password = md5($password);
        $rating = ($role == 'Freelancer') ? 0.0 : NULL;

        $sql = "INSERT INTO users (user_id, first_name, last_name, email, password, phone, country, city, role, bio, rating, status, profile_photo, registration_date) 
                        VALUES (:uid, :fname, :lname, :email, :pass, :phone, :country, :city, :role, :bio, :rating, 'Active', NULL, NOW())";

        $country_map = [
            'New York' => 'USA',
            'London' => 'UK',
            'Berlin' => 'Germany',
            'Paris' => 'France',
            'Jerusalem' => 'Palestine',
            'Nablus' => 'Palestine',
            'Ramallah' => 'Palestine',
            'Birzeit' => 'Palestine'
        ];

        $country = isset($country_map[$city]) ? $country_map[$city] : "International";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':uid', $user_id);
            $stmt->bindValue(':fname', $first_name);
            $stmt->bindValue(':lname', $last_name);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':pass', $hashed_password); // MD5 Hashed
            $stmt->bindValue(':phone', $phone);
            $stmt->bindValue(':country', $country);
            $stmt->bindValue(':city', $city);
            $stmt->bindValue(':role', $role);
            $stmt->bindValue(':bio', $bio);
            $stmt->bindValue(':rating', $rating);

            $stmt->execute();

            $_SESSION['success_msg'] = "Account created successfully! Please login.";
            header("refresh:2;url=login.php");
        } catch (PDOException $e) {
            $errors['db'] = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Account - Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body>

    <?php include "header.php"; ?>

    <div class="page-layout">

        <?php include "nav.php"; ?>

        <main class="main-content">
            <div class="auth-container">
                <h2 class="container-h1">Create Your Account</h2>

                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="success-msg-box">
                        <?php echo $_SESSION['success_msg'];
                        unset($_SESSION['success_msg']); ?>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="auth-form">

                    <h3 class="container-h2 form-section-title">Account Type</h3>
                    <div class="form-group">
                        <label>I want to: <span class="required">*</span></label>
                        <div class="form-row">
                            <label class="radio-label"><input type="radio" name="role" value="Client" <?php echo ($role == 'Client') ? 'checked' : ''; ?>> Hire Freelancers (Client)</label>
                            <label class="radio-label"><input type="radio" name="role" value="Freelancer" <?php echo ($role == 'Freelancer') ? 'checked' : ''; ?>> Work as Freelancer</label>
                        </div>
                        <?php if (isset($errors['role']))
                            echo '<span class="error-msg">' . $errors['role'] . '</span>'; ?>
                    </div>

                    <h3 class="container-h2 form-section-title mt-30">Personal Information</h3>

                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" id="full_name"
                            class="form-control <?php echo isset($errors['full_name']) ? 'input-error' : ''; ?>"
                            value="<?php echo htmlspecialchars($full_name); ?>">
                        <?php if (isset($errors['full_name']))
                            echo '<span class="error-msg">' . $errors['full_name'] . '</span>'; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="text" name="email" id="email"
                            class="form-control <?php echo isset($errors['email']) ? 'input-error' : ''; ?>"
                            value="<?php echo htmlspecialchars($email); ?>">
                        <?php if (isset($errors['email']))
                            echo '<span class="error-msg">' . $errors['email'] . '</span>'; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="text" name="phone" id="phone"
                            class="form-control <?php echo isset($errors['phone']) ? 'input-error' : ''; ?>"
                            value="<?php echo htmlspecialchars($phone); ?>">
                        <?php if (isset($errors['phone']))
                            echo '<span class="error-msg">' . $errors['phone'] . '</span>'; ?>
                    </div>

                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <select name="city" id="city"
                            class="form-control <?php echo isset($errors['city']) ? 'input-error' : ''; ?>">
                            <option value="">Select City</option>
                            <option value="New York" <?php echo ($city == 'New York') ? 'selected' : ''; ?>>New York
                            </option>
                            <option value="London" <?php echo ($city == 'London') ? 'selected' : ''; ?>>London</option>
                            <option value="Berlin" <?php echo ($city == 'Berlin') ? 'selected' : ''; ?>>Berlin</option>
                            <option value="Paris" <?php echo ($city == 'Paris') ? 'selected' : ''; ?>>Paris</option>
                            <option value="Jerusalem" <?php echo ($city == 'Jerusalem') ? 'selected' : ''; ?>>Jerusalem
                            </option>
                            <option value="Nablus" <?php echo ($city == 'Nablus') ? 'selected' : ''; ?>>Nablus</option>
                            <option value="Ramallah" <?php echo ($city == 'Ramallah') ? 'selected' : ''; ?>>Ramallah
                            </option>
                            <option value="Birzeit" <?php echo ($city == 'Birzeit') ? 'selected' : ''; ?>>Birzeit</option>
                        </select>
                        <?php if (isset($errors['city']))
                            echo '<span class="error-msg">' . $errors['city'] . '</span>'; ?>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio / About (Required for Freelancers)</label>
                        <textarea name="bio" id="bio" rows="3"
                            class="form-control <?php echo isset($errors['bio']) ? 'input-error' : ''; ?>"><?php echo htmlspecialchars($bio); ?></textarea>
                        <?php if (isset($errors['bio']))
                            echo '<span class="error-msg">' . $errors['bio'] . '</span>'; ?>
                    </div>

                    <h3 class="container-h2 form-section-title mt-30">Account Security</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <input type="password" name="password" id="password"
                                class="form-control <?php echo isset($errors['password']) ? 'input-error' : ''; ?>">
                            <?php if (isset($errors['password']))
                                echo '<span class="error-msg">' . $errors['password'] . '</span>'; ?>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password"
                                class="form-control <?php echo isset($errors['confirm_password']) ? 'input-error' : ''; ?>">
                            <?php if (isset($errors['confirm_password']))
                                echo '<span class="error-msg">' . $errors['confirm_password'] . '</span>'; ?>
                        </div>
                    </div>

                    <div class="form-group mt-20">
                        <label class="checkbox-label">
                            <input type="checkbox" name="age_verification" value="1" <?php echo ($age_verification == "1") ? 'checked' : ''; ?> />
                            I confirm I am 18+ years old <span class="required">*</span>
                        </label>
                        <?php if (isset($errors['age_verification']))
                            echo '<span class="error-msg">' . $errors['age_verification'] . '</span>'; ?>
                    </div>

                    <div class="form-buttons">
                        <input type="submit" class="btn-submit" value="Create Account" />
                        <a href="index.php" class="btn-cancel btn-mt-10 btn-block-center">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include "footer.php"; ?>

</body>

</html>