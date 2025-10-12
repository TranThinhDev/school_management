<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Lấy lớp hiện tại của học sinh
$stmt = $pdo->prepare("SELECT class_id FROM class_students WHERE student_id = ? AND school_year = '2024-2025'");
$stmt->execute([$student_id]);
$class_info = $stmt->fetch();
$class_id = $class_info['class_id'];

// Xử lý filter
$selected_subject = $_GET['subject'] ?? '';
$selected_type = $_GET['type'] ?? '';

// Lấy danh sách tài liệu
$query = "
    SELECT m.*, u.full_name as teacher_name, s.subject_name, c.class_name
    FROM materials m
    JOIN users u ON m.teacher_id = u.id
    LEFT JOIN subjects s ON m.subject_id = s.id
    LEFT JOIN classes c ON m.class_id = c.id
    WHERE (m.class_id IS NULL OR m.class_id = ?)
";

$params = [$class_id];

if ($selected_subject) {
    $query .= " AND m.subject_id = ?";
    $params[] = $selected_subject;
}

if ($selected_type) {
    $query .= " AND m.file_type = ?";
    $params[] = $selected_type;
}

$query .= " ORDER BY m.uploaded_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

// Lấy danh sách môn học
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài liệu học tập - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-book-open"></i> Tài liệu học tập</h1>
                </div>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <label class="form-label">Loại tài liệu</label>
                                <select class="form-select" name="type">
                                    <option value="">Tất cả</option>
                                    <option value="document" <?php echo $selected_type == 'document' ? 'selected' : ''; ?>>Tài liệu</option>
                                    <option value="video" <?php echo $selected_type == 'video' ? 'selected' : ''; ?>>Video</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách tài liệu -->
                <div class="row">
                    <?php if ($materials): ?>
                        <?php foreach ($materials as $material): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                        <span class="badge bg-<?php echo $material['file_type'] == 'document' ? 'info' : 'warning'; ?>">
                                            <?php echo $material['file_type'] == 'document' ? 'Tài liệu' : 'Video'; ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text"><?php echo htmlspecialchars($material['description']); ?></p>
                                    
                                    <div class="material-info small text-muted mb-3">
                                        <div><i class="fas fa-user"></i> GV: <?php echo htmlspecialchars($material['teacher_name']); ?></div>
                                        <?php if ($material['subject_name']): ?>
                                            <div><i class="fas fa-book"></i> Môn: <?php echo htmlspecialchars($material['subject_name']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($material['class_name']): ?>
                                            <div><i class="fas fa-school"></i> Lớp: <?php echo htmlspecialchars($material['class_name']); ?></div>
                                        <?php endif; ?>
                                        <div><i class="fas fa-calendar"></i> Ngày: <?php echo date('d/m/Y H:i', strtotime($material['uploaded_at'])); ?></div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <a href="../<?php echo $material['file_path']; ?>" class="btn btn-success" target="_blank">
                                            <i class="fas fa-download"></i> Tải xuống
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Không có tài liệu nào phù hợp.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>