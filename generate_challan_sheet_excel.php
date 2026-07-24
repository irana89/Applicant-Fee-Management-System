<?php
require 'vendor/autoload.php'; // Load PhpSpreadsheet
include('db.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

ob_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["month"])) {
    $selectedMonth = $_POST["month"];
    $selectedMonthFormatted = DateTime::createFromFormat('Y-m', $selectedMonth)->format('F-Y');

    if (!preg_match("/^\d{4}-(0[1-9]|1[0-2])$/", $selectedMonth)) {
        die("Invalid month format. Expected YYYY-MM.");
    }

    $singlePaperFee = 300;

    // Fetch Applicants
    $sql = "SELECT a.name, a.date_of_visit, a.cnic,
                   COUNT(*) AS num_of_entries,
                   SUM(a.no_of_security_papers_used) AS total_papers 
            FROM Applicants a 
            WHERE DATE_FORMAT(a.date_of_visit, '%Y-%m') = ?
            GROUP BY a.cnic, a.date_of_visit
            ORDER BY MAX(a.created_at) ASC;";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedMonth);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch Challans
    $challans_by_month = [];
    $challan_sql = "SELECT chalan_no, month, amount, DATE_FORMAT(created_at, '%Y-%m-%d') AS deposit_date FROM challans WHERE (month) = ?";
    $challan_stmt = $conn->prepare($challan_sql);
    $challan_stmt->bind_param("s", $selectedMonth);
    $challan_stmt->execute();
    $challan_result = $challan_stmt->get_result();
    if ($challan_result->num_rows > 0) {
        while ($row = $challan_result->fetch_assoc()) {
            $month_key = date('Y-m', strtotime($row['month']));
            $challans_by_month[$month_key][] = [
                'chalan_no' => $row['chalan_no'],
                'deposit_date' => $row['deposit_date'],
                'amount' => $row['amount'],
                'used' => 0
            ];
        }
    }
	
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    

   

// 1. Merge A1:J2 into a single cell
$sheet->mergeCells('A1:J2');

// 2. Insert logo into top-left corner of merged cell
$drawing = new Drawing();
$drawing->setName('Logo');
$drawing->setDescription('Logo');
$drawing->setPath(__DIR__ . '/logo.png'); // Use full path
$drawing->setHeight(40); // Adjust based on your logo size
$drawing->setCoordinates('A1');
$drawing->setOffsetX(10);
$drawing->setOffsetY(5);
$drawing->setWorksheet($sheet);

// 3. Insert heading text (2 lines)
$sheet->setCellValue('A1', 
    "Collection Receipts For The Month Of {$selectedMonthFormatted}\n" .
    "PEOPLES SERVICE CENTER (LARMIS) TANDO MUHAMMAD KHAN - BOARD OF REVENUE, SINDH"
);

// 4. Style the big title cell
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 9],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
    ],
]);

// 5. Adjust row height to fit logo and text
$sheet->getRowDimension(1)->setRowHeight(40);
$sheet->getRowDimension(2)->setRowHeight(1); // Row 2 is merged, no height needed





    // Headers
    $headers = ['S.No', 'Date', 'Applicant Name', 'No. of Entries', 'No:of Pages Used', 'Fee', 'Amount', 'Challan No.', 'Deposit Date', 'Bank & Branch C03885'];
    $col = 'A';
    $rowStart = 4;
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $rowStart, $header);
        $col++;
    }
    $sheet->getStyle("A{$rowStart}:J{$rowStart}")->applyFromArray([
        'font' => ['bold' => true, 'size' => 10], // Smaller font
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true // Word wrap enabled
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9EAD3']
        ],
    ]);

// Optional: increase row height to fit wrapped content
$sheet->getRowDimension($rowStart)->setRowHeight(40); // Adjust height as needed


    // Data Rows
    $row = $rowStart + 1;
    $serialNo = 1;
    $totalApplicantsPages = 0;
    $totalEntries = 0;
    $totalFees = 0;

    while ($data = $result->fetch_assoc()) {
        $applicant_date = $data['date_of_visit'];
        $applicant_month = date('Y-m', strtotime($applicant_date));
        
        $totalPages = $data['total_papers'];
        $visitDate = strtotime($data['date_of_visit']);
        $feeCutoffDate = strtotime('2025-07-1');

        // Dynamically determine fee
        $dynamicFee = ($visitDate < $feeCutoffDate) ? 300 : 150;
        $totalAmount = $totalPages * $dynamicFee;


        $challanText = '';
        $depositText = '';
        $remainingAmount = $totalAmount;

        if (isset($challans_by_month[$applicant_month])) {
            foreach ($challans_by_month[$applicant_month] as &$challan) {
                $available = $challan['amount'] - $challan['used'];
                if ($available <= 0) continue;

                $used = min($remainingAmount, $available);
                $challan['used'] += $used;
                $remainingAmount -= $used;

                $challanText .= $challan['chalan_no'] . "\n";
                $depositText .= $challan['deposit_date'] . "\n";

                if ($remainingAmount <= 0) break;
            }
        }

        $sheet->setCellValue("A$row", $serialNo);
        $sheet->setCellValue("B$row", date('d-M-Y', strtotime($applicant_date)));
        $sheet->setCellValue("C$row", $data['name']);
        $sheet->setCellValue("D$row", $data['num_of_entries']);
        $sheet->setCellValue("E$row", $totalPages);
        $sheet->setCellValue("F$row", $dynamicFee);
        $sheet->setCellValue("G$row", $totalAmount);
        $sheet->setCellValue("H$row", trim($challanText));
        $sheet->setCellValue("I$row", trim($depositText));
        $sheet->setCellValue("J$row", "");

        $totalApplicantsPages += $totalPages;
        $totalFees += $totalAmount;
        $totalEntries += $data['num_of_entries'];
        $serialNo++;
        $row++;
    }

    // Total Row
    $sheet->setCellValue("A$row", "Total");
    $sheet->setCellValue("D$row", $totalEntries);
    $sheet->setCellValue("E$row", $totalApplicantsPages);
    $sheet->setCellValue("G$row", $totalFees);
    $sheet->getStyle("A$row:J$row")->getFont()->setBold(true);

    // Styling
    $sheet->getStyle("A{$rowStart}:J{$row}")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ]);

        // Set specific widths for each column
        $sheet->getColumnDimension('A')->setWidth(5.54);   // S.No
        $sheet->getColumnDimension('B')->setWidth(12.33);  // Date
        $sheet->getColumnDimension('C')->setWidth(27.67);  // Applicant Name
        $sheet->getColumnDimension('D')->setWidth(5.67);  // No. of Entries
        $sheet->getColumnDimension('E')->setWidth(8.22);  // No. of Pages Used
        $sheet->getColumnDimension('F')->setWidth(3.89);  // Fee
        $sheet->getColumnDimension('G')->setWidth(7.68);  // Amount
        $sheet->getColumnDimension('H')->setWidth(7.56);  // Challan No.
        $sheet->getColumnDimension('I')->setWidth(9.33);  // Deposit Date
        $sheet->getColumnDimension('J')->setWidth(11.78);  // Bank & Branch

// Define the row where you want the signature block to start
$signatureStartRow = $row + 3; // a few rows after totals

// Merge cells for the signature block (adjust columns as needed)
$sheet->mergeCells("H{$signatureStartRow}:J{$signatureStartRow}");
$sheet->mergeCells("H" . ($signatureStartRow + 1) . ":J" . ($signatureStartRow + 1));
$sheet->mergeCells("H" . ($signatureStartRow + 2) . ":J" . ($signatureStartRow + 2));
$sheet->mergeCells("H" . ($signatureStartRow + 3) . ":J" . ($signatureStartRow + 3));

// Set the text lines
$sheet->setCellValue("H{$signatureStartRow}", "(Engr. Muhammad Suleman Siyal)");
$sheet->setCellValue("H" . ($signatureStartRow + 1), "District Manager");
$sheet->setCellValue("H" . ($signatureStartRow + 2), "Peoples Service Center (LARMIS), T.M Khan");
$sheet->setCellValue("H" . ($signatureStartRow + 3), "Board of Revenue, Sindh");

// Center-align the text
$sheet->getStyle("H{$signatureStartRow}:J" . ($signatureStartRow + 3))->applyFromArray([
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    ],
    'font' => [
        'bold' => false,
        'size' => 11
    ]
]);




    // Step: Track and Merge Same Challans & Deposit Dates
function mergeSameCells($sheet, $column, $startRow, $endRow, $challanMapKey) {
    $lastValue = null;
    $mergeStart = $startRow;

    for ($r = $startRow; $r <= $endRow; $r++) {
        $currentValue = $sheet->getCell("{$column}{$r}")->getValue();
        if ($currentValue === $lastValue) {
            continue;
        } else {
            if ($r - 1 > $mergeStart) {
                $sheet->mergeCells("{$column}{$mergeStart}:{$column}" . ($r - 1));
                $sheet->getStyle("{$column}{$mergeStart}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }
            $lastValue = $currentValue;
            $mergeStart = $r;
        }
    }

    // Final merge if at bottom
    if ($endRow > $mergeStart) {
        $sheet->mergeCells("{$column}{$mergeStart}:{$column}{$endRow}");
        $sheet->getStyle("{$column}{$mergeStart}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }
}

mergeSameCells($sheet, 'H', $rowStart + 1, $row - 1, 'chalan_no'); // Challan No.
mergeSameCells($sheet, 'I', $rowStart + 1, $row - 1, 'deposit_date'); // Deposit Date


    // Output Excel File
    $fileName = "Challan_Paid_{$selectedMonth}.xlsx";
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}
?>
