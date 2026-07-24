<?php
include('db.php');

$result = $conn->query("SELECT DISTINCT form_type FROM applicants");

$options = "";
while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['form_type']}'>{$row['form_type']}</option>";
}

echo $options;
$conn->close();
?>
