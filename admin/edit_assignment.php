<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: assignments.php");
    exit();
}

$assignment_id = $_GET['id'];

// Lấy thông tin phân công
$stmt = $pdo->prepare("
    SELECT ta.*, u.full_name as teacher_name, c.class_name, s.subject_name 
    FROM teaching_assignments ta 
    JOIN users u ON ta.teacher_id = u.id 
    JOIN classes c ON ta.class_id = c.id 
    JOIN subjects s ON ta.subject_id = s.id 
    WHERE ta.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    $_SESSION['error'] = "Không tìm thấy phân công!";
    header("Location: assignments.php");
    exit();
}

// Lấy danh sách giáo viên, lớp, môn học
$teachers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' AND status = 'active'")->fetchAll();
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY grade, class_name")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();

// Xử lý cập nhật
if (isset($_POST['update_assignment'])) {
    $teacher_id = $_POST['teacher_id'];
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    $semester = $_POST['semester'];
    $school_year = $_POST['school_year'];

    try {
        $stmt = $pdo->prepare("UPDATE teaching_assignments SET teacher_id = ?, class_id = ?, subject_id = ?, semester = ?, school_year = ? WHERE id = ?");
        $stmt->execute([$teacher_id, $class_id, $subject_id, $semester, $school_year, $assignment_id]);
        $_SESSION['success'] = "Cập nhật phân công thành công!";
        header("Location: assignments.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Phân công Giảng dạy - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-edit"></i> Sửa Phân công Giảng dạy</h1>
                    <a href="assignments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Giáo viên *</label>
                                <select class="form-select" name="teacher_id" required>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo $assignment['teacher_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lớp *</label>
                                <select class="form-select" name="class_id" required>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo $assignment['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Môn học *</label>
                                <select class="form-select" name="subject_id" required>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>" <?php echo $assignment['subject_id'] == $subject['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Học kỳ *</label>
                                        <select class="form-select" name="semester" required>
                                            <option value="1" <?php echo $assignment['semester'] == '1' ? 'selected' : ''; ?>>Học kỳ 1</option>
                                            <option value="2" <?php echo $assignment['semester'] == '2' ? 'selected' : ''; ?>>Học kỳ 2</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Năm học *</label>
                                        <input type="text" class="form-control" name="school_year" value="<?php echo htmlspecialchars($assignment['school_year']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="assignments.php" class="btn btn-secondary me-md-2">Hủy</a>
                                <button type="submit" name="update_assignment" class="btn btn-primary">Cập nhật</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>