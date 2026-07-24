<?php
include('db.php');

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['a_id'];  // Use 'a_id' from the form
    $set_clause = [];
    $security_paper_numbers = [];

    // Collect form data
    $fields = [
        'name', 'father_husband_name', 'district', 'taluka', 'deh',
        'form_type', 'entry_number', 'cnic', 'cell_number',
        'relevance', 'remarks', 'security_papers'
    ];

    foreach ($fields as $field) {
        if (!isset($_POST[$field])) continue;
        $value = trim($_POST[$field]);
        $value = $conn->real_escape_string($value);

        if ($field === 'security_papers') {
            $security_paper_numbers = array_map('trim', explode(',', $value));
            continue;
        }

        $set_clause[] = "$field = '$value'";
    }

    // Update Applicants table
    if (!empty($set_clause)) {
        $set_sql = implode(", ", $set_clause);
        $sql = "UPDATE Applicants SET $set_sql WHERE a_id = '$id'";

        if (!$conn->query($sql)) {
            echo json_encode(['success' => false, 'message' => "Error updating applicant: " . $conn->error]);
            exit;
        }
    }

    // Update security papers
    if (!empty($security_paper_numbers)) {
        $conn->query("DELETE FROM Security_Papers WHERE applicant_id = '$id'");
        foreach ($security_paper_numbers as $serial_no) {
            $serial_no = $conn->real_escape_string($serial_no);
            if (!empty($serial_no)) {
                $conn->query("INSERT INTO Security_Papers (applicant_id, security_paper_serial_no) VALUES ('$id', '$serial_no')");
            }
        }
    }

    // Update count of used papers
    $res = $conn->query("SELECT COUNT(*) AS total FROM Security_Papers WHERE applicant_id = '$id'");
    $row = $res->fetch_assoc();
    $total_papers = $row['total'];
    $conn->query("UPDATE Applicants SET no_of_security_papers_used = '$total_papers' WHERE a_id = '$id'");

    echo json_encode(['success' => true, 'message' => 'Applicant updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
