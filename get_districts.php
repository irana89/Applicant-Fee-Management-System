<?php
include('db.php');
echo "<option value=''>Select District</option>";
$result = $conn->query("SELECT id, name FROM Districts");

while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['name']}</option>";
}

$conn->close();
?>
