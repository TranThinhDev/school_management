<?php
require_once '../includes/config.php';

if (!isset($_GET['year'])) {
    echo json_encode([]);
    exit;
}

$year = $_GET['year'];
$stmt = $pdo->prepare("SELECT id, class_name, grade FROM classes WHERE school_year = ? ORDER BY grade, class_name");
$stmt->execute([$year]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
