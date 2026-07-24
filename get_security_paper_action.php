<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO security_paper_bundle (received_date, from_serial, to_serial, total_pages) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $_POST['received_date'], $_POST['from_serial'], $_POST['to_serial'], $_POST['total_pages']);
        $stmt->execute();
        echo 'success';

    } elseif ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE security_paper_bundle SET received_date=?, from_serial=?, to_serial=?, total_pages=? WHERE scb_id=?");
        $stmt->bind_param("sssii", $_POST['received_date'], $_POST['from_serial'], $_POST['to_serial'], $_POST['total_pages'], $_POST['scb_id']);
        $stmt->execute();
        echo 'success';

    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM security_paper_bundle WHERE scb_id=?");
        $stmt->bind_param("i", $_POST['scb_id']);
        $stmt->execute();
        echo 'success';
    }
}
?>
