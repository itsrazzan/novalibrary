<?php
/**
 * Test script untuk Book model
 * Menguji semua fungsi di Book.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/models/Book.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed!\n");
}

// Create Book instance
$book = new Book($db);

echo "=== TESTING BOOK MODEL ===\n\n";

// Test 1: Count total books
echo "1. Count Total Books:\n";
$total = $book->countBooks();
echo "   Total books: $total\n\n";

// Test 2: Count available books
echo "2. Count Available Books:\n";
$available = $book->countAvailableBooks();
echo "   Available books: $available\n\n";

// Test 3: Get all books
echo "3. Get All Books (first 5):\n";
$result = $book->getAllBooks();
if ($result) {
    $count = 0;
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   - [{$row['book_id']}] {$row['book_title']} by {$row['author']}\n";
        echo "     Category: {$row['category_name']}\n";
        echo "     Image: {$row['image_path']}\n";
        $count++;
        if ($count >= 5) break;
    }
} else {
    echo "   Error fetching books\n";
}
echo "\n";

// Test 4: Get book by ID
echo "4. Get Book by ID (ID=1):\n";
$single_book = $book->getBookById(1);
if ($single_book) {
    echo "   Title: {$single_book['book_title']}\n";
    echo "   Author: {$single_book['author']}\n";
    echo "   Publisher: {$single_book['publisher']}\n";
    echo "   Category: {$single_book['category_name']}\n";
    echo "   Image Path: {$single_book['image_path']}\n";
    echo "   Status: " . ($single_book['book_status'] ? 'Available' : 'Borrowed') . "\n";
} else {
    echo "   Book not found\n";
}
echo "\n";

// Test 5: Get books by category (Category 1 = Novel Fiksi)
echo "5. Get Books by Category (Category 1 - first 3):\n";
$result = $book->getBooksByCategory(1);
if ($result) {
    $count = 0;
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   - {$row['book_title']} by {$row['author']}\n";
        $count++;
        if ($count >= 3) break;
    }
} else {
    echo "   Error fetching books by category\n";
}
echo "\n";

// Test 6: Search books
echo "6. Search Books (keyword: 'Tere Liye' - first 3):\n";
$result = $book->searchBooks('Tere Liye');
if ($result) {
    $count = 0;
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   - {$row['book_title']} by {$row['author']}\n";
        $count++;
        if ($count >= 3) break;
    }
} else {
    echo "   Error searching books\n";
}
echo "\n";

// Test 7: Get all categories
echo "7. Get All Categories:\n";
$result = $book->getAllCategories();
if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   - [{$row['category_id']}] {$row['category_name']}\n";
    }
} else {
    echo "   Error fetching categories\n";
}
echo "\n";

// Test 8: Test image path helper
echo "8. Test Image Path Helper:\n";
$test_paths = [
    '/images/books/test.jpg',
    'public/img/books/test.jpg',
    null,
    ''
];
foreach ($test_paths as $path) {
    $result = $book->getImagePath($path);
    echo "   Input: " . ($path ?: 'null/empty') . "\n";
    echo "   Output: $result\n\n";
}

echo "=== ALL TESTS COMPLETED ===\n";
?>
