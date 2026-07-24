<?php
include('db.php');
include('header.php');

// Pagination setup
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total applicant count
$countSql = "SELECT COUNT(*) as total FROM Applicants";
$totalRows = $conn->query($countSql)->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
// Fetch applicants with joins
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
ORDER BY a.date_of_visit DESC, a.created_at DESC, a.a_id DESC
LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

?>

<title>View Applicants</title>
<style>
.hide-important { display: none !important; }
</style>

<div class="container">
    <h2>Applicants List</h2>

    <div class="top-container">
      <div class="left-block">    
            <div class="toggle-container">
                <fieldset>
                 <legend>Generate Excel Sheet</legend>
               
                <form action="generate_applicant_sheet_excel.php" method="POST">
                    Choose a Month:
                    <input type="month" id="month" name="month" required>
                    <button type="submit">Submit</button>
                </form>
                </fieldset>

                <label class="lablecheckbox">
                    <input type="checkbox" id="toggleColumns" class="toggleColumns" checked> Hide / Show Columns
                </label>
            </div>
         </div>   
            <div class="right-block">
                <?php   // just print the total entries & total nummber of paper issued
                $sql_print = "SELECT COUNT(entry_number) AS Total_Entry, SUM(no_of_security_papers_used) AS Total_Security_Papers FROM applicants";
                    $result_print = $conn->query($sql_print);
                    if ($result_print) {
                        $row_print = $result_print->fetch_assoc();
                        $totalEntries = $row_print['Total_Entry'];
                        $totalSecurity = $row_print['Total_Security_Papers'];
                        echo "Total Entries Issued: " . $totalEntries . "<br>";
                        echo "Total Security Paper Issued: " . $totalSecurity . "<br>";
                    }
                    ?>
            </div>
    </div>

    <table border="1"  style="display: inline-block;user-select: auto;
}">
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
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin'): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Encode JSON safely for attribute, escaping quotes
                    $applicantJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    $dsiplay_s_papers = '';
                    $i = '';
                    $display_s_papers = array_map('trim', explode(',', $row['security_papers']));

                    echo "<tr data-id='{$row['a_id']}' data-date='{$row['date_of_visit']}'>
                        <td>{$row['token_no']}</td>
                        <td>{$row['date_of_visit']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['father_husband_name']}</td>
                        <td class='togglable-col'>{$row['district_name']}</td>
                        <td class='togglable-col'>{$row['taluka_name']}</td>
                        <td class='togglable-col'>{$row['deh_name']}</td>
                        <td class='togglable-col'>{$row['form_type']}</td>
                        <td>{$row['entry_number']}</td>
                        <td>{$row['no_of_security_papers_used']}</td>
                            <td>";
                            foreach ($display_s_papers as $i => $paper) {
                                echo $paper;
                                if ($i < count($display_s_papers) - 1) echo ", ";
                                if (($i + 1) % 2 == 0) echo "<br>";
                            }
                            echo "</td>
                        <td>{$row['cnic']}</td>
                        <td>{$row['cell_number']}</td>
                        <td class='togglable-col'>{$row['relevance']}</td>
                        <td class='togglable-col'>{$row['remarks']}</td>";
                    if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
                        echo "<td>
                            <button class='edit-btn' data-applicant='{$applicantJson}'>✏️</button>
                            <button class='delete-btn' data-id='{$row['a_id']}'>␡</button>
                        </td>";
                    }
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='16'>No applicants found.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="add-btn" href="?page=<?php echo $i; ?>" style="margin-right: 5px;<?php if ($i === $page) echo ' font-weight: bold;'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <form id="editForm">
            <input type="hidden" name="a_id" id="a_id">
            <label>Name: <input type="text" name="name" id="name"></label><br>
            <label>Father/Husband Name: <input type="text" name="father_husband_name" id="father_husband_name"></label><br>
            <label>District: <select name="district" id="district"></select></label><br>
            <label>Taluka: <select name="taluka" id="taluka"></select></label><br>
            <label>Deh: <select name="deh" id="deh"></select></label><br>
            <label>Form Type:
                <select name="form_type" id="form_type">
                    <option value="VF VIIA">VF VIIA</option>
                    <option value="VF VIIB">VF VIIB</option>
                    <option value="VF II">VF II</option>
                </select>
            </label><br>
            <label>Entry Number: <input type="text" name="entry_number" id="entry_number"></label><br>
            <label>CNIC: <input type="text" name="cnic" id="cnic"></label><br>
			<label>Security Papers:	<input type="text" name="security_papers" id="security_papers" placeholder="e.g. 123, 124, 125"></label>
            <label>Cell Number: <input type="text" name="cell_number" id="cell_number"></label><br>
            <label>Relevance: <input type="text" name="relevance" id="relevance"></label><br>
            <label>Remarks: <input type="text" name="remarks" id="remarks"></label><br>
            <button type="submit">Save</button>
        </form>
    </div>
</div>


<script>
$(document).ready(function () {
    const modal = $('#editModal');
    const form = $('#editForm');

    // Toggle columns checkbox
    $('#toggleColumns').change(function () {
        const cols = $('.togglable-col');
        if (this.checked) {
            cols.hide();
        } else {
            cols.show();
        }
    }).trigger('change');

    // Alternate row colors based on date_of_visit
    let lastDate = '';
    let toggle = false;
    $('#tableBody tr').each(function () {
        const date = $(this).data('date');
        if (date !== lastDate) toggle = !toggle;
        $(this).addClass(toggle ? 'gray-date' : 'white-date');
        lastDate = date;
    });

    // Edit button click
    $(document).on('click', '.edit-btn', function () {
        const data = $(this).data('applicant'); // parsed JSON automatically

        // Sometimes data may be string if not parsed properly, fallback parsing
        const applicant = (typeof data === 'string') ? JSON.parse(data) : data;

        // Fill the form fields
        $('#a_id').val(applicant.a_id);
        $('#name').val(applicant.name);
        $('#father_husband_name').val(applicant.father_husband_name);
        $('#form_type').val(applicant.form_type);
        $('#entry_number').val(applicant.entry_number);
        $('#cnic').val(applicant.cnic);
        $('#cell_number').val(applicant.cell_number);
        $('#relevance').val(applicant.relevance);
        $('#remarks').val(applicant.remarks);
		$('#security_papers').val(applicant.security_papers);
        // Load districts first
        $.post('get_districts.php', function (districtsHtml) {
            $('#district').html(districtsHtml);
            $('#district').val(applicant.district);

            // Load talukas for this district
            $.post('get_talukas.php', { district_id: applicant.district, page: 'view' }, function (talukasHtml) {
                $('#taluka').html(talukasHtml);
                $('#taluka').val(applicant.taluka);

                // Load dehs for this taluka
                $.post('get_dehs.php', { taluka_id: applicant.taluka, page: 'view' }, function (dehsHtml) {
                    $('#deh').html(dehsHtml);
                    $('#deh').val(applicant.deh);
                });
            });
        });

        modal.show();
    });

    // District change updates talukas & dehs
    $('#district').change(function () {
        const districtId = $(this).val();
        $('#taluka').html('<option>Loading...</option>');
        $('#deh').html('<option>Select Taluka first</option>');

        $.post('get_talukas.php', { district_id: districtId, page: 'view' }, function (data) {
            $('#taluka').html(data);
            $('#deh').html('<option>Select Taluka first</option>');
        });
    });

    // Taluka change updates dehs
    $('#taluka').change(function () {
        const talukaId = $(this).val();
        $('#deh').html('<option>Loading...</option>');

        $.post('get_dehs.php', { taluka_id: talukaId, page: 'view' }, function (data) {
            $('#deh').html(data);
        });
    });

    // Close modal when clicking X
    $('.close').click(function () {
        modal.hide();
    });

    // Close modal when clicking outside modal content
    $(window).click(function (event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

    // Submit form (Update Applicant)
    form.submit(function (e) {
        e.preventDefault();

        const formData = form.serialize();
        $.post('update_applicant.php', formData, function (response) {
            if (response.success) {
                alert('Applicant updated successfully');
                location.reload();
            } else {
                alert('Error updating applicant: ' + (response.message || 'Unknown error'));
            }
        }, 'json').fail(function () {
            alert('Server error - please try again.');
        });
    });

    // Delete button click
    $(document).on('click', '.delete-btn', function () {
        if (confirm("Are you sure you want to delete this applicant?")) {
            const $row = $(this).closest('tr');
            const id = $(this).data('id');

            $.post('delete_applicant.php', { id }, function (response) {
                if (response.success) {
                    $row.fadeOut(300, function () {
                        $(this).remove();
                    });
                } else {
                    alert(response.message || 'Error deleting applicant');
                }
            }, 'json').fail(function () {
                alert('Server error - please try again.');
            });
        }
    });
});
</script>
<?php include('footer.php'); ?>

