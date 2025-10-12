<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Xử lý thêm lớp học
if (isset($_POST['add_class'])) {
    $class_name = $_POST['class_name'];
    $grade = $_POST['grade'];
    $homeroom_teacher_id = $_POST['homeroom_teacher_id'];
    $school_year = $_POST['school_year'];

    try {
        $stmt = $pdo->prepare("INSERT INTO classes (class_name, grade, homeroom_teacher_id, school_year) VALUES (?, ?, ?, ?)");
        $stmt->execute([$class_name, $grade, $homeroom_teacher_id, $school_year]);
        $new_class_id = $pdo->lastInsertId();
        if (!empty($homeroom_teacher_id)) {
            $stmt = $pdo->prepare("
                INSERT INTO homeroom_history (teacher_id, class_id, school_year, start_date, is_active)
                VALUES (?, ?, ?, CURDATE(), 1)
            ");
            $stmt->execute([$homeroom_teacher_id, $new_class_id, $school_year]);
        }
        $_SESSION['success'] = "Thêm lớp học thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa lớp học
if (isset($_GET['delete'])) {
    $class_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $_SESSION['success'] = "Xóa lớp học thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: classes.php");
    exit();
}

//Xử lý đổi GVCN
// Đổi giáo viên chủ nhiệm
if (isset($_POST['change_homeroom'])) {
    $class_id = $_POST['class_id'];
    $new_teacher_id = $_POST['new_teacher_id'];

    try {
        // Lấy thông tin lớp
        $stmt = $pdo->prepare("SELECT homeroom_teacher_id, school_year FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch();

        if ($class) {
            $old_teacher_id = $class['homeroom_teacher_id'];
            $school_year = $class['school_year'];

            // 1 Cập nhật lớp
            $stmt = $pdo->prepare("UPDATE classes SET homeroom_teacher_id = ? WHERE id = ?");
            $stmt->execute([$new_teacher_id, $class_id]);

            // 2 Cập nhật homeroom_history: kết thúc nhiệm kỳ cũ
            if ($old_teacher_id) {
                $stmt = $pdo->prepare("
                    UPDATE homeroom_history 
                    SET end_date = CURDATE(), is_active = 0
                    WHERE class_id = ? AND is_active = 1
                ");
                $stmt->execute([$class_id]);
            }

            // 3 Ghi nhiệm kỳ mới
            $stmt = $pdo->prepare("
                INSERT INTO homeroom_history (teacher_id, class_id, school_year, start_date, is_active)
                VALUES (?, ?, ?, CURDATE(), 1)
            ");
            $stmt->execute([$new_teacher_id, $class_id, $school_year]);

            $_SESSION['success'] = "Đổi giáo viên chủ nhiệm thành công!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    header("Location: classes.php");
    exit();
}

// Lấy danh sách năm học có trong CSDL (để hiển thị trong combobox)
$year_stmt = $pdo->query("SELECT DISTINCT school_year FROM classes ORDER BY school_year DESC");
$school_years = $year_stmt->fetchAll(PDO::FETCH_COLUMN);

// Xử lý lọc theo năm học (mặc định lấy năm học mới nhất nếu chưa chọn)
$selected_year = isset($_GET['year']) ? $_GET['year'] : ($school_years[0] ?? '2024-2025');

// Lấy danh sách lớp học
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name AS teacher_name
    FROM classes c
    LEFT JOIN users u ON c.homeroom_teacher_id = u.id
    WHERE c.school_year = ?
    ORDER BY c.grade, c.class_name
");
$stmt->execute([$selected_year]);
$classes = $stmt->fetchAll();

// Lấy danh sách giáo viên
$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' AND status = 'active'");
$teachers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Lớp học - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-school"></i> Quản lý Lớp học</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="fas fa-plus"></i> Thêm Lớp học
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="mb-3">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label for="year" class="col-form-label fw-bold">Năm học:</label>
                        </div>
                        <div class="col-auto">
                            <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($school_years as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>" <?php if ($selected_year == $year) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>


                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="classesTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tên lớp</th>
                                        <th>Khối</th>
                                        <th>Giáo viên chủ nhiệm</th>
                                        <th>Năm học</th>
                                        <th>Số học sinh</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                    <?php
                                    // Đếm số học sinh trong lớp
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM class_students WHERE class_id = ?");
                                    $stmt->execute([$class['id']]);
                                    $student_count = $stmt->fetch()['total'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                        <td>Khối <?php echo htmlspecialchars($class['grade']); ?></td>
                                        <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'Chưa có'); ?></td>
                                        <td><?php echo htmlspecialchars($class['school_year']); ?></td>
                                        <td><?php echo $student_count; ?> học sinh</td>
                                        <td>
                                            <a href="class_students.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-list"></i>
                                            </a>
                                            <a href="edit_class.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="classes.php?delete=<?php echo $class['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="#" 
                                                class="btn btn-sm btn-secondary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#changeTeacherModal"
                                                data-class-id="<?php echo $class['id']; ?>"
                                                data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>">
                                                <i class="fas fa-exchange-alt"></i> Đổi GVCN
                                            </a>

                                            <a href="#" 
                                                class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#historyModal"
                                                data-class-id="<?php echo $class['id']; ?>"
                                                data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>">
                                                <i class="fas fa-history"></i> Lịch sử
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

    <!-- Modal Thêm Lớp học -->
    <div class="modal fade" id="addClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Lớp học Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên lớp *</label>
                            <input type="text" class="form-control" name="class_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Khối *</label>
                            <select class="form-select" name="grade" required>
                                <option value="">Chọn khối</option>
                                <option value="10">Khối 10</option>
                                <option value="11">Khối 11</option>
                                <option value="12">Khối 12</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giáo viên chủ nhiệm</label>
                            <select class="form-select" name="homeroom_teacher_id">
                                <option value="">Chọn giáo viên</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Năm học *</label>
                            <input type="text" class="form-control" name="school_year" value="<?php echo htmlspecialchars($selected_year); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_class" class="btn btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Đổi Giáo viên Chủ nhiệm -->
    <div class="modal fade" id="changeTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Đổi Giáo viên Chủ nhiệm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="class_id" id="changeClassId">
                <p><strong>Lớp:</strong> <span id="changeClassName" class="text-primary"></span></p>
                <div class="mb-3">
                <label class="form-label">Chọn giáo viên mới</label>
                <select class="form-select" name="new_teacher_id" required>
                    <option value="">-- Chọn giáo viên --</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>">
                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" name="change_homeroom" class="btn btn-primary">Cập nhật</button>
            </div>
            </form>
        </div>
    </div>

    <!-- Modal Lịch sử Chủ nhiệm -->
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history"></i> Lịch sử Chủ nhiệm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historyContent">
                <p>Đang tải dữ liệu...</p>
            </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#classesTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                }
            });
        });
    </script>

    <script>
        var changeModal = document.getElementById('changeTeacherModal');
        changeModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('changeClassId').value = button.getAttribute('data-class-id');
        document.getElementById('changeClassName').textContent = button.getAttribute('data-class-name');
        });

        var historyModal = document.getElementById('historyModal');
        historyModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var classId = button.getAttribute('data-class-id');
        var className = button.getAttribute('data-class-name');
        var historyContent = document.getElementById('historyContent');
        historyContent.innerHTML = "<p><i class='fas fa-spinner fa-spin'></i> Đang tải dữ liệu...</p>";

        fetch('load_history.php?class_id=' + classId)
            .then(response => response.text())
            .then(data => {
            historyContent.innerHTML = `<h6 class="text-primary mb-3">Lớp: ${className}</h6>` + data;
            })
            .catch(() => historyContent.innerHTML = "<p class='text-danger'>Không tải được dữ liệu!</p>");
        });
    </script>

</body>
</html>