<?php
session_start();

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');

// Cấu hình hệ thống
define('SITE_NAME', 'Hệ thống Quản lý Điểm THPT');
define('UPLOAD_PATH', 'uploads/');

// Kết nối database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
} catch(PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// SỬA: Tạm thời comment các hàm auto-check để tránh lỗi

// Hàm gửi thông báo
function sendNotification($teacher_id, $message) {
    global $pdo;
    error_log("Notification for teacher $teacher_id: $message");
    return true;
}

// Hàm kiểm tra và gửi thông báo hạn nhập điểm
function checkScoreDeadlines() {
    global $pdo;
    // ... code ...
}

// Hàm khóa điểm tự động
function autoLockScores() {
    global $pdo;
    // ... code ...
}

// Tự động kiểm tra hạn nhập điểm
function autoCheckDeadlines() {
    checkScoreDeadlines();
    if (!isset($_SESSION['last_auto_lock']) || $_SESSION['last_auto_lock'] != date('Y-m-d')) {
        autoLockScores();
        $_SESSION['last_auto_lock'] = date('Y-m-d');
    }
}

// Chạy kiểm tra tự động
autoCheckDeadlines();

?>