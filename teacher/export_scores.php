<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Xử lý export
if (isset($_GET['export'])) {
    $class_id = $_GET['class_id'];
    $semester = $_GET['semester'];
    $subject_id = $_GET['subject_id'];

    // Kiểm tra xem giáo viên có được phân công dạy không
    $stmt = $pdo->prepare("
        SELECT id FROM teaching_assignments 
        WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND semester = ?
    ");
    $stmt->execute([$teacher_id, $class_id, $subject_id, $semester]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Bạn không có quyền export điểm của lớp này!";
        header("Location: export_scores.php");
        exit();
    }

    // Lấy thông tin lớp và môn học
    $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    // Lấy danh sách học sinh và điểm
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.username,
               s.oral_1, s.oral_2, s.oral_3,
               s.fifteen_min_1, s.fifteen_min_2, s.fifteen_min_3,
               s.forty_five_min_1, s.forty_five_min_2, s.forty_five_min_3,
               s.mid_term, s.final_term, s.average
        FROM class_students cs
        JOIN users u ON cs.student_id = u.id
        LEFT JOIN scores s ON u.id = s.student_id AND s.class_id = ? AND s.subject_id = ? AND s.semester = ?
        WHERE cs.class_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$class_id, $subject_id, $semester, $class_id]);
    $students = $stmt->fetchAll();

    // Tạo file Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Bang_diem_' . $class['class_name'] . '_' . $subject['subject_name'] . '_HK' . $semester . '.xls"');
    header('Cache-Control: max-age=0');

    echo "<html>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "</head>";
    echo "<body>";
    echo "<table border='1'>";
    echo "<tr>";
    echo "<th colspan='15' style='background-color: #007bff; color: white; font-size: 16px;'>BẢNG ĐIỂM MÔN " . strtoupper($subject['subject_name']) . " - LỚP " . $class['class_name'] . " - HỌC KỲ " . $semester . "</th>";
    echo "</tr>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th>STT</th>";
    echo "<th>Họ và tên</th>";
    echo "<th>Mã HS</th>";
    echo "<th>Miệng 1</th>";
    echo "<th>Miệng 2</th>";
    echo "<th>Miệng 3</th>";
    echo "<th>15p 1</th>";
    echo "<th>15p 2</th>";
    echo "<th>15p 3</th>";
    echo "<th>45p 1</th>";
    echo "<th>45p 2</th>";
    echo "<th>45p 3</th>";
    echo "<th>Giữa kỳ</th>";
    echo "<th>Cuối kỳ</th>";
    echo "<th>Điểm TB</th>";
    echo "</tr>";

    $stt = 1;
    foreach ($students as $student) {
        echo "<tr>";
        echo "<td>" . $stt . "</td>";
        echo "<td>" . htmlspecialchars($student['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($student['username']) . "</td>";
        echo "<td>" . ($student['oral_1'] ?? '') . "</td>";
        echo "<td>" . ($student['oral_2'] ?? '') . "</td>";
        echo "<td>" . ($student['oral_3'] ?? '') . "</td>";
        echo "<td>" . ($student['fifteen_min_1'] ?? '') . "</td>";
        echo "<td>" . ($student['fifteen_min_2'] ?? '') . "</td>";
        echo "<td>" . ($student['fifteen_min_3'] ?? '') . "</td>";
        echo "<td>" . ($student['forty_five_min_1'] ?? '') . "</td>";
        echo "<td>" . ($student['forty_five_min_2'] ?? '') . "</td>";
        echo "<td>" . ($student['forty_five_min_3'] ?? '') . "</td>";
        echo "<td>" . ($student['mid_term'] ?? '') . "</td>";
        echo "<td>" . ($student['final_term'] ?? '') . "</td>";
        echo "<td>" . ($student['average'] ?? '') . "</td>";
        echo "</tr>";
        $stt++;
    }

    echo "</table>";
    echo "</body>";
    echo "</html>";
    exit();
}

// Lấy các lớp và môn giáo viên đang dạy (SỬA LỖI)
$stmt = $pdo->prepare("
    SELECT ta.*, c.class_name, s.subject_name 
    FROM teaching_assignments ta 
    JOIN classes c ON ta.class_id = c.id 
    JOIN subjects s ON ta.subject_id = s.id 
    WHERE ta.teacher_id = ? 
    ORDER BY ta.school_year DESC, ta.semester, c.class_name
");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Excel - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-file-excel"></i> Export Excel Bảng điểm</h1>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <?php if (empty($assignments)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Bạn chưa được phân công dạy lớp nào. Vui lòng liên hệ quản trị viên.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Lớp học *</label>
                                    <select class="form-select" name="class_id" required>
                                        <option value="">Chọn lớp</option>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <option value="<?php echo $assignment['class_id']; ?>">
                                                <?php echo htmlspecialchars($assignment['class_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Môn học *</label>
                                    <select class="form-select" name="subject_id" required>
                                        <option value="">Chọn môn học</option>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <option value="<?php echo $assignment['subject_id']; ?>">
                                                <?php echo htmlspecialchars($assignment['subject_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Học kỳ *</label>
                                    <select class="form-select" name="semester" required>
                                        <option value="1">Học kỳ 1</option>
                                        <option value="2">Học kỳ 2</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" name="export" value="1" class="btn btn-success w-100">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>