<?php
include('db.php');
include('header.php');
?>

<title>Search Records</title>
<style>
input[type="text"], select { padding: 6px; margin: 4px 0; }
#searchBox { width: 300px; margin-bottom: 10px; padding: 6px; }
.gray-date { background-color: #f5f5f5; }
.white-date { background-color: #ffffff; }
.modal { display: none; position: fixed; z-index: 999; left: 0; top: 0;
  width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
.modal-content { background-color: #fff; margin: 10% auto; padding: 20px;
  border: 1px solid #888; width: 400px; border-radius: 8px; }
.close { color: #aaa; float: right; font-size: 24px; font-weight: bold; cursor: pointer; }
.close:hover { color: #000; }
button.edit-btn, button.delete-btn { cursor: pointer; }
.datestyle {
 /* 💡 Force wrapping and breaking long strings */
  white-space: normal !important;
  overflow-wrap: anywhere !important;
  word-break: break-word !important;

  /* 💡 Control column width */
  max-width: 320px;
}
</style>

<div class="container">
    <h2>Search Records</h2>

    <!-- 🔍 Search Bar + Checkbox -->
    <div class="toggle-container">
        <input type="text" id="searchBox" placeholder="🔍 Type to search applicants...">
        <label style="margin-left: 10px;">
            <input type="checkbox" id="mostRecords"> Most Record Holder
        </label>
    </div>

    <!-- Table -->
    <table border="1" style="display: inline-block; border-collapse: collapse; user-select: unset;">
        <thead>
            <tr>
                <th>Token No</th>
                <th >Date of Visit</th>
                <th>Name</th>
                <th>Father/Husband Name</th>
                <th>District</th>
                <th>Taluka</th>
                <th>Deh</th>
                <th>Form Type</th>
                <th>Entry Number</th>
                <th>No. of Security Papers</th>
                <th>Security Paper Numbers</th> 
                <th>CNIC</th>
                <th>Cell Number</th>
                <th>Relevance</th>
                <th>Remarks</th>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin'): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody id="tableBody">
            <tr><td colspan="15" style="text-align:center;">Type in the search box or check "Most Record Holder"...</td></tr>
        </tbody>
    </table>
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
            <label>Security Papers: <input type="text" name="security_papers" id="security_papers" placeholder="e.g. 123, 124, 125"></label><br>
            <label>Cell Number: <input type="text" name="cell_number" id="cell_number"></label><br>
            <label>Relevance: <input type="text" name="relevance" id="relevance"></label><br>
            <label>Remarks: <input type="text" name="remarks" id="remarks"></label><br>
            <button type="submit">Save</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    // Function to load results
    function loadResults(query = '', mostRecords = false) {
        if (query.length === 0 && !mostRecords) {
            $('#tableBody').html('<tr><td colspan="15" style="text-align:center;">Type in the search box or check "Most Record Holder"...</td></tr>');
            return;
        }

        $('#tableBody').html('<tr><td colspan="15" style="text-align:center;">Loading...</td></tr>');

        $.ajax({
            url: 'get_search_record.php',
            type: 'POST',
            data: { query, mostRecords },
            success: function (html) {
                $('#tableBody').html(html);
                applyRowColors();
            },
            error: function () {
                $('#tableBody').html("<tr><td colspan='15'>Server error while fetching records.</td></tr>");
            }
        });
    }

    // 🔍 Live search
    $('#searchBox').on('keyup', function () {
        const query = $(this).val().trim();
        const mostRecords = $('#mostRecords').is(':checked');
        loadResults(query, mostRecords);
    });

    // ✅ Checkbox still works for search, but not for hiding/showing columns
    $('#mostRecords').change(function () {
        const mostRecords = $(this).is(':checked');
        const query = $('#searchBox').val().trim();
        loadResults(query, mostRecords);
    });

    // Alternate row colors grouped by date
    function applyRowColors() {
        let lastDate = '';
        let toggle = false;
        $('#tableBody tr').each(function () {
            const date = $(this).data('date');
            if (date !== lastDate) toggle = !toggle;
            $(this).removeClass('gray-date white-date').addClass(toggle ? 'gray-date' : 'white-date');
            lastDate = date;
        });
    }

    // ==============================
    // 📝 EDIT & DELETE FUNCTIONALITY
    // ==============================
    const modal = $('#editModal');
    const form = $('#editForm');

    // Edit button click
    $(document).on('click', '.edit-btn', function () {
        const data = $(this).data('applicant');
        const applicant = (typeof data === 'string') ? JSON.parse(data) : data;

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

        $.post('get_districts.php', function (districtsHtml) {
            $('#district').html(districtsHtml);
            $('#district').val(applicant.district);
            $.post('get_talukas.php', { district_id: applicant.district, page: 'view' }, function (talukasHtml) {
                $('#taluka').html(talukasHtml);
                $('#taluka').val(applicant.taluka);
                $.post('get_dehs.php', { taluka_id: applicant.taluka, page: 'view' }, function (dehsHtml) {
                    $('#deh').html(dehsHtml);
                    $('#deh').val(applicant.deh);
                });
            });
        });
        modal.show();
    });

    $('.close').click(() => modal.hide());
    $(window).click(function (e) { if ($(e.target).is(modal)) modal.hide(); });

    // Save changes
    form.submit(function (e) {
        e.preventDefault();
        $.post('update_applicant.php', form.serialize(), function (res) {
            if (res.success) {
                alert('Applicant updated successfully');
                modal.hide();
                loadResults($('#searchBox').val().trim(), $('#mostRecords').is(':checked'));
            } else alert('Error: ' + (res.message || 'Update failed'));
        }, 'json').fail(() => alert('Server error.'));
    });

    // Delete button
    $(document).on('click', '.delete-btn', function () {
        if (!confirm('Are you sure you want to delete this applicant?')) return;
        const id = $(this).data('id');
        const $row = $(this).closest('tr');
        $.post('delete_applicant.php', { id }, function (res) {
            if (res.success) {
                $row.fadeOut(300, () => $(this).remove());
            } else alert(res.message || 'Error deleting record.');
        }, 'json').fail(() => alert('Server error.'));
    });
});
</script>

<?php include('footer.php'); ?>
