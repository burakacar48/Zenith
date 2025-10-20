<?php
/**
 * Admin Authentication API for Server Panel
 * 
 * This API provides secure authentication for server admin panel
 * by verifying customer credentials from the main panel database
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "config.php";

$response = ['success' => false, 'message' => 'Bilinmeyen bir hata oluştu.'];

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $customer_id = trim((string)($input['customer_id'] ?? ''));
    $password = trim((string)($input['password'] ?? ''));
    
    if (empty($customer_id)) {
        $response['message'] = 'Müşteri ID gereklidir.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($password)) {
        $response['message'] = 'Şifre gereklidir.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Query database for customer
    $sql = "SELECT id, customer_id, customer_password, name, company FROM customers WHERE customer_id = ?";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $customer_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $customer = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $customer['customer_password'])) {
                    // Generate session token (simple implementation)
                    $session_token = bin2hex(random_bytes(32));
                    
                    // Store session in database (create sessions table if needed)
                    $session_sql = "INSERT INTO admin_sessions (customer_id, session_token, created_at, expires_at) 
                                   VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 8 HOUR))
                                   ON DUPLICATE KEY UPDATE 
                                   session_token = VALUES(session_token), 
                                   created_at = VALUES(created_at), 
                                   expires_at = VALUES(expires_at)";
                    
                    if ($session_stmt = $mysqli->prepare($session_sql)) {
                        $session_stmt->bind_param("is", $customer['id'], $session_token);
                        $session_stmt->execute();
                        $session_stmt->close();
                    }
                    
                    $response = [
                        'success' => true,
                        'message' => 'Giriş başarılı',
                        'data' => [
                            'customer_id' => $customer['customer_id'],
                            'name' => $customer['name'],
                            'company' => $customer['company'],
                            'session_token' => $session_token
                        ]
                    ];
                } else {
                    $response['message'] = 'Geçersiz müşteri ID veya şifre.';
                }
            } else {
                $response['message'] = 'Geçersiz müşteri ID veya şifre.';
            }
        } else {
            $response['message'] = 'Veritabanı sorgu hatası.';
        }
        
        $stmt->close();
    } else {
        $response['message'] = 'Veritabanı bağlantı hatası.';
    }
} else {
    $response['message'] = 'Geçersiz istek metodu. Sadece POST desteklenir.';
}

$mysqli->close();

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
