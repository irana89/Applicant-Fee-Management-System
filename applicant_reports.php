<?php
include('db.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login page
    exit(); // Stop script execution
}

include('header.php');
?>

<style>
 .form-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.form-group {
    flex: 1 1 calc(25% - 15px); /* 4 per row with gap */
    min-width: 200px;
    display: flex;
    flex-direction: column;
}
 </style>
	<script>
	function formatCNIC(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + "-" + value.substring(5);
            }
            if (value.length > 13) {
                value = value.substring(0, 13) + "-" + value.substring(13, 14);
            }
            input.value = value;
        }
		
		
		
		
	$(document).ready(function () {
    // Load Talukas on District change
    $("#district").change(function () {
        var district_id = $(this).val();
        $.ajax({
            type: "POST",
            url: "get_talukas.php",
            data: { district_id: district_id, page: 'applicant_report' },
            success: function (response) {
                $("#taluka").html(response);
                $("#deh").html('<option value="">Select Deh</option>'); // Reset Deh on District change
            }
        });
    });

    // Load Dehs on Taluka change
    $("#taluka").change(function () {
        var taluka_id = $(this).val();
        $.ajax({
            type: "POST",
            url: "get_dehs.php",
            data: { taluka_id: taluka_id, page: 'applicant_report' },
            success: function (response) {
                $("#deh").html(response);
            }
        });
    });
});
</script>
<?php



// Fetch districts from the database
$districts = $conn->query("SELECT id, name FROM districts");
$talukas = $conn->query("SELECT id, name FROM talukas");
$dehs = $conn->query("SELECT id, name FROM dehs");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token_no = $_POST['token_no'];
    $date_of_visit = $_POST['date_of_visit'];
    $name = $_POST['name'];
    $father_husband_name = $_POST['father_husband_name'];
    $district = $_POST['district'];
    $taluka = $_POST['taluka'];
    $deh = $_POST['deh'];
    $form_type = $_POST['form_type'];
    $entry_number = $_POST['entry_number'];
    $cnic = $_POST['cnic'];
    $cell_number = $_POST['cell_number'];
    $relevance = $_POST['relevance'];
    $remarks = $_POST['remarks'];

	// Security Papers (array from form)
    $security_papers = $_POST['security_paper_serial_no']; 
    $no_of_security_papers_used = count($security_papers); // Count the number of security papers

    

    // Insert applicant details
    $sql = "INSERT INTO Applicants (token_no, date_of_visit, name, father_husband_name, district, taluka, deh, form_type, entry_number,no_of_security_papers_used, cnic, cell_number, relevance, remarks) 
            VALUES ('$token_no', '$date_of_visit', '$name', '$father_husband_name', '$district', '$taluka', '$deh', '$form_type', '$entry_number','$no_of_security_papers_used', '$cnic', '$cell_number', '$relevance', '$remarks')";

    if ($conn->query($sql) === TRUE) {
        $applicant_id = $conn->insert_id; // Get last inserted ID for security papers

        // Insert multiple security paper numbers
        if (!empty($_POST['security_paper_serial_no'])) {
            foreach ($_POST['security_paper_serial_no'] as $security_paper) {
                $security_paper = $conn->real_escape_string($security_paper);
                $conn->query("INSERT INTO Security_Papers (applicant_id, security_paper_serial_no) VALUES ('$applicant_id', '$security_paper')");
            }
        }

        echo "<script>alert('Applicant added successfully');</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}
//$last_serial = '';
//	$last_serial_result = $conn->query("SELECT security_paper_serial_no FROM Security_Papers ORDER BY security_paper_serial_no DESC LIMIT 1");
	//if ($last_serial_row = $last_serial_result->fetch_assoc()) {
	//	$last_serial = htmlspecialchars($last_serial_row['security_paper_serial_no']);
	//	if (preg_match('/^([A-Z]+)(\d+)$/i', $last_serial, $matches)) {
		//	$prefix = $matches[1];              // "AB"
		//	$number = (int)$matches[2];         // 601112
		//	$last_serial = $prefix . str_pad($number + 1, strlen($matches[2]), '0', STR_PAD_LEFT); // AB601113
		//} else {
		//	$last_serial = "";
		//}
	//}
	
$overall_last_serial = '';

$query = "
    SELECT MAX(serial_no) AS last_serial
    FROM (
        SELECT security_paper_serial_no AS serial_no FROM Security_Papers
        UNION ALL
        SELECT security_paper_no AS serial_no FROM Wasted_Security_Paper
    ) AS all_serials
";

$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    if ($row && !empty($row['last_serial'])) {
        $overall_last_serial = $row['last_serial'];
		
		if (preg_match('/^([A-Z]+)(\d+)$/i', $overall_last_serial, $matches)) {
			$prefix = $matches[1];              // "AB"
			$number = (int)$matches[2];         // 601112
			$overall_last_serial = $prefix . str_pad($number + 1, strlen($matches[2]), '0', STR_PAD_LEFT); // AB601113
		} else {
			$overall_last_serial = "";
		}
    }
} else {
    echo "Error executing query: " . $conn->error;
}
?>


    <div class="container">
        <h2>Add Applicant</h2>
        <form method="POST">
            <div class="form-container">
                <div class="form-group">
                    <label>Token No:</label>
                    <input type="text" value="<?php echo isset($_POST['token_no']) ? htmlspecialchars($_POST['token_no']) : ''; ?>"  name="token_no" required>
                </div>
                <div class="form-group">
                    <label>Date of Visit:</label>
                    <input type="date"  name="date_of_visit" value="<?php echo isset($_POST['date_of_visit']) ? htmlspecialchars($_POST['date_of_visit']) : date("Y-m-d"); ?>" required>
                </div>
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" name="name" required>
                </div>
                <div class="form-group">
                    <label>Father/Husband Name:</label>
                    <input type="text" value="<?php echo isset($_POST['father_husband_name']) ? htmlspecialchars($_POST['father_husband_name']) : ''; ?>" name="father_husband_name" required>
                </div>

                    
					  <!-- District Dropdown -->
					<div class="form-group">
						<label>District:</label>

						<select name="district" id="district" required>
							<?php 
							if (isset($_POST['district']) && $districts) {
								$selecteddistrict = $_POST['district'];
								while ($row1= $districts->fetch_assoc()) {
									$selected = ($row1['id'] == $selecteddistrict) ? 'selected' : '';
									echo "<option value='{$row1['id']}' $selected>" . htmlspecialchars($row1['name']) . "</option>";
								}
							}else { ?>
								<option value="">Select District</option>
								<?php while ($row = $districts->fetch_assoc()) { ?>
								<option value="<?= $row['id']; ?>"><?= $row['name']; ?></option>
							<?php } ?>
							<?php } 
							?>
						</select>
					</div>
					
      
                   <!-- Taluka Dropdown -->
					<div class="form-group">
						<label>Taluka:</label>

						<select name="taluka" id="taluka" required>
							<?php 
							if (isset($_POST['taluka']) && $talukas) {
								$selectedTaluka = $_POST['taluka'];
								while ($row2= $talukas->fetch_assoc()) {
									$selected = ($row2['id'] == $selectedTaluka) ? 'selected' : '';
									echo "<option value='{$row2['id']}' $selected>" . htmlspecialchars($row2['name']) . "</option>";
								}
							}else { ?>
								<option value="">Select Taluka</option>
							<?php } 
							?>
						</select>
					</div>
		
				
                <!-- Deh Dropdown -->
				<div class="form-group">
					<label>Deh:</label>
					<select name="deh" id="deh" required>
							<?php 
							if (isset($_POST['deh']) && $dehs) {
								$selecteddeh = $_POST['deh'];
								while ($row3 = $dehs->fetch_assoc()) {
									$selected = ($row3['id'] == $selecteddeh) ? 'selected' : '';
									echo "<option value='{$row3['id']}' $selected>" . htmlspecialchars($row3['name']) . "</option>";
								}
							}else { ?>
								<option value="">Select Deh</option>
							<?php } 
							?>
						</select>
				</div>
				
				
				
                <?php
				$selectedFormType = isset($_POST['form_type']) ? $_POST['form_type'] : '';
				?>

				<div class="form-group">
					<label>Form Type:</label>
					<select name="form_type">
						<option value="VF VIIA" <?= $selectedFormType == 'VF VIIA' ? 'selected' : '' ?>>VF VIIA</option>
						<option value="VF VIIB" <?= $selectedFormType == 'VF VIIB' ? 'selected' : '' ?>>VF VIIB</option>
						<option value="VF II" <?= $selectedFormType == 'VF II' ? 'selected' : '' ?>>VF II</option>
					</select>
				</div>
				
                <div class="form-group">
                    <label>Entry Number:</label>
                    <input type="text" name="entry_number" required>
                </div>
                <div class="form-group">
                    <label>CNIC:</label>
                    <input type="text" value="<?php echo isset($_POST['cnic']) ? htmlspecialchars($_POST['cnic']) : ''; ?>" name="cnic" oninput="formatCNIC(this)" required>
                </div>
                <div class="form-group">
                    <label>Cell Number:</label>
                    <input type="text" value="<?php echo isset($_POST['cell_number']) ? htmlspecialchars($_POST['cell_number']) : ''; ?>" name="cell_number" required>
                </div>
                <div class="form-group">
                    <label>Relevance:</label>
                    <input type="text" value="<?php echo isset($_POST['relevance']) ? htmlspecialchars($_POST['relevance']) : '';?>" name="relevance">
                </div>
				
				<div class="security-papers form-group">
					<label>Security Paper Serial No:</label>
					<div id="security-paper-list">
						<input id="last_security_paper" type="text" name="security_paper_serial_no[]" value="<?php echo $overall_last_serial; ?>" required>
					</div>
					<button type="button" class="add-btn" id="add-paper">➕</button>
				</div>
				
                <div class="form-group">
                    <label>Remarks:</label>
                    <textarea name="remarks"></textarea>
                </div>
				
					
            </div>

         

            <br>
            <button type="submit" class="submit-btn">Submit</button>
			<button type="button" onclick="location.href=window.location.pathname;" class="submit-btn">Reset</button>
        </form>
    </div>

    <script>
		$(document).ready(function () {
			$("#add-paper").click(function () {
				// Get the last visible serial number input (including the first one)
				const allInputs = $("input[name='security_paper_serial_no[]']");
				const lastInput = allInputs.last();
				const lastValue = lastInput.val();

				const match = lastValue.match(/^([A-Za-z]*)(\d+)$/);
				if (!match) {
					alert("Invalid serial format");
					return;
				}

				const prefix = match[1];
				let number = parseInt(match[2], 10);
				number += 1;

				const newNumber = number.toString().padStart(match[2].length, '0');
				const newSerial = prefix + newNumber;

				// Append new input with incremented serial
				$("#security-paper-list").append(
					`<div>
						<input type="text" name="security_paper_serial_no[]" value="${newSerial}" required>
						<button type="button" class="remove-btn">X</button>
					</div>`
				);
			});

			$(document).on("click", ".remove-btn", function () {
				$(this).parent().remove();
			});
		});


    </script>


<?php

include('footer.php');
?>
