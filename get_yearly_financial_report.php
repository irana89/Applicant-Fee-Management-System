<?php
include('db.php');

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_data'])) {
    $selected_year = intval($_POST['year']);

    $start_date = date('Y-m-01', strtotime("{$selected_year}-07 -1 year"));
    $end_date = date('Y-m-t', strtotime("{$selected_year}-06"));

        $sql = "SELECT 
            DATE_FORMAT(date_of_visit, '%Y-%m') AS month,

            SUM(
                CASE 
                    WHEN date_of_visit < '2025-07-01' THEN no_of_security_papers_used * 300
                    WHEN date_of_visit >= '2025-07-01' THEN no_of_security_papers_used * 150
                    ELSE 0
                END
            ) AS total_security_amount

        FROM applicants
        WHERE date_of_visit BETWEEN '$start_date' AND '$end_date'
        GROUP BY DATE_FORMAT(date_of_visit, '%Y-%m')
        ORDER BY month ASC";


    $result = $conn->query($sql);

    $sno = 1;
    $total_amount = 0;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $month = DateTime::createFromFormat('Y-m', $row['month'])->format('F Y');
            $amount = $row['total_security_amount'] ?? 0;

            echo "<tr>
                    <td>{$sno}</td>
                    <td>{$month}</td>
                    <td>Rs. " . number_format($amount, 0) . "</td>
                  </tr>";
            $total_amount += $amount;
            $sno++;
        }

        echo "<tr style='font-weight: bold; background-color: #f0f0f0;'>
                <td colspan='2'>Grand Total</td>
                <td>Rs. " . number_format($total_amount, 0) . "</td>
              </tr>";
    } else {
        echo "<tr><td colspan='3'>No records found for this year.</td></tr>";
    }

    exit;
}
?>
