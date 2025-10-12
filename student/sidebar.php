<?php
// Kiểm tra xem user có phải student không
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Đếm tổng số thông báo chưa đọc cho học sinh
require_once '../includes/config.php';
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM announcements a
    LEFT JOIN announcement_views av ON a.id = av.announcement_id AND av.user_id = ?
    WHERE (a.target_audience = 'all' OR a.target_audience = 'students')
    AND a.is_active = TRUE
    AND av.id IS NULL
");
$stmt->execute([$student_id]);
$total_unread = $stmt->fetch()['total'];
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                    <i class="fas fa-bullhorn"></i>
                    Thông báo
                    <?php if ($total_unread > 0): ?>
                        <span class="badge bg-danger rounded-pill float-end">
                            <?php echo $total_unread; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'scores.php' ? 'active' : ''; ?>" href="scores.php">
                    <i class="fas fa-chart-line"></i>
                    Xem điểm
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'materials.php' ? 'active' : ''; ?>" href="materials.php">
                    <i class="fas fa-book-open"></i>
                    Tài liệu học tập
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user-cog"></i>
                    Hồ sơ
                </a>
            </li>
        </ul>
    </div>
</nav>