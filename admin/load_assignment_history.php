<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin', 'manager']);

// --- Kiểm tra tham số ---
if (!isset($_GET['class_id']) || !isset($_GET['subject_id'])) {
    $_SESSION['error'] = "Thiếu thông tin lớp hoặc môn học!";
    header("Location: assignments.php");
    exit();
}

$class_id = $_GET['class_id'];
$subject_id = $_GET['subject_id'];

// --- Lấy thông tin lớp và môn ---
$stmt = $pdo->prepare("SELECT class_name, grade FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

$stmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$class || !$subject) {
    $_SESSION['error'] = "Không tìm thấy lớp hoặc môn học!";
    header("Location: assignments.php");
    exit();
}

// --- Lấy lịch sử phân công ---
$stmt = $pdo->prepare("
    SELECT ta.*, u.full_name AS teacher_name 
    FROM teaching_assignments ta
    JOIN users u ON ta.teacher_id = u.id
    WHERE ta.class_id = ? AND ta.subject_id = ?
    ORDER BY ta.start_date DESC, ta.school_year DESC, ta.semester
");
$stmt->execute([$class_id, $subject_id]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử Phân công Giảng dạy - <?php echo SITE_NAME; ?></title>
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
                    <h2>
                        <i class="fas fa-history"></i> Lịch sử phân công: 
                        <span class="text-primary"><?php echo htmlspecialchars($class['class_name']); ?></span> -
                        <span class="text-success"><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                    </h2>
                    <a href="assignments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <strong><i class="fas fa-list"></i> Danh sách lịch sử phân công</strong>
                    </div>
                    <div class="card-body">
                        <?php if ($history): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Giáo viên</th>
                                            <th>Học kỳ</th>
                                            <th>Năm học</th>
                                            <th>Ngày bắt đầu</th>
                                            <th>Ngày kết thúc</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                                <td>Học kỳ <?php echo htmlspecialchars($row['semester']); ?></td>
                                                <td><?php echo htmlspecialchars($row['school_year']); ?></td>
                                                <td><?php echo $row['start_date'] ? date('d/m/Y', strtotime($row['start_date'])) : '-'; ?></td>
                                                <td><?php echo $row['end_date'] ? date('d/m/Y', strtotime($row['end_date'])) : '-'; ?></td>
                                                <td>
                                                    <?php if ($row['is_active']): ?>
                                                        <span class="badge bg-success">Đang giảng dạy</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Đã kết thúc</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Chưa có lịch sử phân công cho lớp và môn học này.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
