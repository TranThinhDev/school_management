<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// ---------------------------
// Chuẩn bị: danh sách năm học (từ dữ liệu hiện có)
$years_stmt = $pdo->query("SELECT DISTINCT school_year FROM teaching_assignments UNION SELECT DISTINCT school_year FROM classes ORDER BY school_year DESC");
$school_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

// Lấy năm học được chọn (mặc định năm đầu tiên trong danh sách hoặc rỗng)
$selected_year = isset($_GET['school_year']) ? $_GET['school_year'] : ($school_years[0] ?? '');

// ---------------------------
// XỬ LÝ: Thêm phân công (ghi start_date, is_active)
if (isset($_POST['add_assignment'])) {
    $teacher_id = $_POST['teacher_id'];
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    $semester = $_POST['semester'];
    $school_year = $_POST['school_year'];

    try {
        // Kiểm tra ràng buộc: tại cùng class+subject+semester+school_year phải không có assignment active
        $stmt = $pdo->prepare("SELECT id FROM teaching_assignments WHERE class_id = ? AND subject_id = ? AND semester = ? AND school_year = ? AND is_active = 1");
        $stmt->execute([$class_id, $subject_id, $semester, $school_year]);

        if ($stmt->fetch()) {
            $_SESSION['error'] = "Đã tồn tại giáo viên đang dạy lớp này - môn này trong cùng học kỳ/năm học.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO teaching_assignments (teacher_id, class_id, subject_id, semester, school_year, start_date, is_active) VALUES (?, ?, ?, ?, ?, CURDATE(), 1)");
            $stmt->execute([$teacher_id, $class_id, $subject_id, $semester, $school_year]);
            $_SESSION['success'] = "Phân công giảng dạy thành công!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    header("Location: assignments.php" . ($selected_year ? "?school_year=" . urlencode($selected_year) : ""));
    exit();
}

// ---------------------------
// XỬ LÝ: Kết thúc (xóa) phân công -> thay bằng đóng (end_date, is_active=0) để lưu lịch sử
if (isset($_GET['delete'])) {
    $assignment_id = $_GET['delete'];

    try {
        $stmt = $pdo->prepare("UPDATE teaching_assignments SET end_date = CURDATE(), is_active = 0 WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $_SESSION['success'] = "Đã kết thúc phân công (lưu lịch sử).";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    header("Location: assignments.php" . ($selected_year ? "?school_year=" . urlencode($selected_year) : ""));
    exit();
}

// ---------------------------
// XỬ LÝ: Batch kết thúc phân công (khi hoàn thành học kỳ)
if (isset($_POST['end_selected']) && !empty($_POST['assign_ids'])) {
    $ids = $_POST['assign_ids'];
    try {
        $pdo->beginTransaction();
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE teaching_assignments SET end_date = CURDATE(), is_active = 0 WHERE id IN ($in)");
        $stmt->execute($ids);
        $pdo->commit();
        $_SESSION['success'] = "Đã kết thúc " . count($ids) . " phân công.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    header("Location: assignments.php" . ($selected_year ? "?school_year=" . urlencode($selected_year) : ""));
    exit();
}

// ---------------------------
// XỬ LÝ: Đổi giáo viên bộ môn cho 1 phân công (ghi lịch sử: đóng bản cũ, thêm bản mới)
if (isset($_POST['change_teacher'])) {
    $assign_id = $_POST['assignment_id'];
    $new_teacher_id = $_POST['new_teacher_id'];

    // Lấy thông tin phân công cũ
    $stmt = $pdo->prepare("SELECT class_id, subject_id, semester, school_year FROM teaching_assignments WHERE id = ?");
    $stmt->execute([$assign_id]);
    $old = $stmt->fetch();

    if (!$old) {
        $_SESSION['error'] = "Không tìm thấy phân công!";
        header("Location: assignments.php" . ($selected_year ? "?school_year=" . urlencode($selected_year) : ""));
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1) Kết thúc bản cũ
        $stmt = $pdo->prepare("UPDATE teaching_assignments SET end_date = CURDATE(), is_active = 0 WHERE id = ?");
        $stmt->execute([$assign_id]);

        // 2) Trước khi thêm bản mới: kiểm tra ràng buộc (class+subject+semester+school_year) không có active khác
        $check = $pdo->prepare("SELECT COUNT(*) FROM teaching_assignments WHERE class_id = ? AND subject_id = ? AND semester = ? AND school_year = ? AND is_active = 1");
        $check->execute([$old['class_id'], $old['subject_id'], $old['semester'], $old['school_year']]);
        if ($check->fetchColumn() > 0) {
            // Có bản active khác (không nên xảy ra nếu luồng đúng), rollback và báo lỗi
            throw new Exception("Đã tồn tại phân công active khác cho lớp này - môn này. Vui lòng kiểm tra trước khi đổi.");
        }

        // 3) Chèn bản mới ghi start_date = CURDATE()
        $stmt = $pdo->prepare("INSERT INTO teaching_assignments (teacher_id, class_id, subject_id, semester, school_year, start_date, is_active) VALUES (?, ?, ?, ?, ?, CURDATE(), 1)");
        $stmt->execute([$new_teacher_id, $old['class_id'], $old['subject_id'], $old['semester'], $old['school_year']]);

        $pdo->commit();
        $_SESSION['success'] = "Đổi giáo viên bộ môn thành công (lưu lịch sử).";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    header("Location: assignments.php" . ($selected_year ? "?school_year=" . urlencode($selected_year) : ""));
    exit();
}

// ---------------------------
// LẤY dữ liệu: danh sách phân công (lọc theo school_year nếu có)
if ($selected_year) {
    $stmt = $pdo->prepare("
        SELECT ta.*, u.full_name as teacher_name, c.class_name, s.subject_name
        FROM teaching_assignments ta
        JOIN users u ON ta.teacher_id = u.id
        JOIN classes c ON ta.class_id = c.id
        JOIN subjects s ON ta.subject_id = s.id
        WHERE ta.school_year = ?
        ORDER BY ta.is_active DESC, ta.start_date DESC, c.grade, c.class_name
    ");
    $stmt->execute([$selected_year]);
} else {
    $stmt = $pdo->query("
        SELECT ta.*, u.full_name as teacher_name, c.class_name, s.subject_name
        FROM teaching_assignments ta
        JOIN users u ON ta.teacher_id = u.id
        JOIN classes c ON ta.class_id = c.id
        JOIN subjects s ON ta.subject_id = s.id
        ORDER BY ta.school_year DESC, ta.is_active DESC, ta.start_date DESC, c.grade, c.class_name
    ");
}
$assignments = $stmt->fetchAll();

// Lấy danh sách giáo viên, lớp, môn học (dùng cho form thêm & modal đổi)
$teachers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY full_name")->fetchAll();
$classes = $pdo->query("SELECT id, class_name, grade FROM classes ORDER BY grade, class_name")->fetchAll();
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
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
                            <i class="fas fa-plus"></i> Thêm Phân công
                        </button>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Bộ lọc năm học -->
                <form method="GET" class="mb-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0">Năm học:</label>
                        </div>
                        <div class="col-auto">
                            <select name="school_year" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Tất cả --</option>
                                <?php foreach ($school_years as $y): ?>
                                    <option value="<?php echo htmlspecialchars($y); ?>" <?php if ($y === $selected_year) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($y); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <?php if ($selected_year): ?>
                                <a href="assignments.php" class="btn btn-outline-secondary">Xóa bộ lọc</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <!-- Batch actions -->
                <form method="POST" id="batchForm">
                    <div class="mb-2">
                        <button type="submit" name="end_selected" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn kết thúc các phân công đã chọn (chuyển trạng thái thành kết thúc)?')">
                            <i class="fas fa-flag-checkered"></i> Kết thúc các phân công đã chọn
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="assignmentsTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="checkAll"></th>
                                            <th>Giáo viên</th>
                                            <th>Lớp</th>
                                            <th>Môn học</th>
                                            <th>Học kỳ</th>
                                            <th>Năm học</th>
                                            <th>Bắt đầu</th>
                                            <th>Kết thúc</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <td><input type="checkbox" class="assign-check" name="assign_ids[]" value="<?php echo $assignment['id']; ?>"></td>
                                            <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                            <td>Học kỳ <?php echo htmlspecialchars($assignment['semester']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['school_year']); ?></td>
                                            <td><?php echo $assignment['start_date'] ? date('d/m/Y', strtotime($assignment['start_date'])) : '-'; ?></td>
                                            <td><?php echo $assignment['end_date'] ? date('d/m/Y', strtotime($assignment['end_date'])) : '-'; ?></td>
                                            <td>
                                                <?php if ($assignment['is_active']): ?>
                                                    <span class="badge bg-success">Đang giảng dạy</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Đã kết thúc</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Chuyển đổi giáo viên cho phân công này -->
                                                <button type="button" class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#changeTeacherModal"
                                                    data-assignment-id="<?php echo $assignment['id']; ?>"
                                                    data-class-name="<?php echo htmlspecialchars($assignment['class_name']); ?>"
                                                    data-subject-name="<?php echo htmlspecialchars($assignment['subject_name']); ?>">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>

                                                <!-- Xóa (thực chất là kết thúc) -->
                                                <a href="assignments.php?delete=<?php echo $assignment['id']; ?><?php echo $selected_year ? '&school_year='.urlencode($selected_year) : ''; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn kết thúc phân công này?')">
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
                </form>

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
                                        <?php echo htmlspecialchars($class['class_name']) . ' (Khối ' . $class['grade'] . ')'; ?>
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
                                <label class="form-label">Học kỳ *</label>
                                <select class="form-select" name="semester" required>
                                    <option value="1">Học kỳ 1</option>
                                    <option value="2">Học kỳ 2</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Năm học *</label>
                                <select class="form-select" name="school_year" required>
                                    <?php if (!empty($school_years)): ?>
                                        <?php foreach ($school_years as $y): ?>
                                            <option value="<?php echo htmlspecialchars($y); ?>"><?php echo htmlspecialchars($y); ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="2024-2025">2024-2025</option>
                                    <?php endif; ?>
                                </select>
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

    <!-- Modal Đổi Giáo viên -->
    <div class="modal fade" id="changeTeacherModal" tabindex="-1">
      <div class="modal-dialog">
        <form method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Đổi Giáo viên Bộ môn</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="assignment_id" id="assignId">
            <p><strong>Lớp:</strong> <span id="className" class="text-primary"></span></p>
            <p><strong>Môn:</strong> <span id="subjectName" class="text-primary"></span></p>
            <div class="mb-3">
              <label class="form-label">Chọn giáo viên mới</label>
              <select class="form-select" name="new_teacher_id" required>
                <option value="">-- Chọn giáo viên --</option>
                <?php foreach ($teachers as $t): ?>
                  <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" name="change_teacher" class="btn btn-primary">Cập nhật</button>
          </div>
        </form>
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
            },
            "order": []
        });
    });

    // Checkbox chọn tất
    document.addEventListener('DOMContentLoaded', function() {
        var checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                document.querySelectorAll('.assign-check').forEach(function(cb) {
                    cb.checked = checkAll.checked;
                });
            });
        }
    });

    // Đổ dữ liệu vào modal Đổi GV
    var changeModal = document.getElementById('changeTeacherModal');
    changeModal && changeModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var assignId = button.getAttribute('data-assignment-id');
      var className = button.getAttribute('data-class-name');
      var subjectName = button.getAttribute('data-subject-name');

      document.getElementById('assignId').value = assignId;
      document.getElementById('className').textContent = className;
      document.getElementById('subjectName').textContent = subjectName;
    });
    </script>
</body>
</html>
