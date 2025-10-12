<?php
// Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm kiểm tra role
function checkRole($allowed_roles) {
    if (!isLoggedIn() || !in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: ../login.php');
        exit();
    }
}

// Hàm chuyển hướng
function redirect($url) {
    header("Location: $url");
    exit();
}

// Hàm tính điểm trung bình
function calculateAverage($scores) {
    $total = 0;
    $count = 0;
    
    $weights = [
        'oral' => 1,
        'fifteen_min' => 1,
        'forty_five_min' => 2,
        'mid_term' => 2,
        'final_term' => 3
    ];
    
    foreach ($scores as $type => $value) {
        if ($value !== null && $value !== '') {
            // Xác định loại điểm
            if (strpos($type, 'oral') === 0) $score_type = 'oral';
            elseif (strpos($type, 'fifteen_min') === 0) $score_type = 'fifteen_min';
            elseif (strpos($type, 'forty_five_min') === 0) $score_type = 'forty_five_min';
            elseif (strpos($type, 'mid_term') === 0) $score_type = 'mid_term';
            elseif (strpos($type, 'final_term') === 0) $score_type = 'final_term';
            else $score_type = 'other';
            
            if (isset($weights[$score_type])) {
                $total += $value * $weights[$score_type];
                $count += $weights[$score_type];
            }
        }
    }
    
    return $count > 0 ? round($total / $count, 2) : null;
}

// Hàm upload file
function uploadFile($file, $type) {
    // Đường dẫn tuyệt đối để lưu file
    $uploadDir = __DIR__ . '/../uploads/' . $type . 's/';

    // Nếu thư mục chưa tồn tại thì tạo mới
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Tạo tên file an toàn
    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', basename($file['name']));
    $filePath = $uploadDir . $fileName;

    // Di chuyển file từ tạm sang thư mục đích
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // ✅ Trả về đường dẫn tương đối để web dùng được
        return 'uploads/' . $type . 's/' . $fileName;
    }

    return false;
}


// Hàm hiển thị lỗi
function displayError($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

// Hàm hiển thị thành công
function displaySuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}
?>