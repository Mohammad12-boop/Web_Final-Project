<?php

// Database connection settings (PDO)

    try {
    
        $connString = "mysql:host=localhost;dbname=travel_company";
        $db_user = "root";
        $db_pass = "";
        
        // Create PDO connection to MySQL database
        $pdo = new PDO($connString, $db_user, $db_pass);
            
        // Throw exceptions on database errors (for try/catch handling)
        $pdo-> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }catch (PDOException $e) {
        // Stop execution with a generic message (do not expose DB details)
        die("Database connection error. Please try again later !!");
    }
?>