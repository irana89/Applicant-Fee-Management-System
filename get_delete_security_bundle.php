<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    // First check if any papers from this bundle have been issued or wasted
    $check_sql = "SELECT 
                    (SELECT COUNT(*) FROM security_papers WHERE security_paper_serial_no BETWEEN 
                        (SELECT from_serial FROM security_paper_bundle WHERE scb_id = ?) AND 
                        (SELECT to_serial FROM security_paper_bundle WHERE scb_id = ?)) AS issued_count,
                    (SELECT COUNT(*) FROM wasted_security_paper WHERE security_paper_no BETWEEN 
                        (SELECT from_serial FROM security_paper_bundle WHERE scb_id = ?) AND 
                        (SELECT to_serial FROM security_paper_bundle WHERE scb_id = ?)) AS wasted_count";
    
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("iiii", $id, $id, $id, $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['issued_count'] > 0 || $result['wasted_count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete bundle - some papers have already been issued or wasted.']);
        exit;
    }
    
    // If no papers have been issued or wasted, proceed with deletion
    $delete_sql = "DELETE FROM security_paper_bundle WHERE scb_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
}
?>