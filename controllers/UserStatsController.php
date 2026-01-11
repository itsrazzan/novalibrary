<?php
/**
 * User Stats Controller
 * Returns user dashboard statistics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../config/database.php';

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID not found']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Count active borrowings (not returned)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM booklending WHERE id = :user_id AND return_date IS NULL");
    $stmt->execute([':user_id' => $userId]);
    $borrowed = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count waiting list
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM waiting_list WHERE id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $waiting = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count history (all borrowings)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM booklending WHERE id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $history = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'borrowed' => (int)$borrowed,
        'waiting' => (int)$waiting,
        'history' => (int)$history
    ]);
    
} catch (Exception $e) {
    error_log("UserStatsController Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading stats'
    ]);
}
?>
