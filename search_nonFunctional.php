<?php
include('db.php');

$search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';

// Query applicants based on search input
$sql = "SELECT a.*, 
               GROUP_CONCAT(sp.security_paper_serial_no SEPARATOR ', ') AS security_papers 
        FROM Applicants a 
        LEFT JOIN Security_Papers sp ON a.a_id = sp.applicant_id 
        WHERE a.token_no LIKE '%$search%' 
           OR a.name LIKE '%$search%'
           OR a.father_husband_name LIKE '%$search%'
           OR a.cnic LIKE '%$search%'
        GROUP BY a.a_id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr data-id='{$row['a_id']}'>
                <td class='editable-cell' data-column='token_no'>{$row['token_no']}</td>
                <td class='editable-cell' data-column='date_of_visit'>{$row['date_of_visit']}</td>
                <td class='editable-cell' data-column='name'>{$row['name']}</td>
                <td class='editable-cell' data-column='father_husband_name'>{$row['father_husband_name']}</td>
                <td class='editable-cell' data-column='district'>{$row['district']}</td>
                <td class='editable-cell' data-column='taluka'>{$row['taluka']}</td>
                <td class='editable-cell' data-column='deh'>{$row['deh']}</td>
                <td class='editable-cell' data-column='form_type'>{$row['form_type']}</td>
                <td class='editable-cell' data-column='entry_number'>{$row['entry_number']}</td>
                <td class='editable-cell' data-column='no_of_security_papers_used'>{$row['no_of_security_papers_used']}</td>
                <td class='editable-cell' data-column='security_papers'>{$row['security_papers']}</td>
                <td class='editable-cell' data-column='cnic'>{$row['cnic']}</td>
                <td class='editable-cell' data-column='cell_number'>{$row['cell_number']}</td>
                <td class='editable-cell' data-column='relevance'>{$row['relevance']}</td>
                <td class='editable-cell' data-column='remarks'>{$row['remarks']}</td>
                <td>
                    <button class='edit-btn'>Edit</button>
                    <button class='save-btn' style='display:none;'>Save</button>
                    <button class='delete-btn' data-id='{$row['a_id']}'>Delete</button>
                </td>
            </tr>";
    }
} else {
    echo "<tr><td colspan='16'>No applicants found.</td></tr>";
}
?>
