<?php
include('db.php');

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    $conn->query("DELETE FROM Security_Papers WHERE applicant_id = $id");
    $sql = "DELETE FROM Applicants WHERE a_id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "Deleted successfully.";
    } else {
        echo "Error deleting: " . $conn->error;
    }
}
?>
