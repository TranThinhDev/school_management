<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

// Thống kê
$total_students = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'active'")->fetch()['total'];
$total_teachers = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND status = 'active'")->fetch()['total'];
$total_classes = $pdo->query("SELECT COUNT(*) as total FROM classes")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Dashboard Admin</h1>
                </div>

                <!-- Thống kê -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_students; ?></h4>
                                        <p class="mb-0">Học sinh</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_teachers; ?></h4>
                                        <p class="mb-0">Giáo viên</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_classes; ?></h4>
                                        <p class="mb-0">Lớp học</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-school fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-success">
                    <h4>Đăng nhập thành công!</h4>
                    <p>Chào mừng <strong><?php echo $_SESSION['full_name']; ?></strong> đến với hệ thống quản lý điểm THPT.</p>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>