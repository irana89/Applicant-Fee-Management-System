<?php
include('db.php');
include('header.php');
?>

    <title>Monthly Paid Challan Report (July to July)</title>
    <style>
        td { padding: 30px !important; font-size: 14px; }
        .h3heading {color: #010101; width: 70%; margin: 20px auto; text-align: center; }
        .table { width: 70%; margin: 20px auto; }
    </style>

   <script>
     $(document).ready(function() {
            $('#year_select').on('change', function() {
            const selectedYear = $(this).val();
           // loadReport(selectedYear);
            $('#year_hidden').val(selectedYear); // update hidden input
        });

        // Set initial value on load
        $('#year_hidden').val($('#year_select').val());
    });
    </script>


<div class="container">
    <h3 class="h3heading">
        STATEMENT SHOWING MONTH-WISE ACTUAL RECEIPTS OF <br>
        (FEE PAYABLE FOR OBTAINING INFORMATION COPIES OF PUBLIC RECORD) <br>
        <span id="year-label"></span>
    </h3>

    <div style="text-align:center; margin: 20px;">
        <label for="year_select">Select Financial Year:</label>
        <select id="year_select">
            <?php
            $current_month = date('n'); // 1-12
            $current_year = date('Y');

            // If we're in July or later, the financial year has rolled over
            $financial_year = ($current_month >= 7) ? $current_year + 1 : $current_year;

            for ($i = $financial_year; $i >= $financial_year - 5; $i--) {
                $start = $i - 1;
                $end = $i;
                echo "<option value='{$end}'" . ($end == $financial_year ? " selected" : "") . ">{$start} - {$end}</option>";
            }
            ?>
        </select>
        <form method="POST" action="generate_yearly_financial_report.php" style="text-align:center; margin-bottom: 20px;">
            <input type="hidden" name="year" id="year_hidden">
            <button type="submit">Export to Excel</button>
        </form>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Month</th>
                <th>Amount Collected (Rs.)</th>
            </tr>
        </thead>
        <tbody id="report_data">
            <!-- Loaded via AJAX -->
        </tbody>
    </table>
</div>

<script>
$(function() {
    function loadReport(year) {
        $.ajax({
            url: 'get_yearly_financial_report.php',
            method: 'POST',
            data: { fetch_data: true, year: year },
            success: function(response) {
                $('#report_data').html(response);
                $('#year-label').text(`FOR THE FINANCIAL YEAR (${year - 1} - ${year})`);
            },
            error: function() {
                alert("Error loading report data.");
            }
        });
    }

    const initialYear = $('#year_select').val();
    loadReport(initialYear);

    $('#year_select').on('change', function() {
        const selectedYear = $(this).val();
        loadReport(selectedYear);
    });
});
</script>



<?php include('footer.php'); ?>

