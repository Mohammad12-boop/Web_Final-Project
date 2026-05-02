

<?php

    require_once "db.php.inc";

    $services = [];

    $sql = "SELECT service_id, title, category, price, status, featured_status, created_date, image_1
            FROM services ORDER BY created_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt -> execute();

    $services = $stmt->fetchAll();

    $currentPage = $_SERVER['PHP_SELF'];
    $pos = strrpos($currentPage, "/") + 1;
    $currentPage = substr($currentPage, $pos);
    function active($page, $currentPage) {
        return ($page == $currentPage) ? ' nav-link-active' : '';
    }
    
?>

<div class="page-layout">

    <!-- ===== NAV ===== -->
    <nav class="navigation">
        <ul class="nav-list">
            <li class="nav-item">
                <a class="nav-link <?php echo active('main.php', $currentPage); ?>" href="main.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo active('browse-services.php', $currentPage); ?>" href="browse-services.php">Browse Services</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo active('login.php', $currentPage); ?>" href="#">Login</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo active('register.php', $currentPage); ?>" href="#">Sign Up</a>
            </li>
        </ul>
    </nav>

    <!-- ===== MAIN ===== -->
    <main class="main-content">

        <?php if ($currentPage == 'main.php'): ?>

            <section class="home">
                <h1 class="home-title">Welcome to Freelance Marketplace</h1>
                <p class="home-text">
                    Discover professional services and find the right freelancer for your next project.
                </p>
                <a class="home-browse-link" href="browse-services.php">Browse Services</a>
            </section>

        <?php else: ?>

            <h1 class="my-services">My Services</h1>

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

                    <?php if (count($services) == 0): ?>
                        <tr>
                            <td colspan="8">No services found !!</td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><img class="service-thumb" src="<?php echo htmlspecialchars($service['image_1']); ?>" alt="<?php echo htmlspecialchars($service['title']) ."_service"; ?>"></td>
                                <td><a class="service-title-link" href="#"><?php echo htmlspecialchars($service['title']); ?></a></td>
                                <td><?php echo htmlspecialchars($service['category']); ?></td>
                                <td><?php echo "\$".$service['price']; ?></td>
                                <td><?php echo htmlspecialchars($service['status']); ?></td>
                                <td>
                                    <?php if ($service['featured_status'] == "Yes"): ?>
                                        <span class="featured-indicator">Featured</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($service['created_date']); ?></td>
                                <td>
                                    <a class="action-btn" href="#">Edit</a>
                                        
                                    <?php if ($service['status'] == "Active"): ?>
                                        <a class="action-btn danger" href="#">Deactivate</a>
                                    <?php else: ?>
                                        <a class="action-btn success" href="#">Activate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>  
    </main>

</div>