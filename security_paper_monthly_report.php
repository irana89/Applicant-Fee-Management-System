<?php
include('db.php');
include('header.php');

$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$sql = "
SELECT 
    DATE_FORMAT(a.date_of_visit, '%Y-%m') AS month,
    COUNT(DISTINCT a.a_id) AS total_ent,
    COUNT(*) AS total_records,
    COUNT(sp.security_paper_serial_no) AS total_security_issued,
    GROUP_CONCAT(DISTINCT sp.security_paper_serial_no ORDER BY sp.security_paper_serial_no) AS issued_serials,
    COALESCE(w.wasted_count, 0) AS total_wasted,
    COALESCE(w.serials, '') AS wasted_serials,

    -- Dynamic fee logic per month
    CASE 
        WHEN MIN(a.date_of_visit) < '2025-08-21' AND MAX(a.date_of_visit) < '2025-08-22' THEN 300
        WHEN MIN(a.date_of_visit) >= '2025-08-21' AND MAX(a.date_of_visit) >= '2025-08-22' THEN 150
        ELSE 0
    END AS rate,

    -- Dynamic payment calculation
    SUM(
        CASE 
            WHEN a.date_of_visit < '2025-08-22' THEN 300
            WHEN a.date_of_visit >= '2025-08-22' THEN 150
            ELSE 0
        END
    ) AS payment,

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
ORDER BY month DESC
LIMIT $offset, $perPage
";


$result = $conn->query($sql);

// Count total months for pagination
$totalResult = $conn->query("SELECT COUNT(DISTINCT DATE_FORMAT(date_of_visit, '%Y-%m')) AS total FROM applicants");
$totalRow = $totalResult->fetch_assoc();
$totalPages = ceil($totalRow['total'] / $perPage);

// Group serial numbers into ranges (used below)
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
    return implode(',<br>', $ranges);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Security Paper Monthly Report</title>
</head>
<body>
<div class="container">
    <h2>Security Paper Monthly Report</h2>
    <form action="generate_monthly_security_paper_report.php" method="POST">
        <button type="submit" name="export" value="1">Export to Excel</button>
    </form>

    <table border="1" class="table">
        <thead>
            <tr>
                <th>S. No.</th>
                <th>Month</th>
                <th>No. of Records</th>
                <th>No. of Security Pages Issued</th>
                <th>Security Page Number(s)</th>
                <th>No. of Security Pages Wasted</th>
                <th>Wasted Security Page Number(s)</th>
                <th>Rate</th>
                <th>Payment</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
        <?php
		
		$total_records = 0;
		$total_issued = 0;
		$total_wasted = 0;
		$total_payment = 0;
		
        if ($result->num_rows > 0) {
            $sno = $offset + 1;
            while ($row = $result->fetch_assoc()) {
                $month = DateTime::createFromFormat('Y-m', $row['month'])->format('M Y');
                echo "<tr>
                    <td>{$sno}</td>
                    <td>{$month}</td>
                    <td>{$row['total_ent']}</td>
                    <td>{$row['total_security_issued']}</td>
                    <td>" . group_serials_into_ranges($row['issued_serials']) . "</td>
                    <td>{$row['total_wasted']}</td>
                    <td>" . group_serials_into_ranges($row['wasted_serials']) . "</td>
                    <td> 300</td>
                    <td>" . number_format($row['payment'], 0) . "</td>
                    <td>{$row['remarks']}</td>
                </tr>";
				
				// Accumulate totals
				$total_records += $row['total_ent'];
				$total_issued += $row['total_security_issued'];
				$total_wasted += $row['total_wasted'];
				$total_payment += $row['payment'];
				$sno++;
               
            }
        } else {
            echo "<tr><td colspan='10'>No records found.</td></tr>";
        }
        ?>
        </tbody>
		<tfoot>
        <tr style="font-weight: bold; background: #f0f0f0;">
            <td colspan="2">Grand Total</td>
            <td><?= $total_records ?></td>
            <td><?= $total_issued ?></td>
            <td>—</td>
            <td><?= $total_wasted ?></td>
            <td>—</td>
            <td> 300</td>
            <td> <?= number_format($total_payment, 0) ?></td>
            <td>—</td>
        </tr>
    </tfoot>
    </table>

    <div style="margin-top: 20px;">Page : 
        <?php
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'style="font-weight: bold;"' : '';
            echo "<a class='add-btn' href='?page=$i' $active> $i</a> ";
        }
        ?>
    </div>
</div>


<?php
include('footer.php');
?>
