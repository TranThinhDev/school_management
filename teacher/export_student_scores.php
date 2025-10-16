<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Lấy ID học sinh cần export
if (!isset($_GET['student_id'])) {
    $_SESSION['error'] = "Thiếu thông tin học sinh!";
    header("Location: students.php");
    exit();
}

$student_id = $_GET['student_id'];

// --- Lấy thông tin học sinh + lớp học ---
$stmt = $pdo->prepare("
    SELECT u.username, u.full_name, u.email, u.phone, 
           c.class_name, c.grade, c.school_year
    FROM users u
    JOIN class_students cs ON cs.student_id = u.id
    JOIN classes c ON cs.class_id = c.id
    WHERE u.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = "Không tìm thấy thông tin học sinh!";
    header("Location: students.php");
    exit();
}

// --- Lấy danh sách điểm của học sinh theo tất cả các môn ---
$stmt = $pdo->prepare("
    SELECT s.subject_id, sj.subject_name, s.semester, s.school_year,
           s.oral_1, s.oral_2, s.oral_3,
           s.fifteen_min_1, s.fifteen_min_2, s.fifteen_min_3,
           s.forty_five_min_1, s.forty_five_min_2, s.forty_five_min_3,
           s.mid_term, s.final_term, s.average
    FROM scores s
    JOIN subjects sj ON s.subject_id = sj.id
    WHERE s.student_id = ?
    ORDER BY s.school_year DESC, s.semester, sj.subject_name
");
$stmt->execute([$student_id]);
$scores = $stmt->fetchAll();

// --- Tạo file Excel xuất ---
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Bang_diem_' . $student['username'] . '.xls"');
header('Cache-Control: max-age=0');

echo "<html>";
echo "<head><meta charset='UTF-8'></head>";
echo "<body>";
echo "<table border='1' cellpadding='6' cellspacing='0'>";

// --- Thông tin học sinh ---
echo "<tr><th colspan='16' style='background-color:#007bff;color:white;'>THÔNG TIN HỌC SINH</th></tr>";
echo "<tr><td><strong>Họ và tên:</strong></td><td colspan='3'>" . htmlspecialchars($student['full_name']) . "</td>";
echo "<td><strong>Mã học sinh:</strong></td><td colspan='2'>" . htmlspecialchars($student['username']) . "</td></tr>";

echo "<tr><td><strong>Lớp:</strong></td><td colspan='3'>" . htmlspecialchars($student['class_name']) . "</td>";
echo "<td><strong>Khối:</strong></td><td colspan='2'>" . htmlspecialchars($student['grade']) . "</td></tr>";

echo "<tr><td><strong>Năm học:</strong></td><td colspan='3'>" . htmlspecialchars($student['school_year']) . "</td>";
echo "<td><strong>Email:</strong></td><td colspan='2'>" . htmlspecialchars($student['email']) . "</td></tr>";

echo "<tr><td><strong>Số điện thoại:</strong></td><td colspan='3'>" . htmlspecialchars($student['phone']) . "</td></tr>";

echo "<tr><td colspan='16'><hr></td></tr>";

// --- Tiêu đề bảng điểm ---
echo "<tr style='background-color:#28a745;color:white;'>";
echo "<th>STT</th>";
echo "<th>Môn học</th>";
echo "<th>Năm học</th>";
echo "<th>Học kỳ</th>";
echo "<th>Miệng 1</th><th>Miệng 2</th><th>Miệng 3</th>";
echo "<th>15p 1</th><th>15p 2</th><th>15p 3</th>";
echo "<th>45p 1</th><th>45p 2</th><th>45p 3</th>";
echo "<th>Giữa kỳ</th><th>Cuối kỳ</th><th>Điểm TB</th>";
echo "</tr>";

// --- Dữ liệu bảng điểm ---
if ($scores) {
    $stt = 1;
    foreach ($scores as $score) {
        echo "<tr>";
        echo "<td>$stt</td>";
        echo "<td>" . htmlspecialchars($score['subject_name']) . "</td>";
        echo "<td>" . htmlspecialchars($score['school_year']) . "</td>";
        echo "<td>" . htmlspecialchars($score['semester']) . "</td>";
        echo "<td>" . ($score['oral_1'] ?? '') . "</td>";
        echo "<td>" . ($score['oral_2'] ?? '') . "</td>";
        echo "<td>" . ($score['oral_3'] ?? '') . "</td>";
        echo "<td>" . ($score['fifteen_min_1'] ?? '') . "</td>";
        echo "<td>" . ($score['fifteen_min_2'] ?? '') . "</td>";
        echo "<td>" . ($score['fifteen_min_3'] ?? '') . "</td>";
        echo "<td>" . ($score['forty_five_min_1'] ?? '') . "</td>";
        echo "<td>" . ($score['forty_five_min_2'] ?? '') . "</td>";
        echo "<td>" . ($score['forty_five_min_3'] ?? '') . "</td>";
        echo "<td>" . ($score['mid_term'] ?? '') . "</td>";
        echo "<td>" . ($score['final_term'] ?? '') . "</td>";
        echo "<td><strong>" . ($score['average'] ?? '') . "</strong></td>";
        echo "</tr>";
        $stt++;
    }
} else {
    echo "<tr><td colspan='16' align='center'><em>Chưa có dữ liệu điểm</em></td></tr>";
}

echo "</table>";
echo "</body></html>";
exit();
?>
