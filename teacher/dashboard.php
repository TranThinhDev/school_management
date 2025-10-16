<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Lấy thông tin giáo viên
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

// 🆕 Lấy danh sách lớp chủ nhiệm
$stmt = $pdo->prepare("
    SELECT id, class_name, grade, school_year 
    FROM classes 
    WHERE homeroom_teacher_id = ?
    ORDER BY school_year DESC
");
$stmt->execute([$teacher_id]);
$homeroom_classes = $stmt->fetchAll();

// 🆕 Tổng số lớp chủ nhiệm
$total_homeroom = count($homeroom_classes);

// Lấy số lớp đang dạy
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT class_id) as total 
    FROM teaching_assignments 
    WHERE teacher_id = ? AND school_year = '2024-2025'
");
$stmt->execute([$teacher_id]);
$total_classes = $stmt->fetch()['total'];

// Lấy số môn đang dạy
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT subject_id) as total 
    FROM teaching_assignments 
    WHERE teacher_id = ? AND school_year = '2024-2025'
");
$stmt->execute([$teacher_id]);
$total_subjects = $stmt->fetch()['total'];

// Lấy số tài liệu đã upload
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM materials 
    WHERE teacher_id = ?
");
$stmt->execute([$teacher_id]);
$total_materials = $stmt->fetch()['total'];

// Lấy phân công gần đây
$stmt = $pdo->prepare("
    SELECT ta.*, c.class_name, s.subject_name 
    FROM teaching_assignments ta 
    JOIN classes c ON ta.class_id = c.id 
    JOIN subjects s ON ta.subject_id = s.id 
    WHERE ta.teacher_id = ? 
    ORDER BY ta.school_year DESC, ta.semester 
    LIMIT 5
");
$stmt->execute([$teacher_id]);
$recent_assignments = $stmt->fetchAll();

// 🆕 Lấy danh sách lớp chủ nhiệm gần đây
$stmt = $pdo->prepare("
    SELECT id, class_name, grade, school_year 
    FROM classes 
    WHERE homeroom_teacher_id = ?
    ORDER BY school_year DESC 
    LIMIT 5
");
$stmt->execute([$teacher_id]);
$recent_homeroom_classes = $stmt->fetchAll();

// Kiểm tra hạn nhập điểm sắp đến
$current_date = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM scores 
    WHERE teacher_id = ? 
    AND score_entry_deadline >= ? 
    AND score_entry_deadline <= DATE_ADD(?, INTERVAL 7 DAY)
    AND is_locked = FALSE
");
$stmt->execute([$teacher_id, $current_date, $current_date]);
$upcoming_deadlines = $stmt->fetch()['total'];

// Lấy thông báo mới (7 ngày gần đây)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM announcements 
    WHERE (target_audience = 'all' OR target_audience = 'teachers')
    AND is_active = TRUE
    AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmt->execute();
$new_announcements = $stmt->fetch()['total'];

// Lấy điểm số cần nhập (chưa hoàn thành)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT CONCAT(s.class_id, '-', s.subject_id, '-', s.semester)) as total 
    FROM scores s 
    JOIN teaching_assignments ta ON s.class_id = ta.class_id AND s.subject_id = ta.subject_id 
    WHERE ta.teacher_id = ? 
    AND s.is_locked = FALSE
    AND (s.oral_1 IS NULL OR s.fifteen_min_1 IS NULL OR s.forty_five_min_1 IS NULL)
");
$stmt->execute([$teacher_id]);
$pending_scores = $stmt->fetch()['total'];

// Lấy thông báo mới nhất
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name as author_name 
    FROM announcements a 
    JOIN users u ON a.author_id = u.id 
    WHERE (a.target_audience = 'all' OR a.target_audience = 'teachers')
    AND a.is_active = TRUE
    ORDER BY a.created_at DESC 
    LIMIT 3
");
$stmt->execute();
$recent_announcements = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Giáo viên - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
            border-radius: 10px;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .announcement-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .announcement-item:hover {
            background-color: #f8f9fa;
            border-left-color: #0056b3;
        }
        .new-announcement {
            border-left-color: #28a745;
            background-color: #f8fff9;
        }
        .deadline-warning {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { background-color: #fff3cd; }
            50% { background-color: #ffeaa7; }
            100% { background-color: #fff3cd; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Giáo viên -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn"></i>
                                Thông báo
                                <?php if ($new_announcements > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $new_announcements; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php">
                                <i class="fas fa-users"></i>
                                Học sinh
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="scores.php">
                                <i class="fas fa-chart-line"></i>
                                Nhập điểm
                                <?php if ($pending_scores > 0): ?>
                                    <span class="badge bg-warning ms-1"><?php echo $pending_scores; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="materials.php">
                                <i class="fas fa-file-upload"></i>
                                Tài liệu & Video
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="export_scores.php">
                                <i class="fas fa-file-excel"></i>
                                Export Excel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user-cog"></i>
                                Hồ sơ
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Welcome Section -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h2">Xin chào, <?php echo htmlspecialchars($teacher['full_name']); ?>! 👋</h1>
                        <p class="text-muted mb-0">Chào mừng bạn trở lại hệ thống quản lý điểm</p>
                    </div>
                    
                </div>

                <!-- Alert Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <?php if ($upcoming_deadlines > 0): ?>
                            <div class="alert alert-warning deadline-warning d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                <div>
                                    <h5 class="alert-heading mb-1">Hạn nhập điểm sắp đến!</h5>
                                    Bạn có <strong><?php echo $upcoming_deadlines; ?></strong> lớp cần nhập điểm trong 7 ngày tới.
                                    <a href="scores.php" class="alert-link">Kiểm tra ngay</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        

                        <?php if ($pending_scores > 0): ?>
                            <div class="alert alert-danger d-flex align-items-center">
                                <i class="fas fa-clock fa-2x me-3"></i>
                                <div>
                                    <h5 class="alert-heading mb-1">Điểm số chờ nhập!</h5>
                                    Bạn có <strong><?php echo $pending_scores; ?></strong> lớp cần hoàn thành nhập điểm.
                                    <a href="scores.php" class="alert-link">Hoàn thành ngay</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Lớp đang dạy
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_classes; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-school fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Môn đang dạy
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_subjects; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Lớp chủ nhiệm
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_homeroom; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Tài liệu đã upload
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_materials; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-upload fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Thông báo mới
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $new_announcements; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Row -->
                <div class="row">
                    <!-- Phân công giảng dạy -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-tasks"></i> Phân công giảng dạy gần đây
                                </h6>
                                <a href="scores.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_assignments): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Lớp</th>
                                                    <th>Môn học</th>
                                                    <th>Học kỳ</th>
                                                    <th>Năm học</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_assignments as $assignment): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($assignment['class_name']); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info">HK<?php echo htmlspecialchars($assignment['semester']); ?></span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted"><?php echo htmlspecialchars($assignment['school_year']); ?></small>
                                                    </td>
                                                    <td>
                                                        <a href="scores.php?class=<?php echo $assignment['class_id']; ?>&subject=<?php echo $assignment['subject_id']; ?>&semester=<?php echo $assignment['semester']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i> Nhập điểm
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Bạn chưa có phân công giảng dạy nào.</p>
                                        <a href="../logout.php" class="btn btn-primary">Liên hệ quản trị viên</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Thông báo mới nhất -->
                  
                </div>

                <!-- 🆕 Lớp chủ nhiệm gần đây -->
                <?php if ($recent_homeroom_classes): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="fas fa-users"></i> Lớp chủ nhiệm gần đây
                        </h6>
                        <a href="homeroom_classes.php" class="btn btn-sm btn-success">Xem tất cả</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tên lớp</th>
                                        <th>Khối</th>
                                        <th>Năm học</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_homeroom_classes as $class): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                        <td>Khối <?php echo htmlspecialchars($class['grade']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($class['school_year']); ?></small></td>
                                        <td>
                                            <a href="students.php?class=<?php echo $class['id']; ?>" 
                                            class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-users"></i> Danh sách HS
                                            </a>
                                        </td>

                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 10 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 10000);
    </script>
</body>
</html>