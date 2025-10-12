<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Lấy thông tin học sinh
$stmt = $pdo->prepare("
    SELECT u.*, c.class_name, c.grade, hm.full_name as homeroom_teacher
    FROM users u 
    JOIN class_students cs ON u.id = cs.student_id 
    JOIN classes c ON cs.class_id = c.id 
    LEFT JOIN users hm ON c.homeroom_teacher_id = hm.id 
    WHERE u.id = ? AND cs.school_year = '2024-2025'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Lấy điểm trung bình các môn
$stmt = $pdo->prepare("
    SELECT s.subject_name, sc.semester, sc.average
    FROM scores sc
    JOIN subjects s ON sc.subject_id = s.id
    WHERE sc.student_id = ? AND sc.school_year = '2024-2025'
    ORDER BY sc.semester, s.subject_name
");
$stmt->execute([$student_id]);
$scores = $stmt->fetchAll();

// Xử lý cập nhật thông tin
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate
    if (empty($full_name)) {
        $_SESSION['error'] = "Họ và tên không được để trống!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Email không hợp lệ!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $student_id]);
            $_SESSION['success'] = "Cập nhật thông tin thành công!";
            
            // Cập nhật lại session
            $_SESSION['full_name'] = $full_name;
            
            // Reload thông tin
            $stmt = $pdo->prepare("
                SELECT u.*, c.class_name, c.grade, hm.full_name as homeroom_teacher
                FROM users u 
                JOIN class_students cs ON u.id = cs.student_id 
                JOIN classes c ON cs.class_id = c.id 
                LEFT JOIN users hm ON c.homeroom_teacher_id = hm.id 
                WHERE u.id = ? AND cs.school_year = '2024-2025'
            ");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Lỗi: " . $e->getMessage();
        }
    }
}

// Xử lý đổi mật khẩu - PHIÊN BẢN ĐÃ SỬA
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Lấy mật khẩu hiện tại từ database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $password_data = $stmt->fetch();
    
    // SỬA: So sánh mật khẩu trực tiếp (vì database lưu plain text)
    if (!$password_data) {
        $_SESSION['error'] = "Lỗi: Không thể lấy thông tin mật khẩu!";
    } elseif ($current_password !== $password_data['password']) {
        $_SESSION['error'] = "Mật khẩu hiện tại không đúng!";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Mật khẩu mới không khớp!";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "Mật khẩu phải có ít nhất 6 ký tự!";
    } else {
        try {
            // SỬA: Lưu mật khẩu mới dưới dạng plain text
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $student_id]);
            $_SESSION['success'] = "Đổi mật khẩu thành công!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
        }
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4facfe;
        }
        .score-badge {
            font-size: 0.9rem;
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
                    <h1 class="h2"><i class="fas fa-user-graduate"></i> Hồ sơ cá nhân</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Thông tin cá nhân -->
                    <div class="col-md-4">
                        <div class="card profile-card">
                            <div class="profile-header text-center">
                                <div class="avatar mx-auto mb-3">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                                <p class="mb-1">Học sinh</p>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($student['username']); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong><i class="fas fa-school text-primary"></i> Lớp:</strong>
                                    <p class="mb-1"><?php echo htmlspecialchars($student['class_name']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-user-tie text-success"></i> GVCN:</strong>
                                    <p class="mb-1"><?php echo htmlspecialchars($student['homeroom_teacher'] ?? 'Chưa có'); ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-envelope text-info"></i> Email:</strong>
                                    <p class="mb-1"><?php echo htmlspecialchars($student['email'] ?? 'Chưa cập nhật'); ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-phone text-warning"></i> Điện thoại:</strong>
                                    <p class="mb-1"><?php echo htmlspecialchars($student['phone'] ?? 'Chưa cập nhật'); ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-calendar-alt text-secondary"></i> Năm học:</strong>
                                    <p class="mb-0">2024-2025</p>
                                </div>
                            </div>
                        </div>

                        <!-- Điểm trung bình -->
                        <div class="card profile-card mt-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-line"></i> Điểm trung bình</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($scores)): ?>
                                    <p class="text-muted">Chưa có điểm số</p>
                                <?php else: ?>
                                    <?php 
                                    $semester_scores = [];
                                    foreach ($scores as $score) {
                                        $semester_scores[$score['semester']][] = $score;
                                    }
                                    ?>
                                    <?php foreach ($semester_scores as $semester => $subjects): ?>
                                        <h6 class="mt-3">Học kỳ <?php echo $semester; ?></h6>
                                        <?php foreach ($subjects as $subject): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                                                <span class="badge bg-primary score-badge">
                                                    <?php echo $subject['average'] ?? 'Chưa có'; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Cập nhật thông tin -->
                    <div class="col-md-8">
                        <div class="card profile-card">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                                            <i class="fas fa-user-edit"></i> Cập nhật thông tin
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                                            <i class="fas fa-lock"></i> Đổi mật khẩu
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="profileTabsContent">
                                    <!-- Tab thông tin -->
                                    <div class="tab-pane fade show active" id="info" role="tabpanel">
                                        <form method="POST">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Tên đăng nhập</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['username']); ?>" readonly>
                                                        <div class="form-text">Tên đăng nhập không thể thay đổi</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Lớp</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['class_name']); ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Họ và tên *</label>
                                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Email *</label>
                                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Điện thoại</label>
                                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Cập nhật thông tin
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Tab đổi mật khẩu -->
                                    <div class="tab-pane fade" id="password" role="tabpanel">
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label class="form-label">Mật khẩu hiện tại *</label>
                                                <input type="password" class="form-control" name="current_password" required>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Mật khẩu mới *</label>
                                                        <input type="password" class="form-control" name="new_password" required minlength="6">
                                                        <div class="form-text">Mật khẩu có thể chứa chữ, số và ký tự đặc biệt</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Xác nhận mật khẩu *</label>
                                                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> Mật khẩu phải có ít nhất 6 ký tự. Có thể sử dụng chữ, số và ký tự đặc biệt.
                                            </div>
                                            
                                            <button type="submit" name="change_password" class="btn btn-warning">
                                                <i class="fas fa-key"></i> Đổi mật khẩu
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin học tập -->
                        <div class="card profile-card mt-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Thông tin học tập</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-primary">
                                                <?php 
                                                $stmt = $pdo->prepare("
                                                    SELECT COUNT(DISTINCT subject_id) as subject_count 
                                                    FROM scores 
                                                    WHERE student_id = ? AND school_year = '2024-2025'
                                                ");
                                                $stmt->execute([$student_id]);
                                                $result = $stmt->fetch();
                                                echo $result['subject_count'];
                                                ?>
                                            </h4>
                                            <p class="mb-0">Môn học</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-success">
                                                <?php 
                                                $stmt = $pdo->prepare("
                                                    SELECT AVG(average) as overall_avg 
                                                    FROM scores 
                                                    WHERE student_id = ? AND school_year = '2024-2025' AND average IS NOT NULL
                                                ");
                                                $stmt->execute([$student_id]);
                                                $result = $stmt->fetch();
                                                echo $result['overall_avg'] ? number_format($result['overall_avg'], 2) : '0.00';
                                                ?>
                                            </h4>
                                            <p class="mb-0">Điểm TB chung</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-warning">
                                                <?php 
                                                $stmt = $pdo->prepare("
                                                    SELECT COUNT(*) as score_count 
                                                    FROM scores 
                                                    WHERE student_id = ? AND school_year = '2024-2025'
                                                ");
                                                $stmt->execute([$student_id]);
                                                $result = $stmt->fetch();
                                                echo $result['score_count'];
                                                ?>
                                            </h4>
                                            <p class="mb-0">Bảng điểm</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Khởi tạo tabs
        const triggerTabList = [].slice.call(document.querySelectorAll('#profileTabs button'))
        triggerTabList.forEach(function (triggerEl) {
            const tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        });
    </script>
</body>
</html>