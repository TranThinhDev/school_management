<?php
require_once '../includes/config.php';

if (!isset($_GET['class_id'])) exit("Thiếu tham số!");

$class_id = $_GET['class_id'];

$stmt = $pdo->prepare("
    SELECT h.*, u.full_name 
    FROM homeroom_history h
    JOIN users u ON h.teacher_id = u.id
    WHERE h.class_id = ?
    ORDER BY h.start_date DESC
");
$stmt->execute([$class_id]);
$history = $stmt->fetchAll();

if (!$history) {
    echo "<div class='alert alert-info'>Chưa có lịch sử chủ nhiệm.</div>";
    exit;
}

echo "<table class='table table-bordered table-striped'>";
echo "<thead><tr><th>Giáo viên</th><th>Thời gian bắt đầu</th><th>Thời gian kết thúc</th><th>Trạng thái</th></tr></thead><tbody>";
foreach ($history as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['start_date'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['end_date'] ?? '-') . "</td>";
    echo "<td>" . ($row['is_active'] ? "<span class='badge bg-success'>Đang chủ nhiệm</span>" : "<span class='badge bg-secondary'>Đã kết thúc</span>") . "</td>";
    echo "</tr>";
}
echo "</tbody></table>";
?>
