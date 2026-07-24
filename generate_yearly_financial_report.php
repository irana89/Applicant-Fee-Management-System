<?php
require 'vendor/autoload.php';
include('db.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (isset($_POST['year'])) {
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    if (!$year) {
        die("Invalid year.");
    }

    $start_year = $year - 1;

    // Database connection
    $db = isset($conn) ? $conn : (isset($con) ? $con : null);
    if (!$db) {
        die("Database connection not found.");
    }

    // 1. Pre-define and initialize ALL months in Fiscal Year order (July to June)
    $fiscal_months = [
        "July" => 0, "August" => 0, "September" => 0, "October" => 0, "November" => 0, "December" => 0,
        "January" => 0, "February" => 0, "March" => 0, "April" => 0, "May" => 0, "June" => 0
    ];

    // Optimized SQL to restrict data strictly to the fiscal year range (July 1st dynamic to June 30th)
    $sql = "
        SELECT month, amount 
        FROM challans 
        WHERE 
            (YEAR(month) = ? AND MONTH(month) <= 6) OR 
            (YEAR(month) = ? AND MONTH(month) >= 7)
    ";
	
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $year, $start_year);
	echo "<pre>$debugSql</pre>";
    $stmt->execute();
    $result = $stmt->get_result();

    // 2. Populate values into our pre-defined array
    while ($row = $result->fetch_assoc()) {
        $month = date('F', strtotime($row['month']));
        if (isset($fiscal_months[$month])) {
            $fiscal_months[$month] += $row['amount'];
        }
    }

    // Create spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Monthly Report');

    // Page setup margins
    $sheet->getPageSetup()->setHorizontalCentered(true);
    $sheet->getPageSetup()->setVerticalCentered(true);
    $sheet->getPageMargins()->setTop(1)->setRight(0.5)->setLeft(0.5)->setBottom(1);

    // Heading (merged across A1 to C3)
    $title = "STATEMENT SHOWING MONTH-WISE ACTUAL RECEIPTS OF\n(FEE PAYABLE FOR OBTAINING INFORMATION COPIES OF PUBLIC RECORD)\nFOR THE FINANCIAL YEAR ({$start_year} - {$year})";
    $sheet->mergeCells('A1:C3');
    $sheet->setCellValue('A1', $title);

    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ]
    ]);
    $sheet->getRowDimension(1)->setRowHeight(60);

    // Table headers
    $headers = ['S.No', 'Month', 'Amount Collected (Rs.)'];
    $sheet->fromArray($headers, NULL, 'A5');

    // Style headers
    $sheet->getStyle('A5:C5')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    // Fill data
    $rowNum = 6;
    $serial = 1;
    $totalAmount = 0;

    // 3. Loop through the pre-initialized fiscal months array
    foreach ($fiscal_months as $month => $amount) {
        $sheet->setCellValue("A{$rowNum}", $serial);
        $sheet->setCellValue("B{$rowNum}", $month);
        $sheet->setCellValue("C{$rowNum}", $amount);
        $sheet->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $totalAmount += $amount;
        $rowNum++;
        $serial++;
    }

    // Total row
    $sheet->setCellValue("B{$rowNum}", "Total");
    $sheet->setCellValue("C{$rowNum}", $totalAmount);
    $sheet->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    // Currency formatting
    $sheet->getStyle("C6:C{$rowNum}")
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');

    // Auto-size columns
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Output XLSX
    $filename = "monthly_report_{$start_year}_to_{$year}.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>