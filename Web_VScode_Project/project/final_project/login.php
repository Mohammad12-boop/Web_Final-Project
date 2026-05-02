<?php
require_once "Service.php";
session_start();
require_once "db.php.inc";

$email = "";
$error_msg = "";
$user = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? $_POST['password'] : "";

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, email, password, role, first_name, last_name, profile_photo, status, failed_attempts, lockout_time, UNIX_TIMESTAMP(lockout_time) as lockout_timestamp FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            if (!empty($user['lockout_timestamp']) && $user['lockout_timestamp'] > time()) {
                $remaining = ceil(($user['lockout_timestamp'] - time()) / 60);
                $error_msg = "Account temporarily locked. Please try again in $remaining minutes.";
            } else {

                if ($user['status'] == 'Active' && md5($password) == $user['password']) {

                    $resetStmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE user_id = :uid");
                    $resetStmt->bindValue(':uid', $user['user_id']);
                    $resetStmt->execute();

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['profile_photo'] = $user['profile_photo'];

                    if ($user['role'] == 'Client') {
                        header("Location: browse-services.php");
                    } else {
                        header("Location: browse-services.php");
                    }
                    exit();

                } else {
                    $new_attempts = $user['failed_attempts'] + 1;

                    if ($new_attempts >= 5) {
                        $lockout_time = date("Y-m-d H:i:s", time() + (30 * 60));

                        $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = :fa, lockout_time = :lt WHERE user_id = :uid");
                        $updateStmt->bindValue(':fa', $new_attempts);
                        $updateStmt->bindValue(':lt', $lockout_time);
                        $updateStmt->bindValue(':uid', $user['user_id']);
                        $updateStmt->execute();

                        $error_msg = "Account temporarily locked. Please try again in 30 minutes.";
                    } else {
                        $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = :fa WHERE user_id = :uid");
                        $updateStmt->bindValue(':fa', $new_attempts);
                        $updateStmt->bindValue(':uid', $user['user_id']);
                        $updateStmt->execute();

                        $remaining_attempts = 5 - $new_attempts;
                        if ($remaining_attempts <= 2) {
                            $error_msg = "Invalid email or password. Warning: $remaining_attempts attempts remaining.";
                        } else {
                            $error_msg = "Invalid email or password.";
                        }
                    }
                }
            }
        } else {
            $error_msg = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">

        <?php include("nav.php"); ?>

        <main class="main-content">
            <div class="auth-container">
                <h2 class="container-h1 text-center">Login to Your Account</h2>

                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="success-msg-box">
                        <?php echo $_SESSION['success_msg'];
                        unset($_SESSION['success_msg']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="error-msg login-error-container">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="auth-form text-center">

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="text" name="email" id="email"
                            class="form-control <?php echo !empty($error_msg) ? 'input-error' : ''; ?>"
                            value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" name="password" id="password"
                            class="form-control <?php echo !empty($error_msg) ? 'input-error' : ''; ?>">
                    </div>

                    <div class="form-group text-left">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember_me" value="1"> Remember Me
                        </label>
                    </div>

                    <div class="form-group forgot-password-container">
                        <a href="#" class="primary-link forgot-password-link">Forgot password?</a>
                    </div>

                    <input type="submit" class="btn-submit" value="Login" />

                    <div class="mt-20">
                        <a href="register.php" class="primary-link">Don't have an account? Sign Up</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include("footer.php"); ?>
</body>

</html>