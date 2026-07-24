<?php
include('db.php');

$query = isset($_POST['query']) ? trim($_POST['query']) : '';
$mostRecords = isset($_POST['mostRecords']) && $_POST['mostRecords'] === 'true';
$search = $conn->real_escape_string($query);

if ($mostRecords) {
    // MOST RECORD HOLDER MODE
    $sql = "
        SELECT 
            a.cnic,
            MAX(a.name) AS name,
            MAX(a.father_husband_name) AS father_husband_name,
            MAX(d.name) AS district_name,
            MAX(t.name) AS taluka_name,
            MAX(deh.name) AS deh_name,
            GROUP_CONCAT(DISTINCT a.date_of_visit ORDER BY a.date_of_visit ASC SEPARATOR ', ') AS visit_dates,
            COUNT(sp.security_paper_serial_no) AS total_security_papers,
            MAX(a.cell_number) AS cell_number,
            MAX(a.relevance) AS relevance,
            MAX(a.remarks) AS remarks
        FROM Applicants a
        LEFT JOIN Districts d ON a.district = d.id
        LEFT JOIN Talukas t ON a.taluka = t.id
        LEFT JOIN Dehs deh ON a.deh = deh.id
        LEFT JOIN Security_Papers sp ON a.a_id = sp.applicant_id
        GROUP BY a.cnic
        ORDER BY total_security_papers DESC
        LIMIT 100
    ";
} else {
 // NORMAL SEARCH MODE
$sql = "
    SELECT 
        a.*,  
        d.name AS district_name, 
        t.name AS taluka_name, 
        deh.name AS deh_name,
        GROUP_CONCAT(DISTINCT sp.security_paper_serial_no ORDER BY sp.security_paper_serial_no ASC SEPARATOR ', ') AS security_papers,
        COUNT(sp.security_paper_serial_no) AS total_security_papers
    FROM Applicants a
    LEFT JOIN Districts d ON a.district = d.id
    LEFT JOIN Talukas t ON a.taluka = t.id
    LEFT JOIN Dehs deh ON a.deh = deh.id
    LEFT JOIN Security_Papers sp ON a.a_id = sp.applicant_id
";

// Add search condition
if ($search !== '') {
    $sql .= "
    WHERE (
           a.name LIKE '%$search%'
        OR a.father_husband_name LIKE '%$search%'
        OR a.cnic LIKE '%$search%'
        OR a.cell_number LIKE '%$search%'
        OR d.name LIKE '%$search%'
        OR t.name LIKE '%$search%'
        OR deh.name LIKE '%$search%'
        OR sp.security_paper_serial_no LIKE '%$search%'
    )
    ";
}

$sql .= "
    GROUP BY a.a_id
    ORDER BY a.date_of_visit DESC, a.created_at DESC, a.a_id DESC
    LIMIT 100
";
}

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $applicantJson = json_encode($row, JSON_HEX_APOS | JSON_HEX_AMP);

        echo "<tr data-id='" . htmlspecialchars($row['a_id'] ?? '', ENT_QUOTES) . "'>";

        // Columns that can be hidden
        echo "<td>" . htmlspecialchars($row['token_no'] ?? '-', ENT_QUOTES) . "</td>";

                    $value = $row['visit_dates'] ?? $row['date_of_visit'] ?? '-';

                if ($value !== '-') {
                    // Split by comma (change separator if needed)
                    $parts = explode(',', $value);

                    // Group every 2 values
                    $lines = [];
                    for ($i = 0; $i < count($parts); $i += 2) {
                        $pair = array_slice($parts, $i, 2);
                        $lines[] = implode(',', array_map(fn($v) => htmlspecialchars(trim($v), ENT_QUOTES), $pair));
                    }

                    // Join all lines with <br>
                    $value = implode('<br>', $lines);
                } else {
                    $value = '-';
                }
                echo "<td class='datestyles'>$value</td>";


        echo "<td>" . htmlspecialchars($row['name'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['father_husband_name'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['district_name'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['taluka_name'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['deh_name'] ?? '-', ENT_QUOTES) . "</td>";

        echo "<td>" . htmlspecialchars($row['form_type'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['entry_number'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['total_security_papers'] ?? '0', ENT_QUOTES) . "</td>";

        // Security paper numbers: only for normal search
        if (!$mostRecords) {
            echo "<td>" . htmlspecialchars($row['security_papers'] ?? '-', ENT_QUOTES) . "</td>";
        } else {
            echo "<td>-</td>";
        }

        echo "<td>" . htmlspecialchars($row['cnic'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['cell_number'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['relevance'] ?? '-', ENT_QUOTES) . "</td>";
        echo "<td>" . htmlspecialchars($row['remarks'] ?? '-', ENT_QUOTES) . "</td>";

        if (!$mostRecords && isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
            echo "<td>
                    <button class='edit-btn' data-applicant='{$applicantJson}'>✏️</button>
                    <button class='delete-btn' data-id='" . htmlspecialchars($row['a_id'] ?? '', ENT_QUOTES) . "'>␡</button>
                  </td>";
        } elseif (!$mostRecords) {
            echo "<td>-</td>";
        }

        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='16' style='text-align:center;'>No records found</td></tr>";
}

$conn->close();
?>
