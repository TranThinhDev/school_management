<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Xử lý phân công giảng dạy
if (isset($_POST['add_assignment'])) {
    $teacher_id = $_POST['teacher_id'];
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    $semester = $_POST['semester'];
    $school_year = $_POST['school_year'];

    try {
        // Kiểm tra xem đã phân công chưa
        $stmt = $pdo->prepare("SELECT id FROM teaching_assignments WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND semester = ? AND school_year = ?");
        $stmt->execute([$teacher_id, $class_id, $subject_id, $semester, $school_year]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Giáo viên đã được phân công dạy môn này cho lớp này!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO teaching_assignments (teacher_id, class_id, subject_id, semester, school_year) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$teacher_id, $class_id, $subject_id, $semester, $school_year]);
            $_SESSION['success'] = "Phân công giảng dạy thành công!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa phân công
if (isset($_GET['delete'])) {
    $assignment_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM teaching_assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $_SESSION['success'] = "Xóa phân công thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: assignments.php");
    exit();
}

// Lấy danh sách phân công
$stmt = $pdo->query("
    SELECT ta.*, u.full_name as teacher_name, c.class_name, s.subject_name 
    FROM teaching_assignments ta 
    JOIN users u ON ta.teacher_id = u.id 
    JOIN classes c ON ta.class_id = c.id 
    JOIN subjects s ON ta.subject_id = s.id 
    ORDER BY ta.school_year DESC, ta.semester, c.class_name
");
$assignments = $stmt->fetchAll();

// Lấy danh sách giáo viên, lớp, môn học
$teachers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' AND status = 'active'")->fetchAll();
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY grade, class_name")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công Giảng dạy - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-tasks"></i> Phân công Giảng dạy</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
                        <i class="fas fa-plus"></i> Thêm Phân công
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
                            <table id="assignmentsTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Giáo viên</th>
                                        <th>Lớp</th>
                                        <th>Môn học</th>
                                        <th>Học kỳ</th>
                                        <th>Năm học</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                        <td>Học kỳ <?php echo htmlspecialchars($assignment['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['school_year']); ?></td>
                                        <td>
                                            <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="assignments.php?delete=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">
                                                <i class="fas fa-trash"></i>
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

    <!-- Modal Thêm Phân công -->
    <div class="modal fade" id="addAssignmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Phân công Giảng dạy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Giáo viên *</label>
                            <select class="form-select" name="teacher_id" required>
                                <option value="">Chọn giáo viên</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lớp *</label>
                            <select class="form-select" name="class_id" required>
                                <option value="">Chọn lớp</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Môn học *</label>
                            <select class="form-select" name="subject_id" required>
                                <option value="">Chọn môn học</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>">
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
                                        <option value="1">Học kỳ 1</option>
                                        <option value="2">Học kỳ 2</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Năm học *</label>
                                    <input type="text" class="form-control" name="school_year" value="2024-2025" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_assignment" class="btn btn-primary">Thêm</button>
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
            $('#assignmentsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                }
            });
        });
    </script>
</body>
</html>