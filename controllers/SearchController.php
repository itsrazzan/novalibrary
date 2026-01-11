<?php
/**
 * Book Search Controller
 * Server-side search using B-tree indexed columns
 * Supports: search mode (q=query) and all books mode (all=true)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Book.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;
$getAllBooks = isset($_GET['all']) && $_GET['all'] === 'true';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : null;

// If getting all books, skip query validation
if (!$getAllBooks && strlen($query) < 2) {
    echo json_encode([
        'success' => false,
        'message' => 'Query minimal 2 karakter',
        'data' => []
    ]);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $book = new Book($conn);
    
    // Get books based on mode
    if ($getAllBooks) {
        // Get all books (or by category if filter specified)
        if ($categoryFilter) {
            $result = $book->getBooksByCategory($categoryFilter);
        } else {
            $result = $book->getAllBooks();
        }
    } else {
        // Search mode
        $result = $book->searchBooks($query, $limit);
    }
    
    if ($result) {
        $books = $result->fetchAll(PDO::FETCH_ASSOC);
        
        // Apply limit for all books mode
        if ($getAllBooks && count($books) > $limit) {
            $books = array_slice($books, 0, $limit);
        }
        
        // Fix image paths for display
        foreach ($books as &$b) {
            $b['image_url'] = !empty($b['image_path']) 
                ? '/NOVA-Library/' . $b['image_path']
                : '/NOVA-Library/public/img/books/default-book.jpg';
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($books),
            'data' => $books
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'count' => 0,
            'data' => []
        ]);
    }
} catch (Exception $e) {
    error_log("Search Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mencari',
        'data' => []
    ]);
}
?>
