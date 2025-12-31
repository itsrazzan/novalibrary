<?php
/**
 * BookReturn Model
 * Handles all database operations related to book returns
 */
class BookReturn {
    private $conn;
    private $table_name = "bookreturn";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all returns
     * @return PDOStatement|false
     */
    public function getAllReturns() {
        $query = "SELECT 
                    br.return_id,
                    br.loan_id,
                    br.return_date,
                    br.penalty_id,
                    bl.book_id,
                    bl.loan_date,
                    bl.due_date,
                    b.book_title,
                    b.author,
                    b.image_path,
                    CASE 
                        WHEN br.return_date > bl.due_date THEN true 
                        ELSE false 
                    END as was_overdue,
                    CASE 
                        WHEN br.return_date > bl.due_date 
                        THEN (br.return_date - bl.due_date) 
                        ELSE 0 
                    END as days_late
                  FROM " . $this->table_name . " br
                  INNER JOIN booklending bl ON br.loan_id = bl.loan_id
                  INNER JOIN book b ON bl.book_id = b.book_id
                  ORDER BY br.return_date DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getAllReturns(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get late returns (returned after due date)
     * @return PDOStatement|false
     */
    public function getLateReturns() {
        $query = "SELECT 
                    br.return_id,
                    br.loan_id,
                    br.return_date,
                    br.penalty_id,
                    bl.book_id,
                    bl.loan_date,
                    bl.due_date,
                    b.book_title,
                    b.author,
                    b.image_path,
                    (br.return_date - bl.due_date) as days_late,
                    p.large_fines as penalty_amount
                  FROM " . $this->table_name . " br
                  INNER JOIN booklending bl ON br.loan_id = bl.loan_id
                  INNER JOIN book b ON bl.book_id = b.book_id
                  LEFT JOIN penalty p ON br.penalty_id = p.penalty_id
                  WHERE br.return_date > bl.due_date
                  ORDER BY br.return_date DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getLateReturns(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count total returns
     * @return int
     */
    public function countReturns() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in countReturns(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent returns for dashboard
     * @param int $limit
     * @return PDOStatement|false
     */
    public function getRecentReturns($limit = 5) {
        $query = "SELECT 
                    br.return_id,
                    br.return_date,
                    bl.due_date,
                    b.book_title,
                    b.author,
                    CASE 
                        WHEN br.return_date > bl.due_date THEN true 
                        ELSE false 
                    END as was_late
                  FROM " . $this->table_name . " br
                  INNER JOIN booklending bl ON br.loan_id = bl.loan_id
                  INNER JOIN book b ON bl.book_id = b.book_id
                  ORDER BY br.return_date DESC
                  LIMIT :limit";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getRecentReturns(): " . $e->getMessage());
            return false;
        }
    }
}
?>
