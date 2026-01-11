<?php
/**
 * Waiting List Controller
 * Handle adding/removing books to/from waiting list
 */

// Load secure session configuration
require_once __DIR__ . '/../config/session_config.php';
session_start();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User ID not found']);
    exit;
}

// Load models
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/BookLending.php';
require_once __DIR__ . '/../models/Book.php';

$database = new Database();
$conn = $database->getConnection();
$bookLending = new BookLending($conn);
$book = new Book($conn);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Add to waiting list
        $bookId = $_POST['book_id'] ?? null;
        
        if (!$bookId) {
            echo json_encode(['success' => false, 'message' => 'Book ID is required']);
            exit;
        }
        
        // Check if book exists
        $bookData = $book->getBookById($bookId);
        if (!$bookData) {
            echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan']);
            exit;
        }
        
        // Check if book is available (shouldn't add to waiting list if available)
        if ($bookData['book_status'] === true || $bookData['book_status'] === 't') {
            echo json_encode(['success' => false, 'message' => 'Buku tersedia, silakan pinjam langsung']);
            exit;
        }
        
        // Check if already in waiting list
        if ($bookLending->checkUserInWaitingList($userId, $bookId)) {
            $position = $bookLending->getWaitingPosition($userId, $bookId);
            echo json_encode([
                'success' => false, 
                'message' => 'Anda sudah ada di waiting list (posisi #' . $position . ')'
            ]);
            exit;
        }
        
        // Add to waiting list
        $waitingId = $bookLending->addToWaitingList($userId, $bookId);
        
        if ($waitingId) {
            $position = $bookLending->getWaitingPosition($userId, $bookId);
            echo json_encode([
                'success' => true,
                'message' => 'Berhasil masuk waiting list!',
                'waiting_id' => $waitingId,
                'position' => $position,
                'book_title' => $bookData['book_title']
            ]);
        } else {
            // Check if error was due to already being in waiting list
            if ($bookLending->checkUserInWaitingList($userId, $bookId)) {
                $position = $bookLending->getWaitingPosition($userId, $bookId);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Anda sudah ada di waiting list (posisi #' . $position . ')'
                ]);
            } else {
                error_log("WaitingList failed for user $userId, book $bookId");
                echo json_encode(['success' => false, 'message' => 'Gagal menambahkan ke waiting list. Silakan coba lagi.']);
            }
        }
        
    } elseif ($method === 'DELETE') {
        // Remove from waiting list
        parse_str(file_get_contents('php://input'), $data);
        $bookId = $data['book_id'] ?? null;
        
        if (!$bookId) {
            echo json_encode(['success' => false, 'message' => 'Book ID is required']);
            exit;
        }
        
        $result = $bookLending->removeFromWaitingList($userId, $bookId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Berhasil keluar dari waiting list']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus dari waiting list']);
        }
        
    } elseif ($method === 'GET') {
        // Check waiting list status for a book
        $bookId = $_GET['book_id'] ?? null;
        
        if (!$bookId) {
            echo json_encode(['success' => false, 'message' => 'Book ID is required']);
            exit;
        }
        
        $inWaitingList = $bookLending->checkUserInWaitingList($userId, $bookId);
        $position = $inWaitingList ? $bookLending->getWaitingPosition($userId, $bookId) : 0;
        $totalQueue = $bookLending->getWaitingCount($bookId);
        
        echo json_encode([
            'success' => true,
            'in_waiting_list' => $inWaitingList,
            'position' => $position,
            'total_queue' => $totalQueue
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("WaitingListController error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
