<?php
class User {
    private $conn;
    private $table_name = "username";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Count members (non-admin users)
     * @return int
     */
    public function countMembers() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'member'";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in countMembers(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all members
     * @return PDOStatement|false
     */
    public function getAllMembers() {
        $query = "SELECT 
                    id,
                    username,
                    name,
                    email,
                    phone_number,
                    auth_provider
                  FROM " . $this->table_name . " 
                  WHERE status = 'member'
                  ORDER BY id ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getAllMembers(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count all users
     * @return int
     */
    public function countUsers() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } catch (PDOException $e) {
            error_log("Error in countUsers(): " . $e->getMessage());
            return 0;
        }
    }

    // Read all users
    public function read() {
        $query = "SELECT id, username, name, email, phone_number, status, auth_provider 
                  FROM " . $this->table_name . " ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get Single User
    public function show($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Delete
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
?>