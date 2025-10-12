<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Lấy các lớp mà giáo viên đang chủ nhiệm
$stmt = $pdo->prepare("
    SELECT c.id, c.class_name, c.grade, c.school_year
    FROM classes c
    WHERE c.homeroom_teacher_id = ?
    ORDER BY c.grade, c.class_name
");
$stmt->execute([$teacher_id]);
$teacher_classes = $stmt->fetchAll();


// Xử lý chọn lớp
$selected_class = $_GET['class'] ?? '';

// Lấy danh sách học sinh khi đã chọn lớp
$students = [];
$class_info = null;

if ($selected_class) {
    // Kiểm tra xem giáo viên có dạy lớp này không
    $valid_class = false;
    foreach ($teacher_classes as $class) {
        if ($class['id'] == $selected_class) {
            $valid_class = true;
            $class_info = $class;
            break;
        }
    }
    
    if ($valid_class) {
        // Lấy danh sách học sinh trong lớp
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.full_name, u.email, u.phone, u.created_at
            FROM class_students cs
            JOIN users u ON cs.student_id = u.id
            WHERE cs.class_id = ? AND u.status = 'active'
            ORDER BY u.full_name
        ");
        $stmt->execute([$selected_class]);
        $students = $stmt->fetchAll();
        
        // Lấy điểm trung bình của học sinh
        foreach ($students as &$student) {
            $stmt = $pdo->prepare("
                SELECT AVG(average) as overall_average,
                       COUNT(*) as subject_count
                FROM scores 
                WHERE student_id = ? AND class_id = ? AND average IS NOT NULL
            ");
            $stmt->execute([$student['id'], $selected_class]);
            $score_info = $stmt->fetch();
            
            $student['overall_average'] = $score_info['overall_average'];
            $student['subject_count'] = $score_info['subject_count'];
        }
        unset($student);
    }
}
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
                </div>

                <!-- Form chọn lớp -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Chọn lớp</label>
                                <select class="form-select" name="class" required onchange="this.form.submit()">
                                    <option value="">Chọn lớp</option>
                                    <?php foreach ($teacher_classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?> (Khối <?php echo $class['grade']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <?php if ($selected_class && $class_info): ?>
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Đang xem: <strong><?php echo htmlspecialchars($class_info['class_name']); ?></strong>
                                        - Tổng số: <strong><?php echo count($students); ?> học sinh</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selected_class && $class_info): ?>
                    <!-- Thống kê nhanh - ĐÃ SỬA -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body text-center">
                                    <h4><?php echo count($students); ?></h4>
                                    <p class="mb-0">Tổng học sinh</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success">
                                <div class="card-body text-center">
                                    <h4>
                                        <?php
                                        $excellent = array_filter($students, function($student) {
                                            return $student['overall_average'] >= 8;
                                        });
                                        echo count($excellent);
                                        ?>
                                    </h4>
                                    <p class="mb-0">Học sinh giỏi</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body text-center">
                                    <h4>
                                        <?php
                                        $good = array_filter($students, function($student) {
                                            return $student['overall_average'] >= 6.5 && $student['overall_average'] < 8;
                                        });
                                        echo count($good);
                                        ?>
                                    </h4>
                                    <p class="mb-0">Học sinh khá</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info">
                                <div class="card-body text-center">
                                    <h4>
                                        <?php
                                        $average = array_filter($students, function($student) {
                                            return $student['overall_average'] >= 5 && $student['overall_average'] < 6.5;
                                        });
                                        echo count($average);
                                        ?>
                                    </h4>
                                    <p class="mb-0">Học sinh trung bình</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thêm hàng thống kê cho học sinh yếu -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-danger">
                                <div class="card-body text-center">
                                    <h4>
                                        <?php
                                        $weak = array_filter($students, function($student) {
                                            return $student['overall_average'] < 5 && $student['overall_average'] > 0;
                                        });
                                        echo count($weak);
                                        ?>
                                    </h4>
                                    <p class="mb-0">Học sinh yếu</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-secondary">
                                <div class="card-body text-center">
                                    <h4>
                                        <?php
                                        $no_scores = array_filter($students, function($student) {
                                            return $student['overall_average'] === null || $student['overall_average'] == 0;
                                        });
                                        echo count($no_scores);
                                        ?>
                                    </h4>
                                    <p class="mb-0">Chưa có điểm</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Danh sách học sinh -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list"></i> Danh sách học sinh lớp <?php echo htmlspecialchars($class_info['class_name']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($students): ?>
                                <div class="table-responsive">
                                    <table id="studentsTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>STT</th>
                                                <th>Họ tên</th>
                                                <th>Mã HS</th>
                                                <th>Email</th>
                                                <th>Điện thoại</th>
                                                <th>Điểm TB</th>
                                                <th>Môn có điểm</th>
                                                <th>Xếp loại</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $index => $student): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                <td>
                                                    <?php if ($student['overall_average']): ?>
                                                        <span class="badge bg-<?php 
                                                            if ($student['overall_average'] >= 8) echo 'success';
                                                            elseif ($student['overall_average'] >= 6.5) echo 'warning';
                                                            elseif ($student['overall_average'] >= 5) echo 'info';
                                                            else echo 'danger';
                                                        ?> fs-6">
                                                            <?php echo number_format($student['overall_average'], 2); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Chưa có</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo $student['subject_count'] ?? 0; ?> môn
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($student['overall_average']): ?>
                                                        <?php
                                                        if ($student['overall_average'] >= 8) echo '<span class="badge bg-success">Giỏi</span>';
                                                        elseif ($student['overall_average'] >= 6.5) echo '<span class="badge bg-warning">Khá</span>';
                                                        elseif ($student['overall_average'] >= 5) echo '<span class="badge bg-info">Trung bình</span>';
                                                        else echo '<span class="badge bg-danger">Yếu</span>';
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="scores.php?class=<?php echo $selected_class; ?>&student=<?php echo $student['id']; ?>" 
                                                           class="btn btn-info" title="Xem điểm chi tiết">
                                                            <i class="fas fa-chart-line"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#studentDetailModal"
                                                                data-name="<?php echo htmlspecialchars($student['full_name']); ?>"
                                                                data-username="<?php echo htmlspecialchars($student['username']); ?>"
                                                                data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                                                data-phone="<?php echo htmlspecialchars($student['phone']); ?>"
                                                                data-created="<?php echo date('d/m/Y', strtotime($student['created_at'])); ?>"
                                                                title="Thông tin chi tiết">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Lớp học này chưa có học sinh.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($selected_class && !$class_info): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Bạn không có quyền xem thông tin lớp học này.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Vui lòng chọn lớp để xem danh sách học sinh.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal Thông tin học sinh -->
    <div class="modal fade" id="studentDetailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thông tin học sinh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Họ và tên:</strong></td>
                                    <td id="modal-student-name"></td>
                                </tr>
                                <tr>
                                    <td><strong>Mã học sinh:</strong></td>
                                    <td id="modal-student-username"></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td id="modal-student-email"></td>
                                </tr>
                                <tr>
                                    <td><strong>Điện thoại:</strong></td>
                                    <td id="modal-student-phone"></td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày tạo tài khoản:</strong></td>
                                    <td id="modal-student-created"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
            $('#studentsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                },
                order: [[1, 'asc']]
            });

            // Xử lý modal thông tin học sinh
            $('#studentDetailModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                $('#modal-student-name').text(button.data('name'));
                $('#modal-student-username').text(button.data('username'));
                $('#modal-student-email').text(button.data('email'));
                $('#modal-student-phone').text(button.data('phone'));
                $('#modal-student-created').text(button.data('created'));
            });
        });
    </script>
</body>
</html>