<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: announcements.php");
    exit();
}

$announcement_id = $_GET['id'];

// Lấy thông tin thông báo
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$announcement_id]);
$announcement = $stmt->fetch();

if (!$announcement) {
    $_SESSION['error'] = "Không tìm thấy thông báo!";
    header("Location: announcements.php");
    exit();
}

// Xử lý cập nhật
if (isset($_POST['update_announcement'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $target_audience = $_POST['target_audience'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, target_audience = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$title, $content, $target_audience, $is_active, $announcement_id]);
        $_SESSION['success'] = "Cập nhật thông báo thành công!";
        header("Location: announcements.php");
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
    <title>Sửa Thông báo - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-edit"></i> Sửa Thông báo</h1>
                    <a href="announcements.php" class="btn btn-secondary">
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
                                <label class="form-label">Tiêu đề *</label>
                                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nội dung *</label>
                                <textarea class="form-control" name="content" rows="6" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Đối tượng *</label>
                                <select class="form-select" name="target_audience" required>
                                    <option value="all" <?php echo $announcement['target_audience'] == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                                    <option value="teachers" <?php echo $announcement['target_audience'] == 'teachers' ? 'selected' : ''; ?>>Chỉ giáo viên</option>
                                    <option value="students" <?php echo $announcement['target_audience'] == 'students' ? 'selected' : ''; ?>>Chỉ học sinh</option>
                                </select>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" <?php echo $announcement['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Hiển thị thông báo</label>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="announcements.php" class="btn btn-secondary me-md-2">Hủy</a>
                                <button type="submit" name="update_announcement" class="btn btn-primary">Cập nhật</button>
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