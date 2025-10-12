<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: classes.php");
    exit();
}

$class_id = $_GET['id'];

// Lấy thông tin lớp
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class) {
    $_SESSION['error'] = "Không tìm thấy lớp học!";
    header("Location: classes.php");
    exit();
}

// Lấy danh sách giáo viên
$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' AND status = 'active'");
$teachers = $stmt->fetchAll();

// Xử lý cập nhật
if (isset($_POST['update_class'])) {
    $class_name = $_POST['class_name'];
    $grade = $_POST['grade'];
    $homeroom_teacher_id = $_POST['homeroom_teacher_id'];
    $school_year = $_POST['school_year'];

    try {
        $stmt = $pdo->prepare("UPDATE classes SET class_name = ?, grade = ?, homeroom_teacher_id = ?, school_year = ? WHERE id = ?");
        $stmt->execute([$class_name, $grade, $homeroom_teacher_id, $school_year, $class_id]);
        $_SESSION['success'] = "Cập nhật lớp học thành công!";
        header("Location: classes.php");
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
    <title>Sửa thông tin Lớp học - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-edit"></i> Sửa thông tin Lớp học</h1>
                    <a href="classes.php" class="btn btn-secondary">
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
                                <label class="form-label">Tên lớp *</label>
                                <input type="text" class="form-control" name="class_name" value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Khối *</label>
                                <select class="form-select" name="grade" required>
                                    <option value="10" <?php echo $class['grade'] == '10' ? 'selected' : ''; ?>>Khối 10</option>
                                    <option value="11" <?php echo $class['grade'] == '11' ? 'selected' : ''; ?>>Khối 11</option>
                                    <option value="12" <?php echo $class['grade'] == '12' ? 'selected' : ''; ?>>Khối 12</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Giáo viên chủ nhiệm</label>
                                <select class="form-select" name="homeroom_teacher_id">
                                    <option value="">Chọn giáo viên</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo $class['homeroom_teacher_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Năm học *</label>
                                <input type="text" class="form-control" name="school_year" value="<?php echo htmlspecialchars($class['school_year']); ?>" required>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="classes.php" class="btn btn-secondary me-md-2">Hủy</a>
                                <button type="submit" name="update_class" class="btn btn-primary">Cập nhật</button>
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