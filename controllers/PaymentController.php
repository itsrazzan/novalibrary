<?php
/**
 * Payment Controller
 * Handles penalty payment
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../config/database.php';

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID tidak ditemukan']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get penalty_id from POST
$penaltyId = isset($_POST['penalty_id']) ? (int)$_POST['penalty_id'] : 0;

if ($penaltyId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Penalty ID tidak valid']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Verify penalty belongs to user and not yet paid
    $stmt = $conn->prepare("
        SELECT penalty_id, id, large_fines, paid 
        FROM penalty 
        WHERE penalty_id = :penalty_id AND id = :user_id
    ");
    $stmt->execute([':penalty_id' => $penaltyId, ':user_id' => $userId]);
    $penalty = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$penalty) {
        echo json_encode(['success' => false, 'message' => 'Denda tidak ditemukan']);
        exit;
    }
    
    if ($penalty['paid']) {
        echo json_encode(['success' => false, 'message' => 'Denda sudah dibayar sebelumnya']);
        exit;
    }
    
    // Mark as paid
    $stmt = $conn->prepare("
        UPDATE penalty 
        SET paid = true, paid_date = CURRENT_DATE 
        WHERE penalty_id = :penalty_id
    ");
    $stmt->execute([':penalty_id' => $penaltyId]);
    
    // Refresh MVs
    try {
        $conn->exec("REFRESH MATERIALIZED VIEW mv_total_denda");
        $conn->exec("REFRESH MATERIALIZED VIEW mv_rekap_denda_member");
        $conn->exec("REFRESH MATERIALIZED VIEW mv_rekap_denda");
    } catch (Exception $e) {
        error_log("MV refresh error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Denda berhasil dibayar!',
        'amount_paid' => $penalty['large_fines'],
        'amount_formatted' => 'Rp ' . number_format($penalty['large_fines'], 0, ',', '.')
    ]);
    
} catch (Exception $e) {
    error_log("PaymentController Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
