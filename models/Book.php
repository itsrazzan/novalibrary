<?php
/**
 * Book Model
 * Handles all database operations related to books
 */
class Book {
    private $conn;
    private $table_name = "book";
    private $category_table = "bookcategory";

    // Default image path jika gambar tidak ada
    private $default_image = "public/img/books/default-book.jpg";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all books with category information
     * @return PDOStatement
     */
    public function getAllBooks() {
        $query = "SELECT 
                    b.book_id,
                    b.book_title,
                    b.author,
                    b.publisher,
                    b.published_year,
                    b.image_path,
                    b.book_status,
                    b.sinopsis,
                    c.category_id,
                    c.category_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN " . $this->category_table . " c 
                    ON b.category_id = c.category_id
                  ORDER BY b.book_id ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getAllBooks(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get single book by ID
     * @param int $book_id
     * @return array|false
     */
    public function getBookById($book_id) {
        $query = "SELECT 
                    b.book_id,
                    b.book_title,
                    b.author,
                    b.publisher,
                    b.published_year,
                    b.image_path,
                    b.book_status,
                    b.sinopsis,
                    c.category_id,
                    c.category_name,
                    c.explanation as category_explanation
                  FROM " . $this->table_name . " b
                  LEFT JOIN " . $this->category_table . " c 
                    ON b.category_id = c.category_id
                  WHERE b.book_id = :book_id
                  LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':book_id', $book_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getBookById(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new book
     * @param array $data
     * @return int|false Returns book_id or false
     */
    public function createBook($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (book_id, category_id, book_title, author, publisher, published_year, image_path, sinopsis, book_status)
                  VALUES ((SELECT COALESCE(MAX(book_id), 0) + 1 FROM book), :category_id, :book_title, :author, :publisher, :published_year, :image_path, :sinopsis, true)
                  RETURNING book_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
            $stmt->bindParam(':book_title', $data['book_title']);
            $stmt->bindParam(':author', $data['author']);
            
            // Handle nullable fields
            $publisher = !empty($data['publisher']) ? $data['publisher'] : null;
            $published_year = !empty($data['published_year']) ? $data['published_year'] : null;
            $sinopsis = !empty($data['sinopsis']) ? $data['sinopsis'] : null;
            
            $stmt->bindParam(':publisher', $publisher);
            $stmt->bindParam(':published_year', $published_year);
            $stmt->bindParam(':image_path', $data['image_path']);
            $stmt->bindParam(':sinopsis', $sinopsis);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['book_id'] : false;
        } catch (PDOException $e) {
            // Store error for debugging
            $this->lastError = $e->getMessage();
            error_log("Error in createBook(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get last error message
     * @return string|null
     */
    public function getLastError() {
        return isset($this->lastError) ? $this->lastError : null;
    }

    /**
     * Update a book
     * @param int $book_id
     * @param array $data
     * @return bool
     */
    public function updateBook($book_id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET category_id = :category_id,
                      book_title = :book_title,
                      author = :author,
                      publisher = :publisher,
                      published_year = :published_year,
                      image_path = :image_path,
                      sinopsis = :sinopsis
                  WHERE book_id = :book_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':book_id', $book_id, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
            $stmt->bindParam(':book_title', $data['book_title']);
            $stmt->bindParam(':author', $data['author']);
            $stmt->bindParam(':publisher', $data['publisher']);
            $stmt->bindParam(':published_year', $data['published_year']);
            $stmt->bindParam(':image_path', $data['image_path']);
            $stmt->bindParam(':sinopsis', $data['sinopsis']);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in updateBook(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a book
     * @param int $book_id
     * @return bool
     */
    public function deleteBook($book_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE book_id = :book_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':book_id', $book_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in deleteBook(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload book cover image
     * @param array $file $_FILES array
     * @return string|false Returns file path or false
     */
    public function uploadCover($file) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $this->lastError = 'Invalid file type. Allowed: JPG, PNG, WEBP';
            error_log("Upload error: Invalid file type - " . $file['type']);
            return false;
        }
        
        if ($file['size'] > $max_size) {
            $this->lastError = 'File too large. Max 5MB';
            error_log("Upload error: File too large - " . $file['size']);
            return false;
        }
        
        // Get project root - resolve symlinks to get real path
        // __DIR__ is /path/to/NOVA-Library/models
        // We need /path/to/NOVA-Library
        $project_root = realpath(__DIR__ . '/..'); // models -> NOVA-Library
        $upload_dir = $project_root . '/public/img/books/';
        
        error_log("__DIR__: " . __DIR__);
        error_log("Project root (realpath): " . $project_root);
        error_log("Upload dir: " . $upload_dir);
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            $this->lastError = 'Upload directory is not writable: ' . $upload_dir;
            error_log("Upload dir not writable: " . $upload_dir);
            return false;
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'book_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        error_log("Saving to: " . $filepath);
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log("Upload successful: " . $filepath);
            return 'public/img/books/' . $filename;
        }
        
        $this->lastError = 'Failed to move uploaded file. Check permissions.';
        error_log("Upload failed: Could not move file to " . $filepath);
        return false;
    }

    /**
     * Get default image path
     * @return stringg
     */
    public function getDefaultImage() {
        return $this->default_image;
    }

    /**
     * Get books by category
     * @param int $category_id
     * @return PDOStatement|false
     */
    public function getBooksByCategory($category_id) {
        $query = "SELECT 
                    b.book_id,
                    b.book_title,
                    b.author,
                    b.publisher,
                    b.published_year,
                    b.image_path,
                    b.book_status,
                    c.category_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN " . $this->category_table . " c 
                    ON b.category_id = c.category_id
                  WHERE b.category_id = :category_id
                  ORDER BY b.book_title ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getBooksByCategory(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get only available books (book_status = true)
     * @return PDOStatement|false
     */
    public function getAvailableBooks() {
        $query = "SELECT 
                    b.book_id,
                    b.book_title,
                    b.author,
                    b.publisher,
                    b.published_year,
                    b.image_path,
                    b.book_status,
                    c.category_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN " . $this->category_table . " c 
                    ON b.category_id = c.category_id
                  WHERE b.book_status = true
                  ORDER BY b.book_title ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getAvailableBooks(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search books by title, author, or category
     * @param string $keyword
     * @param int $limit Max results (default 20)
     * @return PDOStatement|false
     */
    public function searchBooks($keyword, $limit = 20) {
        $query = "SELECT 
                    b.book_id,
                    b.book_title,
                    b.author,
                    b.publisher,
                    b.published_year,
                    b.image_path,
                    b.book_status,
                    b.sinopsis,
                    c.category_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN " . $this->category_table . " c 
                    ON b.category_id = c.category_id
                  WHERE b.book_title ILIKE :keyword 
                    OR b.author ILIKE :keyword
                    OR c.category_name ILIKE :keyword
                  ORDER BY b.book_title ASC
                  LIMIT :limit";
        
        try {
            $stmt = $this->conn->prepare($query);
            $search_term = '%' . $keyword . '%';
            $stmt->bindParam(':keyword', $search_term);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in searchBooks(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get book image path with default fallback
     * @param string|null $image_path
     * @return string
     */
    public function getImagePath($image_path) {
        // Jika image_path kosong atau null, return default
        if (empty($image_path)) {
            return $this->default_image;
        }
        
        // Jika path sudah benar (dimulai dengan public/), return as is
        if (strpos($image_path, 'public/') === 0) {
            return $image_path;
        }
        
        // Jika masih format lama (/images/books/), convert ke format baru
        if (strpos($image_path, '/images/books/') === 0) {
            return str_replace('/images/books/', 'public/img/books/', $image_path);
        }
        
        // Default fallback
        return $this->default_image;
    }

    /**
     * Get all categories
     * @return PDOStatement|false
     */
    public function getAllCategories() {
        $query = "SELECT 
                    category_id,
                    category_name,
                    explanation
                  FROM " . $this->category_table . "
                  ORDER BY category_name ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getAllCategories(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count total books
     * @return int
     */
    public function countBooks() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in countBooks(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count available books
     * @return int
     */
    public function countAvailableBooks() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " 
                  WHERE book_status = true";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in countAvailableBooks(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get popular books from view_buku_populer
     * @param int $limit Max results (default 5)
     * @return array
     */
    public function getPopularBooks($limit = 5) {
        $query = "SELECT 
                    v.book_title,
                    v.total_dipinjam,
                    b.book_id,
                    b.author,
                    b.image_path,
                    c.category_name
                  FROM view_buku_populer v
                  LEFT JOIN book b ON v.book_title = b.book_title
                  LEFT JOIN bookcategory c ON b.category_id = c.category_id
                  ORDER BY v.total_dipinjam DESC
                  LIMIT :limit";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPopularBooks(): " . $e->getMessage());
            return [];
        }
    }
}
?>
