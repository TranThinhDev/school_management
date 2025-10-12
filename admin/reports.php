<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Thống kê tổng quan
$total_students = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'active'")->fetch()['total'];
$total_teachers = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND status = 'active'")->fetch()['total'];
$total_classes = $pdo->query("SELECT COUNT(*) as total FROM classes")->fetch()['total'];

// Thống kê điểm theo khối
$grade_stats = $pdo->query("
    SELECT c.grade, 
           AVG(s.average) as avg_score,
           COUNT(DISTINCT s.student_id) as student_count
    FROM scores s
    JOIN classes c ON s.class_id = c.id
    WHERE s.average IS NOT NULL
    GROUP BY c.grade
    ORDER BY c.grade
")->fetchAll();

// Top học sinh
$top_students = $pdo->query("
    SELECT u.full_name, c.class_name, AVG(s.average) as avg_score
    FROM scores s
    JOIN users u ON s.student_id = u.id
    JOIN classes c ON s.class_id = c.id
    WHERE s.average IS NOT NULL
    GROUP BY s.student_id
    HAVING COUNT(s.id) >= 3
    ORDER BY avg_score DESC
    LIMIT 10
")->fetchAll();

// Môn học có điểm cao nhất
$top_subjects = $pdo->query("
    SELECT sj.subject_name, AVG(s.average) as avg_score
    FROM scores s
    JOIN subjects sj ON s.subject_id = sj.id
    WHERE s.average IS NOT NULL
    GROUP BY s.subject_id
    ORDER BY avg_score DESC
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo & Thống kê - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar"></i> Báo cáo & Thống kê</h1>
                    <div>
                        <a href="export_scores.php" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>

                <!-- Thống kê tổng quan -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h3><?php echo $total_students; ?></h3>
                                <p class="mb-0">Học sinh</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h3><?php echo $total_teachers; ?></h3>
                                <p class="mb-0">Giáo viên</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h3><?php echo $total_classes; ?></h3>
                                <p class="mb-0">Lớp học</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h3>
                                    <?php
                                    $avg_total = array_sum(array_column($grade_stats, 'avg_score')) / count($grade_stats);
                                    echo number_format($avg_total, 2);
                                    ?>
                                </h3>
                                <p class="mb-0">Điểm TB toàn trường</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Biểu đồ điểm theo khối -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Điểm trung bình theo khối</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="gradeChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top môn học -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Top môn học có điểm cao nhất</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="subjectChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Top học sinh -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Top 10 học sinh xuất sắc</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Họ tên</th>
                                                <th>Lớp</th>
                                                <th>Điểm TB</th>
                                                <th>Xếp loại</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_students as $index => $student): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-success fs-6">
                                                        <?php echo number_format($student['avg_score'], 2); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($student['avg_score'] >= 8) echo '<span class="badge bg-success">Giỏi</span>';
                                                    elseif ($student['avg_score'] >= 6.5) echo '<span class="badge bg-warning">Khá</span>';
                                                    else echo '<span class="badge bg-info">Trung bình</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê phân loại -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Phân loại học lực</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="performanceChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Biểu đồ điểm theo khối
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        const gradeChart = new Chart(gradeCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'Khối " . $item['grade'] . "'"; }, $grade_stats)); ?>],
                datasets: [{
                    label: 'Điểm trung bình',
                    data: [<?php echo implode(',', array_column($grade_stats, 'avg_score')); ?>],
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        title: {
                            display: true,
                            text: 'Điểm trung bình'
                        }
                    }
                }
            }
        });

        // Biểu đồ top môn học
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        const subjectChart = new Chart(subjectCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['subject_name'] . "'"; }, $top_subjects)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($top_subjects, 'avg_score')); ?>],
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });

        // Biểu đồ phân loại học lực (demo)
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(performanceCtx, {
            type: 'pie',
            data: {
                labels: ['Giỏi', 'Khá', 'Trung bình', 'Yếu'],
                datasets: [{
                    data: [25, 35, 30, 10],
                    backgroundColor: [
                        '#28a745', '#ffc107', '#17a2b8', '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>