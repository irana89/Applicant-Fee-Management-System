<?php
require 'vendor/autoload.php'; // Load PhpSpreadsheet
include('db.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

ob_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["month"])) {
         $selectedMonth = $_POST["month"]; // Format: YYYY-MM

        // Validate input format
        if (!preg_match("/^\d{4}-(0[1-9]|1[0-2])$/", $selectedMonth)) {
            die("Invalid month format. Expected YYYY-MM.");
        }

        // Convert to "Applicants_Month_Year" format
        $dateObj = DateTime::createFromFormat('Y-m', $selectedMonth);
        $formattedMonth =  $dateObj->format('F_Y'); // e.g.August_2025

    $sql = "SELECT a.*,  
           d.name AS district_name, 
           t.name AS taluka_name, 
           deh.name AS deh_name,
           GROUP_CONCAT(sp.security_paper_serial_no ORDER BY sp.security_paper_serial_no SEPARATOR ', ') AS security_papers 
    FROM Applicants a 
    LEFT JOIN Districts d ON a.district = d.id  
    LEFT JOIN Talukas t ON a.taluka = t.id 
    LEFT JOIN Dehs deh ON a.deh = deh.id
    LEFT JOIN Security_Papers sp ON a.a_id = sp.applicant_id 
    WHERE DATE_FORMAT(a.date_of_visit, '%Y-%m') = ?
    GROUP BY a.a_id, d.name, t.name, deh.name
    ORDER BY a.created_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedMonth);
    $stmt->execute();
    $result = $stmt->get_result();

    // Create Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // ====== TITLE ======
    $sheet->mergeCells('A1:P1');
    $sheet->mergeCells('A2:P2');
    $sheet->setCellValue('A1', 'Monthly Applicants Report for ' . date('F Y', strtotime($selectedMonth)));
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
    $headers = [
       'S.No','Token No', 'Date of Visit', 'Name', 'Father/Husband Name', 'District', 'Taluka', 'Deh',
        'Form Type', 'Entry Number', 'Security Papers', 'Number of Security Paper',
        'CNIC Number', 'Cell Number', 'Relevance', 'Remarks'
    ];
    $headerRow = 4;
    $col = 'A';

    foreach ($headers as $header) {
        $sheet->setCellValue($col . $headerRow, $header);
        $col++;
    }

    $headerStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9EAD3'],
        ],
    ];
    $sheet->getStyle("A{$headerRow}:O{$headerRow}")->applyFromArray($headerStyle);

    // ====== DATA ROWS ======
	 $serialNo = 1;
    $row = $headerRow + 1;
    while ($data = $result->fetch_assoc()) {
        $securityPapers = $data['security_papers'] ?? '';
        $numOfSecurity = ($securityPapers === '') ? 0 : substr_count($securityPapers, ',') + 1;
		
		$date_of_visit = date('j-M-Y', strtotime($data['date_of_visit']));
		
		$sheet->setCellValue("A$row", $serialNo);
        $sheet->setCellValue("B$row", $data['token_no']);
        $sheet->setCellValue("C$row", $date_of_visit);
        $sheet->setCellValue("d$row", $data['name']);
        $sheet->setCellValue("E$row", $data['father_husband_name']);
        $sheet->setCellValue("F$row", $data['district_name']);
        $sheet->setCellValue("G$row", $data['taluka_name']);
        $sheet->setCellValue("H$row", $data['deh_name']);
        $sheet->setCellValue("I$row", $data['form_type']);
        $sheet->setCellValue("J$row", $data['entry_number']);
        $sheet->setCellValue("K$row", $securityPapers);
        $sheet->setCellValue("L$row", $numOfSecurity);
        $sheet->setCellValue("M$row", $data['cnic']);
        $sheet->setCellValue("N$row", $data['cell_number']);
        $sheet->setCellValue("O$row", $data['relevance']);
        $sheet->setCellValue("P$row", $data['remarks']);
        $row++;
		$serialNo++;
    }

    // ====== BORDERS ======
    $borderStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];
    $sheet->getStyle("A{$headerRow}:P" . ($row - 1))->applyFromArray($borderStyle);

    // ====== AUTO-SIZE COLUMNS ======
    foreach (range('A', 'P') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // ====== OUTPUT FILE ======
    $fileName = "Applicants_Report___$formattedMonth.xlsx";

    ob_end_clean(); // Ensure no output before Excel download

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}
?>
