<?php
include('db.php');
include('header.php');

// Handle new entry
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['received_date'])) {
    $received_date = $_POST['received_date'];
    $from_serial = $_POST['from_serial'];
    $to_serial = $_POST['to_serial'];
    $total_pages = $_POST['total_pages'];

    $sql = "INSERT INTO security_paper_bundle (received_date, from_serial, to_serial, total_pages)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $received_date, $from_serial, $to_serial, $total_pages);
    if ($stmt->execute()) {
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Security Paper Bundle</title>
<style>
 .wastedpaperstyle{max-width: 320px; word-wrap: break-word;overflow-wrap: break-word; white-space: normal;}

</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll("form#searchForm button[type='submit']").forEach(button => {
        button.addEventListener("click", function(e) {
            e.preventDefault();

            const form = e.target.closest("form");
            const from = form.querySelector("#from_no").value;
            const to   = form.querySelector("#to_no").value;
            const action = e.target.name; // search1, search2, search3

            if (!from || !to || parseInt(from) > parseInt(to)) {
                alert("Please enter a valid From and To serial number range.");
                return;
            }

            fetch("get_search_missing_serial.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ from: from, to: to, action: action })
            })
            .then(res => res.json())
            .then(data => {
                const resultDiv = document.getElementById("result");
                let html = "";

                if (data.length === 0) {
                    html = "<p>No available serial numbers found.</p>";
                } else {
                     html = `<p><strong>Total Available:</strong> ${data.length}</p>`;
                    html += `<p>${data.join(', ')}</p>`;
                }

                resultDiv.innerHTML = html;
            })
            .catch(err => {
                document.getElementById("result").innerHTML = "Error fetching data.";
                console.error(err);
            });
        });
    });
});
</script>

</head>
<body>

<div class="container">
 
  <?php if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') { ?>
  
    <div id="successAlert">✅ Bundle saved successfully!</div>
	 <fieldset class="form-wrapper">
     <legend>Security Paper Bundle Entry</legend>
    <form id="bundleForm" method="POST">
        <label>Date Received:</label>
        <input type="date" name="received_date" required><br><br>

        <label>From Serial:</label>
        <input type="text" name="from_serial" id="from_serial" required><br><br>

        <label>To Serial:</label>
        <input type="text" name="to_serial" id="to_serial" required><br><br>

        <label>Total Pages:</label>
        <input type="number" name="total_pages" id="total_pages" readonly><br><br>

        <button type="submit">Save Bundle</button>
    </form>
	 </fieldset>
	 
  <?php } ?>
  <div>
    
    <fieldset class="form-wrapper">
         <legend>Search Missed Security Paper</legend>
        <form id="searchForm">
        <label>From:</label><br>
        <input type="number" id="from_no" required placeholder="360001"> 
        <label>To:</label><br>
        <input type="number" id="to_no" required placeholder="360500">
        <button type="submit" >Search</button>
    </form>
    
    </fieldset>
    <div id="result"></div>
</div>

<div>
    
    <fieldset class="form-wrapper">
        <legend>Record Filter</legend>
        <label for="monthPicker">Month:</label>
        <input type="month" id="monthPicker"> 
        <span>||</span>
        <label for="startDate">From:</label>
        <input type="date" id="startDate">
        <label for="endDate">To:</label>
        <input type="date" id="endDate">
        <button id="applyFilter">Apply Filter</button>
        <button id="resetFilter">Reset</button>
    </fieldset>
</div>
    <table class="responsive-table">
        <thead>
        <tr>
            <th>Sr</th>
            <th>Date Received</th>
            <th>From Serial</th>
            <th>To Serial</th>
            <th>Total Pages</th>
            <th>Issued</th>
            <th>Wasted</th>
            <th>Wasted Serials</th>
            <th>Remaining</th>
			<?php
					// Check if the user is logged in and only show "Actions" if logged in
					if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
						echo '<th><div id="thwidth">Actions</div></th>';
					}
				?>
        </tr>
        </thead>
        <tbody id="bundleTableBody">
        <?php
   
        $sql = "SELECT b.*, 
                       COUNT(DISTINCT sp.security_paper_serial_no) AS issued_count,
                       COUNT(DISTINCT w.w_id) AS wasted_count,
                       GROUP_CONCAT(DISTINCT w.security_paper_no ORDER BY w.security_paper_no) AS wasted_serials
                FROM security_paper_bundle b
                LEFT JOIN security_papers sp 
                    ON sp.security_paper_serial_no BETWEEN b.from_serial AND b.to_serial
                LEFT JOIN wasted_security_paper w 
                    ON w.security_paper_no BETWEEN b.from_serial AND b.to_serial
                GROUP BY b.scb_id
                ORDER BY b.received_date DESC";

        $result = $conn->query($sql);
        $count = 1;
        while ($row = $result->fetch_assoc()) {
            $remaining = $row['total_pages'] - $row['issued_count'] - $row['wasted_count'];
            $received_date = date('j-M-Y', strtotime($row['received_date']));
            echo "<tr data-id='{$row['scb_id']}'>
                    <td>{$count}</td>
                    <td>{$received_date}</td>
                    <td>{$row['from_serial']}</td>
                    <td>{$row['to_serial']}</td>
                    <td>{$row['total_pages']}</td>
                    <td>{$row['issued_count']}</td>
                    <td>{$row['wasted_count']}</td>
                    <td class='wastedpaperstyle'>{$row['wasted_serials']}</td>
                    <td>{$remaining}</td>";
					if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
                    echo "<td>
                        <button class='editBtn'>✏️</button>
                        <button class='deleteBtn'>❌️</button>
                    </td>";
					}
                 "</tr>";
            $count++;
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <form id="editForm">
            <input type="hidden" name="scb_id" id="edit_id">
            <label>Date:</label>
            <input type="date" name="received_date" id="edit_date" required><br>
            <label>From Serial:</label>
            <input type="text" name="from_serial" id="edit_from" required><br>
            <label>To Serial:</label>
            <input type="text" name="to_serial" id="edit_to" required><br>
            <label>Total:</label>
            <input type="number" name="total_pages" id="edit_total" readonly><br>
            <button type="submit">Update</button>
        </form>
    </div>
</div>

<script>
function extractParts(serial) {
    const match = serial.match(/^([A-Za-z]+)(\d+)$/);
    return match ? { prefix: match[1], number: parseInt(match[2], 10) } : null;
}

function calculateTotal(from, to) {
    from = extractParts(from);
    to = extractParts(to);
    if (from && to && from.prefix === to.prefix) {
        return to.number - from.number + 1;
    }
    return '';
}

$(document).ready(function () {
    // For the main form
    $('#from_serial, #to_serial').on('input', function () {
        const total = calculateTotal($('#from_serial').val(), $('#to_serial').val());
        $('#total_pages').val(total);
    });

    // For the edit form
    $('#edit_from, #edit_to').on('input', function () {
        const total = calculateTotal($('#edit_from').val(), $('#edit_to').val());
        $('#edit_total').val(total);
    });

    // Filter functionality
    $('#applyFilter').click(function () {
        const month = $('#monthPicker').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        if (month) {
            fetchBundles('month', month);
        } else if (startDate && endDate) {
            fetchBundles('range', JSON.stringify({ start: startDate, end: endDate }));
        } else {
            alert('Please select a filter option.');
        }
    });

    $('#resetFilter').click(function () {
        $('#monthPicker').val('');
        $('#startDate').val('');
        $('#endDate').val('');
        fetchBundles('all', '');
    });

    function fetchBundles(filterType, filterValue) {
        $.ajax({
            url: 'get_security_pages_bundle.php',
            method: 'POST',
            data: { filterType: filterType, filterValue: filterValue },
            success: function (data) {
                $('#bundleTableBody').html(data);
            }
        });
    }

    // Edit functionality
    $(document).on('click', '.editBtn', function () {
        const row = $(this).closest('tr');
        const id = row.data('id');
        const cols = row.find('td');

        $('#edit_id').val(id);
        $('#edit_date').val(cols.eq(1).text());
        $('#edit_from').val(cols.eq(2).text());
        $('#edit_to').val(cols.eq(3).text());
        $('#edit_total').val(calculateTotal(cols.eq(2).text(), cols.eq(3).text()));
        
        $('#editModal').fadeIn();
    });

    // Single form submission handler
    $('#editForm').submit(function (e) {
        e.preventDefault();
        $.post('get_update_security_bundle.php', $(this).serialize(), function (response) {
            if (response.success) {
                $('#editModal').fadeOut();
                fetchBundles('all', ''); // Refresh the table
            }
        }, 'json');
    });

    // Delete functionality
    $(document).on('click', '.deleteBtn', function () {
        if (confirm("Are you sure you want to delete this bundle?")) {
            const id = $(this).closest('tr').data('id');
            $.post('get_delete_security_bundle.php', { id }, function (response) {
                if (response.success) {
                    fetchBundles('all', '');
                } else {
                    alert(response.message || 'Error deleting bundle');
                }
            }, 'json');
        }
    });

    // Close modal
    $('#closeModal').click(function() {
        $('#editModal').fadeOut();
    });

    // Success message
    <?php if ($success): ?>
    $('#successAlert').fadeIn();
    setTimeout(() => $('#successAlert').fadeOut(), 3000);
    $('#bundleForm')[0].reset();
    history.replaceState(null, null, location.pathname);
    <?php endif; ?>
});



document.querySelector("#bundleTableBody").insertAdjacentHTML(
  "beforeend",
  `
<tr data-id="20">
    <td>20</td>
    <td>30-Apr-2025</td>
    <td>AB-341001</td>
    <td>AB-341500</td>
    <td>500</td>
    <td>346</td>
    <td>9</td>
    <td class="wastedpaperstyle"></td>
    <td>145</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="19">
    <td>19</td>
    <td>21-Aug-2024</td>
    <td>AB-306501</td>
    <td>AB-307000</td>
    <td>500</td>
    <td>496</td>
    <td>4</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="18">
    <td>18</td>
    <td>10-May-2024</td>
    <td>AB-304101</td>
    <td>AB-304300</td>
    <td>200</td>
    <td>200</td>
    <td>0</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="17">
    <td>17</td>
    <td>24-Apr-2024</td>
    <td>AB-304001</td>
    <td>AB-304100</td>
    <td>100</td>
    <td>100</td>
    <td>0</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="16">
    <td>16</td>
    <td>29-May-2023</td>
    <td>AB278001</td>
    <td>AB278500</td>
    <td>500</td>
    <td>497</td>
    <td>3</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="15">
    <td>15</td>
    <td>06-Dec-2021</td>
    <td>AB215501</td>
    <td>AB216000</td>
    <td>500</td>
    <td>497</td>
    <td>3</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="14">
    <td>14</td>
    <td>16-Sep-2021</td>
    <td>AB207101</td>
    <td>AB207200</td>
    <td>100</td>
    <td>100</td>
    <td>0</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="13">
    <td>13</td>
    <td>12-Aug-2021</td>
    <td>AB205701</td>
    <td>AB205900</td>
    <td>200</td>
    <td>200</td>
    <td>0</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="12">
    <td>12</td>
    <td>12-Mar-2021</td>
    <td>AB193501</td>
    <td>AB194000</td>
    <td>500</td>
    <td>493</td>
    <td>7</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="11">
    <td>11</td>
    <td>1-Jan-2020</td>
    <td>AB127501</td>
    <td>AB128000</td>
    <td>500</td>
    <td>493</td>
    <td>7</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="10">
    <td>10</td>
    <td></td>
    <td>AB098751</td>
    <td>AB099000</td>
    <td>250</td>
    <td>248</td>
    <td>2</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="9">
    <td>9</td>
    <td>1-Jan-2019</td>
    <td>AB098501</td>
    <td>AB098750</td>
    <td>250</td>
    <td>247</td>
    <td>3</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="8">
    <td>8</td>
    <td></td>
    <td>AB098251</td>
    <td>AB098500</td>
    <td>250</td>
    <td>249</td>
    <td>1</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="7">
    <td>7</td>
    <td></td>
    <td>AB098001</td>
    <td>AB098250</td>
    <td>250</td>
    <td>248</td>
    <td>2</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="6">
    <td>6</td>
    <td></td>
    <td>AB074751</td>
    <td>AB075000</td>
    <td>250</td>
    <td>248</td>
    <td>2</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="5">
    <td>5</td>
    <td>1-Jan-2018</td>
    <td>AB074501</td>
    <td>AB074750</td>
    <td>250</td>
    <td>250</td>
    <td>0</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="4">
    <td>4</td>
    <td></td>
    <td>AB041251</td>
    <td>AB041500</td>
    <td>250</td>
    <td>250</td>
    <td>0</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="3">
    <td>3</td>
    <td></td>
    <td>AB041001</td>
    <td>AB041250</td>
    <td>250</td>
    <td>247</td>
    <td>3</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="2">
    <td>2</td>
    <td>1-Jan-2017</td>
    <td>AB006201</td>
    <td>AB006400</td>
    <td>200</td>
    <td>200</td>
    <td>0</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>

<tr data-id="1">
    <td>1</td>
    <td>1-Jan-2015</td>
    <td>AB000701</td>
    <td>AB000900</td>
    <td>200</td>
    <td>193</td>
    <td>7</td>
    <td class="wastedpaperstyle"></td>
    <td>0</td>
    <td>
        <button class="editBtn">✏️</button>
        <button class="deleteBtn">❌️</button>
    </td>
</tr>  `
);


</script>

<?php
include('footer.php');
?>