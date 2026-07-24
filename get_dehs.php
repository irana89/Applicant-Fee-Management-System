<?php
include('db.php');

if ($_POST['page']== 'view'){
			$taluka_id = $_POST['taluka_id'];
		$dehs = $conn->query("SELECT * FROM Dehs WHERE taluka_id = '$taluka_id'");

		$options = "<option value=''>Select Deh</option>";
		while ($deh = $dehs->fetch_assoc()) {
			$options .= "<option value='{$deh['id']}'>{$deh['name']}</option>";
		}

		echo $options;
}


if ($_POST['page']== 'applicant_report'){
	if (isset($_POST['taluka_id'])) {
		$taluka_id = $_POST['taluka_id'];
		$dehs = $conn->query("SELECT id, name FROM dehs WHERE taluka_id = '$taluka_id'");
		echo "<option value=''>Select Deh</option>";
		while ($row = $dehs->fetch_assoc()) {
			echo "<option value='{$row['id']}'>{$row['name']}</option>";
		}
	}
}

$conn->close();
?>
