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

    /**
     * Get borrowings by user ID (for RLS filter)
     * @param int $userId
     * @return array
     */
    public function getBorrowingsByUser($userId) {
        $query = "SELECT 
                    bl.loan_id,
                    bl.book_id,
                    bl.loan_date,
                    bl.due_date,
                    bl.return_date,
                    b.book_title,
                    b.author,
                    b.image_path,
                    b.sinopsis,
                    c.category_name,
                    CASE WHEN bl.return_date IS NULL AND bl.due_date < CURRENT_DATE THEN true ELSE false END as is_overdue,
                    CASE WHEN bl.return_date IS NULL AND bl.due_date < CURRENT_DATE 
                         THEN (CURRENT_DATE - bl.due_date) ELSE 0 END as days_overdue
                  FROM " . $this->table_name . " bl
                  INNER JOIN book b ON bl.book_id = b.book_id
                  LEFT JOIN bookcategory c ON b.category_id = c.category_id
                  WHERE bl.id = :user_id
                  ORDER BY bl.loan_date DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getBorrowingsByUser(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active borrowings by user ID
     * @param int $userId
     * @return array
     */
    public function getActiveBorrowingsByUser($userId) {
        $query = "SELECT 
                    bl.loan_id,
                    bl.book_id,
                    bl.loan_date,
                    bl.due_date,
                    b.book_title,
                    b.author,
                    b.image_path,
                    b.sinopsis,
                    c.category_name,
                    CASE WHEN bl.due_date < CURRENT_DATE THEN true ELSE false END as is_overdue,
                    CASE WHEN bl.due_date < CURRENT_DATE 
                         THEN (CURRENT_DATE - bl.due_date) ELSE 0 END as days_overdue,
                    CASE WHEN bl.due_date < CURRENT_DATE 
                         THEN (CURRENT_DATE - bl.due_date) * 2000 ELSE 0 END as penalty_amount
                  FROM " . $this->table_name . " bl
                  INNER JOIN book b ON bl.book_id = b.book_id
                  LEFT JOIN bookcategory c ON b.category_id = c.category_id
                  WHERE bl.id = :user_id AND bl.return_date IS NULL
                  ORDER BY bl.due_date ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getActiveBorrowingsByUser(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate penalty for a loan (Rp 2.000 per day)
     * @param string $dueDate
     * @param int $baseRate Default 2000
     * @return int Penalty in Rupiah
     */
    public static function calculatePenalty($dueDate, $baseRate = 2000) {
        $today = new DateTime();
        $due = new DateTime($dueDate);
        
        if ($today <= $due) {
            return 0; // Not late
        }
        
        $daysLate = $today->diff($due)->days;
        return $baseRate * $daysLate;
    }

    /**
     * Get total penalty for a user
     * @param int $userId
     * @return int
     */
    public function getTotalPenaltyByUser($userId) {
        $query = "SELECT COALESCE(SUM(large_fines), 0) as total 
                  FROM penalty WHERE id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in getTotalPenaltyByUser(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get member statistics from mv_statistik_member
     * @param int $userId
     * @return array|null
     */
    public function getMemberStats($userId) {
        $query = "SELECT * FROM mv_statistik_member WHERE id = :user_id LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getMemberStats(): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new loan
     * @param int $userId
     * @param int $bookId
     * @param int $durationDays Default 14
     * @return int|false loan_id or false
     */
    public function createLoan($userId, $bookId, $durationDays = 14) {
        // PostgreSQL requires explicit INTERVAL cast for date arithmetic
        $query = "INSERT INTO " . $this->table_name . " 
                  (loan_id, id, book_id, loan_date, due_date)
                  VALUES (
                    (SELECT COALESCE(MAX(loan_id), 0) + 1 FROM booklending),
                    :user_id, :book_id, CURRENT_DATE, CURRENT_DATE + INTERVAL '" . (int)$durationDays . " days'
                  )
                  RETURNING loan_id";
        
        try {
            $this->conn->beginTransaction();
            
            // Insert loan
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update book status to unavailable
                $updateStmt = $this->conn->prepare("UPDATE book SET book_status = false WHERE book_id = :book_id");
                $updateStmt->execute([':book_id' => $bookId]);
                
                $this->conn->commit();
                
                // Refresh materialized view
                $this->refreshMV('mv_statistik_member');
                return $result['loan_id'];
            }
            
            $this->conn->rollBack();
            return false;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in createLoan(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Refresh materialized view
     * @param string $mvName
     */
    private function refreshMV($mvName) {
        try {
            $this->conn->exec("REFRESH MATERIALIZED VIEW {$mvName}");
        } catch (PDOException $e) {
            error_log("Error refreshing MV {$mvName}: " . $e->getMessage());
        }
    }
}
?>
