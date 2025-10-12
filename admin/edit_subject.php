<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: subjects.php");
    exit();
}

$subject_id = $_GET['id'];

// Lấy thông tin môn học
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    $_SESSION['error'] = "Không tìm thấy môn học!";
    header("Location: subjects.php");
    exit();
}

// Xử lý cập nhật
if (isset($_POST['update_subject'])) {
    $subject_name = $_POST['subject_name'];
    $subject_code = $_POST['subject_code'];

    try {
        $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ?, subject_code = ? WHERE id = ?");
        $stmt->execute([$subject_name, $subject_code, $subject_id]);
        $_SESSION['success'] = "Cập nhật môn học thành công!";
        header("Location: subjects.php");
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
    <title>Sửa thông tin Môn học - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-edit"></i> Sửa thông tin Môn học</h1>
                    <a href="subjects.php" class="btn btn-secondary">
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
                                <label class="form-label">Tên môn học *</label>
                                <input type="text" class="form-control" name="subject_name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mã môn học *</label>
                                <input type="text" class="form-control" name="subject_code" value="<?php echo htmlspecialchars($subject['subject_code']); ?>" required>
                                <div class="form-text">Mã duy nhất để nhận diện môn học</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="subjects.php" class="btn btn-secondary me-md-2">Hủy</a>
                                <button type="submit" name="update_subject" class="btn btn-primary">Cập nhật</button>
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