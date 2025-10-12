<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: teachers.php");
    exit();
}

$teacher_id = $_GET['id'];

// Lấy thông tin giáo viên
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND role = 'teacher'");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    $_SESSION['error'] = "Không tìm thấy giáo viên!";
    header("Location: teachers.php");
    exit();
}

// Lấy danh sách phân công của giáo viên
$stmt = $pdo->prepare("
    SELECT ta.*, c.class_name, s.subject_name 
    FROM teaching_assignments ta 
    JOIN classes c ON ta.class_id = c.id 
    JOIN subjects s ON ta.subject_id = s.id 
    WHERE ta.teacher_id = ? 
    ORDER BY ta.school_year DESC, ta.semester
");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công Giáo viên - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">
                        <i class="fas fa-tasks"></i> Phân công: <?php echo htmlspecialchars($teacher['full_name']); ?>
                    </h1>
                    <a href="teachers.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if ($assignments): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Lớp</th>
                                            <th>Môn học</th>
                                            <th>Học kỳ</th>
                                            <th>Năm học</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                            <td>Học kỳ <?php echo htmlspecialchars($assignment['semester']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['school_year']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Giáo viên chưa được phân công giảng dạy.
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