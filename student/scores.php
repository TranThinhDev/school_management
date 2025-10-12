<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Lấy lớp hiện tại
$stmt = $pdo->prepare("SELECT class_id FROM class_students WHERE student_id = ? AND school_year = '2024-2025'");
$stmt->execute([$student_id]);
$class_info = $stmt->fetch();
$class_id = $class_info['class_id'];

// Xử lý filter
$selected_semester = $_GET['semester'] ?? '1';
$selected_subject = $_GET['subject'] ?? '';

// Lấy danh sách môn học
$stmt = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name 
    FROM teaching_assignments ta
    JOIN subjects s ON ta.subject_id = s.id
    WHERE ta.class_id = ?
    ORDER BY s.subject_name
");
$stmt->execute([$class_id]);
$subjects = $stmt->fetchAll();

// Lấy điểm chi tiết
$query = "
    SELECT s.subject_name, sc.*, u.full_name as teacher_name, u.email as teacher_email, u.phone as teacher_phone
    FROM scores sc
    JOIN subjects s ON sc.subject_id = s.id
    JOIN users u ON sc.teacher_id = u.id
    WHERE sc.student_id = ? AND sc.class_id = ? AND sc.semester = ?
";

$params = [$student_id, $class_id, $selected_semester];

if ($selected_subject) {
    $query .= " AND sc.subject_id = ?";
    $params[] = $selected_subject;
}

$query .= " ORDER BY s.subject_name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$scores = $stmt->fetchAll();

// Lấy điểm trung bình các học kỳ để vẽ biểu đồ
$stmt = $pdo->prepare("
    SELECT semester, AVG(average) as avg_score
    FROM scores 
    WHERE student_id = ? AND class_id = ? AND average IS NOT NULL
    GROUP BY semester
    ORDER BY semester
");
$stmt->execute([$student_id, $class_id]);
$semester_averages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem điểm - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-chart-line"></i> Xem điểm</h1>
                </div>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Học kỳ</label>
                                <select class="form-select" name="semester">
                                    <option value="1" <?php echo $selected_semester == '1' ? 'selected' : ''; ?>>Học kỳ 1</option>
                                    <option value="2" <?php echo $selected_semester == '2' ? 'selected' : ''; ?>>Học kỳ 2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Môn học</label>
                                <select class="form-select" name="subject">
                                    <option value="">Tất cả môn</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>" <?php echo $selected_subject == $subject['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Biểu đồ tiến bộ -->
                <?php if ($semester_averages): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar"></i> Tiến bộ học tập qua các học kỳ
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="progressChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bảng điểm chi tiết -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-table"></i> Bảng điểm chi tiết - Học kỳ <?php echo $selected_semester; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($scores): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th rowspan="2">Môn học</th>
                                            <th rowspan="2">Giáo viên</th>
                                            <th colspan="3" class="text-center">Điểm miệng</th>
                                            <th colspan="3" class="text-center">15 phút</th>
                                            <th colspan="3" class="text-center">45 phút</th>
                                            <th rowspan="2">Giữa kỳ</th>
                                            <th rowspan="2">Cuối kỳ</th>
                                            <th rowspan="2">Trung bình</th>
                                            <th rowspan="2">Xếp loại</th>
                                        </tr>
                                        <tr>
                                            <th>M1</th><th>M2</th><th>M3</th>
                                            <th>15p1</th><th>15p2</th><th>15p3</th>
                                            <th>45p1</th><th>45p2</th><th>45p3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($scores as $score): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($score['subject_name']); ?></strong>
                                            </td>
                                            <td>
                                                <div class="teacher-info">
                                                    <strong><?php echo htmlspecialchars($score['teacher_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($score['teacher_email']); ?>
                                                        <br>
                                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($score['teacher_phone']); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <!-- Điểm miệng -->
                                            <td><?php echo $score['oral_1'] ?? '-'; ?></td>
                                            <td><?php echo $score['oral_2'] ?? '-'; ?></td>
                                            <td><?php echo $score['oral_3'] ?? '-'; ?></td>
                                            <!-- 15 phút -->
                                            <td><?php echo $score['fifteen_min_1'] ?? '-'; ?></td>
                                            <td><?php echo $score['fifteen_min_2'] ?? '-'; ?></td>
                                            <td><?php echo $score['fifteen_min_3'] ?? '-'; ?></td>
                                            <!-- 45 phút -->
                                            <td><?php echo $score['forty_five_min_1'] ?? '-'; ?></td>
                                            <td><?php echo $score['forty_five_min_2'] ?? '-'; ?></td>
                                            <td><?php echo $score['forty_five_min_3'] ?? '-'; ?></td>
                                            <!-- Giữa kỳ và cuối kỳ -->
                                            <td><?php echo $score['mid_term'] ?? '-'; ?></td>
                                            <td><?php echo $score['final_term'] ?? '-'; ?></td>
                                            <!-- Trung bình -->
                                            <td>
                                                <span class="badge bg-<?php 
                                                    if ($score['average'] >= 8) echo 'success';
                                                    elseif ($score['average'] >= 6.5) echo 'warning';
                                                    elseif ($score['average'] >= 5) echo 'info';
                                                    else echo 'danger';
                                                ?> fs-6">
                                                    <?php echo $score['average'] ?? 'Chưa có'; ?>
                                                </span>
                                            </td>
                                            <!-- Xếp loại -->
                                            <td>
                                                <?php if ($score['average']): ?>
                                                    <?php
                                                    if ($score['average'] >= 8) echo '<span class="badge bg-success">Giỏi</span>';
                                                    elseif ($score['average'] >= 6.5) echo '<span class="badge bg-warning">Khá</span>';
                                                    elseif ($score['average'] >= 5) echo '<span class="badge bg-info">Trung bình</span>';
                                                    else echo '<span class="badge bg-danger">Yếu</span>';
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
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <?php echo $selected_subject ? 'Chưa có điểm cho môn học này.' : 'Chưa có điểm trong học kỳ này.'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Biểu đồ tiến bộ
        <?php if ($semester_averages): ?>
        const ctx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'HK' + " . $item['semester']; }, $semester_averages)); ?>],
                datasets: [{
                    label: 'Điểm trung bình',
                    data: [<?php echo implode(',', array_column($semester_averages, 'avg_score')); ?>],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        max: 10,
                        title: {
                            display: true,
                            text: 'Điểm trung bình'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Học kỳ'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>