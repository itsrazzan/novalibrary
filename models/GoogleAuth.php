<?php
/**
 * Google OAuth Authentication Model
 * Handles Google Sign-In authentication and user management
 * 
 * Features:
 * - Verify Google ID token
 * - Find/create users from Google account
 * - Auto-generate username from email
 * - Link Google account to existing users
 */

require_once __DIR__ . '/../config/database.php';

class GoogleAuth {
    private $conn;
    private $client_id = '903855622906-2qt28u76dhvk5cc6hj4gmfqa8m1q5sqt.apps.googleusercontent.com';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Verify Google ID token dengan Google API
     * 
     * @param string $idToken - ID token dari Google Sign-In
     * @return array|false - User data dari Google atau false jika invalid
     */
    public function verifyGoogleToken($idToken) {
        // Google token verification endpoint
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
        
        try {
            // Call Google API untuk verify token
            $response = file_get_contents($url);
            
            if ($response === false) {
                error_log("Google token verification failed: Unable to connect to Google API");
                return false;
            }
            
            $data = json_decode($response, true);
            
            // Check if token is valid
            if (!isset($data['sub']) || !isset($data['email'])) {
                error_log("Google token verification failed: Invalid token data");
                return false;
            }
            
            // Verify audience (client_id) untuk security
            if ($data['aud'] !== $this->client_id) {
                error_log("Google token verification failed: Invalid audience");
                return false;
            }
            
            // Return user data dari Google
            return [
                'google_id' => $data['sub'],           // Google user ID
                'email' => $data['email'],             // Email address
                'name' => $data['name'] ?? '',         // Full name
                'email_verified' => $data['email_verified'] ?? false
            ];
            
        } catch (Exception $e) {
            error_log("Google token verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by Google ID
     * 
     * @param string $googleId - Google user ID
     * @return array|false - User data atau false jika tidak ditemukan
     */
    public function findUserByGoogleId($googleId) {
        try {
            $query = "SELECT id, username, email, status, google_id, auth_provider 
                      FROM username 
                      WHERE google_id = :google_id 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':google_id', $googleId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("findUserByGoogleId error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by email
     * 
     * @param string $email - Email address
     * @return array|false - User data atau false jika tidak ditemukan
     */
    public function findUserByEmail($email) {
        try {
            $query = "SELECT id, username, email, status, google_id, auth_provider 
                      FROM username 
                      WHERE email = :email 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("findUserByEmail error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique username dari email
     * 
     * @param string $email - Email address
     * @return string - Generated username
     */
    private function generateUsername($email) {
        // Extract username part dari email (sebelum @)
        $emailParts = explode('@', $email);
        $baseUsername = $emailParts[0];
        
        // Remove special characters, hanya alphanumeric
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
        
        // Lowercase
        $baseUsername = strtolower($baseUsername);
        
        // Ensure minimal 4 characters
        if (strlen($baseUsername) < 4) {
            $baseUsername = $baseUsername . 'user';
        }
        
        // Ensure maksimal 20 characters
        if (strlen($baseUsername) > 20) {
            $baseUsername = substr($baseUsername, 0, 20);
        }
        
        // Check if username already exists
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->usernameExists($username)) {
            // Jika exists, tambah angka di belakang
            $username = $baseUsername . $counter;
            $counter++;
            
            // Ensure tidak lebih dari 20 chars
            if (strlen($username) > 20) {
                $baseUsername = substr($baseUsername, 0, 20 - strlen((string)$counter));
                $username = $baseUsername . $counter;
            }
        }
        
        return $username;
    }
    
    /**
     * Check if username already exists
     * 
     * @param string $username - Username to check
     * @return bool - True jika exists, false jika available
     */
    private function usernameExists($username) {
        try {
            $query = "SELECT id FROM username WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            error_log("usernameExists error: " . $e->getMessage());
            return true; // Assume exists jika error (untuk safety)
        }
    }
    
    /**
     * Create new user dari Google account
     * 
     * @param string $googleId - Google user ID
     * @param string $email - Email address
     * @param string $name - Full name
     * @return array|false - Created user data atau false jika gagal
     */
    public function createGoogleUser($googleId, $email, $name) {
        try {
            // Generate unique username dari email
            $username = $this->generateUsername($email);
            
            // Get next ID
            $getMaxIdQuery = "SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM username";
            $stmt = $this->conn->prepare($getMaxIdQuery);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextId = $result['next_id'];
            
            // Insert new user
            // Password = NULL karena Google user tidak perlu password
            // Status = 'member' (default untuk Google users)
            // auth_provider = 'google'
            $query = "INSERT INTO username 
                      (id, username, password, status, name, email, phone_number, google_id, auth_provider) 
                      VALUES 
                      (:id, :username, NULL, 'member', :name, :email, NULL, :google_id, 'google')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $nextId, PDO::PARAM_INT);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':google_id', $googleId);
            
            $success = $stmt->execute();
            
            if ($success) {
                // Return created user data
                return [
                    'id' => $nextId,
                    'username' => $username,
                    'email' => $email,
                    'status' => 'member',
                    'google_id' => $googleId,
                    'auth_provider' => 'google'
                ];
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("createGoogleUser error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Link Google account ke existing user
     * Digunakan jika user sudah punya account (via signup biasa) dengan email yang sama
     * 
     * @param int $userId - User ID yang akan di-link
     * @param string $googleId - Google user ID
     * @return bool - True jika berhasil, false jika gagal
     */
    public function linkGoogleAccount($userId, $googleId) {
        try {
            $query = "UPDATE username 
                      SET google_id = :google_id, 
                          auth_provider = 'google' 
                      WHERE id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':google_id', $googleId);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("linkGoogleAccount error: " . $e->getMessage());
            return false;
        }
    }
}
?>
