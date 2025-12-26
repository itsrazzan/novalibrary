<?php
/*
 * Login validation function
 * Checks: 1) username exists, 2) password matches, 3) returns user status and id
 */
require_once __DIR__ . '/../config/database.php';

function loginCheck($username, $password) {
    $database = new Database();
    $db = $database->getConnection();

    try {
        // Ambil data user berdasarkan username melalui function
        $query = "SELECT * FROM get_user_for_auth(:username)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validasi password menggunakan Bcrypt di sisi PHP
        if ($user && password_verify($password, $user['hashed_password'])) {
            // Hapus password dari array sebelum dikirim ke controller (keamanan)
            unset($user['hashed_password']);
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}
?>