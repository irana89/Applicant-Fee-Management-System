<?php
include('db.php');

$filterType = $_POST['filterType'] ?? 'all';
$filterValue = $_POST['filterValue'] ?? '';

$whereClause = '';
$params = [];
$types = '';

if ($filterType === 'month') {
    $whereClause = "WHERE DATE_FORMAT(b.received_date, '%Y-%m') = ?";
    $params[] = $filterValue;
    $types .= 's';
} elseif ($filterType === 'range') {
    $range = json_decode($filterValue, true);
    $whereClause = "WHERE b.received_date BETWEEN ? AND ?";
    $params[] = $range['start'];
    $params[] = $range['end'];
    $types .= 'ss';
}


$sql = "SELECT b.scb_id, b.received_date, b.from_serial, b.to_serial, b.total_pages,
               COUNT(DISTINCT w.w_id) AS wasted_count,
               GROUP_CONCAT(DISTINCT w.security_paper_no ORDER BY w.security_paper_no) AS wasted_serials,
               (SELECT COUNT(*) FROM security_papers sp 
                WHERE sp.security_paper_serial_no BETWEEN b.from_serial AND b.to_serial) AS issued_count
        FROM security_paper_bundle b
        LEFT JOIN wasted_security_paper w
        ON w.security_paper_no BETWEEN b.from_serial AND b.to_serial
        $whereClause
        GROUP BY b.scb_id
        ORDER BY b.received_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $a=$stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$count = 1;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $remaining = $row['total_pages'] - $row['issued_count'] - $row['wasted_count'];
        echo "<tr>
                <td>{$count}</td>
                <td>{$row['received_date']}</td>
                <td>{$row['from_serial']}</td>
                <td>{$row['to_serial']}</td>
                <td>{$row['total_pages']}</td>
                <td>{$row['issued_count']}</td>
                <td>{$row['wasted_count']}</td>
                <td>{$row['wasted_serials']}</td>
                <td>{$remaining}</td>
				<td class='action-btns'>
					<button class='editBtn' data-id='{$row['scb_id']}'>✏️</button>
					<button class='deleteBtn' data-id='{$row['scb_id']}'>❌️</button>
				</td>
              </tr>";
        $count++;
    }
} else {
    echo "<tr><td colspan='9'>No records found.</td></tr>";
}
