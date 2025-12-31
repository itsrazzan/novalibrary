<?php
/**
 * Penalty Model
 * Handles all database operations related to fines/penalties
 */
class Penalty {
    private $conn;
    private $table_name = "penalty";
    private $fine_per_day = 5000; // Rp 5.000 per hari keterlambatan

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all penalties
     * @return PDOStatement|false
     */
    public function getAllPenalties() {
        $query = "SELECT 
                    p.penalty_id,
                    p.id as user_id,
                    p.large_fines,
                    u.username,
                    u.name as user_name,
                    u.email
                  FROM " . $this->table_name . " p
                  INNER JOIN username u ON p.id = u.id
                  ORDER BY p.penalty_id DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getAllPenalties(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get penalties with book return details
     * @return PDOStatement|false
     */
    public function getPenaltiesWithDetails() {
        $query = "SELECT 
                    p.penalty_id,
                    p.id as user_id,
                    p.large_fines,
                    u.username,
                    u.name as user_name,
                    br.return_date,
                    bl.due_date,
                    b.book_title,
                    (br.return_date - bl.due_date) as days_late
                  FROM " . $this->table_name . " p
                  INNER JOIN username u ON p.id = u.id
                  LEFT JOIN bookreturn br ON br.penalty_id = p.penalty_id
                  LEFT JOIN booklending bl ON br.loan_id = bl.loan_id
                  LEFT JOIN book b ON bl.book_id = b.book_id
                  ORDER BY p.penalty_id DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getPenaltiesWithDetails(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total unpaid fines amount
     * @return int
     */
    public function getTotalUnpaidFines() {
        $query = "SELECT COALESCE(SUM(large_fines), 0) as total 
                  FROM " . $this->table_name;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in getTotalUnpaidFines(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count total penalties
     * @return int
     */
    public function countPenalties() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in countPenalties(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get penalties by user
     * @param int $user_id
     * @return PDOStatement|false
     */
    public function getPenaltiesByUser($user_id) {
        $query = "SELECT 
                    p.penalty_id,
                    p.large_fines,
                    br.return_date,
                    b.book_title
                  FROM " . $this->table_name . " p
                  LEFT JOIN bookreturn br ON br.penalty_id = p.penalty_id
                  LEFT JOIN booklending bl ON br.loan_id = bl.loan_id
                  LEFT JOIN book b ON bl.book_id = b.book_id
                  WHERE p.id = :user_id
                  ORDER BY p.penalty_id DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getPenaltiesByUser(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get fine per day rate
     * @return int
     */
    public function getFinePerDay() {
        return $this->fine_per_day;
    }

    /**
     * Format currency in Rupiah
     * @param int $amount
     * @return string
     */
    public function formatRupiah($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
?>
