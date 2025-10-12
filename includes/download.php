<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("HTTP/1.0 403 Forbidden");
    exit("Truy cập bị từ chối");
}

if (!isset($_GET['id'])) {
    header("HTTP/1.0 400 Bad Request");
    exit("Thiếu thông tin file");
}

$file_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

try {
    // Kiểm tra quyền truy cập file
    if ($user_role === 'student') {
        // Học sinh chỉ được tải file của lớp mình hoặc file chung (class_id IS NULL)
        $stmt = $pdo->prepare("
            SELECT m.*, cs.class_id as student_class_id 
            FROM materials m
            LEFT JOIN class_students cs ON cs.student_id = ? AND cs.school_year = '2024-2025'
            WHERE m.id = ? AND (m.class_id IS NULL OR m.class_id = cs.class_id)
        ");
        $stmt->execute([$user_id, $file_id]);
    } else {
        // Giáo viên và admin có quyền truy cập rộng hơn
        $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ?");
        $stmt->execute([$file_id]);
    }
    
    $material = $stmt->fetch();
    
    if (!$material) {
        header("HTTP/1.0 404 Not Found");
        exit("File không tồn tại hoặc bạn không có quyền truy cập");
    }
    
    $file_path = $material['file_path'];
    $full_path = __DIR__ . '/../' . $file_path;
    
    // Kiểm tra file có tồn tại không
    if (!file_exists($full_path)) {
        // Ghi log lỗi
        error_log("File not found: " . $full_path . " for material ID: " . $file_id);
        header("HTTP/1.0 404 Not Found");
        exit("File không tồn tại trên server: " . $file_path);
    }
    
    // Thiết lập headers cho download
    $safe_filename = preg_replace('/[^a-zA-Z0-9\.\_\-]/', '_', $material['title']) . '.' . pathinfo($material['file_path'], PATHINFO_EXTENSION);
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($full_path));
    
    // Xóa output buffer và gửi file
    ob_clean();
    flush();
    readfile($full_path);
    exit;
    
} catch (PDOException $e) {
    error_log("Download error: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    exit("Lỗi server khi tải file");
}