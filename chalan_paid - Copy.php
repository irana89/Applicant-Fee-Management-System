<?php
include('db.php');
include('header.php');

$singlePaperFee = 300; // Fee per single security paper
$currentMonth = date('Y-m'); // Format: YYYY-MM

// Fetch applicants and their security paper count for the current month
$monthFilter = isset($_GET['month']) ? $_GET['month'] : $currentMonth;

$sql = "SELECT a.name,a.cnic,a.date_of_visit,
			COUNT(*) AS num_of_entries,
			SUM(a.no_of_security_papers_used) AS total_papers 
		FROM Applicants a
		WHERE DATE_FORMAT(a.date_of_visit, '%Y-%m') = ?
		GROUP BY a.cnic, a.date_of_visit
		ORDER BY MAX(a.created_at) DESC;";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $monthFilter);

$stmt->execute();

$result = $stmt->get_result();

$totalApplicantsPages = 0;
$totalEntries=0;
$total_amount_Paid = 0;
$challan_remaining_amount = 0;

// Fetch total amount of challans for the month
$challanSql = "SELECT SUM(amount) AS total_challan_amount FROM Challans WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
$challanStmt = $conn->prepare($challanSql);
$challanStmt->bind_param("s", $monthFilter);
$challanStmt->execute();
$challanResult = $challanStmt->get_result();
$challanRow = $challanResult->fetch_assoc();
$totalChallanAmount = $challanRow['total_challan_amount'] ?? 0;

?>

    <title>Challan Paid - <?php echo date('F Y', strtotime($monthFilter)); ?></title>

    <script>
        $(document).ready(function() {
			
			// Alternate row colors based on date_of_visit
			let lastDate = '';
			let toggle = false;
			$('#tableBody tr').each(function () {
				const date = $(this).data('date');
				if (date !== lastDate) toggle = !toggle;
				$(this).addClass(toggle ? 'gray-date' : 'white-date');
				lastDate = date;
			});
			
            $('#uploadForm').submit(function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: 'upload_challan.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#uploadStatus').html(response);
                       // location.reload();
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Challan Paid Details - <?php echo date('F Y', strtotime($monthFilter)); ?></h2>
        
        <div class="form-wrapper">
            <form method="GET" action="">
                <label for="month">Select Month To View Record:</label>
                <input type="month" name="month" value="<?php echo $monthFilter; ?>">
                <button type="submit">View</button>
            </form>
            <form action="generate_challan_sheet_excel.php" method="POST">
                <label for="month">Generate Excel Sheet:</label>
                <input type="month" id="month" name="month" required>
                <button type="submit">Extract</button>
            </form>
        </div>
		
        <?php if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') { ?>
		
        <fieldset>
            <legend>Upload Challan</legend>
            <form id="uploadForm" enctype="multipart/form-data" class="upload-form-container">
                <label>Challan No:</label>
                <input type="text" name="chalan_no" required>
				<label>Month:</label>
                <input type="month"  name="challan_month" required>
                <label>Amount Paid:</label>
                <input type="number" name="amount" required>
                <label>Select Challan Image:</label>
                <input type="file" name="challan_image[]" multiple required>
                <button type="submit">Upload</button>
            </form>
            <div id="uploadStatus"></div>
        </fieldset>
		
		<?php }?>
        
		<table id="tableBody">
            <thead>
                <tr>
                    <th>S. No.</th>
                    <th>Applicant Name</th>
                    <th>Date of Visit</th>
                    <th>No. of Entries</th>
                    <th>Total Pages</th>
                    <th>Single Paper Fee (PKR)</th>
                    <th>Total Fee (PKR)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
					 $sno = 1;
                    while ($row = $result->fetch_assoc()) {
						
                        $totalPages = $row['total_papers'];
                        $totalAmount = $totalPages * $singlePaperFee;
                        $totalEntries += $row['num_of_entries'];

                        $totalApplicantsPages += $totalPages;
                        $total_amount_Paid += $totalAmount;
						
						$statusClass = ($totalChallanAmount >= $total_amount_Paid) ? 'paid' : 'unpaid';

                        echo "<tr data-date='{$row['date_of_visit']}' class='{$statusClass}' >
                                <td>{$sno}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['date_of_visit']}</td>
                                <td>{$row['num_of_entries']}</td>
                                <td>{$totalPages}</td>
                                <td>{$singlePaperFee}</td>
                                <td>{$totalAmount}</td>
                              </tr>";
							$sno++;
                    }
                } else {
                    echo "<tr><td colspan='6'>No records found.</td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
					<th>-</th>
                    <th>-</th>
                    <th>-</th>
                    <th><?php echo $totalEntries; ?></th>
                    <th><?php echo $totalApplicantsPages; ?></th>
                    <th>-</th>
                    <th><?php echo $total_amount_Paid; ?> PKR</th>
                </tr>
            </tfoot>
        </table>
		<table>
			<?php $challan_remaining_amount = $total_amount_Paid - $totalChallanAmount; ?>
			<th>Challan Paind Amount  </th>
			<th>Challan Remaining Amount </th>   
			<tr>
				<td style="background: #eb3d5f;"><h3><?php echo number_format($totalChallanAmount, 0); ?> </h3></td>
				<td style="background: #eb3d5f;"><h3><?php echo number_format($challan_remaining_amount, 0); ?> </h3></td>
			</tr>
		</table>
    </div>
<?php
include('footer.php');
?>