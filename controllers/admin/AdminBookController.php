<?php
/**
 * Admin Book Controller
 * Handles CRUD operations for books via AJAX
 */

header('Content-Type: application/json');

// Allow CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Book.php';

$database = new Database();
$db = $database->getConnection();
$book = new Book($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'get') {
                // Get single book
                $book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if ($book_id > 0) {
                    $result = $book->getBookById($book_id);
                    if ($result) {
                        echo json_encode(['success' => true, 'data' => $result]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Book not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
                }
            } elseif ($action === 'categories') {
                // Get all categories
                $stmt = $book->getAllCategories();
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $categories]);
            } else {
                // Get all books
                $stmt = $book->getAllBooks();
                if ($stmt) {
                    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    // Fix image paths
                    foreach ($books as &$b) {
                        $b['image_path'] = $book->getImagePath($b['image_path']);
                    }
                    echo json_encode(['success' => true, 'data' => $books]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to fetch books']);
                }
            }
            break;

        case 'POST':
            if ($action === 'create') {
                // Create new book
                $data = [
                    'category_id' => isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0,
                    'book_title' => isset($_POST['book_title']) ? trim($_POST['book_title']) : '',
                    'author' => isset($_POST['author']) ? trim($_POST['author']) : '',
                    'publisher' => isset($_POST['publisher']) ? trim($_POST['publisher']) : '',
                    'published_year' => isset($_POST['published_year']) ? $_POST['published_year'] . '-01-01' : null,
                    'description' => isset($_POST['description']) ? trim($_POST['description']) : '',
                    'image_path' => $book->getDefaultImage()
                ];

                // Handle file upload
                if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $uploaded_path = $book->uploadCover($_FILES['cover']);
                    if ($uploaded_path) {
                        $data['image_path'] = $uploaded_path;
                    }
                }

                // Validate required fields
                if (empty($data['book_title']) || empty($data['author']) || $data['category_id'] === 0) {
                    echo json_encode(['success' => false, 'message' => 'Title, author, and category are required']);
                    exit;
                }

                $book_id = $book->createBook($data);
                if ($book_id) {
                    echo json_encode(['success' => true, 'message' => 'Book created successfully', 'book_id' => $book_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create book']);
                }
            } elseif ($action === 'update') {
                // Update book
                $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
                
                if ($book_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
                    exit;
                }

                // Get existing book data
                $existing = $book->getBookById($book_id);
                if (!$existing) {
                    echo json_encode(['success' => false, 'message' => 'Book not found']);
                    exit;
                }

                $data = [
                    'category_id' => isset($_POST['category_id']) ? (int)$_POST['category_id'] : $existing['category_id'],
                    'book_title' => isset($_POST['book_title']) ? trim($_POST['book_title']) : $existing['book_title'],
                    'author' => isset($_POST['author']) ? trim($_POST['author']) : $existing['author'],
                    'publisher' => isset($_POST['publisher']) ? trim($_POST['publisher']) : $existing['publisher'],
                    'published_year' => isset($_POST['published_year']) ? $_POST['published_year'] . '-01-01' : $existing['published_year'],
                    'description' => isset($_POST['description']) ? trim($_POST['description']) : $existing['description'],
                    'image_path' => $existing['image_path']
                ];

                // Handle file upload if new file provided
                if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $uploaded_path = $book->uploadCover($_FILES['cover']);
                    if ($uploaded_path) {
                        $data['image_path'] = $uploaded_path;
                    }
                }

                if ($book->updateBook($book_id, $data)) {
                    echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update book']);
                }
            } elseif ($action === 'delete') {
                // Delete book
                $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
                
                if ($book_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
                    exit;
                }

                if ($book->deleteBook($book_id)) {
                    echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete book']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("AdminBookController Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
