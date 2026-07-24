<?php
include('db.php');

// Read JSON input from AJAX
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$from = isset($data['from']) ? (int)$data['from'] : 0;
$to   = isset($data['to']) ? (int)$data['to'] : 0;

if ($from <= 0 || $to <= 0) {
    echo json_encode([]);
    exit;
}

// Recursive query to get all serial numbers in range and filter used/wasted
$sql = "
WITH RECURSIVE seq AS (
    SELECT $from AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < $to
)
SELECT CONCAT('AB', n) AS available_serial_no
FROM seq s
WHERE NOT EXISTS (
    SELECT 1 FROM security_papers sp
    WHERE sp.security_paper_serial_no = CONCAT('AB', s.n)
)
AND NOT EXISTS (
    SELECT 1 FROM wasted_security_paper wp
    WHERE wp.security_paper_no = CONCAT('AB', s.n)
)
";

$result = $conn->query($sql);

$available = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $available[] = $row['available_serial_no'];
    }
}

// Return JSON for AJAX
header('Content-Type: application/json');
echo json_encode($available);