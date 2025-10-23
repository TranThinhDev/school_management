<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['student']);

$student_id = $_SESSION['user_id'];

// 🟢 Bộ lọc: all / unread / read
$filter = $_GET['filter'] ?? 'all';

// 🟢 Xử lý đánh dấu đã đọc khi xem chi tiết
if (isset($_GET['view'])) {
    $announcement_id = $_GET['view'];
    
    // Cập nhật viewed_at thay vì chỉ insert
    $stmt = $pdo->prepare("
        INSERT INTO announcement_views (announcement_id, user_id, viewed_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE viewed_at = NOW()
    ");
    $stmt->execute([$announcement_id, $student_id]);
    
    // Lấy chi tiết thông báo
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name as author_name 
        FROM announcements a 
        JOIN users u ON a.author_id = u.id 
        WHERE a.id = ? 
        AND (a.target_audience = 'all' OR a.target_audience = 'students')
        AND a.is_active = TRUE
    ");
    $stmt->execute([$announcement_id]);
    $announcement_detail = $stmt->fetch();
    
    if (!$announcement_detail) {
        $_SESSION['error'] = "Không tìm thấy thông báo!";
        header("Location: announcements.php");
        exit();
    }
}

// 🟢 Xử lý bộ lọc hiển thị
$filter_condition = "";
if ($filter === 'unread') {
    $filter_condition = "AND av.id IS NULL";
} elseif ($filter === 'read') {
    $filter_condition = "AND av.id IS NOT NULL";
}

// 🟢 Lấy danh sách thông báo
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name as author_name,
           av.viewed_at,
           CASE WHEN av.viewed_at IS NOT NULL THEN 1 ELSE 0 END AS is_read
    FROM announcements a
    JOIN users u ON a.author_id = u.id
    LEFT JOIN announcement_views av 
        ON a.id = av.announcement_id AND av.user_id = ?
    WHERE (a.target_audience = 'all' OR a.target_audience = 'students')
      AND a.is_active = TRUE
      $filter_condition
    ORDER BY 
        is_read ASC, 
        a.created_at DESC
");
$stmt->execute([$student_id]);
$announcements = $stmt->fetchAll();

// 🟢 Đếm tổng chưa đọc
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM announcements a
    LEFT JOIN announcement_views av 
        ON a.id = av.announcement_id AND av.user_id = ?
    WHERE (a.target_audience = 'all' OR a.target_audience = 'students')
      AND a.is_active = TRUE
      AND av.id IS NULL
");
$stmt->execute([$student_id]);
$total_unread = $stmt->fetch()['total'];

// 🟢 Đếm số thông báo mới trong 7 ngày
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM announcements a
    LEFT JOIN announcement_views av 
        ON a.id = av.announcement_id AND av.user_id = ?
    WHERE (a.target_audience = 'all' OR a.target_audience = 'students')
      AND a.is_active = TRUE
      AND a.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND av.id IS NULL
");
$stmt->execute([$student_id]);
$new_announcements_count = $stmt->fetch()['total'];
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo - <?php echo SITE_NAME; ?></title>
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
        .unread-announcement {
            border-left-color: #28a745;
            background-color: #f8fff9;
            border: 1px solid #d4edda;
        }
        .read-announcement {
            border-left-color: #6c757d;
            background-color: #f8f9fa;
            opacity: 0.9;
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
                        <i class="fas fa-bullhorn"></i> Thông báo
                        <?php if ($total_unread > 0): ?>
                            <span class="badge bg-danger ms-2" id="newCountBadge"><?php echo $total_unread; ?> chưa đọc</span>
                        <?php endif; ?>
                    </h1>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?filter=all">Tất cả</a></li>
                            <li><a class="dropdown-item" href="?filter=unread">Chưa đọc</a></li>
                            <li><a class="dropdown-item" href="?filter=read">Đã đọc</a></li>
                        </ul>
                    </div>
                </div>

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
                        <p class="text-muted">Hiện tại không có thông báo nào dành cho bạn.</p>
                    </div>
                <?php else: ?>
                    <!-- Phân loại thông báo -->
                    <?php
                    $unread_announcements = array_filter($announcements, function($a) { return !$a['is_read']; });
                    $read_announcements = array_filter($announcements, function($a) { return $a['is_read']; });
                    ?>
                    
                    <!-- THÔNG BÁO CHƯA ĐỌC -->
                    <?php if (!empty($unread_announcements)): ?>
                        <div class="section-divider">
                            <h4 class="section-title text-success">
                                <i class="fas fa-exclamation-circle"></i>
                                Thông báo chưa đọc (<?php echo count($unread_announcements); ?>)
                            </h4>
                            <div class="row">
                                <?php foreach ($unread_announcements as $announcement): ?>
                                    <?php
                                    $is_new = strtotime($announcement['created_at']) > strtotime('-7 days');
                                    $preview_content = strip_tags($announcement['content']);
                                    if (strlen($preview_content) > 150) {
                                        $preview_content = substr($preview_content, 0, 150) . '...';
                                    }
                                    ?>
                                    <div class="col-12 mb-3">
                                        <div class="card announcement-card unread-announcement position-relative" 
                                             data-bs-toggle="modal" 
                                             data-bs-target="#announcementModal"
                                             data-announcement-id="<?php echo $announcement['id']; ?>"
                                             data-is-read="0">
                                            <div class="priority-indicator"></div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="card-title mb-0 text-dark">
                                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                                            <span class="badge bg-success status-badge ms-2">CHƯA ĐỌC</span>
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
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i>
                                                        Đăng bởi: <?php echo htmlspecialchars($announcement['author_name']); ?>
                                                    </small>
                                                    <small class="text-muted">
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
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- THÔNG BÁO ĐÃ ĐỌC -->
                    <?php if (!empty($read_announcements)): ?>
                        <div>
                            <h4 class="section-title text-secondary">
                                <i class="fas fa-check-circle"></i>
                                Thông báo đã đọc (<?php echo count($read_announcements); ?>)
                            </h4>
                            <div class="row">
                                <?php foreach ($read_announcements as $announcement): ?>
                                    <?php
                                    $is_new = strtotime($announcement['created_at']) > strtotime('-7 days');
                                    $preview_content = strip_tags($announcement['content']);
                                    if (strlen($preview_content) > 150) {
                                        $preview_content = substr($preview_content, 0, 150) . '...';
                                    }
                                    ?>
                                    <div class="col-12 mb-3">
                                        <div class="card announcement-card read-announcement" 
                                             data-bs-toggle="modal" 
                                             data-bs-target="#announcementModal"
                                             data-announcement-id="<?php echo $announcement['id']; ?>"
                                             data-is-read="1">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="card-title mb-0 text-muted">
                                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                                            <span class="badge bg-secondary status-badge ms-2">ĐÃ ĐỌC</span>
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
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i>
                                                        Đăng bởi: <?php echo htmlspecialchars($announcement['author_name']); ?>
                                                    </small>
                                                    <small class="text-muted">
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
                    isRead: <?php echo $announcement['is_read']; ?>
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

                // Đánh dấu đã đọc nếu chưa đọc
                if (!announcement.isRead) {
                    markAsRead(announcementId);
                }
            }
        }

        // Đánh dấu đã đọc bằng AJAX
        function markAsRead(announcementId) {
            fetch(`mark_as_read.php?announcement_id=${announcementId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cập nhật giao diện - di chuyển thông báo từ phần chưa đọc sang đã đọc
                        moveToReadSection(announcementId);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Di chuyển thông báo từ phần chưa đọc sang đã đọc
        function moveToReadSection(announcementId) {
            const card = document.querySelector(`[data-announcement-id="${announcementId}"]`);
            if (card) {
                // Cập nhật trạng thái card
                card.classList.remove('unread-announcement');
                card.classList.add('read-announcement');
                
                // Cập nhật badge
                const badge = card.querySelector('.badge.bg-success, .badge.bg-danger');
                if (badge) {
                    badge.textContent = 'ĐÃ ĐỌC';
                    badge.className = 'badge bg-secondary status-badge ms-2';
                }
                
                // Di chuyển card đến phần đã đọc
                const readSection = document.querySelector('.section-divider + div .row');
                if (readSection) {
                    readSection.appendChild(card.parentElement);
                }
                
                // Cập nhật số lượng
                updateUnreadCount();
            }
        }

        // Cập nhật số lượng thông báo chưa đọc
        function updateUnreadCount() {
            const badge = document.getElementById('newCountBadge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = (currentCount - 1) + ' chưa đọc';
                } else {
                    badge.remove();
                    
                    // Ẩn tiêu đề phần chưa đọc nếu không còn thông báo nào
                    const unreadSection = document.querySelector('.section-divider');
                    if (unreadSection) {
                        const unreadCards = unreadSection.querySelectorAll('.announcement-card');
                        if (unreadCards.length === 0) {
                            unreadSection.remove();
                        }
                    }
                }
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

        // Tự động đóng alert sau 5 giây
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
</body>
</html>