<?php
include('db.php');
include('header.php');

// Pagination setup
$limit = 50; // Number of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total applicants for pagination
$countSql = "SELECT COUNT(*) as total FROM Applicants";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch applicants & security papers with pagination
$sql = "SELECT a.*,  
       d.name AS district_name, 
       t.name AS taluka_name, 
       deh.name AS deh_name,
       GROUP_CONCAT(sp.security_paper_serial_no SEPARATOR ', ') AS security_papers 
FROM Applicants a 
LEFT JOIN Districts d ON a.district = d.id  
LEFT JOIN Talukas t ON a.taluka = t.id 
LEFT JOIN Dehs deh ON a.deh = deh.id
LEFT JOIN Security_Papers sp ON a.a_id = sp.applicant_id 
GROUP BY a.a_id 
ORDER BY a.created_at DESC, a.token_no DESC
LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
?>

<title>View Applicants</title>
<style>
.hide-important{display: none !important; }
</style>

<script>

$(document).ready(function () {
    $(document).on("click", ".edit-btn", function () {
        var row = $(this).closest("tr");
        row.data("original", row.html());
	
        row.find(".editable-cell").each(function () {
            var cell = $(this);
            var column = cell.data("column");
            var idValue = cell.data("id");
            var currentValue = cell.find("select").length ? cell.find("select").val() : cell.text().trim();

            if (column === "form_type") {
                var selectHTML = `
                    <select class='form-type-dropdown'>
                        <option value="VF VIIA" ${currentValue === "VF VIIA" ? "selected" : ""}>VF VIIA</option>
                        <option value="VF VIIB" ${currentValue === "VF VIIB" ? "selected" : ""}>VF VIIB</option>
                        <option value="VF II" ${currentValue === "VF II" ? "selected" : ""}>VF II</option>
                    </select>`;
                cell.html(selectHTML);
            } else if (column === "district") {
                $.post("get_districts.php", function (data) {
                    var selectHTML = `<select  class='district-dropdown' data-id='${idValue}'>${data}</select>`;
                    cell.html(selectHTML);
                    cell.find("select").val(idValue);
                });
            } else if (column === "taluka") {
                var districtId = row.find("[data-column='district'] select").val();
                $.post("get_talukas.php", { district_id: districtId, page: 'view' }, function (data) {
                    var selectHTML = `<select class='taluka-dropdown' data-id='${idValue}'>${data}</select>`;
                    cell.html(selectHTML);
                    cell.find("select").val(idValue);
                });
            } else if (column === "deh") {
                var talukaId = row.find("[data-column='taluka'] select").val();
                $.post("get_dehs.php", { taluka_id: talukaId, page: 'view' }, function (data) {
                    var selectHTML = `<select class='deh-dropdown' data-id='${idValue}'>${data}</select>`;
                    cell.html(selectHTML);
                    cell.find("select").val(idValue);
                });
            } else {
                cell.html(`<input type="text" value="${currentValue}">`);
            }
        });

        row.find(".edit-btn").hide();
        row.find(".save-btn, .cancel-btn").show();
    });

    $(document).on("change", ".district-dropdown", function () {
        var districtId = $(this).val();
        var row = $(this).closest("tr");

        $.post("get_talukas.php", { district_id: districtId, page: 'view' }, function (data) {
            row.find(".taluka-dropdown").html(data).prop("disabled", false);
            row.find(".deh-dropdown").html("<option value=''>Select Deh</option>").prop("disabled", true);
        });
    });

    $(document).on("change", ".taluka-dropdown", function () {
        var talukaId = $(this).val();
        var row = $(this).closest("tr");

        $.post("get_dehs.php", { taluka_id: talukaId, page: 'view' }, function (data) {
            row.find(".deh-dropdown").html(data).prop("disabled", false);
        });
    });

    $(document).on("click", ".cancel-btn", function () {
        var row = $(this).closest("tr");
        row.html(row.data("original"));
    });

    $(document).on("click", ".save-btn", function () {
        var row = $(this).closest("tr");
        var id = row.data("id");
        var updatedData = {};

        row.find(".editable-cell").each(function () {
            var cell = $(this);
            var column = cell.data("column");
            var newValue = cell.find("select").length ? cell.find("select").val() : cell.find("input").val();
            updatedData[column] = newValue;
            cell.html(newValue);
        });

        $.post("update_applicant.php", { id: id, data: updatedData }, function (response) {
            console.log(response);
        });

        row.find(".save-btn, .cancel-btn").hide();
        row.find(".edit-btn").show();
    });
});
    // Delete functionality
    $(document).on('click', '.delete-btn', function () {
    if (confirm("Are you sure you want to delete this bundle?")) {
        const $row = $(this).closest('tr'); // Store the row reference
        const id = $row.data('id');

        $.post('delete_applicant.php', { id }, function (response) {
            if (response.success) {
                $row.fadeOut(300, function() { 
                    $(this).remove(); // Remove row after fade animation
                });
            } else {
                alert(response.message || 'Error deleting bundle');
            }
        }, 'json').fail(function() {
            alert('Server error - please try again.');
        });
    }
});

// show hide TD
$(document).ready(function () {
    // Hide on page load
    $('.togglable-col').addClass('hide-important');
	$('#toggleColumns').prop('checked', true);
});

$(document).on("click", "#toggleColumns", function () {
    $('.togglable-col').removeClass('hide-important');
});

$(document).ready(function() {  
    $('#toggleColumns').change(function() {
        const $cols = $('.togglable-col');
        if ($(this).is(':checked')) {
            $cols.animate({ opacity: 0, width: 0 }, 300, function() {
                $(this).hide(); // Ensures it's fully hidden
            });
        } else {
            $cols.css({ display: 'table-cell', width: 0, opacity: 0 }) // Start hidden
                 .animate({ opacity: 1, width: 'auto' }, 300); // Animate to full visibility
        }
    }).trigger('change'); // Trigger once on page load if default is checked
});

</script>

<div class="container">
    <h2>Applicants List</h2>
	

	
	<div class="toggle-container">
<h3>Generate Excel Sheet</h3>
    <form action="generate_applicant_sheet_excel.php" method="POST">
        <label for="month">Choose a Month:</label>
        <input type="month" id="month" name="month" required>
        <button type="submit">Submit</button>
    </form>
	
	
	
        <label>
            <input type="checkbox" id="toggleColumns"> 
            Hide / Show Columns
        </label>
    </div>
	
    <table border="1">
        <thead>
            <tr>
                <th>Token No</th>
                <th>Date of Visit</th>
                <th>Name</th>
                <th>Father/Husband Name</th>
                <th class="togglable-col">District</th>
                <th class="togglable-col">Taluka</th>
                <th class="togglable-col">Deh</th>
                <th class="togglable-col">Form Type</th>
                <th>Entry Number</th>
                <th>No. of Security Papers</th>
                <th>Security Papers</th>
                <th>CNIC</th>
                <th>Cell Number</th>
                <th class="togglable-col">Relevance</th>
                <th class="togglable-col">Remarks</th>
				 <?php
					// Check if the user is logged in and only show "Actions" if logged in
					if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
						echo '<th><div id="thwidth">Actions</div></th>';
					}
				?>
				
            </tr>
        </thead>
<tbody id='tableBody'>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr data-id='{$row['a_id']}' data-date='{$row['date_of_visit']}'>
                    <td class='editable-cell' data-column='token_no'>{$row['token_no']}</td>
                    <td class='editable-cell' data-column='date_of_visit'>{$row['date_of_visit']}</td>
                    <td class='editable-cell' data-column='name'>{$row['name']}</td>
                    <td class='editable-cell' data-column='father_husband_name'>{$row['father_husband_name']}</td>

                    <td class='editable-cell togglable-col' data-column='district' data-id='{$row['district']}'>
                        <select id='selectbox' class='district-dropdown' disabled>
                            <option value='{$row['district']}' selected>{$row['district_name']}</option>
                        </select>
                    </td>

                    <td class='editable-cell togglable-col' data-column='taluka' data-id='{$row['taluka']}'>
                        <select id='selectbox' class='taluka-dropdown' disabled>
                            <option value='{$row['taluka']}' selected>{$row['taluka_name']}</option>
                        </select>
                    </td>

                    <td class='editable-cell togglable-col' data-column='deh' data-id='{$row['deh']}'>
                        <select id='selectbox' class='deh-dropdown' disabled>
                            <option value='{$row['deh']}' selected>{$row['deh_name']}</option>
                        </select>
                    </td>

                    <td class='editable-cell togglable-col' data-column='form_type'>
                        <select id='selectbox' class='form-type-dropdown' disabled>
                           <option value='{$row['form_type']}' selected>{$row['form_type']}</option>
                        </select>
                    </td>

                    <td class='editable-cell' data-column='entry_number'>{$row['entry_number']}</td>
                    <td class='editable-cell' data-column='no_of_security_papers_used'>{$row['no_of_security_papers_used']}</td>
                    <td class='editable-cell' data-column='security_papers'>{$row['security_papers']}</td>
                    <td class='editable-cell' data-column='cnic'>{$row['cnic']}</td>
                    <td class='editable-cell' data-column='cell_number'>{$row['cell_number']}</td>
                    <td class='editable-cell togglable-col' data-column='relevance'>{$row['relevance']}</td>
                    <td class='editable-cell togglable-col' data-column='remarks'>{$row['remarks']}</td>";
                   if  (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
				     echo  "<td style='padding: 1px;'>
                        <button class='edit-btn'>✏️</button>
                        <button class='save-btn' style='display:none;'>✔️</button>
                        <button class='cancel-btn' style='display:none;'>❌️</button>
                        <button class='delete-btn' data-id='{$row['a_id']}'>␡ </button>	
						
                    </td>";
				   }	
                "</tr>";
        }
    } else {
        echo "<tr><td colspan='16'>No applicants found.</td></tr>";
    }
    ?>
</tbody>
</table>

<!-- Pagination Controls -->
<div style="margin-top: 20px;">
    <?php if ($totalPages > 1): ?>
        <div>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="add-btn" href="?page=<?php echo $i; ?>" style="margin-right: 5px;<?php if ($i === $page) echo ' font-weight: bold;'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

</div>

<script>
$(document).ready(function () {
    let lastDate = '';
    let toggle = false;

    $('#tableBody tr').each(function () {
        let currentDate = $(this).data('date');

        if (currentDate !== lastDate) {
            toggle = !toggle;
            lastDate = currentDate;
        }

        $(this).addClass(toggle ? 'gray-date' : 'white-date');
    });
});


</script>

<?php

include('footer.php');
?>