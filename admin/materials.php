<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Xử lý xóa tài liệu
if (isset($_GET['delete'])) {
    $material_id = $_GET['delete'];
    
    try {
        // Lấy đường dẫn file trước khi xóa
        $stmt = $pdo->prepare("SELECT file_path FROM materials WHERE id = ?");
        $stmt->execute([$material_id]);
        $material = $stmt->fetch();
        
        if ($material) {
            // Xóa file vật lý
            if (file_exists($material['file_path'])) {
                unlink($material['file_path']);
            }
            
            // Xóa record trong database
            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
            $stmt->execute([$material_id]);
            $_SESSION['success'] = "Xóa tài liệu thành công!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: materials.php");
    exit();
}

// Xử lý filter
$selected_teacher = $_GET['teacher'] ?? '';
$selected_subject = $_GET['subject'] ?? '';
$selected_class = $_GET['class'] ?? '';
$selected_type = $_GET['type'] ?? '';

// Lấy danh sách tài liệu với filter
$query = "
    SELECT m.*, u.full_name as teacher_name, s.subject_name, c.class_name
    FROM materials m
    JOIN users u ON m.teacher_id = u.id
    LEFT JOIN subjects s ON m.subject_id = s.id
    LEFT JOIN classes c ON m.class_id = c.id
    WHERE 1=1
";

$params = [];

if ($selected_teacher) {
    $query .= " AND m.teacher_id = ?";
    $params[] = $selected_teacher;
}

if ($selected_subject) {
    $query .= " AND m.subject_id = ?";
    $params[] = $selected_subject;
}

if ($selected_class) {
    $query .= " AND m.class_id = ?";
    $params[] = $selected_class;
}

if ($selected_type) {
    $query .= " AND m.file_type = ?";
    $params[] = $selected_type;
}

$query .= " ORDER BY m.uploaded_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

// Lấy danh sách giáo viên
$teachers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name")->fetchAll();

// Lấy danh sách môn học
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();

// Lấy danh sách lớp
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài liệu - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .file-icon {
            font-size: 1.2em;
            margin-right: 5px;
        }
        .badge-document {
            background-color: #0dcaf0;
        }
        .badge-video {
            background-color: #ffc107;
            color: #000;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-file-alt"></i> Quản lý Tài liệu & Video</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Thống kê -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo count($materials); ?></h4>
                                        <p class="mb-0">Tổng số file</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0">
                                            <?php echo count(array_filter($materials, function($m) { return $m['file_type'] == 'document'; })); ?>
                                        </h4>
                                        <p class="mb-0">Tài liệu</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-pdf fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0">
                                            <?php echo count(array_filter($materials, function($m) { return $m['file_type'] == 'video'; })); ?>
                                        </h4>
                                        <p class="mb-0">Video</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-video fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo count($teachers); ?></h4>
                                        <p class="mb-0">Giáo viên</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter"></i> Bộ lọc
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Giáo viên</label>
                                <select class="form-select" name="teacher">
                                    <option value="">Tất cả giáo viên</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo $selected_teacher == $teacher['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Môn học</label>
                                <select class="form-select" name="subject">
                                    <option value="">Tất cả môn</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>" <?php echo $selected_subject == $subject['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Lớp</label>
                                <select class="form-select" name="class">
                                    <option value="">Tất cả lớp</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Loại</label>
                                <select class="form-select" name="type">
                                    <option value="">Tất cả</option>
                                    <option value="document" <?php echo $selected_type == 'document' ? 'selected' : ''; ?>>Tài liệu</option>
                                    <option value="video" <?php echo $selected_type == 'video' ? 'selected' : ''; ?>>Video</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="btn-group w-100">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Lọc
                                    </button>
                                    <a href="materials.php" class="btn btn-secondary">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách tài liệu -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list"></i> Danh sách tài liệu & video
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($materials)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Không có tài liệu nào.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="materialsTable" class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Tên tài liệu</th>
                                            <th>Giáo viên</th>
                                            <th>Môn học</th>
                                            <th>Lớp</th>
                                            <th>Loại</th>
                                            <th>Ngày upload</th>
                                            <th>Kích thước</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materials as $material): ?>
                                        <?php
                                            $file_size = file_exists($material['file_path']) ? 
                                                round(filesize($material['file_path']) / (1024 * 1024), 2) . ' MB' : 
                                                'N/A';
                                        ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-<?php echo $material['file_type'] == 'document' ? 'file-pdf' : 'video'; ?> file-icon text-<?php echo $material['file_type'] == 'document' ? 'danger' : 'primary'; ?>"></i>
                                                <strong><?php echo htmlspecialchars($material['title']); ?></strong>
                                                <?php if ($material['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($material['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($material['teacher_name']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($material['subject_name']): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($material['subject_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Chung</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($material['class_name']): ?>
                                                    <span class="badge bg-success"><?php echo htmlspecialchars($material['class_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Tất cả</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $material['file_type'] == 'document' ? 'badge-document' : 'badge-video'; ?>">
                                                    <?php echo $material['file_type'] == 'document' ? 'Tài liệu' : 'Video'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($material['uploaded_at'])); ?></td>
                                            <td><small class="text-muted"><?php echo $file_size; ?></small></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../<?php echo $material['file_path']; ?>" class="btn btn-success" target="_blank" title="Tải xuống">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <a href="materials.php?delete=<?php echo $material['id']; ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa tài liệu này?')" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
            // Khởi tạo DataTable
            $('#materialsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                },
                order: [[5, 'desc']], // Sắp xếp theo ngày upload mới nhất
                pageLength: 25
            });
        });
    </script>
</body>
</html>