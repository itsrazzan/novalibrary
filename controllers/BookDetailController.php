<?php
/**
 * Book Detail Controller
 * Get detailed information about a book
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

// Only GET method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$bookId = $_GET['book_id'] ?? null;

if (!$bookId) {
    echo json_encode(['success' => false, 'message' => 'Book ID is required']);
    exit;
}

// Load models
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/BookLending.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $bookModel = new Book($conn);
    $bookLending = new BookLending($conn);
    
    // Get book details
    $bookData = $bookModel->getBookById($bookId);
    
    if (!$bookData) {
        echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan']);
        exit;
    }
    
    // Get waiting list info
    $inWaitingList = false;
    $waitingPosition = 0;
    $totalQueue = 0;
    
    if ($userId) {
        $inWaitingList = $bookLending->checkUserInWaitingList($userId, $bookId);
        $waitingPosition = $inWaitingList ? $bookLending->getWaitingPosition($userId, $bookId) : 0;
        $totalQueue = $bookLending->getWaitingCount($bookId);
    }
    
    // Format response
    $isAvailable = $bookData['book_status'] === true || $bookData['book_status'] === 't' || $bookData['book_status'] === 1;
    
    // Fix image path
    $imagePath = $bookData['image_path'] ?? 'public/img/books/default-book.jpg';
    if (!str_starts_with($imagePath, 'public/') && !str_starts_with($imagePath, '/')) {
        $imagePath = 'public/img/books/' . $imagePath;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'book_id' => $bookData['book_id'],
            'book_title' => $bookData['book_title'],
            'author' => $bookData['author'] ?? 'Unknown',
            'publisher' => $bookData['publisher'] ?? '-',
            'published_year' => $bookData['published_year'] ?? '-',
            'category_name' => $bookData['category_name'] ?? 'Umum',
            'category_explanation' => $bookData['category_explanation'] ?? '',
            'sinopsis' => $bookData['sinopsis'] ?? 'Tidak ada sinopsis.',
            'image_path' => $imagePath,
            'is_available' => $isAvailable,
            'status_text' => $isAvailable ? 'Tersedia' : 'Dipinjam',
            'in_waiting_list' => $inWaitingList,
            'waiting_position' => $waitingPosition,
            'total_queue' => $totalQueue
        ]
    ]);
    
} catch (Exception $e) {
    error_log("BookDetailController error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
