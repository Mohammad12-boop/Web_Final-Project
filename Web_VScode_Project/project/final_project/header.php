<?php
require_once "Service.php";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 86400)) {
    session_unset();
    session_destroy();
    echo '<meta http-equiv="refresh" content="0;url=login.php">';
    exit();
}
$_SESSION['last_activity'] = time();
?>

<header class="header">

    <figure class="header-logo">
        <a href="main.php">
            <img src="./uploads/Logo.png" alt="Freelance Marketplace" width="180" height="70" />
        </a>
    </figure>

    <div class="header-search">
        <form action="#" method="get">
            <input type="text" name="search" placeholder="Search services.">
            <input type="submit" value="Search">
        </form>
    </div>

    <div class="header-auth">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php
            $roleClass = ($_SESSION['role'] == 'Client') ? 'client' : 'freelancer';
            $photo = !empty($_SESSION['profile_photo']) ? $_SESSION['profile_photo'] : 'https://via.placeholder.com/60';
            ?>

            <?php if ($_SESSION['role'] == 'Client'): ?>
                <a href="cart.php" class="cart-icon-container">
                    🛒
                    <?php
                    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                    if ($cart_count > 0):
                        ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            <div class="profile-card <?php echo $roleClass; ?>" onclick="window.location.href='profile.php'">
                <img src="<?php echo htmlspecialchars($photo); ?>" alt="Profile" class="profile-img">
                <div class="profile-info">
                    <span
                        class="profile-name"><?php echo htmlspecialchars($_SESSION['first_name'] . " " . $_SESSION['last_name']); ?></span>
                    <span class="profile-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                </div>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        <?php else: ?>
            <a href="login.php">Log In</a>
            <a href="register.php" class="primary">Sign Up</a>
        <?php endif; ?>
    </div>

</header>