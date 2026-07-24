<?php
include('db.php');
include('header.php');

$singlePaperFee = 300; // Fee per single security paper
$currentMonth = date('Y-m'); // Format: YYYY-MM

// Fetch applicants and their security paper count for the current month
$monthFilter = isset($_GET['month']) ? $_GET['month'] : $currentMonth;

$sql =      "SELECT c_id, chalan_no, amount, deposit_date, created_at, image_path FROM Challans WHERE month = ?  ORDER BY month DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $monthFilter);
$stmt->execute();
$result = $stmt->get_result();

$challans = [];
while ($row = $result->fetch_assoc()) {
    $challans[] = $row;
}
?>

<title>Challan Paid - <?php echo date('F Y', strtotime($monthFilter)); ?></title>
<style>

    .challan-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-start;
    }
    .challan-card {
        width: 150px;
        cursor: pointer;
        text-align: center;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 5px;
        background: #f9f9f9;
    }
    .challan-card img {
        width: 100%;
        height: auto;
        border-radius: 5px;
    }
    .popup-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }
    .popup-content {
        background: white;
        padding: 20px;
        border-radius: 5px;
        text-align: center;
        max-width: 90%;
        max-height: 90vh;
        overflow: auto;
        position: relative;
    }
    .popup-close {
        cursor: pointer;
        font-size: 20px;
        font-weight: bold;
        position: absolute;
        top: 10px;
        right: 10px;
    }
    .carousel {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .carousel img {
        max-width: 100%;
        max-height: 70vh;
        height: auto;
    }
    .carousel-controls {
        display: flex;
        justify-content: space-between;
        position: absolute;
        top: 50%;
        width: 100%;
        transform: translateY(-50%);
    }
    .carousel-controls button {
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        padding: 10px;
        cursor: pointer;
    }
	.form-wrapper{
		padding: 0 0 10px 0px;
	}
    .challanfont{
        font-size:12px;
    }
</style>

<script>
    $(document).ready(function() {
        let currentIndex = 0;
        let images = [];
        let dates = [];
        let amounts = [];

        $('.challan-card').click(function() {
            images = [];
            dates = [];
            amounts = [];
            
            $('.challan-card').each(function() {
                images.push($(this).data('img'));
                dates.push($(this).data('date'));
                amounts.push($(this).data('amount'));
            });
            
            currentIndex = images.indexOf($(this).data('img'));
            updateCarousel();
            $('.popup-overlay').fadeIn();
        });

        $('.popup-close, .popup-overlay').click(function(event) {
            if (!$(event.target).closest('.popup-content').length || $(event.target).hasClass('popup-close')) {
                $('.popup-overlay').fadeOut();
            }
        });

        $('#prev').click(function() {
            if (currentIndex > 0) {
                currentIndex--;
                updateCarousel();
            }
        });

        $('#next').click(function() {
            if (currentIndex < images.length - 1) {
                currentIndex++;
                updateCarousel();
            }
        });

        function updateCarousel() {
            $('#popup-img').attr('src', images[currentIndex]);
            $('#popup-date').text(dates[currentIndex]);
            $('#popup-amount').text(amounts[currentIndex]);
        }

        $('#monthPicker').change(function() {
            var selectedMonth = $(this).val();
            window.location.href = '?month=' + selectedMonth;
        });
    });




    $(document).on('click', '#printChallan', function (e) {
    e.stopPropagation(); 
    var imgSrc = $('#popup-img').attr('src');
    var date = $('#popup-date').text();
    var amount = $('#popup-amount').text();

    var printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Challan</title>
            <style>
                body { text-align: center; font-family: Arial; }
                img { max-width: 100%; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <img src="${imgSrc}">
           
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
});


</script>
</head>
<body>
    <div class="container">
        <h2>Challan Paid Details - <?php echo date('F Y', strtotime($monthFilter)); ?></h2>
        
        <div class="form-wrapper">
            <form method="GET" action="">
                <label for="month">Select Month To View Record:</label>
                <input type="month" name="month" id="monthPicker" value="<?php echo $monthFilter; ?>">
                <button type="submit">View</button>
            </form>
        </div>

        <div class="challan-container">
            <?php if (!empty($challans)): ?>
                <?php foreach ($challans as $challan): ?>
                    <div class="challan-card" data-img="<?php echo $challan['image_path']; ?>" data-date="<?php echo date('d-F-Y', strtotime($challan['deposit_date'])); ?>" data-amount="<?php echo $challan['amount']; ?>">
                        <img src="<?php echo $challan['image_path']; ?>" alt="Challan Image">
                        <div class="challan-date challanfont">Deposit Date:   <?php echo date('d-m-Y', strtotime($challan['deposit_date'])); ?></div>
                        <div class="challan-amount challanfont">Rs. <?php echo $challan['amount']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No challans found.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="popup-overlay">
        <div class="popup-content">
            <span class="popup-close">&times;</span>
            <div class="carousel">
                <button id="prev">&lt;</button>
                <img id="popup-img" src="" alt="Challan Image">
                <button id="next">&gt;</button>
            </div>
            <button id="printChallan" style="margin-top:10px;">Print</button>
            <p><strong>Date:</strong> <span id="popup-date"></span></p>
            <p><strong>Amount:</strong> Rs. <span id="popup-amount"></span></p>
        </div>
    </div>
<?php
include('footer.php');
?>
