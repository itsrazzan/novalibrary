<?php
/**
 * Return Controller
 * Handles book return requests and penalty calculation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/BookLending.php';
require_once __DIR__ . '/../config/constants.php';

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

// Get loan_id from POST
$loanId = isset($_POST['loan_id']) ? (int)$_POST['loan_id'] : 0;

if ($loanId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Loan ID tidak valid']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Verify loan belongs to user and not yet returned
    $stmt = $conn->prepare("
        SELECT bl.loan_id, bl.book_id, bl.id, bl.loan_date, bl.due_date, bl.return_date,
               b.book_title
        FROM booklending bl
        JOIN book b ON bl.book_id = b.book_id
        WHERE bl.loan_id = :loan_id AND bl.id = :user_id
    ");
    $stmt->execute([':loan_id' => $loanId, ':user_id' => $userId]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        echo json_encode(['success' => false, 'message' => 'Peminjaman tidak ditemukan']);
        exit;
    }
    
    if ($loan['return_date'] !== null) {
        echo json_encode(['success' => false, 'message' => 'Buku sudah dikembalikan sebelumnya']);
        exit;
    }
    
    // Calculate penalty if overdue
    $penaltyAmount = 0;
    $daysLate = 0;
    $today = new DateTime();
    $dueDate = new DateTime($loan['due_date']);
    
    if ($today > $dueDate) {
        $daysLate = $today->diff($dueDate)->days;
        $penaltyAmount = $daysLate * PENALTY_BASE_RATE;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // 1. Update booklending - set return_date
        $stmt = $conn->prepare("UPDATE booklending SET return_date = CURRENT_DATE WHERE loan_id = :loan_id");
        $stmt->execute([':loan_id' => $loanId]);
        
        // 2. Update book status to available
        $stmt = $conn->prepare("UPDATE book SET book_status = true WHERE book_id = :book_id");
        $stmt->execute([':book_id' => $loan['book_id']]);
        
        // 3. Insert into bookreturn
        $stmt = $conn->prepare("
            INSERT INTO bookreturn (return_id, loan_id, return_date) 
            VALUES ((SELECT COALESCE(MAX(return_id), 0) + 1 FROM bookreturn), :loan_id, CURRENT_DATE)
        ");
        $stmt->execute([':loan_id' => $loanId]);
        
        // 4. Insert penalty if late
        if ($penaltyAmount > 0) {
            $stmt = $conn->prepare("
                INSERT INTO penalty (penalty_id, id, large_fines, paid, paid_date) 
                VALUES ((SELECT COALESCE(MAX(penalty_id), 0) + 1 FROM penalty), :user_id, :amount, false, NULL)
            ");
            $stmt->execute([':user_id' => $userId, ':amount' => $penaltyAmount]);
        }
        
        $conn->commit();
        
        // Refresh MVs
        try {
            $conn->exec("REFRESH MATERIALIZED VIEW mv_statistik_member");
            if ($penaltyAmount > 0) {
                $conn->exec("REFRESH MATERIALIZED VIEW mv_total_denda");
                $conn->exec("REFRESH MATERIALIZED VIEW mv_rekap_denda_member");
                $conn->exec("REFRESH MATERIALIZED VIEW mv_rekap_denda");
            }
        } catch (Exception $e) {
            error_log("MV refresh error: " . $e->getMessage());
        }
        
        $response = [
            'success' => true,
            'message' => 'Buku berhasil dikembalikan!',
            'book_title' => $loan['book_title']
        ];
        
        if ($penaltyAmount > 0) {
            $response['has_penalty'] = true;
            $response['days_late'] = $daysLate;
            $response['penalty_amount'] = $penaltyAmount;
            $response['penalty_formatted'] = 'Rp ' . number_format($penaltyAmount, 0, ',', '.');
            $response['message'] = "Buku dikembalikan. Anda terlambat {$daysLate} hari. Denda: Rp " . number_format($penaltyAmount, 0, ',', '.');
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("ReturnController Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
