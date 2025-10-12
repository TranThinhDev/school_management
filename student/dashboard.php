<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Lấy thông tin học sinh
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Lấy lớp hiện tại của học sinh
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as teacher_name 
    FROM class_students cs 
    JOIN classes c ON cs.class_id = c.id 
    LEFT JOIN users u ON c.homeroom_teacher_id = u.id 
    WHERE cs.student_id = ? AND cs.school_year = '2024-2025'
");
$stmt->execute([$student_id]);
$current_class = $stmt->fetch();

// Lấy điểm trung bình các môn học kỳ gần nhất
$stmt = $pdo->prepare("
    SELECT s.subject_name, sc.average, sc.semester, sc.school_year, sc.teacher_id,
           u.full_name as teacher_name, u.email as teacher_email, u.phone as teacher_phone
    FROM scores sc
    JOIN subjects s ON sc.subject_id = s.id
    JOIN users u ON sc.teacher_id = u.id
    WHERE sc.student_id = ? AND sc.school_year = '2024-2025'
    ORDER BY sc.semester DESC, s.subject_name
    LIMIT 6
");
$stmt->execute([$student_id]);
$recent_scores = $stmt->fetchAll();

// Lấy thông báo mới nhất
$stmt = $pdo->query("
    SELECT a.*, u.full_name as author_name 
    FROM announcements a 
    JOIN users u ON a.author_id = u.id 
    WHERE (a.target_audience = 'all' OR a.target_audience = 'students')
    AND a.is_active = TRUE 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$recent_announcements = $stmt->fetchAll();

// Đếm tổng số thông báo chưa đọc
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

// Tính điểm trung bình chung và thống kê
$overall_average = 0;
$total_subjects = 0;
$excellent_count = 0;
$good_count = 0;
$average_count = 0;
$weak_count = 0;

if ($recent_scores) {
    $total = 0;
    $count = 0;
    foreach ($recent_scores as $score) {
        if ($score['average'] !== null) {
            $total += $score['average'];
            $count++;
            $total_subjects++;
            
            // Phân loại điểm
            if ($score['average'] >= 8) {
                $excellent_count++;
            } elseif ($score['average'] >= 6.5) {
                $good_count++;
            } elseif ($score['average'] >= 5) {
                $average_count++;
            } else {
                $weak_count++;
            }
        }
    }
    $overall_average = $count > 0 ? round($total / $count, 2) : 0;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Học sinh - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .teacher-info-popup {
            cursor: pointer;
        }
        .stats-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 10px;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .score-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .subject-badge {
            font-size: 0.8em;
            padding: 0.3em 0.6em;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Xin chào, <?php echo htmlspecialchars($student['full_name']); ?>!</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-graduation-cap"></i>
                            <?php echo $current_class ? htmlspecialchars($current_class['class_name']) : 'Chưa có lớp'; ?>
                        </span>
                    </div>
                </div>

                <!-- Thông tin lớp học -->
                <?php if ($current_class): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body bg-light">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <h6><i class="fas fa-school text-primary"></i> Lớp</h6>
                                            <h4 class="text-primary"><?php echo htmlspecialchars($current_class['class_name']); ?></h4>
                                        </div>
                                        <div class="col-md-3">
                                            <h6><i class="fas fa-user-tie text-success"></i> GVCN</h6>
                                            <h5 class="text-success"><?php echo htmlspecialchars($current_class['teacher_name'] ?? 'Chưa có'); ?></h5>
                                        </div>
                                        <div class="col-md-3">
                                            <h6><i class="fas fa-calendar text-info"></i> Năm học</h6>
                                            <h5 class="text-info"><?php echo htmlspecialchars($current_class['school_year']); ?></h5>
                                        </div>
                                        <div class="col-md-3">
                                            <h6><i class="fas fa-chart-line text-warning"></i> Điểm TB</h6>
                                            <h4 class="text-warning"><?php echo $overall_average; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Điểm số và Thống kê -->
                <div class="row mb-4">
                    <!-- Điểm số gần đây - Chiếm 8 cột -->
                    <div class="col-md-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line"></i> Điểm số gần đây
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_scores): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover score-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Môn học</th>
                                                    <th>Giáo viên</th>
                                                    <th>Học kỳ</th>
                                                    <th>Điểm TB</th>
                                                    <th>Xếp loại</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_scores as $score): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($score['subject_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="teacher-info-popup" 
                                                             data-bs-toggle="tooltip" 
                                                             title="Email: <?php echo htmlspecialchars($score['teacher_email'] ?? 'Chưa có'); ?>&#10;Điện thoại: <?php echo htmlspecialchars($score['teacher_phone'] ?? 'Chưa có'); ?>">
                                                            <i class="fas fa-user-tie text-success"></i>
                                                            <?php echo htmlspecialchars($score['teacher_name']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary subject-badge">HK<?php echo htmlspecialchars($score['semester']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo ($score['average'] >= 8) ? 'success' : 
                                                                (($score['average'] >= 6.5) ? 'warning' : 
                                                                (($score['average'] >= 5) ? 'info' : 'danger')); 
                                                        ?> fs-6">
                                                            <?php echo $score['average'] ?? 'Chưa có'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($score['average']): ?>
                                                            <?php
                                                            if ($score['average'] >= 8) echo '<span class="badge bg-success subject-badge">Giỏi</span>';
                                                            elseif ($score['average'] >= 6.5) echo '<span class="badge bg-warning subject-badge">Khá</span>';
                                                            elseif ($score['average'] >= 5) echo '<span class="badge bg-info subject-badge">Trung bình</span>';
                                                            else echo '<span class="badge bg-danger subject-badge">Yếu</span>';
                                                            ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <a href="scores.php" class="btn btn-primary">
                                            <i class="fas fa-list"></i> Xem tất cả điểm
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Chưa có điểm số nào</h5>
                                        <p class="text-muted">Hãy liên hệ với giáo viên để biết thêm thông tin</p>
                                        <a href="scores.php" class="btn btn-primary">Xem điểm</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê học tập - Chiếm 4 cột -->
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie"></i> Thống kê học tập
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <!-- Tổng môn có điểm -->
                                    <div class="col-6 mb-4">
                                        <div class="stats-card bg-primary text-white p-3 rounded">
                                            <div class="stats-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <h3 class="mb-1"><?php echo $total_subjects; ?></h3>
                                            <small>Môn có điểm</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Môn giỏi -->
                                    <div class="col-6 mb-4">
                                        <div class="stats-card bg-success text-white p-3 rounded">
                                            <div class="stats-icon">
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <h3 class="mb-1"><?php echo $excellent_count; ?></h3>
                                            <small>Môn giỏi</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Môn khá -->
                                    <div class="col-6 mb-4">
                                        <div class="stats-card bg-warning text-white p-3 rounded">
                                            <div class="stats-icon">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <h3 class="mb-1"><?php echo $good_count; ?></h3>
                                            <small>Môn khá</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Điểm trung bình -->
                                    <div class="col-6 mb-4">
                                        <div class="stats-card bg-secondary text-white p-3 rounded">
                                            <div class="stats-icon">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                            <h3 class="mb-1"><?php echo $overall_average; ?></h3>
                                            <small>Điểm TB</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Chi tiết thống kê -->
                                <div class="mt-4 pt-3 border-top">
                                    <h6 class="text-center mb-3">Phân loại chi tiết</h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="p-2">
                                                <div class="text-info">
                                                    <i class="fas fa-circle fa-sm"></i>
                                                </div>
                                                <small class="d-block">Trung bình</small>
                                                <strong><?php echo $average_count; ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="p-2">
                                                <div class="text-danger">
                                                    <i class="fas fa-circle fa-sm"></i>
                                                </div>
                                                <small class="d-block">Yếu</small>
                                                <strong><?php echo $weak_count; ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="p-2">
                                                <div class="text-success">
                                                    <i class="fas fa-check-circle fa-sm"></i>
                                                </div>
                                                <small class="d-block">Hoàn thành</small>
                                                <strong><?php echo $total_subjects; ?>/<?php echo $total_subjects; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thông báo mới nhất -->
                
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Khởi tạo tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>