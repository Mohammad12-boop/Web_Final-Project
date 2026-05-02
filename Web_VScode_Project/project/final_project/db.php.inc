<?php
// This file establishes the connection to the database using PDO

    try {
        $connString = "mysql:host=localhost;dbname=freelance_marketplace";
        $db_user = "root";
        $db_pass = "";
        $pdo = new PDO($connString, $db_user, $db_pass);
        $pdo-> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch (PDOException $e) {
        die("Database connection error. Please try again later !!");
    }
?>