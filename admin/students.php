<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Xử lý thêm học sinh
if (isset($_POST['add_student'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; // Lưu mật khẩu dạng plain text
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $class_id = $_POST['class_id'];

    try {
        $pdo->beginTransaction();
        
        // Thêm user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, 'student', ?, ?, ?)");
        $stmt->execute([$username, $password, $full_name, $email, $phone]);
        $student_id = $pdo->lastInsertId();
        
        // Thêm vào lớp
        if ($class_id) {
            $stmt = $pdo->prepare("INSERT INTO class_students (student_id, class_id, school_year) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $class_id, '2024-2025']);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Thêm học sinh thành công!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa học sinh
if (isset($_GET['delete'])) {
    $student_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'student'");
        $stmt->execute([$student_id]);
        $_SESSION['success'] = "Xóa học sinh thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: students.php");
    exit();
}

// Lấy các tham số lọc
$search = $_GET['search'] ?? '';
$class_filter = $_GET['class_filter'] ?? '';
$grade_filter = $_GET['grade_filter'] ?? '';

// Xây dựng câu truy vấn với bộ lọc
$sql = "
    SELECT u.*, c.class_name, c.grade, cs.school_year 
    FROM users u 
    LEFT JOIN class_students cs ON u.id = cs.student_id 
    LEFT JOIN classes c ON cs.class_id = c.id 
    WHERE u.role = 'student' AND u.status = 'active'
";

$params = [];

// Thêm điều kiện tìm kiếm theo tên
if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Thêm điều kiện lọc theo lớp
if (!empty($class_filter)) {
    $sql .= " AND c.id = ?";
    $params[] = $class_filter;
}

// Thêm điều kiện lọc theo khối
if (!empty($grade_filter)) {
    $sql .= " AND c.grade = ?";
    $params[] = $grade_filter;
}

$sql .= " ORDER BY c.class_name, u.full_name";

// Thực thi truy vấn
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Lấy danh sách lớp
$stmt = $pdo->query("SELECT * FROM classes ORDER BY grade, class_name");
$classes = $stmt->fetchAll();

// Lấy danh sách khối lớp duy nhất
$stmt = $pdo->query("SELECT DISTINCT grade FROM classes WHERE grade IS NOT NULL ORDER BY grade");
$grades = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Học sinh - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-users"></i> Quản lý Học sinh</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-plus"></i> Thêm Học sinh
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Bộ lọc -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-filter"></i> Bộ lọc</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tìm kiếm theo tên</label>
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nhập tên hoặc mã học sinh...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Lọc theo khối</label>
                                <select class="form-select" name="grade_filter">
                                    <option value="">Tất cả khối</option>
                                    <?php foreach ($grades as $grade): ?>
                                        <option value="<?php echo htmlspecialchars($grade['grade']); ?>" 
                                            <?php echo $grade_filter == $grade['grade'] ? 'selected' : ''; ?>>
                                            Khối <?php echo htmlspecialchars($grade['grade']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Lọc theo lớp</label>
                                <select class="form-select" name="class_filter">
                                    <option value="">Tất cả lớp</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                            <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Lọc
                                </button>
                                <a href="students.php" class="btn btn-secondary">
                                    <i class="fas fa-refresh"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Thống kê nhanh -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo count($students); ?></h4>
                                        <p class="mb-0">Tổng học sinh</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="studentsTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mã HS</th>
                                        <th>Họ tên</th>
                                        <th>Khối</th>
                                        <th>Lớp</th>
                                        <th>Email</th>
                                        <th>Điện thoại</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td>
                                            <?php if ($student['grade']): ?>
                                                Khối <?php echo htmlspecialchars($student['grade']); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['class_name'] ?? 'Chưa xếp lớp'); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($student['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="students.php?delete=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="student_scores.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-chart-line"></i>
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

    <!-- Modal Thêm Học sinh -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Học sinh Mới</h5>
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
                        <div class="mb-3">
                            <label class="form-label">Lớp</label>
                            <select class="form-select" name="class_id">
                                <option value="">Chọn lớp</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_student" class="btn btn-primary">Thêm</button>
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
            $('#studentsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                },
                // Giữ nguyên các chức năng của DataTables nhưng vẫn hỗ trợ lọc từ server
                searching: false, // Tắt tìm kiếm client-side vì đã có tìm kiếm server-side
                ordering: true,
                paging: true
            });
        });
    </script>
</body>
</html>