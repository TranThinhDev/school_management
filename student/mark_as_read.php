<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

if (!isset($_GET['announcement_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit();
}

$announcement_id = intval($_GET['announcement_id']);
$user_id = $_SESSION['user_id'];

try {
    // Kiểm tra xem thông báo có tồn tại và user có quyền xem không
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM announcements a 
        WHERE a.id = ? 
        AND (a.target_audience = 'all' OR a.target_audience = ?)
        AND a.is_active = TRUE
    ");
    
    $user_role = $_SESSION['role'];
    $stmt->execute([$announcement_id, $user_role . 's']);
    $announcement = $stmt->fetch();
    
    if (!$announcement) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Thông báo không tồn tại']);
        exit();
    }
    
    // Đánh dấu đã đọc
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO announcement_views (announcement_id, user_id, viewed_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$announcement_id, $user_id]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Mark as read error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Lỗi server']);
}