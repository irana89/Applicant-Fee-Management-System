<?php
require 'vendor/autoload.php';
include('db.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

ob_start();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->mergeCells('A1:J1');
$sheet->mergeCells('A2:J2');
$sheet->setCellValue('A1', 'Security Paper Monthly Report');
$sheet->setCellValue('A2', 'PEOPLES SERVICE CENTER (LARMIS) TANDO MUHAMMAD KHAN - BOARD OF REVENUE, SINDH');

$titleStyle = [
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAD3']],
];
$sheet->getStyle('A1')->applyFromArray($titleStyle);
$sheet->getStyle('A2')->applyFromArray($titleStyle);

// Headers
$headers = ['S. No.', 'Month', 'No. of Records', 'No. of Security Pages Issued', 'Security Page Number(s)', 'No. of Security Pages Wasted', 'Wasted Security Page Number(s)', 'Rate', 'Payment', 'Remarks'];
$col = 'A';
$rowStart = 4;

foreach ($headers as $header) {
    $sheet->setCellValue($col . $rowStart, $header);
    $col++;
}

$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAD3']],
];
$sheet->getStyle("A{$rowStart}:J{$rowStart}")->applyFromArray($headerStyle);

// Query
$sql = "
SELECT DATE_FORMAT(a.date_of_visit, '%Y-%m') AS month,
	   COUNT(DISTINCT a.a_id) AS total_ent,
       COUNT(*) AS total_records,
       COUNT(sp.security_paper_serial_no) AS total_security_issued,
       GROUP_CONCAT(DISTINCT sp.security_paper_serial_no ORDER BY sp.security_paper_serial_no) AS issued_serials,
       COALESCE(w.wasted_count, 0) AS total_wasted,
       COALESCE(w.serials, '') AS wasted_serials,
       300 AS rate,
       COUNT(sp.security_paper_serial_no)*300 AS payment,
       '' AS remarks
FROM applicants a
LEFT JOIN security_papers sp ON a.a_id = sp.applicant_id
LEFT JOIN (
    SELECT DATE_FORMAT(waste_date, '%Y-%m') AS month,
           COUNT(*) AS wasted_count,
           GROUP_CONCAT(security_paper_no ORDER BY security_paper_no) AS serials
    FROM wasted_security_paper
    GROUP BY DATE_FORMAT(waste_date, '%Y-%m')
) w ON DATE_FORMAT(a.date_of_visit, '%Y-%m') = w.month
GROUP BY DATE_FORMAT(a.date_of_visit, '%Y-%m')
ORDER BY month DESC";

$result = $conn->query($sql);

function group_serials_into_ranges($serials) {
    if (empty($serials)) return 'N/A';
    $serials = array_filter(array_map('trim', explode(',', $serials)));
    sort($serials, SORT_STRING);
    $ranges = [];
    $start = $end = $serials[0];
    for ($i = 1; $i < count($serials); $i++) {
        $prev = (int)substr($end, 2);
        $curr = (int)substr($serials[$i], 2);
        if ($curr == $prev + 1 && substr($serials[$i], 0, 2) == substr($end, 0, 2)) {
            $end = $serials[$i];
        } else {
            $ranges[] = ($start == $end) ? $start : "$start to $end";
            $start = $end = $serials[$i];
        }
    }
    $ranges[] = ($start == $end) ? $start : "$start to $end";
    return implode(', ', $ranges);
}

// Data rows
$row = $rowStart + 1;
$sno = 1;
$total_records = $total_issued = $total_wasted = $total_payment = 0;

while ($data = $result->fetch_assoc()) {
    $month = DateTime::createFromFormat('Y-m', $data['month'])->format('F Y');
    $sheet->setCellValue("A$row", $sno);
    $sheet->setCellValue("B$row", $month);
    $sheet->setCellValue("C$row", $data['total_ent']);
    $sheet->setCellValue("D$row", $data['total_security_issued']);
    $sheet->setCellValue("E$row", group_serials_into_ranges($data['issued_serials']));
    $sheet->setCellValue("F$row", $data['total_wasted']);
    $sheet->setCellValue("G$row", group_serials_into_ranges($data['wasted_serials']));
    $sheet->setCellValue("H$row", '300');
    $sheet->setCellValue("I$row", $data['payment']);
    $sheet->setCellValue("J$row", '');

    $total_records += $data['total_ent'];
    $total_issued += $data['total_security_issued'];
    $total_wasted += $data['total_wasted'];
    $total_payment += $data['payment'];

    $sno++;
    $row++;
}

// Totals row
$sheet->setCellValue("A$row", "Total");
$sheet->setCellValue("C$row", $total_records);
$sheet->setCellValue("D$row", $total_issued);
$sheet->setCellValue("F$row", $total_wasted);
$sheet->setCellValue("I$row", $total_payment);
$sheet->getStyle("A$row:J$row")->getFont()->setBold(true);

// Apply borders
$sheet->getStyle("A$rowStart:J$row")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// Auto-size columns
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output Excel
$fileName = "Security_Paper_Monthly_Report.xlsx";
ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$fileName\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
