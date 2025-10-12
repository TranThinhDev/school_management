<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Xử lý mở khóa nhập điểm
if (isset($_POST['unlock_scores'])) {
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    $semester = $_POST['semester'];
    $new_deadline = $_POST['new_deadline'];

    try {
        $stmt = $pdo->prepare("
            UPDATE scores 
            SET is_locked = FALSE, score_entry_deadline = ?
            WHERE class_id = ? AND subject_id = ? AND semester = ?
        ");
        $stmt->execute([$new_deadline, $class_id, $subject_id, $semester]);
        $_SESSION['success'] = "Mở khóa nhập điểm thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách điểm bị khóa
$locked_scores = $pdo->query("
    SELECT DISTINCT s.class_id, s.subject_id, s.semester, s.score_entry_deadline,
           c.class_name, sj.subject_name
    FROM scores s
    JOIN classes c ON s.class_id = c.id
    JOIN subjects sj ON s.subject_id = sj.id
    WHERE s.is_locked = TRUE
    ORDER BY s.score_entry_deadline DESC
")->fetchAll();

// Lấy danh sách lớp và môn học
$classes = $pdo->query("SELECT * FROM classes ORDER BY grade, class_name")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Điểm - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-chart-line"></i> Quản lý Điểm</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Form mở khóa nhập điểm -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-unlock"></i> Mở khóa Nhập điểm
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Lớp học *</label>
                                <select class="form-select" name="class_id" required>
                                    <option value="">Chọn lớp</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>">
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-2">
                                <label class="form-label">Học kỳ *</label>
                                <select class="form-select" name="semester" required>
                                    <option value="1">Học kỳ 1</option>
                                    <option value="2">Học kỳ 2</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hạn mới *</label>
                                <input type="date" class="form-control" name="new_deadline" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="unlock_scores" class="btn btn-success w-100">
                                    <i class="fas fa-unlock"></i> Mở khóa
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách điểm bị khóa -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lock"></i> Danh sách Điểm đã Khóa
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($locked_scores): ?>
                            <div class="table-responsive">
                                <table id="lockedScoresTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Lớp</th>
                                            <th>Môn học</th>
                                            <th>Học kỳ</th>
                                            <th>Hạn nhập điểm cũ</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locked_scores as $score): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($score['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($score['subject_name']); ?></td>
                                            <td>Học kỳ <?php echo htmlspecialchars($score['semester']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($score['score_entry_deadline'])); ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-lock"></i> Đã khóa
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Không có bảng điểm nào bị khóa.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#lockedScoresTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                }
            });

            // Set min date for new deadline to today
            $('input[name="new_deadline"]').val(new Date().toISOString().split('T')[0]);
            $('input[name="new_deadline"]').attr('min', new Date().toISOString().split('T')[0]);
        });
    </script>
</body>
</html>