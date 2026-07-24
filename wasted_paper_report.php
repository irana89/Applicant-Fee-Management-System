<?php
include('db.php');

// Handle AJAX fetch by month
if (isset($_POST['fetchMonth'])) {
    $month = $_POST['fetchMonth'];

   if (!empty($month)) {
        $sql = "SELECT w_id, security_paper_no, waste_date, reason 
                FROM wasted_security_paper 
                WHERE DATE_FORMAT(waste_date, '%Y-%m') = ? 
                ORDER BY waste_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $month);
    } else {
        // No filter — fetch all
        $sql = "SELECT w_id, security_paper_no, waste_date, reason 
                FROM wasted_security_paper 
                ORDER BY waste_date DESC";
        $stmt = $conn->prepare($sql);
    }
	

    $stmt->execute();
    $result = $stmt->get_result();

    $count = 1;
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $formattedDate = date("d-F-Y", strtotime($row['waste_date']));
            echo "<tr>
                    <td>{$count}</td>
                    <td>{$row['security_paper_no']}</td>
                    <td>{$formattedDate}</td>
                    <td>{$row['reason']}</td>
					<td>
						<button class='editBtn'>✏️</button>
						<button class='deleteBtn'>❌️</button>
					</td>
                  </tr>";
            $count++;
        }
    } else {
        echo "<tr><td colspan='4'>No wasted papers found.</td></tr>";
    }
    exit;
}

// Default: full page view
include('header.php');

// Handle new record
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['security_paper_no']) && isset($_POST['submitcheck']) ) {
    $security_paper_no = $_POST['security_paper_no'];
    $waste_date = $_POST['waste_date'];
    $reason = $_POST['reason'];

    $sql = "INSERT INTO wasted_security_paper (security_paper_no, waste_date, reason) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $security_paper_no, $waste_date, $reason);
    $stmt->execute();
}

// Handle delete action
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $sql = "DELETE FROM wasted_security_paper WHERE w_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    exit;
}

// Handle edit action
if (isset($_POST['edit_id'])) {
    $id = $_POST['edit_id'];
    $security_paper_no = $_POST['security_paper_no'];
    $waste_date = $_POST['waste_date'];
    $reason = $_POST['reason'];
    
    $sql = "UPDATE wasted_security_paper 
            SET security_paper_no = ?, waste_date = ?, reason = ?
            WHERE w_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $security_paper_no, $waste_date, $reason, $id);
    $stmt->execute();
    exit;
}


// Fetch all for default display
$sql = "SELECT w_id, security_paper_no, waste_date, reason 
        FROM wasted_security_paper 
        ORDER BY waste_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Wasted Paper Report</title>
    <style>
        .form-container input, .form-container textarea, .form-container select {
            margin: 5px;
        }
        .filter-container {
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Wasted Paper Report</h2>

	<?php if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') { ?> 
    <!-- Entry Form -->
    <form id="wastedPaperForm" class="form-container" method="POST">
        <input type="text" name="security_paper_no" placeholder="Security Paper No" required>
        <input type="date" name="waste_date" required>
        <input type="hidden" name="submitcheck" required>
        <textarea name="reason" placeholder="Reason" required></textarea>
        <button type="submit">Save</button>
    </form>
	
	<?php } ?>
	
    <!-- Filter -->
    <div class="filter-container">
        <label for="monthPicker">Filter by Month:</label>
        <input type="month" id="monthPicker">
        <button id="resetFilter">Reset</button>
    </div>

    <!-- Table -->
    <table border="1" class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Security Paper No</th>
                <th>Waste Date</th>
                <th>Reason</th>
				<?php
					// Check if the user is logged in and only show "Actions" if logged in
					if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
						echo '<th><div id="thwidth">Actions</div></th>';
					}
				?> 
            </tr>
        </thead>
        <tbody id="paperList">
            <?php
            $count = 1;
            if ($result->num_rows > 0) {
					while ($row = $result->fetch_assoc()) {
						$formattedDate = date("d-F-Y", strtotime($row['waste_date']));
					//$formattedDate = date("Y-m-d", strtotime($row['waste_date'])); // Changed format for edit form
					echo "<tr data-id='{$row['w_id']}'>
							<td>{$count}</td>
							<td>{$row['security_paper_no']}</td>
							<td>{$formattedDate}</td>
							<td>{$row['reason']}</td>";
							  if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
							echo "<td>
								<button class='editBtn'>✏️</button>
								<button class='deleteBtn'>❌️</button>
							</td>";
							  }
						  echo "</tr>";
					$count++;
				}
            } else {
                echo "<tr><td colspan='4'>No wasted papers found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
	
	
	
	<!-- Edit Modal -->
<div id="editModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div class="modal-content" style="background:white;width:400px;margin:100px auto;padding:20px;">
        <span class="close" style="float:right;cursor:pointer;font-size:20px;">&times;</span>
        <h3>Edit Wasted Paper</h3>
        <form id="editForm">
            <input type="hidden" name="edit_id" id="edit_id">
            <div style="margin:10px 0;">
                <label>Paper No:</label>
                <input type="text" name="security_paper_no" id="edit_paper_no" required style="width:100%;padding:8px;">
            </div>
            <div style="margin:10px 0;">
                <label>Waste Date:</label>
                <input type="date" name="waste_date" id="edit_waste_date" required style="width:100%;padding:8px;">
            </div>
            <div style="margin:10px 0;">
                <label>Reason:</label>
                <textarea name="reason" id="edit_reason" required style="width:100%;padding:8px;"></textarea>
            </div>
            <button type="submit" style="padding:8px 16px;background:#4CAF50;color:white;border:none;">Update</button>
        </form>
    </div>
</div>
</div>

<!-- JavaScript -->
<script>

$(document).ready(function () {
    // Save form via AJAX
    $("#wastedPaperForm").submit(function (e) {
        e.preventDefault();
        var formData = $(this).serialize();

        $.post("", formData, function () {
            alert("Record saved successfully!");
            $("#wastedPaperForm")[0].reset();
            fetchPapers($("#monthPicker").val());
        });
    });

    // Month change filter
    $("#monthPicker").change(function () {
        const month = $(this).val();
        if (month) {
            fetchPapers(month);
        }
    });

    // Reset filter
    $("#resetFilter").click(function () {
        $("#monthPicker").val("");
        fetchPapers(""); // load all data
    });

    // Fetch function
    function fetchPapers(month) {
        $.post("", { fetchMonth: month }, function (data) {
            $("#paperList").html(data);
        });
    }

    // Edit button click
    $(document).on('click', '.editBtn', function() {
        var row = $(this).closest('tr');
        $('#edit_id').val(row.data('id'));
        $('#edit_paper_no').val(row.find('td:eq(1)').text());
		
		var dateText = row.find('td:eq(2)').text().trim(); // "02-May-2025"
		var [day, monthName, year] = dateText.split('-');
		var monthMap = { "Jan":"01", "Feb":"02", "Mar":"03", "Apr":"04", "May":"05", "Jun":"06", "Jul":"07", "Aug":"08", "Sep":"09", "Oct":"10", "Nov":"11", "Dec":"12" };
		var formattedDate = `${year}-${monthMap[monthName]}-${day.padStart(2, '0')}`;
		$('#edit_waste_date').val(formattedDate); // Sets "2025-05-02"
		
        $('#edit_reason').val(row.find('td:eq(3)').text());
        $('#editModal').show();
    });

    // Delete button click
    $(document).on('click', '.deleteBtn', function() {
        if (confirm('Are you sure you want to delete this record?')) {
            var id = $(this).closest('tr').data('id');
            $.post("", { delete_id: id }, function() {
                fetchPapers($("#monthPicker").val());
            });
        }
    });

    // Edit form submission
    $('#editForm').submit(function(e) {
        e.preventDefault();
        $.post("", $(this).serialize(), function() {
            $('#editModal').hide();
            fetchPapers($("#monthPicker").val());
        });
    });

    // Close modal
    $('.close').click(function() {
        $('#editModal').hide();
    });
});
</script>


<?php
include('footer.php');
?>
