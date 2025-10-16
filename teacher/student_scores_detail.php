<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];
$student_id = $_GET['student'] ?? null;
$class_id = $_GET['class'] ?? null;

if (!$student_id || !$class_id) {
    header("Location: students.php");
    exit();
}

// Lấy thông tin học sinh
$stmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = "Không tìm thấy học sinh.";
    header("Location: students.php");
    exit();
}

// Bộ lọc
$filter_year = $_GET['year'] ?? '';
$filter_semester = $_GET['semester'] ?? '';
$filter_subject = $_GET['subject'] ?? '';

// Lấy danh sách năm học có dữ liệu
$stmt = $pdo->prepare("SELECT DISTINCT school_year FROM scores WHERE student_id = ? ORDER BY school_year DESC");
$stmt->execute([$student_id]);
$years = $stmt->fetchAll();

// Lấy danh sách môn học
$stmt = $pdo->prepare("
    SELECT DISTINCT s.subject_id, sub.subject_name
    FROM scores s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.student_id = ?
");
$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll();

// Tạo câu SQL động để lọc
$sql = "
    SELECT sub.subject_name, s.semester, s.school_year,
           s.oral_1, s.oral_2, s.oral_3, s.fifteen_min_1, s.fifteen_min_2, s.fifteen_min_3,
           s.forty_five_min_1, s.forty_five_min_2, s.forty_five_min_3, s.mid_term, s.final_term,
           s.average
    FROM scores s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.student_id = ?
";
$params = [$student_id];

if ($filter_year) {
    $sql .= " AND s.school_year = ?";
    $params[] = $filter_year;
}
if ($filter_semester) {
    $sql .= " AND s.semester = ?";
    $params[] = $filter_semester;
}
if ($filter_subject) {
    $sql .= " AND s.subject_id = ?";
    $params[] = $filter_subject;
}

$sql .= " ORDER BY s.school_year DESC, s.semester, sub.subject_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$score_rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng điểm chi tiết - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h3"><i class="fas fa-chart-line"></i> Bảng điểm chi tiết của <?php echo htmlspecialchars($student['full_name']); ?></h1>
                <div class="d-flex justify-content-end gap-2">
                    <a href="export_student_scores.php?student_id=<?php echo $student_id; ?>&class=<?php echo $class_id; ?>" 
                    class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </a>

                </div>
                <a href="students.php?class=<?php echo $class_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách
                </a>
            </div>

            <!-- Bộ lọc -->
            <form method="GET" class="row g-3 mb-4">
                <input type="hidden" name="student" value="<?php echo $student_id; ?>">
                <input type="hidden" name="class" value="<?php echo $class_id; ?>">

                <div class="col-md-3">
                    <label class="form-label">Năm học</label>
                    <select class="form-select" name="year" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y['school_year']; ?>" <?php echo $filter_year == $y['school_year'] ? 'selected' : ''; ?>>
                                <?php echo $y['school_year']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Học kỳ</label>
                    <select class="form-select" name="semester" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <option value="1" <?php echo $filter_semester == '1' ? 'selected' : ''; ?>>Học kỳ I</option>
                        <option value="2" <?php echo $filter_semester == '2' ? 'selected' : ''; ?>>Học kỳ II</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Môn học</label>
                    <select class="form-select" name="subject" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['subject_id']; ?>" <?php echo $filter_subject == $s['subject_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php
            // Tính điểm trung bình theo học kỳ
            $stmt = $pdo->prepare("
                SELECT school_year, semester, AVG(average) as avg_score
                FROM scores
                WHERE student_id = ? AND average IS NOT NULL
                GROUP BY school_year, semester
                ORDER BY school_year, semester
            ");
            $stmt->execute([$student_id]);
            $chart_data = $stmt->fetchAll();
            ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Biểu đồ điểm trung bình theo học kỳ</h5>
                </div>
                <div class="card-body">
                    <canvas id="scoreChart" height="120"></canvas>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            const ctx = document.getElementById('scoreChart');
            const chartData = {
                labels: <?php echo json_encode(array_map(fn($d) => $d['school_year'] . ' - HK' . $d['semester'], $chart_data)); ?>,
                datasets: [{
                    label: 'Điểm trung bình',
                    data: <?php echo json_encode(array_map(fn($d) => round($d['avg_score'], 2), $chart_data)); ?>,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    fill: true,
                    tension: 0.3
                }]
            };
            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10,
                            title: { display: true, text: 'Điểm TB' }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    }
                }
            });
            </script>

            <!-- Bảng điểm -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Chi tiết điểm số</h5>
                </div>
                <div class="card-body">
                    <?php if ($score_rows): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Môn học</th>
                                        <th>Học kỳ</th>
                                        <th>Năm học</th>
                                        <th>Miệng</th>
                                        <th>15'</th>
                                        <th>45'</th>
                                        <th>Thi giữa kỳ</th>
                                        <th>Thi cuối kỳ</th>
                                        <th>Điểm TB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($score_rows as $row): ?>
                                        <tr class="text-center">
                                            <td class="text-start"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                            <td>HK<?php echo $row['semester']; ?></td>
                                            <td><?php echo htmlspecialchars($row['school_year']); ?></td>
                                            <td><?php echo implode('; ', array_filter([$row['oral_1'], $row['oral_2'], $row['oral_3']])); ?></td>
                                            <td><?php echo implode('; ', array_filter([$row['fifteen_min_1'], $row['fifteen_min_2'], $row['fifteen_min_3']])); ?></td>
                                            <td><?php echo implode('; ', array_filter([$row['forty_five_min_1'], $row['forty_five_min_2'], $row['forty_five_min_3']])); ?></td>
                                            <td><?php echo htmlspecialchars($row['mid_term']); ?></td>
                                            <td><?php echo htmlspecialchars($row['final_term']); ?></td>
                                            <td>
                                                <?php if ($row['average'] !== null): ?>
                                                    <span class="badge bg-<?php 
                                                        if ($row['average'] >= 8) echo 'success';
                                                        elseif ($row['average'] >= 6.5) echo 'warning';
                                                        elseif ($row['average'] >= 5) echo 'info';
                                                        else echo 'danger';
                                                    ?> fs-6">
                                                        <?php echo number_format($row['average'], 2); ?>
                                                    </span>
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
                            <i class="fas fa-info-circle"></i> Không có dữ liệu điểm cho học sinh này.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>
