<?php
include('db.php');
include('header.php');

// Fetch monthly report data
$sql = "SELECT 
    ym.month,
    COUNT(a.entry_number) AS total_entries_issued,

    -- Updated fee logic: 300 before 21-Aug-2025, 150 after
    SUM(
        CASE 
            WHEN a.date_of_visit < '2025-07-01' THEN a.no_of_security_papers_used * 300
            WHEN a.date_of_visit >= '2025-07-01' THEN a.no_of_security_papers_used * 150
            ELSE 0
        END
    ) AS total_security_amount,

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
?>

<title>Monthly Paid Challan Report</title>
<div class="container">

<h3>Generate Excel Sheet</h3>
<form action="generate_monthly_paid_challan_excel.php" method="POST">
    <button type="submit">Generate</button>
</form>

<h2>Monthly Paid Challan Report</h2>

<table border="1" class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Month</th>
            <th>Total Entries Issued</th>
            <th>Amount Collected (Rs.)</th>
            <th>No. of Security Papers Used</th>
            <th>No. of Wasted Security Papers</th>
            <th>Total Security Papers</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            $sno = 1;
            $total_entries = $total_amount = $total_used = $total_wasted = $total_all = 0;

            // Fetch and display rows
            while ($row = $result->fetch_assoc()) {
				$d = DateTime::createFromFormat('Y-m', $row['month'])->format('M Y');
                echo "<tr>
                        <td>{$sno}</td>
                        <td>{$d}</td>
                        <td>{$row['total_entries_issued']}</td>
                        <td>Rs. " . number_format($row['total_security_amount'], 00) . "</td>
                        <td>{$row['total_security_paper']}</td>
                        <td>{$row['wasted_security_paper']}</td>
                        <td>{$row['total_papers']}</td>
                      </tr>";
                $sno++;

                // Sum totals
                $total_entries += $row['total_entries_issued'];
                $total_amount += $row['total_security_amount'];
                $total_used += $row['total_security_paper'];
                $total_wasted += $row['wasted_security_paper'];
                $total_all += $row['total_papers'];
            }
        ?>
    </tbody>
    <tfoot>
        <tr style='font-weight: bold; background: #f0f0f0;'>
            <td colspan='2'>Grand Total</td>
            <td><?= $total_entries ?></td>
            <td>Rs. <?= number_format($total_amount, 2) ?></td>
            <td><?= $total_used ?></td>
            <td><?= $total_wasted ?></td>
            <td><?= $total_all ?></td>
        </tr>
    </tfoot>
    <?php
        } else {
            echo "<tr><td colspan='7'>No records found.</td></tr>";
        }
    ?>
</table>
</div>

<?php
include('footer.php');
?>

