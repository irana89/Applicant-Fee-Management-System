<?php
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $data = $_POST['data'];

    $set_clause = [];
    $security_paper_numbers = []; // Track security papers separately

    foreach ($data as $column => $value) {
        $value = $conn->real_escape_string($value);
        
        // Handle security papers separately
        if ($column == "security_papers") {
            $security_paper_numbers = array_map('trim', explode(",", $value)); // Convert to array
            continue;
        }

        // Handle District, Taluka, and Deh separately (convert name to ID)
        if (in_array($column, ["district", "taluka", "deh"])) {
            $table = ucfirst($column) . "s"; // Convert column name to table name
            $id_query = "SELECT id FROM $table WHERE name = '$value' LIMIT 1";
            $result = $conn->query($id_query);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $value = $row['id']; // Replace name with ID
            }
        }

        $set_clause[] = "$column = '$value'";
    }

    // Update applicants table
    if (!empty($set_clause)) {
        $set_clause = implode(", ", $set_clause);
        $sql = "UPDATE Applicants SET $set_clause WHERE a_id = '$id'";

        if (!$conn->query($sql)) {
            echo "Error updating applicant record: " . $conn->error;
            exit;
        }
    }

    // Handle security paper serial numbers update
    if (!empty($security_paper_numbers)) {
        // Delete existing security papers for the applicant
        $conn->query("DELETE FROM Security_Papers WHERE applicant_id = '$id'");

        // Insert updated security papers
        foreach ($security_paper_numbers as $serial_no) {
            $serial_no = $conn->real_escape_string(trim($serial_no));
            if (!empty($serial_no)) {
                $conn->query("INSERT INTO Security_Papers (applicant_id, security_paper_serial_no) VALUES ('$id', '$serial_no')");
            }
        }
    }

    // Correctly update total security papers count after insertions
    $result = $conn->query("SELECT COUNT(*) AS total FROM Security_Papers WHERE applicant_id = '$id'");
    $row = $result->fetch_assoc();
    $total_papers = $row['total'];

    $conn->query("UPDATE Applicants SET no_of_security_papers_used = '$total_papers' WHERE a_id = '$id'");

    echo "Applicant updated successfully!";
}

$conn->close();
?>