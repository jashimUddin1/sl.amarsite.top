<?php
// config.php
session_start();

$host = "localhost";
$db   = "amarsite_school_notes";
$user = "root";
$pass = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


/* ================= BASE URL ================= */
define('BASE_URL', '/school_list');

function base_url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/* ================= AUTH GUARD ================= */
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: " . base_url('auth/login.php'));
        exit;
    }
}