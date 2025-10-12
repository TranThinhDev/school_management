<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = $_GET['id'];

// Lấy thông tin học sinh
$stmt = $pdo->prepare("
    SELECT u.*, cs.class_id 
    FROM users u 
    LEFT JOIN class_students cs ON u.id = cs.student_id 
    WHERE u.id = ? AND u.role = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = "Không tìm thấy học sinh!";
    header("Location: students.php");
    exit();
}

// Lấy danh sách lớp
$stmt = $pdo->query("SELECT * FROM classes ORDER BY grade, class_name");
$classes = $stmt->fetchAll();

// Xử lý cập nhật
if (isset($_POST['update_student'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $class_id = $_POST['class_id'];
    $status = $_POST['status'];

    try {
        $pdo->beginTransaction();
        
        // Cập nhật thông tin user
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, status = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $status, $student_id]);
        
        // Cập nhật lớp học
        if ($class_id) {
            // Xóa phân lớp cũ
            $stmt = $pdo->prepare("DELETE FROM class_students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            // Thêm phân lớp mới
            $stmt = $pdo->prepare("INSERT INTO class_students (student_id, class_id, school_year) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $class_id, '2024-2025']);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Cập nhật thông tin học sinh thành công!";
        header("Location: students.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa thông tin Học sinh - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-edit"></i> Sửa thông tin Học sinh</h1>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tên đăng nhập</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['username']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái</label>
                                        <select class="form-select" name="status" required>
                                            <option value="active" <?php echo $student['status'] == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                                            <option value="inactive" <?php echo $student['status'] == 'inactive' ? 'selected' : ''; ?>>Ngừng hoạt động</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Họ tên *</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Điện thoại</label>
                                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Lớp học</label>
                                <select class="form-select" name="class_id">
                                    <option value="">Chọn lớp</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo $student['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="students.php" class="btn btn-secondary me-md-2">Hủy</a>
                                <button type="submit" name="update_student" class="btn btn-primary">Cập nhật</button>
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