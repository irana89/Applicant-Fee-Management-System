<style>
<link href="https://fonts.googleapis.com/css2?family=Special+Elite&display=swap" rel="stylesheet">
</style>
<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $chalan_no = $_POST['chalan_no'];
    $amount = $_POST['amount'];
    $deposit_date = $_POST['deposit_date'];
    $challan_month = date('Y-m', strtotime($_POST['challan_month']));  // Example: 2025-10
    $uploadDir = 'uploads/'; // Base directory for uploads

    // Get current month and year
    $monthYear = date('M_Y',strtotime($challan_month));
    $folderPath = $uploadDir . $monthYear . '/';

    // Create folder if it doesn't exist
    if (!file_exists($folderPath)) {
        mkdir($folderPath, 0777, true);
    }

    $uploadedFiles = [];

    foreach ($_FILES['challan_image']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['challan_image']['error'][$key] === UPLOAD_ERR_OK) {
            $extension = pathinfo($_FILES['challan_image']['name'][$key], PATHINFO_EXTENSION);

            // Ensure unique numbering
            $existingFiles = glob($folderPath . "*.$extension");
            $fileNumber = count($existingFiles) + 1;
            $fileName = $monthYear . "_" . $fileNumber . "." . $extension;
            $filePath = $folderPath . $fileName;

            // Move file to the target directory
            if (move_uploaded_file($tmpName, $filePath)) {
                $uploadedFiles[] = $fileName;

                // Save entry in the database
                $stmt = $conn->prepare("INSERT INTO Challans (chalan_no, month, amount, deposit_date, image_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssss", $chalan_no, $challan_month, $amount,$deposit_date, $filePath);
                $stmt->execute();
            }
        }
    }

    if (!empty($uploadedFiles)) {
        echo "Challan(s) uploaded successfully!";
    } else {
        echo "Upload failed.";
    }
}
?>
