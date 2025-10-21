<?php
require_once "config.php";

header("Content-Type: application/json; charset=UTF-8");

$response = ['success' => false, 'licenses' => []];

$customer_id = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);

if ($customer_id) {
    $sql = "SELECT id, license_key, license_type FROM licenses WHERE customer_id = ? ORDER BY created_at DESC";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $customer_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $licenses = [];
            
            while ($row = $result->fetch_assoc()) {
                $licenses[] = $row;
            }
            
            $response['success'] = true;
            $response['licenses'] = $licenses;
        }
        
        $stmt->close();
    }
}

$mysqli->close();

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
