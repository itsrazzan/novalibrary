<?php
/**
 * BookLending Model
 * Handles all database operations related to book lending/borrowing
 */
class BookLending {
    private $conn;
    private $table_name = "booklending";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all active borrowings (return_date is NULL)
     * @return PDOStatement|false
     */
    public function getActiveBorrowings() {
        $query = "SELECT 
                    bl.loan_id,
                    bl.book_id,
                    bl.loan_date,
                    bl.due_date,
                    bl.return_date,
                    b.book_title,
                    b.author,
                    b.image_path
                  FROM " . $this->table_name . " bl
                  INNER JOIN book b ON bl.book_id = b.book_id
                  WHERE bl.return_date IS NULL
                  ORDER BY bl.loan_date DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getActiveBorrowings(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all borrowings
     * @return PDOStatement|false
     */
    public function getAllBorrowings() {
        $query = "SELECT 
                    bl.loan_id,
                    bl.book_id,
                    bl.loan_date,
                    bl.due_date,
                    bl.return_date,
                    b.book_title,
                    b.author,
                    b.image_path,
                    CASE 
                        WHEN bl.return_date IS NULL AND bl.due_date < CURRENT_DATE THEN true 
                        ELSE false 
                    END as is_overdue
                  FROM " . $this->table_name . " bl
                  INNER JOIN book b ON bl.book_id = b.book_id
                  ORDER BY bl.loan_date DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getAllBorrowings(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get overdue borrowings (due_date < today AND return_date is NULL)
     * @return PDOStatement|false
     */
    public function getOverdueBorrowings() {
        $query = "SELECT 
                    bl.loan_id,
                    bl.book_id,
                    bl.loan_date,
                    bl.due_date,
                    bl.return_date,
                    b.book_title,
                    b.author,
                    b.image_path,
                    (CURRENT_DATE - bl.due_date) as days_overdue
                  FROM " . $this->table_name . " bl
                  INNER JOIN book b ON bl.book_id = b.book_id
                  WHERE bl.return_date IS NULL 
                    AND bl.due_date < CURRENT_DATE
                  ORDER BY bl.due_date ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getOverdueBorrowings(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count active borrowings
     * @return int
     */
    public function countActiveBorrowings() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " 
                  WHERE return_date IS NULL";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in countActiveBorrowings(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count overdue borrowings
     * @return int
     */
    public function countOverdueBorrowings() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " 
                  WHERE return_date IS NULL 
                    AND due_date < CURRENT_DATE";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in countOverdueBorrowings(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent borrowings for dashboard
     * @param int $limit
     * @return PDOStatement|false
     */
    public function getRecentBorrowings($limit = 5) {
        $query = "SELECT 
                    bl.loan_id,
                    bl.book_id,
                    bl.loan_date,
                    bl.due_date,
                    b.book_title,
                    b.author
                  FROM " . $this->table_name . " bl
                  INNER JOIN book b ON bl.book_id = b.book_id
                  WHERE bl.return_date IS NULL
                  ORDER BY bl.loan_date DESC
                  LIMIT :limit";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getRecentBorrowings(): " . $e->getMessage());
            return false;
        }
    }
}
?>
