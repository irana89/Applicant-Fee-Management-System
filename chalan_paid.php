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
	$totalFees = 0;
	$challan_remaining_amount = 0;
	$challan_totalAmount = 0;

	// Fetch total amount of challans for the month
	$challanSql = "SELECT SUM(amount) AS total_challan_amount FROM Challans WHERE month = ?";
	$challanStmt = $conn->prepare($challanSql);
	$challanStmt->bind_param("s", $monthFilter);
	$challanStmt->execute();
	$challanResult = $challanStmt->get_result();
	$challanRow = $challanResult->fetch_assoc();
	$totalChallanAmount = $challanRow['total_challan_amount'] ?? 0;
	
// Fetch challans
$challans = [];
$challan_sql = "SELECT chalan_no, month, amount, DATE_FORMAT(deposit_date, '%Y-%m-%d') AS deposit_date FROM challans";
$result2 = $conn->query($challan_sql);


$challans_by_month = [];

if ($result2->num_rows > 0) {
    while ($row2 = $result2->fetch_assoc()) {
        $month_key = date('Y-m', strtotime($row2['month']));
        $challans_by_month[$month_key][] = [
            'chalan_no' => $row2['chalan_no'],
            'deposit_date' => $row2['deposit_date'],
            'amount' => $row2['amount'],
            'used' => 0 // track how much of this challan has been used
        ];
    }

    // Sort each month's challans by date
    foreach ($challans_by_month as &$challans) {
        usort($challans, function ($a, $b) {
            return strtotime($a['deposit_date']) <=> strtotime($b['deposit_date']);
        });
    }
}
	
	

?>

    <title>Challan Paid - <?php echo date('F Y', strtotime($monthFilter)); ?></title>
	
	
<script>
  const challans = <?php echo json_encode($challans); ?>;
</script>


    <script>
        $(document).ready(function() {
			
			$(document).ready(function() {
			$('tr.paid').first().addClass('paid-stamp-wrapper');
			});
			const hasPaid = document.querySelector('tr.paid');
    if (!hasPaid) {
        document.querySelector('.paid-watermark').style.display = 'none';
    }
			
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

		// rowspan
		$(document).ready(function () {   
			function mergeCommonRows(columnClass) {
				let prevText = '';
				let rowspan = 1;
				let $prevCell = null;

				$('#feesTable tbody tr').each(function () {
					const $cell = $(this).find('td.' + columnClass);
					const cellText = $cell.text().trim();

					if (cellText === prevText && cellText !== '') {
						rowspan++;
						$cell.remove(); // remove current cell
						$prevCell.attr('rowspan', rowspan); // update rowspan
					} else {
						prevText = cellText;
						rowspan = 1;
						$prevCell = $cell;
					}
				});
			}

			mergeCommonRows('challan-cell');
			mergeCommonRows('deposit-cell');
		});	

		// Mouse Selection Fees COUNT
		
		$(document).ready(function() {	
		  $(function(){

			  let isSelecting = false;
			  let $selectedCells = $();
			  

			  function clearSelection() {
				$selectedCells.removeClass('selected');
				$selectedCells = $();
				$('#sumBox').hide();
			  }

			  function calculateSum() {
				let sum = 0;
				$selectedCells.each(function(){
				  let val = parseFloat($(this).text());
				  if (!isNaN(val)) sum += val;
				});
				return sum;
			  }

			  $('#feesTable').on('mousedown', 'td.selectlikeexcel', function(e){
				clearSelection();
				isSelecting = true;
				$selectedCells = $(this).addClass('selected');
				updateSumBox(e.pageX, e.pageY);
				e.preventDefault();
			  });

			  $('#feesTable').on('mouseover', 'td.selectlikeexcel', function(e){
				if (isSelecting) {
				  if (!$selectedCells.is(this)) {
					$selectedCells = $selectedCells.add(this);
					$(this).addClass('selected');
				  }
				  updateSumBox(e.pageX, e.pageY);
				}
			  });

			  $(document).on('mouseup', function(){
				if (isSelecting) {
				  isSelecting = false;
				  if ($selectedCells.length === 0) {
					$('#sumBox').hide();
				  }
				}
			  });

			  function updateSumBox(x, y) {
				  
				  
				let sum = calculateSum();
				$('#sumBox').text(' ' + sum).css({
				  left: (1100 + 15) + 'px',
				  top: (85 + 15) + 'px',
				  display: 'block'
				});
			  }
		  
					$(document).on('click', e => !$(e.target).closest('#feesTable,#sumBox').length && $('#sumBox').fadeOut());
					setTimeout(() => $('#sumBox').fadeOut(), 3000); // Auto hide after 4 sec
					
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
                <input type="text" name="chalan_no" required style="width: 20px;">
				<label>Month:</label>
                <input type="month"  name="challan_month" required style="width: 100px;">
                <label>Amount Paid:</label>
				<input type="number" name="amount" required style="width: 80px;">

				 <label>Deposit Date:</label>
				<input type="date" value=""  name="deposit_date" required style="width: 100px;">
				
                
                <label>Select Challan Image:</label>
                <input type="file" name="challan_image[]" multiple required style="width: 100px;">
                <button type="submit">Upload</button>
            </form>
            <div id="uploadStatus"></div>
        </fieldset>
		
		<?php }?>
        
		
		<div class="table-wrapper">
    <div class="paid-watermark">PAID</div>
		<table id="feesTable">
            <thead>
                <tr>
                    <th>S. No.</th>
                    <th>Date of Visit</th>
					 <th>Applicant Name</th>
                    <th>No. of Entries</th>
                    <th>Total Pages</th>
                    <th>Single Paper Fee (PKR)</th>
                    <th>Total Fee (PKR)</th>
                    <th>Challan No</th>
                    <th>Deposit Date</th>
                    <th>Bank & Branch C03885</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
					 $sno = 1;
                    while ($row = $result->fetch_assoc()) {
						
						$applicant_date = $row['date_of_visit'];
						$applicantDate = date('d-M-Y', strtotime($applicant_date));
						$applicant_month = date('Y-m', strtotime($applicant_date));

						$totalPages = $row['total_papers'];
						$visitDate = strtotime($row['date_of_visit']);
						$feeCutoffDate = strtotime('2025-07-1');

						// Dynamically determine fee
						$dynamicFee = ($visitDate < $feeCutoffDate) ? 300 : 150;

						$totalAmount = $totalPages * $dynamicFee;

						$totalEntries += $row['num_of_entries'];
						$challan_totalAmount += $totalAmount;
						$totalApplicantsPages += $totalPages;
						$totalFees += $totalAmount;

						$statusClass = ($totalChallanAmount >= $totalFees) ? 'paid' : 'unpaid';
						$TDstatusClass = ($totalChallanAmount >= $totalFees) ? 'pay' : '';

						echo "<tr data-date='{$row['date_of_visit']}' class='{$statusClass}'>
								<td>{$sno}</td>
								<td>{$applicantDate}</td>
								<td>{$row['name']}</td>
								<td>{$row['num_of_entries']}</td>
								<td class='selectlikeexcel {$TDstatusClass}'>{$totalPages}</td>
								<td>{$dynamicFee}</td>
								<td class='selectlikeexcel {$TDstatusClass}'>{$totalAmount}</td>";

						// assign this applicant to one or more challans
						if (isset($challans_by_month[$applicant_month])) {
							$remainingAmount = $totalAmount;
							$displayedChallans = [];

							foreach ($challans_by_month[$applicant_month] as &$challan) {
								$available = $challan['amount'] - $challan['used'];

								if ($available <= 0) continue;

								$usedAmount = min($remainingAmount, $available);
								$challan['used'] += $usedAmount;
								$remainingAmount -= $usedAmount;

								$displayedChallans[] = [
									'chalan_no' => $challan['chalan_no'],
									'deposit_date' => $challan['deposit_date']
								];

								if ($remainingAmount <= 0) break;
							}

							if (!empty($displayedChallans)) {
								// Display first challan (or more if needed)
								echo "<td class='challan-cell'>";
								foreach ($displayedChallans as $disp) {
									echo "{$disp['chalan_no']}<br>";
								}
								echo "</td>";

								echo "<td class='deposit-cell'>";
								foreach ($displayedChallans as $disp) {
									echo "{$disp['deposit_date']}<br>";
								}
								echo "</td>";
							} else {
								echo "<td class='challan-cell'></td><td class='deposit-cell'></td>";
							}
						} else {
							echo "<td class='challan-cell'></td><td class='deposit-cell'></td>";
						}

						echo "<td></td></tr>";
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
                    <th style="text-align:center;">TOTAL FEES</th>
                    <th><?php echo number_format($totalFees, 0); ?> </th>
					<th>-</th>
                    <th>-</th>
                    <th>-</th>
                </tr>
					<?php $challan_remaining_amount = $totalFees - $totalChallanAmount; ?>
				<tr>
					<th>-</th>
                    <th>-</th>
                    <th>-</th>
					<th>-</th>
                    <th>-</th>
                    <th style="text-align:center;">Challan Paid Amount</th>
					<th><?php echo number_format($totalChallanAmount, 0); ?> </th>
					<th>-</th>
                    <th>-</th>
                    <th>-</th>
				</tr>
				<tr>
					<th>-</th>
                    <th>-</th>
                    <th>-</th>
					<th>-</th>
                    <th>-</th>
                    <th style="text-align:center;">Challan Remaining Amount</th>
					<th><?php echo number_format($challan_remaining_amount, 0); ?></th>
					<th>-</th>
                    <th>-</th>
                    <th>-</th>
				</tr>
            </tfoot>
        </table>
		<div id="sumBox"></div>
		</div>
		
    </div>

<script>
$(document).ready(function () {
// Alternate row colors based on date_of_visit
    let lastDate = '';
    let toggle = false;
    $('#feesTable tr').each(function () {
        const date = $(this).data('date');
        if (date !== lastDate) toggle = !toggle;
        $(this).addClass(toggle ? 'gray-date' : 'white-date');
        lastDate = date;
    });
});
</script>


<?php
include('footer.php');
?>
