<?php
include('db.php');
if ($_POST['page']== 'view'){
	
		$district_id = $_POST['district_id'];
		$talukas = $conn->query("SELECT * FROM Talukas WHERE district_id = '$district_id'");

		$options = "<option value=''>Select Taluka</option>";
		while ($taluka = $talukas->fetch_assoc()) {
			$options .= "<option value='{$taluka['id']}'>{$taluka['name']}</option>";
		}

	echo $options;
}

if ($_POST['page']== 'applicant_report'){
	if (isset($_POST['district_id'])) {
		$district_id = $_POST['district_id'];
		$talukas = $conn->query("SELECT id, name FROM talukas WHERE district_id = '$district_id'");
		echo "<option value=''>Select Taluka</option>";
		while ($row = $talukas->fetch_assoc()) {
			echo "<option value='{$row['id']}'>{$row['name']}</option>";
		}
	}
}

$conn->close();
?>
