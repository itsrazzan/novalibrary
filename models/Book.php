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
     * Search books by title or author
     * @param string $keyword
     * @return PDOStatement|false
     */
    public function searchBooks($keyword) {
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
                  WHERE b.book_title ILIKE :keyword 
                    OR b.author ILIKE :keyword
                  ORDER BY b.book_title ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $search_term = '%' . $keyword . '%';
            $stmt->bindParam(':keyword', $search_term);
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
}
?>
