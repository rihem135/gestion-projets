<?php
// Connexion base
$host = '127.0.0.1:3306';  // ou localhost:3306   127.0.0.1:3306
$dbname = 'projet';
$username = 'root';    // You defined this as $user
$password = '';    // Empty password for root (not recommended for production)

try {
    // Changed $username to $user to match your variable
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>