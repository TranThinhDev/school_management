<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: classes.php");
    exit();
}

$class_id = $_GET['id'];

// Lấy thông tin lớp, năm học
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();
$school_year = $class['school_year']; // năm học của lớp hiện tại

if (!$class) {
    $_SESSION['error'] = "Không tìm thấy lớp học!";
    header("Location: classes.php");
    exit();
}

// Lấy danh sách học sinh trong lớp
$stmt = $pdo->prepare("
    SELECT u.*, cs.status AS class_status
    FROM class_students cs
    JOIN users u ON cs.student_id = u.id
    WHERE cs.class_id = ? 
      AND cs.school_year = ?
      AND u.status = 'active'
    ORDER BY u.full_name
");
$stmt->execute([$class_id, $school_year]);
$students = $stmt->fetchAll();


// Lấy danh sách học sinh chưa học lớp nào trong NĂM HỌC hiện tại
$stmt = $pdo->prepare("
    SELECT u.*
    FROM users u
    WHERE u.role = 'student' 
      AND u.status = 'active'
      AND u.id NOT IN (
          SELECT student_id 
          FROM class_students 
          WHERE school_year = ?
      )
    ORDER BY u.full_name
");
$stmt->execute([$school_year]);
$available_students = $stmt->fetchAll();


// Xử lý thêm học sinh vào lớp
if (isset($_POST['add_student'])) {
    $student_id = $_POST['student_id'];
    
    try {
        // Kiểm tra xem học sinh đã có lớp chưa
        $stmt = $pdo->prepare("SELECT id FROM class_students WHERE student_id = ? AND school_year = ?");
        $stmt->execute([$student_id, $school_year]);

        if ($stmt->fetch()) {
            $_SESSION['error'] = "Học sinh đã có lớp!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO class_students (student_id, class_id, school_year) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $class_id, '2024-2025']);
            $_SESSION['success'] = "Thêm học sinh vào lớp thành công!";
            header("Location: class_students.php?id=" . $class_id);
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa học sinh khỏi lớp
if (isset($_GET['remove'])) {
    $student_id = $_GET['remove'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE class_students 
            SET status = 'dropped' 
            WHERE student_id = ? AND class_id = ? AND school_year = ?
        ");
        $stmt->execute([$student_id, $class_id, $class['school_year']]);
        $_SESSION['success'] = "Xóa học sinh khỏi lớp thành công!";
        header("Location: class_students.php?id=" . $class_id);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý chuyển lớp
if (isset($_POST['transfer_student'])) {
    $student_id = $_POST['student_id'];
    $new_class_id = $_POST['new_class_id'];

    try {
        // Lấy năm học của lớp hiện tại
        $school_year = $class['school_year'];

        // Kiểm tra xem lớp mới có cùng năm học và cùng khối không
        $stmt = $pdo->prepare("SELECT school_year, grade FROM classes WHERE id = ?");
        $stmt->execute([$new_class_id]);
        $target_class = $stmt->fetch();

        if (!$target_class) {
            $_SESSION['error'] = "Không tìm thấy lớp mới!";
        } elseif ($target_class['school_year'] != $school_year) {
            $_SESSION['error'] = "Lớp mới phải thuộc cùng năm học!";
        } elseif ($target_class['grade'] != $class['grade']) {
            $_SESSION['error'] = "Chỉ được phép chuyển lớp trong cùng khối (khối " . htmlspecialchars($class['grade']) . ")!";
        } else {
            // ✅ Cập nhật lớp như trước
            $stmt = $pdo->prepare("UPDATE class_students 
                                SET status = 'transferred' 
                                WHERE student_id = ? AND class_id = ? AND school_year = ?");
            $stmt->execute([$student_id, $class_id, $school_year]);

            $stmt = $pdo->prepare("INSERT INTO class_students (student_id, class_id, school_year, status) 
                                VALUES (?, ?, ?, 'active')");
            $stmt->execute([$student_id, $new_class_id, $school_year]);

            $_SESSION['success'] = "Chuyển học sinh sang lớp mới thành công!";
        }

        header("Location: class_students.php?id=" . $class_id);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}
// Xử lý cập nhật trạng thái học sinh
if (isset($_POST['update_status'])) {
    $student_id = $_POST['status_student_id'];
    $new_status = $_POST['new_status'];

    try {
        $stmt = $pdo->prepare("
            UPDATE class_students 
            SET status = ? 
            WHERE student_id = ? AND class_id = ? AND school_year = ?
        ");
        $stmt->execute([$new_status, $student_id, $class_id, $class['school_year']]);

        $_SESSION['success'] = "Cập nhật trạng thái học sinh thành công!";
        header("Location: class_students.php?id=" . $class_id);
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
    <title>Danh sách Học sinh Lớp - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-list"></i> Danh sách Học sinh: <?php echo htmlspecialchars($class['class_name']); ?>
                    </h1>
                    <a href="classes.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Form thêm học sinh -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus"></i> Thêm Học sinh vào Lớp
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Chọn học sinh</label>
                                <input type="text" id="studentSearch" class="form-control mb-2" placeholder="Nhập tên hoặc mã học sinh để tìm...">
                                <select class="form-select" name="student_id" required>
                                    <option value="">Chọn học sinh</option>
                                    <?php foreach ($available_students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['full_name']); ?> (<?php echo htmlspecialchars($student['username']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="add_student" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Thêm vào lớp
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách học sinh trong lớp -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users"></i> Danh sách Học sinh (<?php echo count($students); ?> học sinh)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($students): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mã HS</th>
                                            <th>Họ tên</th>
                                            <th>Email</th>
                                            <th>Điện thoại</th>
                                            <th>Thao tác</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                            <td><?php
                                                    switch ($student['class_status']) {
                                                        case 'active': echo '<span class="badge bg-success">Đang học</span>'; break;
                                                        case 'transferred': echo '<span class="badge bg-warning text-dark">Đã chuyển lớp</span>'; break;
                                                        case 'graduated': echo '<span class="badge bg-primary">Đã tốt nghiệp</span>'; break;
                                                        case 'dropped': echo '<span class="badge bg-danger">Nghỉ học</span>'; break;
                                                        default: echo '<span class="badge bg-secondary">Không rõ</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="class_students.php?id=<?php echo $class_id; ?>&remove=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa học sinh này khỏi lớp?')">
                                                    <i class="fas fa-trash"></i> Xóa khỏi lớp
                                                </a>
                                                <a href="#" 
                                                    class="btn btn-sm btn-secondary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#transferModal" 
                                                    data-student-id="<?php echo $student['id']; ?>"
                                                    data-student-name="<?php echo htmlspecialchars($student['full_name']); ?>">
                                                    <i class="fas fa-exchange-alt"></i> Chuyển lớp
                                                </a>
                                                <a href="#" 
                                                    class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#statusModal"
                                                    data-student-id="<?php echo $student['id']; ?>"
                                                    data-student-name="<?php echo htmlspecialchars($student['full_name']); ?>"
                                                    data-current-status="<?php echo $student['class_status']; ?>">
                                                    <i class="fas fa-sync-alt"></i> Trạng thái
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Lớp học chưa có học sinh nào.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Modal chuyển lớp -->
    <div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Chuyển lớp học sinh</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="student_id" id="transferStudentId">
            <p><strong>Học sinh:</strong> <span id="transferStudentName" class="text-primary"></span></p>
            <div class="mb-3">
            <label class="form-label">Chọn lớp mới (cùng năm học)</label>
            <select class="form-select" name="new_class_id" required>
                <option value="">-- Chọn lớp --</option>
                <?php
                $stmt = $pdo->prepare("SELECT id, class_name, grade 
                       FROM classes 
                       WHERE school_year = ? AND grade = ?");
                $stmt->execute([$class['school_year'], $class['grade']]);
                $available_classes = $stmt->fetchAll();
                foreach ($available_classes as $cl):
                if ($cl['id'] != $class_id): // Không cho chọn lớp hiện tại
                ?>
                    <option value="<?php echo $cl['id']; ?>">
                        <?php echo htmlspecialchars($cl['class_name']) . ' (Khối ' . $cl['grade'] . ')'; ?>
                    </option>
                <?php 
                endif;
                endforeach;
                ?>
            </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" name="transfer_student" class="btn btn-primary">Xác nhận chuyển</button>
        </div>
        </form>
    </div>
    </div>
    <!-- Modal Chuyển Trạng Thái -->
    <div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-sync-alt"></i> Cập nhật trạng thái học sinh</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="status_student_id" id="statusStudentId">
            <p><strong>Học sinh:</strong> <span id="statusStudentName" class="text-primary"></span></p>
            <div class="mb-3">
            <label class="form-label">Chọn trạng thái mới</label>
            <select class="form-select" name="new_status" required>
                <option value="">-- Chọn trạng thái --</option>
                <option value="active">Đang học</option>
                <option value="dropped">Bỏ học</option>
                <option value="graduated">Đã tốt nghiệp</option>
            </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" name="update_status" class="btn btn-primary">Cập nhật</button>
        </div>
        </form>
    </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('studentSearch').addEventListener('keyup', function() {
        var filter = this.value.toLowerCase();
        var options = document.querySelectorAll('select[name="student_id"] option');
        options.forEach(o => {
            if (o.text.toLowerCase().includes(filter)) {
                o.style.display = '';
            } else {
                o.style.display = 'none';
            }
        });
    });
    </script>
    <script>
        var transferModal = document.getElementById('transferModal');
        transferModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var studentId = button.getAttribute('data-student-id');
        var studentName = button.getAttribute('data-student-name');
        
        document.getElementById('transferStudentId').value = studentId;
        document.getElementById('transferStudentName').textContent = studentName;
        });
    </script>
    <script>
        var statusModal = document.getElementById('statusModal');
        statusModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var studentId = button.getAttribute('data-student-id');
        var studentName = button.getAttribute('data-student-name');
        var currentStatus = button.getAttribute('data-current-status');
        
        document.getElementById('statusStudentId').value = studentId;
        document.getElementById('statusStudentName').textContent = studentName;
        statusModal.querySelector('select[name="new_status"]').value = currentStatus;
        });
    </script>


</body>
</html>