<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['teacher']);

header('Content-Type: application/json');

if (!isset($_GET['announcement_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID thông báo']);
    exit();
}

$announcement_id = $_GET['announcement_id'];
$teacher_id = $_SESSION['user_id'];

try {
    // Đánh dấu đã đọc
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO announcement_views (announcement_id, user_id) 
        VALUES (?, ?)
    ");
    $stmt->execute([$announcement_id, $teacher_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}