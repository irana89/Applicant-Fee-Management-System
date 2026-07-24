<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['scb_id'];
    $received_date = $_POST['received_date'];
    $from_serial = $_POST['from_serial'];
    $to_serial = $_POST['to_serial'];
    $total_pages = $_POST['total_pages'];

    $sql = "UPDATE security_paper_bundle 
            SET received_date = ?, from_serial = ?, to_serial = ?, total_pages = ?
            WHERE scb_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $received_date, $from_serial, $to_serial, $total_pages, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
}
?>