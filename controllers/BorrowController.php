<?php
/**
 * Borrow Controller
 * Handles book borrowing requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/BookLending.php';

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

// Get book_id from POST
$bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

if ($bookId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Book ID tidak valid']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Check if book is available
    $stmt = $conn->prepare("SELECT book_id, book_title, book_status FROM book WHERE book_id = :book_id");
    $stmt->execute([':book_id' => $bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan']);
        exit;
    }
    
    if (!$book['book_status']) {
        echo json_encode(['success' => false, 'message' => 'Buku sedang dipinjam orang lain']);
        exit;
    }
    
    // Check if user already has active loan for this book
    $stmt = $conn->prepare("SELECT loan_id FROM booklending WHERE id = :user_id AND book_id = :book_id AND return_date IS NULL");
    $stmt->execute([':user_id' => $userId, ':book_id' => $bookId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah meminjam buku ini']);
        exit;
    }
    
    // Create loan
    $bookLending = new BookLending($conn);
    $result = $bookLending->createLoan($userId, $bookId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Buku berhasil dipinjam!',
            'book_title' => $book['book_title'],
            'due_date' => date('d F Y', strtotime('+14 days'))
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal meminjam buku']);
    }
    
} catch (Exception $e) {
    error_log("BorrowController Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
