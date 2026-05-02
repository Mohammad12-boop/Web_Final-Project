<?php require_once "Service.php";
session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Home - Freelance Marketplace</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">

    <?php include("header.php"); ?>

    <div class="page-layout">
        <?php include("nav.php"); ?>

        <main class="main-content">
            <section class="home">
                <h1 class="home-title">Welcome to Freelance Marketplace</h1>
                <p class="home-text">
                    Discover professional services and find the right freelancer for your next project.
                </p>
                <a class="home-browse-link" href="browse-services.php">Browse Services</a>
            </section>
        </main>
    </div>

    <?php include("footer.php"); ?>

</body>

</html>