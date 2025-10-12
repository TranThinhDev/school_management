<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = $_GET['id'];

// Lấy thông tin học sinh
$stmt = $pdo->prepare("
    SELECT u.*, c.class_name 
    FROM users u 
    LEFT JOIN class_students cs ON u.id = cs.student_id 
    LEFT JOIN classes c ON cs.class_id = c.id 
    WHERE u.id = ? AND u.role = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = "Không tìm thấy học sinh!";
    header("Location: students.php");
    exit();
}

// Lấy điểm của học sinh
$stmt = $pdo->prepare("
    SELECT s.*, sj.subject_name, c.class_name 
    FROM scores s 
    JOIN subjects sj ON s.subject_id = sj.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.student_id = ? 
    ORDER BY s.semester, sj.subject_name
");
$stmt->execute([$student_id]);
$scores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điểm số Học sinh - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">
                        <i class="fas fa-chart-line"></i> Điểm số: <?php echo htmlspecialchars($student['full_name']); ?>
                    </h1>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Thông tin học sinh</h5>
                                <p><strong>Mã HS:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
                                <p><strong>Lớp:</strong> <?php echo htmlspecialchars($student['class_name'] ?? 'Chưa xếp lớp'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if ($scores): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Môn học</th>
                                            <th>Học kỳ</th>
                                            <th>Điểm miệng</th>
                                            <th>Điểm 15p</th>
                                            <th>Điểm 45p</th>
                                            <th>Giữa kỳ</th>
                                            <th>Cuối kỳ</th>
                                            <th>Điểm TB</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($scores as $score): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($score['subject_name']); ?></td>
                                            <td>Học kỳ <?php echo htmlspecialchars($score['semester']); ?></td>
                                            <td>
                                                <?php 
                                                $oral_scores = array_filter([$score['oral_1'], $score['oral_2'], $score['oral_3']]);
                                                echo $oral_scores ? implode(', ', $oral_scores) : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $fifteen_scores = array_filter([$score['fifteen_min_1'], $score['fifteen_min_2'], $score['fifteen_min_3']]);
                                                echo $fifteen_scores ? implode(', ', $fifteen_scores) : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $forty_five_scores = array_filter([$score['forty_five_min_1'], $score['forty_five_min_2'], $score['forty_five_min_3']]);
                                                echo $forty_five_scores ? implode(', ', $forty_five_scores) : '-';
                                                ?>
                                            </td>
                                            <td><?php echo $score['mid_term'] ?? '-'; ?></td>
                                            <td><?php echo $score['final_term'] ?? '-'; ?></td>
                                            <td>
                                                <strong class="<?php echo $score['average'] >= 5 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $score['average'] ?? 'Chưa có'; ?>
                                                </strong>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Học sinh chưa có điểm số.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>