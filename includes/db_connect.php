<?php
// Database configuration
$host = "localhost";
$db_name = "kstu_clearance_db";
$username = "root";
$password = "";

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set character set to UTF-8
    $conn->exec("set names utf8");
    
} catch(PDOException $e) {
    // Show error if connection fails
    die("Connection failed: " . $e->getMessage());
}
?>