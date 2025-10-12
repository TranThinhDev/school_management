<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Xử lý upload tài liệu
if (isset($_POST['upload_material'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
    $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
    $file_type = $_POST['file_type'];

    // Upload file
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_path = uploadFile($_FILES['file'], $file_type);
        
        if ($file_path) {
            try {
                $stmt = $pdo->prepare("INSERT INTO materials (teacher_id, title, description, file_path, file_type, subject_id, class_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$teacher_id, $title, $description, $file_path, $file_type, $subject_id, $class_id]);
                $_SESSION['success'] = "Upload tài liệu thành công!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Lỗi: " . $e->getMessage();
                // Xóa file đã upload nếu có lỗi
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        } else {
            $_SESSION['error'] = "Lỗi upload file!";
        }
    } else {
        $_SESSION['error'] = "Vui lòng chọn file!";
    }
}

// Xử lý xóa tài liệu
if (isset($_GET['delete'])) {
    $material_id = $_GET['delete'];
    
    try {
        // Lấy đường dẫn file trước khi xóa
        $stmt = $pdo->prepare("SELECT file_path FROM materials WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$material_id, $teacher_id]);
        $material = $stmt->fetch();
        
        if ($material) {
            // Xóa file vật lý
            if (file_exists($material['file_path'])) {
                unlink($material['file_path']);
            }
            
            // Xóa record trong database
            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$material_id, $teacher_id]);
            $_SESSION['success'] = "Xóa tài liệu thành công!";
        } else {
            $_SESSION['error'] = "Không tìm thấy tài liệu hoặc bạn không có quyền xóa!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: materials.php");
    exit();
}

// Lấy danh sách tài liệu của giáo viên
try {
    $stmt = $pdo->prepare("
        SELECT m.*, s.subject_name, c.class_name 
        FROM materials m 
        LEFT JOIN subjects s ON m.subject_id = s.id 
        LEFT JOIN classes c ON m.class_id = c.id 
        WHERE m.teacher_id = ? 
        ORDER BY m.uploaded_at DESC
    ");
    $stmt->execute([$teacher_id]);
    $materials = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Lỗi khi lấy danh sách tài liệu: " . $e->getMessage());
    $materials = [];
}

// Lấy danh sách môn học giáo viên đang dạy
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.subject_name 
        FROM teaching_assignments ta 
        JOIN subjects s ON ta.subject_id = s.id 
        WHERE ta.teacher_id = ? 
        ORDER BY s.subject_name
    ");
    $stmt->execute([$teacher_id]);
    $subjects = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Lỗi khi lấy danh sách môn học: " . $e->getMessage());
    $subjects = [];
}

// Lấy danh sách lớp giáo viên đang dạy
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.class_name 
        FROM teaching_assignments ta 
        JOIN classes c ON ta.class_id = c.id 
        WHERE ta.teacher_id = ? 
        ORDER BY c.class_name
    ");
    $stmt->execute([$teacher_id]);
    $classes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Lỗi khi lấy danh sách lớp: " . $e->getMessage());
    $classes = [];
}
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-file-upload"></i> Quản lý Tài liệu & Video</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal">
                        <i class="fas fa-upload"></i> Upload Tài liệu
                    </button>
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

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list"></i> Danh sách tài liệu đã upload
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($materials)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có tài liệu nào được upload.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="materialsTable" class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Tên tài liệu</th>
                                            <th>Mô tả</th>
                                            <th>Môn học</th>
                                            <th>Lớp</th>
                                            <th>Loại</th>
                                            <th>Ngày upload</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materials as $material): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-<?php echo $material['file_type'] == 'document' ? 'file-pdf' : 'video'; ?> file-icon text-<?php echo $material['file_type'] == 'document' ? 'danger' : 'primary'; ?>"></i>
                                                <strong><?php echo htmlspecialchars($material['title']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($material['description'] ?: 'Không có mô tả'); ?></td>
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

    <!-- Modal Upload Tài liệu -->
    <div class="modal fade" id="uploadMaterialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-upload"></i> Upload Tài liệu/Video
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Loại file *</label>
                                    <select class="form-select" name="file_type" required id="fileType">
                                        <option value="document">Tài liệu</option>
                                        <option value="video">Video</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">File *</label>
                                    <input type="file" class="form-control" name="file" required id="fileInput" accept="">
                                    <div class="form-text" id="fileHelp">
                                        Đối với tài liệu: PDF, DOC, DOCX, PPT, PPTX (Tối đa 10MB)
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề *</label>
                            <input type="text" class="form-control" name="title" required placeholder="Nhập tiêu đề tài liệu">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Nhập mô tả cho tài liệu"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Môn học</label>
                                    <select class="form-select" name="subject_id">
                                        <option value="">Chọn môn học (tùy chọn)</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>">
                                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lớp</label>
                                    <select class="form-select" name="class_id">
                                        <option value="">Chọn lớp (tùy chọn)</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>">
                                                <?php echo htmlspecialchars($class['class_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="upload_material" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload
                        </button>
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
            // Khởi tạo DataTable
            $('#materialsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                },
                order: [[5, 'desc']] // Sắp xếp theo ngày upload mới nhất
            });

            // Xử lý thay đổi loại file
            $('#fileType').change(function() {
                const fileType = $(this).val();
                const fileInput = $('#fileInput');
                const fileHelp = $('#fileHelp');
                
                if (fileType === 'document') {
                    fileInput.attr('accept', '.pdf,.doc,.docx,.ppt,.pptx,.txt');
                    fileHelp.html('Đối với tài liệu: PDF, DOC, DOCX, PPT, PPTX, TXT (Tối đa 10MB)');
                } else {
                    fileInput.attr('accept', '.mp4,.avi,.mov,.mkv,.wmv');
                    fileHelp.html('Đối với video: MP4, AVI, MOV, MKV, WMV (Tối đa 50MB)');
                }
            });

            // Kiểm tra file size trước khi upload
            $('#uploadForm').on('submit', function(e) {
                const fileInput = document.getElementById('fileInput');
                const fileType = document.getElementById('fileType').value;
                const maxSize = fileType === 'document' ? 10 * 1024 * 1024 : 50 * 1024 * 1024; // 10MB hoặc 50MB
                
                if (fileInput.files.length > 0) {
                    const fileSize = fileInput.files[0].size;
                    if (fileSize > maxSize) {
                        e.preventDefault();
                        alert('Kích thước file quá lớn! Vui lòng chọn file nhỏ hơn ' + (maxSize / (1024 * 1024)) + 'MB.');
                        return false;
                    }
                }
            });
        });
    </script>
</body>
</html>