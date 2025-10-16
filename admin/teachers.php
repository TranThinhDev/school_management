<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Xử lý thêm giáo viên
if (isset($_POST['add_teacher'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; // Lưu mật khẩu dạng plain text
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, 'teacher', ?, ?, ?)");
        $stmt->execute([$username, $password, $full_name, $email, $phone]);
        $_SESSION['success'] = "Thêm giáo viên thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa giáo viên
if (isset($_GET['delete'])) {
    $teacher_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$teacher_id]);
        $_SESSION['success'] = "Xóa giáo viên thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: teachers.php");
    exit();
}

// Xử lý đổi trạng thái giáo viên
if (isset($_GET['toggle_status'])) {
    $teacher_id = $_GET['toggle_status'];

    try {
        // Lấy trạng thái hiện tại của giáo viên
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch();

        if ($teacher) {
            $current_status = $teacher['status'];
            $new_status = ($current_status === 'active') ? 'inactive' : 'active';

            // ✅ Nếu chuyển sang "inactive" thì kiểm tra ràng buộc bàn giao
            if ($new_status === 'inactive') {

                // Kiểm tra còn lớp đang giảng dạy không
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM teaching_assignments
                    WHERE teacher_id = ? AND (end_date IS NULL OR end_date > NOW())
                ");
                $stmt->execute([$teacher_id]);
                $teaching_count = $stmt->fetchColumn();

                // Kiểm tra còn chủ nhiệm lớp nào không
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM homeroom_history
                    WHERE teacher_id = ? AND (end_date IS NULL OR end_date > NOW())
                ");
                $stmt->execute([$teacher_id]);
                $homeroom_count = $stmt->fetchColumn();

                if ($teaching_count > 0 || $homeroom_count > 0) {
                    $_SESSION['error'] = "Không thể chuyển giáo viên sang trạng thái 'ngừng giảng dạy'. 
                    Vui lòng hoàn tất bàn giao giảng dạy và chủ nhiệm trước khi ngừng công tác.";
                    header("Location: teachers.php" . (isset($_GET['show_all']) ? '?show_all=' . $_GET['show_all'] : ''));
                    exit();
                }
            }

            // ✅ Nếu không vi phạm, cho phép đổi trạng thái
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $teacher_id]);

            $_SESSION['success'] = "Cập nhật trạng thái giáo viên thành công!";
        } else {
            $_SESSION['error'] = "Không tìm thấy giáo viên!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    header("Location: teachers.php" . (isset($_GET['show_all']) ? '?show_all=' . $_GET['show_all'] : ''));
    exit();
}



// Lấy danh sách giáo viên
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';

if ($show_all) {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY status DESC, full_name");
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY full_name");
}
$teachers = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Giáo viên - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chalkboard-teacher"></i> Quản lý Giáo viên</h1>
                    <div class="form-check form-switch ms-3">
                        <input class="form-check-input" type="checkbox" id="showAll" <?php echo $show_all ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="showAll">Hiển thị toàn bộ giáo viên</label>
                    </div>

                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <i class="fas fa-plus"></i> Thêm Giáo viên
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="teachersTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mã GV</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th>Điện thoại</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                        <td>
                                            <?php if ($teacher['status'] === 'active'): ?>
                                                <span class="badge bg-success">Đang giảng dạy</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Ngừng giảng dạy</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($teacher['created_at'])); ?></td>
                                        <td>
                                            <!-- Nút đổi trạng thái -->
                                            <a href="teachers.php?toggle_status=<?php echo $teacher['id']; ?><?php echo $show_all ? '&show_all=1' : ''; ?>"
                                                class="btn btn-sm <?php echo $teacher['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                                onclick="return confirm('Bạn có chắc muốn <?php echo $teacher['status'] === 'active' ? 'ngừng giảng dạy' : 'kích hoạt lại'; ?> giáo viên này?')">
                                                    <i class="fas <?php echo $teacher['status'] === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                                    <?php echo $teacher['status'] === 'active' ? 'Ngừng dạy' : 'Kích hoạt'; ?>
                                            </a>
                                            <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="teachers.php?delete=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="teacher_assignments.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Thêm Giáo viên -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Giáo viên Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Họ tên *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Điện thoại</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_teacher" class="btn btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#teachersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                }
            });
        });
    </script>
    <script>
        document.getElementById('showAll').addEventListener('change', function() {
            const checked = this.checked ? '1' : '0';
            window.location.href = 'teachers.php?show_all=' + checked;
        });
    </script>

</body>
</html>