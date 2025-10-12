<?php
require_once 'config.php';
require_once 'functions.php';

// Xử lý đăng nhập
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        switch ($user['role']) {
            case 'admin':
                redirect('admin/dashboard.php');
                break;
            case 'teacher':
                redirect('teacher/dashboard.php');
                break;
            case 'student':
                redirect('student/dashboard.php');
                break;
        }
    } else {
        $_SESSION['error'] = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}
?>