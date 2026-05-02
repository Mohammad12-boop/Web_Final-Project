<?php
$currentPage = $_SERVER['PHP_SELF'];
$pos = strrpos($currentPage, "/") + 1;
$currentPage = substr($currentPage, $pos);

function active($page, $currentPage)
{
    return ($page == $currentPage) ? ' nav-link-active' : '';
}
?>


<nav class="navigation">
    <ul class="nav-list">
        <li class="nav-item">
            <a class="nav-link <?php echo active('main.php', $currentPage); ?>" href="main.php">Home</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo active('browse-services.php', $currentPage); ?>"
                href="browse-services.php">Browse Services</a>
        </li>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo active('login.php', $currentPage); ?>" href="login.php">Login</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo active('register.php', $currentPage); ?>" href="register.php">Sign Up</a>
            </li>
        <?php else: ?>
            <?php if ($_SESSION['role'] == 'Client'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo active('cart.php', $currentPage); ?>" href="cart.php">Shopping Cart</a>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'Freelancer'): ?>
                <!-- Freelancer Specific -->
                <li class="nav-item">
                    <a class="nav-link <?php echo active('create-service.php', $currentPage); ?>"
                        href="create-service.php">Create New Service</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo active('my-services.php', $currentPage); ?>" href="my-services.php">My
                        Services</a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link <?php echo active('my-orders.php', $currentPage); ?>" href="my-orders.php">My Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo active('profile.php', $currentPage); ?>" href="profile.php">My Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>