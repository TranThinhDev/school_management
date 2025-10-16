<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php'; // Đảm bảo đúng đường dẫn đến mpdf

$mpdf = new \Mpdf\Mpdf(['format' => 'A4']);

// --- Thông tin học sinh ---
$html = '
<h2 style="text-align:center;">BẢNG ĐIỂM CHI TIẾT</h2>
<table width="100%" border="0" cellpadding="5">
    <tr>
        <td><strong>Họ và tên:</strong> ' . htmlspecialchars($student['full_name']) . '</td>
        <td><strong>Mã học sinh:</strong> ' . htmlspecialchars($student['username']) . '</td>
    </tr>
    <tr>
        <td><strong>Lớp:</strong> ' . htmlspecialchars($student['class_name']) . '</td>
        <td><strong>Năm học:</strong> ' . htmlspecialchars($student['school_year']) . '</td>
    </tr>
    <tr>
        <td><strong>Email:</strong> ' . htmlspecialchars($student['email']) . '</td>
        <td><strong>Số điện thoại:</strong> ' . htmlspecialchars($student['phone']) . '</td>
    </tr>
</table>
<hr>
';

// --- Bảng điểm chi tiết ---
$html .= '
<table border="1" width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
    <thead>
        <tr style="background-color:#f0f0f0;">
            <th>Môn học</th>
            <th>Học kỳ</th>
            <th>Điểm miệng</th>
            <th>15 phút</th>
            <th>45 phút</th>
            <th>Cuối kỳ</th>
            <th>Điểm TB</th>
        </tr>
    </thead>
    <tbody>
';

foreach ($scores as $row) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($row['subject_name']) . '</td>
            <td>HK ' . htmlspecialchars($row['semester']) . '</td>
            <td>' . htmlspecialchars($row['oral_1']) . '</td>
            <td>' . htmlspecialchars($row['fifteen_min_1']) . '</td>
            <td>' . htmlspecialchars($row['forty_five_min_1']) . '</td>
            <td>' . htmlspecialchars($row['final_term']) . '</td>
            <td><strong>' . number_format($row['average'], 2) . '</strong></td>
        </tr>';
}

$html .= '</tbody></table>';

$mpdf->WriteHTML($html);
$mpdf->Output('Bang_diem_' . $student['username'] . '.pdf', 'I');
exit;
