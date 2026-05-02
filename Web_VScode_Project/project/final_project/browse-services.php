<?php
// This file is for browsing all the services available on the marketplace

require_once "Service.php";
// Start session and connect to db
session_start();
require_once "db.php.inc";

$page_input = isset($_GET['page']) ? $_GET['page'] : 1;
if (!preg_match('/^[0-9]+$/', $page_input)) {
    $page = 1;
} else {
    $page = (int) $page_input;
}
if ($page < 1)
    $page = 1;

// Pagination logic to show 12 services per page
$limit = 12;
$offset = ($page - 1) * $limit;

// Get search, category, and sort parameters from URL
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$where_clauses = ["s.status = 'Active'"];
$params = [];

if ($search != '') {
    $where_clauses[] = "(s.title LIKE :search OR s.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($category != '') {
    $where_clauses[] = "s.category = :cat";
    $params[':cat'] = $category;
}

// Build the WHERE clause for SQL query
$where_sql = "";
$first = true;
foreach ($where_clauses as $wc) {
    if (!$first) {
        $where_sql .= " AND ";
    }
    $where_sql .= $wc;
    $first = false;
}

$sql_count = "SELECT COUNT(*) FROM services s WHERE $where_sql";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_services = $stmt_count->fetchColumn();

$total_pages = 0;
if ($limit > 0) {
    $total_pages = (int) (($total_services + $limit - 1) / $limit);
}

$start_res = 0;
$end_res = 0;
if ($total_services > 0) {
    $start_res = $offset + 1;
    $end_calc = $offset + $limit;
    if ($end_calc < $total_services) {
        $end_res = $end_calc;
    } else {
        $end_res = $total_services;
    }
}

// Main query to select services with freelancer names
$sql = "SELECT s.service_id, s.title, s.category, s.price, s.image_1, s.created_date, s.featured_status, 
               u.first_name, u.last_name, u.profile_photo 
        FROM services s 
        JOIN users u ON s.freelancer_id = u.user_id 
        WHERE $where_sql";

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY s.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY s.price DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY s.created_date ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY s.created_date DESC";
        break;
}

// Limit
$sql .= " LIMIT :offset, :limit";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$services = $stmt->fetchAll();


// --- FEATURED SERVICES (All Pages) ---
$sql_featured = "SELECT s.service_id, s.title, s.category, s.price, s.image_1, s.featured_status, 
                        u.first_name, u.last_name, u.profile_photo 
                 FROM services s 
                 JOIN users u ON s.freelancer_id = u.user_id 
                 WHERE s.status = 'Active' AND s.featured_status = 'Yes' 
                 ORDER BY s.created_date DESC 
                 LIMIT 4";
$stmt_featured = $pdo->prepare($sql_featured);
$stmt_featured->execute();
$featured_services = $stmt_featured->fetchAll();


// Categories
$categories = [
    "Web Development",
    "Graphic Design",
    "Writing & Translation",
    "Digital Marketing",
    "Video & Animation",
    "Music & Audio",
    "Business Consulting",
    "Tutoring & Education"
];

// Helper to manually build URL
function build_filter_url($target_page, $s_search, $s_cat, $s_sort)
{
    $url = "?";
    $params_added = false;

    if ($target_page > 1) {
        $url .= "page=" . $target_page;
        $params_added = true;
    }

    if ($s_search != '') {
        if ($params_added)
            $url .= "&";
        $url .= "search=" . $s_search;
        $params_added = true;
    }

    if ($s_cat != '') {
        if ($params_added)
            $url .= "&";
        $url .= "category=" . $s_cat;
        $params_added = true;
    }

    if ($s_sort != 'newest' && $s_sort != '') {
        if ($params_added)
            $url .= "&";
        $url .= "sort=" . $s_sort;
    }

    return $url;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Browse Services - Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">

        <?php include("nav.php"); ?>

        <main class="main-content">

            <h1 class="container-h1">Browse Services</h1>

            <!-- SEARCH & FILTER BAR -->
            <div class="search-filter-container">
                <form action="browse-services.php" method="GET" class="search-form">

                    <input type="text" name="search" class="form-control search-input" placeholder="Search services..."
                        value="<?php echo htmlspecialchars($search); ?>">

                    <select name="category" class="form-control filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($category == $cat)
                                   echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="sort" class="form-control filter-select">
                        <option value="newest" <?php if ($sort == 'newest')
                            echo 'selected'; ?>>Newest First</option>
                        <option value="oldest" <?php if ($sort == 'oldest')
                            echo 'selected'; ?>>Oldest First</option>
                        <option value="price_asc" <?php if ($sort == 'price_asc')
                            echo 'selected'; ?>>Price: Low to High
                        </option>
                        <option value="price_desc" <?php if ($sort == 'price_desc')
                            echo 'selected'; ?>>Price: High to Low
                        </option>
                    </select>

                    <input type="submit" class="btn-search" value="Search" />

                    <?php if ($search != '' || $category != '' || $sort != 'newest'): ?>
                        <a href="browse-services.php" class="reset-link">Show All Services</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- SEARCH METADATA -->
            <?php if ($search != '' || $category != '' || $total_services > 0): ?>
                <div class="search-meta">
                    Found <?php echo $total_services; ?> results
                    <?php
                    if ($total_services > 0)
                        echo "(Showing $start_res-$end_res)";
                    ?>
                    <?php if ($search != ''): ?>
                        for '<b><?php echo htmlspecialchars($search); ?></b>'
                    <?php endif; ?>
                    <?php if ($category != ''): ?>
                        in category '<b><?php echo htmlspecialchars($category); ?></b>'
                        <?php
                        $link_no_cat = build_filter_url(1, $search, '', $sort);
                        ?>
                        <a href="browse-services.php<?php echo $link_no_cat; ?>" class="meta-link">Show All Categories</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- FEATURED SERVICES SECTION -->
            <?php if (count($featured_services) > 0): ?>
                <h2 class="container-h2 featured-heading">Featured Services</h2>
                <div class="services-grid featured-grid">
                    <?php foreach ($featured_services as $svc): ?>
                        <div class="service-card featured-card">
                            <div class="card-img-container">
                                <?php
                                $img = ($svc['image_1'] != '') ? $svc['image_1'] : "https://via.placeholder.com/300x200";
                                ?>
                                <a href="service-detail.php?id=<?php echo $svc['service_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Service" class="card-img">
                                </a>
                                <span class="featured-badge">Featured</span>
                            </div>

                            <div class="card-body">
                                <div class="card-category">
                                    <?php echo htmlspecialchars($svc['category']); ?>
                                </div>
                                <h3 class="card-title">
                                    <a href="service-detail.php?id=<?php echo $svc['service_id']; ?>">
                                        <?php echo htmlspecialchars($svc['title']); ?>
                                    </a>
                                </h3>
                                <div class="card-meta">
                                    <div class="card-freelancer">
                                        <?php
                                        $f_photo = ($svc['profile_photo'] != '') ? $svc['profile_photo'] : "https://via.placeholder.com/30";
                                        ?>
                                        <img src="<?php echo htmlspecialchars($f_photo); ?>" class="freelancer-mini-photo"
                                            alt="User">
                                        <span><?php
                                        echo htmlspecialchars($svc['first_name'] . ' ' . $svc['last_name'][0] . '.');
                                        ?></span>
                                    </div>
                                    <div class="card-price">
                                        Starting at $<?php echo number_format($svc['price'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr class="section-divider">
            <?php endif; ?>

            <h2 class="container-h2">All Services</h2>

            <!-- SERVICES GRID -->
            <div class="services-grid">
                <?php if (count($services) > 0): ?>
                    <?php foreach ($services as $svc): ?>
                        <div class="service-card">
                            <div class="card-img-container">
                                <?php
                                $img = ($svc['image_1'] != '') ? $svc['image_1'] : "https://via.placeholder.com/300x200";
                                ?>
                                <a href="service-detail.php?id=<?php echo $svc['service_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Service" class="card-img">
                                </a>
                                <?php if ($svc['featured_status'] === 'Yes'): ?>
                                    <span class="featured-badge">Featured</span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <div class="card-category">
                                    <?php echo htmlspecialchars($svc['category']); ?>
                                </div>
                                <h3 class="card-title">
                                    <a href="service-detail.php?id=<?php echo $svc['service_id']; ?>">
                                        <?php echo htmlspecialchars($svc['title']); ?>
                                    </a>
                                </h3>
                                <div class="card-meta">
                                    <div class="card-freelancer">
                                        <?php
                                        $f_photo = ($svc['profile_photo'] != '') ? $svc['profile_photo'] : "https://via.placeholder.com/30";
                                        ?>
                                        <img src="<?php echo htmlspecialchars($f_photo); ?>" class="freelancer-mini-photo"
                                            alt="User">

                                        <span>By
                                            <?php echo htmlspecialchars($svc['first_name'] . ' ' . $svc['last_name'][0] . '.'); ?></span>
                                    </div>
                                    <div class="card-price">
                                        Starting at $<?php echo number_format($svc['price'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p>No services found matching your criteria.</p>
                        <?php if ($search != '' || $category != ''): ?>
                            <a href="browse-services.php" class="btn-submit btn-view-all">View All Services</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">

                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <a href="browse-services.php<?php echo build_filter_url($page - 1, $search, $category, $sort); ?>"
                                class="page-link">&lt; Previous</a>
                        <?php else: ?>
                            <span class="page-link disabled-link"> &lt; Previous</span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php
                            $active_class = ($page == $i) ? 'active' : '';
                            ?>
                            <a href="browse-services.php<?php echo build_filter_url($i, $search, $category, $sort); ?>"
                                class="page-link <?php echo $active_class; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <a href="browse-services.php<?php echo build_filter_url($page + 1, $search, $category, $sort); ?>"
                                class="page-link">Next &gt;</a>
                        <?php else: ?>
                            <span class="page-link disabled-link"> Next &gt;</span>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <?php include("footer.php"); ?>
</body>

</html>