<?php
// config.php
session_start();

$host = "localhost";
$db   = "school_list_final";
$user = "root";
$pass = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: /school_list/auth/login.php");
        exit;
    }
}