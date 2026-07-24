<?php
require 'vendor/autoload.php';
include('db.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$sql = "SELECT 
    ym.month,
    COUNT(a.entry_number) AS total_entries_issued,
    COALESCE(SUM(a.no_of_security_papers_used), 0) * 300 AS total_security_amount,
    COALESCE(SUM(a.no_of_security_papers_used), 0) AS total_security_paper,
    COALESCE(w.wasted_count, 0) AS wasted_security_paper,
    (COALESCE(SUM(a.no_of_security_papers_used), 0) + COALESCE(w.wasted_count, 0)) AS total_papers
FROM (
    SELECT DATE_FORMAT(date_of_visit, '%Y-%m') AS month
    FROM applicants
    GROUP BY DATE_FORMAT(date_of_visit, '%Y-%m')
) ym
LEFT JOIN applicants a ON DATE_FORMAT(a.date_of_visit, '%Y-%m') = ym.month
LEFT JOIN (
    SELECT DATE_FORMAT(waste_date, '%Y-%m') AS waste_month, COUNT(*) AS wasted_count
    FROM wasted_security_paper
    GROUP BY DATE_FORMAT(waste_date, '%Y-%m')
) w ON w.waste_month = ym.month
GROUP BY ym.month
ORDER BY ym.month ASC;";

$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ====== TITLE ======
$sheet->mergeCells('A1:G1');
$sheet->mergeCells('A2:G2');
$sheet->setCellValue('A1', 'Monthly  Paid Challan Consolidated Report');
$sheet->setCellValue('A2', 'PEOPLES SERVICE CENTER (LARMIS) TANDO MUHAMMAD KHAN - BOARD OF REVENUE, SINDH');

$titleStyle = [
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'D9EAD3'], // Light green
    ],
];
$sheet->getStyle('A1')->applyFromArray($titleStyle);
$sheet->getStyle('A2')->applyFromArray($titleStyle);

// ====== HEADERS ======
$headers = ['S.No', 'Month', 'Total Entries Issued', 'Amount Collected (Rs.)', 'Security Papers Used', 'Wasted Security Papers', 'Total Papers'];
$col = 'A';
$rowStart = 4;

foreach ($headers as $header) {
    $sheet->setCellValue($col . $rowStart, $header);
    $col++;
}

$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'D9EAD3'], // Light green
    ],
];
$sheet->getStyle("A{$rowStart}:G{$rowStart}")->applyFromArray($headerStyle);

// ====== DATA ROWS ======
$rowNum = $rowStart + 1;
$sno = 1;

$totals = [
    'entries' => 0,
    'amount' => 0,
    'used' => 0,
    'wasted' => 0,
    'total' => 0,
];

while ($row = $result->fetch_assoc()) {
	$d = DateTime::createFromFormat('Y-m', $row['month'])->format('F Y');
	
    $sheet->setCellValue("A$rowNum", $sno);
    $sheet->setCellValue("B$rowNum", $d);
    $sheet->setCellValue("C$rowNum", $row['total_entries_issued']);
    $sheet->setCellValue("D$rowNum", $row['total_security_amount']);
    $sheet->setCellValue("E$rowNum", $row['total_security_paper']);
    $sheet->setCellValue("F$rowNum", $row['wasted_security_paper']);
    $sheet->setCellValue("G$rowNum", $row['total_papers']);

    // Totals
    $totals['entries'] += $row['total_entries_issued'];
    $totals['amount'] += $row['total_security_amount'];
    $totals['used'] += $row['total_security_paper'];
    $totals['wasted'] += $row['wasted_security_paper'];
    $totals['total'] += $row['total_papers'];

    $sno++;
    $rowNum++;
}

// ====== TOTAL ROW ======
$sheet->setCellValue("A$rowNum", 'Total');
$sheet->setCellValue("C$rowNum", $totals['entries']);
$sheet->setCellValue("D$rowNum", $totals['amount']);
$sheet->setCellValue("E$rowNum", $totals['used']);
$sheet->setCellValue("F$rowNum", $totals['wasted']);
$sheet->setCellValue("G$rowNum", $totals['total']);
$sheet->getStyle("A$rowNum:G$rowNum")->getFont()->setBold(true);

// ====== BORDERS ======
$borderStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$sheet->getStyle("A{$rowStart}:G$rowNum")->applyFromArray($borderStyle);

// ====== AUTO-SIZE COLUMNS ======
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ====== OUTPUT EXCEL ======
$filename = "Monthly_Paid_Challan_Report.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
