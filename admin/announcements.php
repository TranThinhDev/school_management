<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Xử lý thêm thông báo
if (isset($_POST['add_announcement'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $target_audience = $_POST['target_audience'];
    $author_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, author_id, target_audience) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $author_id, $target_audience]);
        $_SESSION['success'] = "Đăng thông báo thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa thông báo
if (isset($_GET['delete'])) {
    $announcement_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$announcement_id]);
        $_SESSION['success'] = "Xóa thông báo thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: announcements.php");
    exit();
}

// Xử lý ẩn/hiện thông báo
if (isset($_GET['toggle'])) {
    $announcement_id = $_GET['toggle'];
    
    try {
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$announcement_id]);
        $_SESSION['success'] = "Cập nhật trạng thái thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: announcements.php");
    exit();
}

// Xử lý hiện thông báo khi xem chi tiết
if (isset($_GET['view'])) {
    $announcement_id = $_GET['view'];
    
    try {
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = 1 WHERE id = ?");
        $stmt->execute([$announcement_id]);
        $_SESSION['success'] = "Thông báo đã được hiển thị!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    header("Location: announcements.php");
    exit();
}

// Lấy danh sách thông báo
$stmt = $pdo->query("
    SELECT a.*, u.full_name as author_name 
    FROM announcements a 
    JOIN users u ON a.author_id = u.id 
    ORDER BY 
        a.is_active DESC,
        a.created_at DESC
");
$announcements = $stmt->fetchAll();

// Đếm số thông báo đang hiển thị
$active_count = 0;
$inactive_count = 0;
foreach ($announcements as $announcement) {
    if ($announcement['is_active']) {
        $active_count++;
    } else {
        $inactive_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Thông báo - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .announcement-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        .announcement-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .active-announcement {
            border-left-color: #28a745;
            background-color: #f8fff9;
            border: 1px solid #d4edda;
        }
        .inactive-announcement {
            border-left-color: #6c757d;
            background-color: #f8f9fa;
            opacity: 0.8;
        }
        .announcement-date {
            font-size: 0.9em;
            color: #6c757d;
        }
        .announcement-preview {
            color: #6c757d;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .status-badge {
            font-size: 0.7em;
            padding: 0.25em 0.6em;
        }
        .section-divider {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .priority-indicator {
            width: 4px;
            background: #28a745;
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
        }
        .action-buttons {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .announcement-card:hover .action-buttons {
            opacity: 1;
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
                    <h1 class="h2">
                        <i class="fas fa-bullhorn"></i> Quản lý Thông báo
                        
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                        <i class="fas fa-plus"></i> Thêm Thông báo
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($announcements)): ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-bullhorn fa-3x mb-3 text-muted"></i>
                        <h4>Không có thông báo nào</h4>
                        <p class="text-muted">Hãy tạo thông báo đầu tiên để bắt đầu.</p>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                            <i class="fas fa-plus"></i> Thêm Thông báo
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Phân loại thông báo -->
                    <?php
                    $active_announcements = array_filter($announcements, function($a) { return $a['is_active']; });
                    $inactive_announcements = array_filter($announcements, function($a) { return !$a['is_active']; });
                    ?>
                    
                    <!-- THÔNG BÁO ĐANG HIỂN THỊ -->
                    <?php if (!empty($active_announcements)): ?>
                        <div class="section-divider">
                            <h4 class="section-title text-success">
                                <i class="fas fa-eye"></i>
                                Thông báo đang hiển thị (<?php echo count($active_announcements); ?>)
                            </h4>
                            <div class="row">
                                <?php foreach ($active_announcements as $announcement): ?>
                                    <?php
                                    $is_new = strtotime($announcement['created_at']) > strtotime('-7 days');
                                    $preview_content = strip_tags($announcement['content']);
                                    if (strlen($preview_content) > 150) {
                                        $preview_content = substr($preview_content, 0, 150) . '...';
                                    }
                                    ?>
                                    <div class="col-12 mb-3">
                                        <div class="card announcement-card active-announcement position-relative" 
                                             data-bs-toggle="modal" 
                                             data-bs-target="#announcementModal"
                                             data-announcement-id="<?php echo $announcement['id']; ?>"
                                             data-is-active="1">
                                            <div class="priority-indicator"></div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="card-title mb-0 text-dark">
                                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                                            <span class="badge bg-success status-badge ms-2">ĐANG HIỂN THỊ</span>
                                                            <?php if ($is_new): ?>
                                                                <span class="badge bg-danger status-badge ms-1">MỚI</span>
                                                            <?php endif; ?>
                                                        </h5>
                                                    </div>
                                                    <small class="announcement-date">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($announcement['created_at'])); ?>
                                                    </small>
                                                </div>
                                                
                                                <p class="card-text announcement-preview mb-3">
                                                    <?php echo htmlspecialchars($preview_content); ?>
                                                </p>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="text-muted">
                                                        <small>
                                                            <i class="fas fa-user"></i>
                                                            Đăng bởi: <?php echo htmlspecialchars($announcement['author_name']); ?>
                                                        </small>
                                                        <small class="ms-3">
                                                            <i class="fas fa-users"></i>
                                                            Đối tượng: 
                                                            <?php
                                                            $audience_labels = [
                                                                'all' => 'Tất cả',
                                                                'teachers' => 'Giáo viên',
                                                                'students' => 'Học sinh'
                                                            ];
                                                            echo $audience_labels[$announcement['target_audience']];
                                                            ?>
                                                        </small>
                                                    </div>
                                                    <div class="action-buttons">
                                                        <!-- Nút Ẩn thông báo -->
                                                        <a href="announcements.php?toggle=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-warning" title="Ẩn thông báo">
                                                            <i class="fas fa-eye-slash"></i>
                                                        </a>
                                                        <!-- Nút Sửa -->
                                                        <a href="edit_announcement.php?id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-info" title="Sửa thông báo">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <!-- Nút Xóa -->
                                                        <a href="announcements.php?delete=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa thông báo này?')" title="Xóa thông báo">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- THÔNG BÁO ĐANG ẨN -->
                    <?php if (!empty($inactive_announcements)): ?>
                        <div>
                            
                            <div class="row">
                                <?php foreach ($inactive_announcements as $announcement): ?>
                                    <?php
                                    $is_new = strtotime($announcement['created_at']) > strtotime('-7 days');
                                    $preview_content = strip_tags($announcement['content']);
                                    if (strlen($preview_content) > 150) {
                                        $preview_content = substr($preview_content, 0, 150) . '...';
                                    }
                                    ?>
                                    <div class="col-12 mb-3">
                                        <div class="card announcement-card inactive-announcement" 
                                             data-bs-toggle="modal" 
                                             data-bs-target="#announcementModal"
                                             data-announcement-id="<?php echo $announcement['id']; ?>"
                                             data-is-active="0">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="card-title mb-0 text-muted">
                                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                                            <span class="badge bg-secondary status-badge ms-2">ĐANG ẨN</span>
                                                        </h5>
                                                    </div>
                                                    <small class="announcement-date">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($announcement['created_at'])); ?>
                                                    </small>
                                                </div>
                                                
                                                <p class="card-text announcement-preview mb-3">
                                                    <?php echo htmlspecialchars($preview_content); ?>
                                                </p>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="text-muted">
                                                        <small>
                                                            <i class="fas fa-user"></i>
                                                            Đăng bởi: <?php echo htmlspecialchars($announcement['author_name']); ?>
                                                        </small>
                                                        <small class="ms-3">
                                                            <i class="fas fa-users"></i>
                                                            Đối tượng: 
                                                            <?php
                                                            $audience_labels = [
                                                                'all' => 'Tất cả',
                                                                'teachers' => 'Giáo viên',
                                                                'students' => 'Học sinh'
                                                            ];
                                                            echo $audience_labels[$announcement['target_audience']];
                                                            ?>
                                                        </small>
                                                    </div>
                                                    <div class="action-buttons">
                                                        <!-- Nút Hiện thông báo -->
                                                        <a href="announcements.php?view=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-success" title="Hiển thị thông báo">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <!-- Nút Sửa -->
                                                        <a href="edit_announcement.php?id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-info" title="Sửa thông báo">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <!-- Nút Xóa -->
                                                        <a href="announcements.php?delete=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa thông báo này?')" title="Xóa thông báo">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal Thêm Thông báo -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Thông báo Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nội dung *</label>
                            <textarea class="form-control" name="content" rows="6" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Đối tượng *</label>
                            <select class="form-select" name="target_audience" required>
                                <option value="all">Tất cả</option>
                                <option value="teachers">Chỉ giáo viên</option>
                                <option value="students">Chỉ học sinh</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_announcement" class="btn btn-primary">Đăng thông báo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal xem chi tiết thông báo -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="announcementTitle"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="text-muted" id="announcementAuthor"></small>
                        <small class="text-muted" id="announcementDate"></small>
                    </div>
                    <div class="announcement-content" id="announcementContent" style="line-height: 1.6;"></div>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto" id="announcementAudience"></small>
                    <small class="text-muted me-auto" id="announcementStatus"></small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dữ liệu thông báo
        const announcements = {
            <?php foreach ($announcements as $announcement): ?>
                <?php echo $announcement['id']; ?>: {
                    title: `<?php echo addslashes($announcement['title']); ?>`,
                    content: `<?php echo addslashes(nl2br($announcement['content'])); ?>`,
                    author: `<?php echo addslashes($announcement['author_name']); ?>`,
                    date: `<?php echo date('d/m/Y H:i', strtotime($announcement['created_at'])); ?>`,
                    audience: `<?php 
                        $audience_labels = [
                            'all' => 'Tất cả',
                            'teachers' => 'Giáo viên', 
                            'students' => 'Học sinh'
                        ];
                        echo addslashes($audience_labels[$announcement['target_audience']]);
                    ?>`,
                    isActive: <?php echo $announcement['is_active']; ?>
                },
            <?php endforeach; ?>
        };

        // Hàm xem chi tiết thông báo
        function viewAnnouncement(announcementId) {
            const announcement = announcements[announcementId];
            if (announcement) {
                document.getElementById('announcementTitle').textContent = announcement.title;
                document.getElementById('announcementContent').innerHTML = announcement.content;
                document.getElementById('announcementAuthor').innerHTML = `<i class="fas fa-user"></i> Đăng bởi: ${announcement.author}`;
                document.getElementById('announcementDate').innerHTML = `<i class="fas fa-clock"></i> ${announcement.date}`;
                document.getElementById('announcementAudience').innerHTML = `<i class="fas fa-users"></i> Đối tượng: ${announcement.audience}`;
                document.getElementById('announcementStatus').innerHTML = announcement.isActive ? 
                    '<span class="badge bg-success">ĐANG HIỂN THỊ</span>' : 
                    '<span class="badge bg-secondary">ĐANG ẨN</span>';
            }
        }

        // Xử lý khi click vào thông báo
        document.addEventListener('DOMContentLoaded', function() {
            const announcementCards = document.querySelectorAll('.announcement-card');
            announcementCards.forEach(card => {
                card.addEventListener('click', function() {
                    const announcementId = this.getAttribute('data-announcement-id');
                    viewAnnouncement(announcementId);
                });
            });
        });
    </script>
</body>
</html>