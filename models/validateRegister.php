<?php
/**
 * User Registration Model
 * Handles database operations for user signup/registration
 * 
 * Security Features:
 * - Bcrypt password hashing
 * - Prepared statements (SQL injection prevention)
 * - Duplicate username/email checking
 */

// Load database connection
require_once __DIR__ . '/../config/database.php';

/**
 * Register new user to database
 * 
 * @param string $username - Username (4-20 chars, alphanumeric)
 * @param string $password - Plain text password (will be hashed)
 * @param string $name - Full name of member
 * @param string $email - Email address
 * @param string $phone_number - Phone number
 * @return array - ['success' => bool, 'message' => string, 'user_id' => int]
 */
function registerUser($username, $password, $name, $email, $phone_number) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // STEP 1: Check if username already exists
        // Menggunakan prepared statement untuk keamanan
        $checkUsernameQuery = "SELECT id FROM username WHERE username = :username LIMIT 1";
        $stmt = $db->prepare($checkUsernameQuery);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        // Jika username sudah ada, return error
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return [
                'success' => false,
                'message' => 'Username sudah digunakan! Silakan pilih username lain.',
                'user_id' => null
            ];
        }
        
        // STEP 2: Check if email already exists
        // Email harus unique untuk recovery password di masa depan
        $checkEmailQuery = "SELECT id FROM username WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($checkEmailQuery);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        // Jika email sudah ada, return error
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return [
                'success' => false,
                'message' => 'Email sudah terdaftar! Gunakan email lain atau login.',
                'user_id' => null
            ];
        }
        
        // STEP 3: Hash password menggunakan bcrypt
        // Bcrypt secara otomatis menambahkan salt dan cost factor
        // Cost factor default = 10 (2^10 = 1024 iterations)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Verify hash berhasil dibuat
        if (!$hashedPassword) {
            return [
                'success' => false,
                'message' => 'Gagal mengenkripsi password. Silakan coba lagi.',
                'user_id' => null
            ];
        }
        
        // STEP 4: Get next ID for new user
        // PostgreSQL menggunakan SERIAL untuk auto-increment
        $getMaxIdQuery = "SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM username";
        $stmt = $db->prepare($getMaxIdQuery);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextId = $result['next_id'];
        
        // STEP 5: Insert new user into database
        // Default status = 'member' (bukan admin)
        $insertQuery = "INSERT INTO username 
                        (id, username, password, status, name, email, phone_number) 
                        VALUES 
                        (:id, :username, :password, 'member', :name, :email, :phone_number)";
        
        $stmt = $db->prepare($insertQuery);
        
        // Bind semua parameter untuk keamanan
        $stmt->bindParam(':id', $nextId, PDO::PARAM_INT);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashedPassword);  // Password yang sudah di-hash
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone_number', $phone_number);
        
        // Execute insert query
        $success = $stmt->execute();
        
        // STEP 6: Return result
        if ($success) {
            return [
                'success' => true,
                'message' => 'Registrasi berhasil! Silakan login dengan akun Anda.',
                'user_id' => $nextId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Gagal menyimpan data. Silakan coba lagi.',
                'user_id' => null
            ];
        }
        
    } catch (PDOException $e) {
        // Log error untuk debugging (jangan tampilkan ke user)
        error_log("Registration error: " . $e->getMessage());
        
        // Return generic error message untuk user
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.',
            'user_id' => null
        ];
    }
}

/**
 * Check if username is available
 * Helper function untuk real-time validation
 * 
 * @param string $username
 * @return bool - true jika available, false jika sudah dipakai
 */
function isUsernameAvailable($username) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT id FROM username WHERE username = :username LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        // Return true jika TIDAK ditemukan (available)
        return !$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Username check error: " . $e->getMessage());
        return false;  // Assume not available jika error
    }
}

/**
 * Check if email is available
 * Helper function untuk real-time validation
 * 
 * @param string $email
 * @return bool - true jika available, false jika sudah dipakai
 */
function isEmailAvailable($email) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "SELECT id FROM username WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        // Return true jika TIDAK ditemukan (available)
        return !$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Email check error: " . $e->getMessage());
        return false;  // Assume not available jika error
    }
}
?>
