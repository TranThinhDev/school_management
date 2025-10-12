<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Lấy các lớp và môn giáo viên đang dạy
$stmt = $pdo->prepare("
    SELECT ta.*, c.class_name, s.subject_name 
    FROM teaching_assignments ta 
    JOIN classes c ON ta.class_id = c.id 
    JOIN subjects s ON ta.subject_id = s.id 
    WHERE ta.teacher_id = ? 
    ORDER BY ta.school_year DESC, ta.semester, c.class_name
");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll();

// Xử lý chọn lớp/môn
$selected_class = $_GET['class'] ?? '';
$selected_subject = $_GET['subject'] ?? '';
$selected_semester = $_GET['semester'] ?? '1';

// Lấy danh sách học sinh khi đã chọn lớp
$students = [];
$score_entry_deadline = null;
$is_locked = false;

if ($selected_class && $selected_subject && $selected_semester) {
    // Kiểm tra hạn nhập điểm
    $stmt = $pdo->prepare("
        SELECT score_entry_deadline, is_locked 
        FROM scores 
        WHERE class_id = ? AND subject_id = ? AND semester = ? AND teacher_id = ?
        LIMIT 1
    ");
    $stmt->execute([$selected_class, $selected_subject, $selected_semester, $teacher_id]);
    $deadline_info = $stmt->fetch();
    
    if ($deadline_info) {
        $score_entry_deadline = $deadline_info['score_entry_deadline'];
        $is_locked = $deadline_info['is_locked'];
    } else {
        // Nếu chưa có bản ghi điểm, tạo hạn mặc định (2 tuần từ hiện tại)
        $score_entry_deadline = date('Y-m-d', strtotime('+2 weeks'));
        $is_locked = false;
    }
    
    // Kiểm tra xem đã quá hạn chưa
    $current_date = date('Y-m-d');
    if ($score_entry_deadline && $current_date > $score_entry_deadline && !$is_locked) {
        $is_locked = true;
        // Cập nhật trạng thái khóa
        $stmt = $pdo->prepare("UPDATE scores SET is_locked = TRUE WHERE class_id = ? AND subject_id = ? AND semester = ? AND teacher_id = ?");
        $stmt->execute([$selected_class, $selected_subject, $selected_semester, $teacher_id]);
    }
    
    // Lấy danh sách học sinh
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.username,
               s.oral_1, s.oral_2, s.oral_3,
               s.fifteen_min_1, s.fifteen_min_2, s.fifteen_min_3,
               s.forty_five_min_1, s.forty_five_min_2, s.forty_five_min_3,
               s.mid_term, s.final_term, s.average
        FROM class_students cs
        JOIN users u ON cs.student_id = u.id
        LEFT JOIN scores s ON u.id = s.student_id AND s.class_id = ? AND s.subject_id = ? AND s.semester = ?
        WHERE cs.class_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$selected_class, $selected_subject, $selected_semester, $selected_class]);
    $students = $stmt->fetchAll();
}

// Xử lý lưu điểm
if (isset($_POST['save_scores']) && $selected_class && $selected_subject && $selected_semester) {
    if ($is_locked) {
        $_SESSION['error'] = "Đã quá hạn nhập điểm! Vui lòng liên hệ Admin để mở lại quyền nhập điểm.";
    } else {
        try {
            $pdo->beginTransaction();
            
            foreach ($_POST['scores'] as $student_id => $scores) {
                // Tính điểm trung bình
                $average = calculateAverage($scores);
                
                // Kiểm tra xem đã có bản ghi chưa
                $stmt = $pdo->prepare("SELECT id FROM scores WHERE student_id = ? AND class_id = ? AND subject_id = ? AND semester = ?");
                $stmt->execute([$student_id, $selected_class, $selected_subject, $selected_semester]);
                
                if ($stmt->fetch()) {
                    // Cập nhật
                    $stmt = $pdo->prepare("
                        UPDATE scores SET 
                        oral_1 = ?, oral_2 = ?, oral_3 = ?,
                        fifteen_min_1 = ?, fifteen_min_2 = ?, fifteen_min_3 = ?,
                        forty_five_min_1 = ?, forty_five_min_2 = ?, forty_five_min_3 = ?,
                        mid_term = ?, final_term = ?, average = ?,
                        score_entry_deadline = ?, last_modified = NOW()
                        WHERE student_id = ? AND class_id = ? AND subject_id = ? AND semester = ?
                    ");
                    $stmt->execute([
                        $scores['oral_1'], $scores['oral_2'], $scores['oral_3'],
                        $scores['fifteen_min_1'], $scores['fifteen_min_2'], $scores['fifteen_min_3'],
                        $scores['forty_five_min_1'], $scores['forty_five_min_2'], $scores['forty_five_min_3'],
                        $scores['mid_term'], $scores['final_term'], $average,
                        $score_entry_deadline,
                        $student_id, $selected_class, $selected_subject, $selected_semester
                    ]);
                } else {
                    // Thêm mới
                    $stmt = $pdo->prepare("
                        INSERT INTO scores (
                            student_id, class_id, subject_id, teacher_id, semester, school_year,
                            oral_1, oral_2, oral_3,
                            fifteen_min_1, fifteen_min_2, fifteen_min_3,
                            forty_five_min_1, forty_five_min_2, forty_five_min_3,
                            mid_term, final_term, average, score_entry_deadline
                        ) VALUES (?, ?, ?, ?, ?, '2024-2025', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $student_id, $selected_class, $selected_subject, $teacher_id, $selected_semester,
                        $scores['oral_1'], $scores['oral_2'], $scores['oral_3'],
                        $scores['fifteen_min_1'], $scores['fifteen_min_2'], $scores['fifteen_min_3'],
                        $scores['forty_five_min_1'], $scores['forty_five_min_2'], $scores['forty_five_min_3'],
                        $scores['mid_term'], $scores['final_term'], $average, $score_entry_deadline
                    ]);
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Lưu điểm thành công!";
            header("Location: scores.php?class=$selected_class&subject=$selected_subject&semester=$selected_semester");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Lỗi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập điểm - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-chart-line"></i> Nhập điểm</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Form chọn lớp/môn -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Lớp</label>
                                <select class="form-select" name="class" required>
                                    <option value="">Chọn lớp</option>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <option value="<?php echo $assignment['class_id']; ?>" <?php echo $selected_class == $assignment['class_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($assignment['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Môn học</label>
                                <select class="form-select" name="subject" required>
                                    <option value="">Chọn môn</option>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <?php if ($selected_class == $assignment['class_id'] || !$selected_class): ?>
                                            <option value="<?php echo $assignment['subject_id']; ?>" <?php echo $selected_subject == $assignment['subject_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($assignment['subject_name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Học kỳ</label>
                                <select class="form-select" name="semester" required>
                                    <option value="1" <?php echo $selected_semester == '1' ? 'selected' : ''; ?>>Học kỳ 1</option>
                                    <option value="2" <?php echo $selected_semester == '2' ? 'selected' : ''; ?>>Học kỳ 2</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Chọn</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selected_class && $selected_subject && $selected_semester): ?>
                    <!-- Thông tin hạn nhập điểm -->
                    <div class="alert <?php echo $is_locked ? 'alert-danger' : 'alert-info'; ?>">
                        <i class="fas fa-clock"></i>
                        <?php if ($is_locked): ?>
                            <strong>ĐÃ KHÓA NHẬP ĐIỂM</strong> - Hạn nhập điểm: <?php echo date('d/m/Y', strtotime($score_entry_deadline)); ?>
                            <br><small>Vui lòng liên hệ Admin để mở lại quyền nhập điểm.</small>
                        <?php else: ?>
                            Hạn nhập điểm: <?php echo date('d/m/Y', strtotime($score_entry_deadline)); ?>
                            <?php 
                            $days_remaining = floor((strtotime($score_entry_deadline) - time()) / (60 * 60 * 24));
                            if ($days_remaining <= 7): ?>
                                <br><small><strong>Còn <?php echo $days_remaining; ?> ngày nữa là hết hạn!</strong></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Form nhập điểm -->
                    <?php if ($students): ?>
                        <form method="POST">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Danh sách điểm</h5>
                                    <?php if (!$is_locked): ?>
                                        <button type="submit" name="save_scores" class="btn btn-success">
                                            <i class="fas fa-save"></i> Lưu điểm
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th rowspan="2">Họ tên</th>
                                                    <th colspan="3" class="text-center">Điểm miệng</th>
                                                    <th colspan="3" class="text-center">15 phút</th>
                                                    <th colspan="3" class="text-center">45 phút</th>
                                                    <th rowspan="2">Giữa kỳ</th>
                                                    <th rowspan="2">Cuối kỳ</th>
                                                    <th rowspan="2">Trung bình</th>
                                                </tr>
                                                <tr>
                                                    <th>M1</th><th>M2</th><th>M3</th>
                                                    <th>15p1</th><th>15p2</th><th>15p3</th>
                                                    <th>45p1</th><th>45p2</th><th>45p3</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($student['username']); ?></small>
                                                    </td>
                                                    <!-- Điểm miệng -->
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][oral_1]" value="<?php echo $student['oral_1'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][oral_2]" value="<?php echo $student['oral_2'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][oral_3]" value="<?php echo $student['oral_3'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <!-- 15 phút -->
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][fifteen_min_1]" value="<?php echo $student['fifteen_min_1'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][fifteen_min_2]" value="<?php echo $student['fifteen_min_2'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][fifteen_min_3]" value="<?php echo $student['fifteen_min_3'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <!-- 45 phút -->
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][forty_five_min_1]" value="<?php echo $student['forty_five_min_1'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][forty_five_min_2]" value="<?php echo $student['forty_five_min_2'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][forty_five_min_3]" value="<?php echo $student['forty_five_min_3'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <!-- Giữa kỳ và cuối kỳ -->
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][mid_term]" value="<?php echo $student['mid_term'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <td><input type="number" class="form-control form-control-sm" name="scores[<?php echo $student['id']; ?>][final_term]" value="<?php echo $student['final_term'] ?? ''; ?>" min="0" max="10" step="0.1" <?php echo $is_locked ? 'readonly' : ''; ?>></td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm bg-light" value="<?php echo $student['average'] ?? ''; ?>" readonly>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Lớp học này chưa có học sinh.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tự động tính điểm trung bình khi nhập điểm
        document.addEventListener('input', function(e) {
            if (e.target.type === 'number' && e.target.name.includes('scores')) {
                const studentId = e.target.name.match(/\[(\d+)\]/)[1];
                calculateAverageForStudent(studentId);
            }
        });

        function calculateAverageForStudent(studentId) {
            const inputs = document.querySelectorAll(`input[name="scores[${studentId}][*]"]`);
            let total = 0;
            let count = 0;
            
            const weights = {
                'oral': 1,
                'fifteen_min': 1,
                'forty_five_min': 2,
                'mid_term': 2,
                'final_term': 3
            };
            
            inputs.forEach(input => {
                const value = parseFloat(input.value);
                if (!isNaN(value)) {
                    const type = input.name.split('[')[2].split(']')[0].split('_')[0];
                    const weight = weights[type] || 1;
                    total += value * weight;
                    count += weight;
                }
            });
            
            const average = count > 0 ? (total / count).toFixed(2) : '';
            const avgInput = document.querySelector(`input[value][name*="[${studentId}]"]`).closest('tr').querySelector('td:last-child input');
            avgInput.value = average;
        }
    </script>
</body>
</html>