<?php
include('db.php');

if (isset($_POST['month'])) {
    $selectedMonth = $_POST['month'];

    $sql = "SELECT c_id, chalan_no, amount, created_at, image_path FROM challans 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = '$selectedMonth' 
            ORDER BY created_at DESC";
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $formattedDate = date("d-F-Y", strtotime($row['created_at'])); // Format date
            echo "<div class='challan-card' data-id='{$row['c_id']}' data-img='{$row['image_path']}' data-date='{$row['created_at']}' data-amount='{$row['amount']}'>
                    <img src='{$row['image_path']}' alt='Challan Image'>
                    <div class='challan-date'>{$formattedDate}</div>
                    <div class='challan-amount'>Rs. {$row['amount']}</div>
                </div>";
        }
    } else {
        echo "<p>No challans found for this month.</p>";
    }
}
?>
