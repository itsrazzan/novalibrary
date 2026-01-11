<?php
/**
 * Secure Session Configuration
 * 
 * File ini harus di-include SEBELUM session_start() di semua halaman
 * untuk memastikan session memiliki konfigurasi keamanan yang konsisten.
 */

// Hanya jalankan jika session belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    
    // Deteksi apakah menggunakan HTTPS
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || $_SERVER['SERVER_PORT'] == 443;
    
    // Cookie settings yang aman
    $cookieParams = [
        'lifetime' => 0,              // Session cookie (expired saat browser ditutup)
        'path' => '/',                // Cookie berlaku untuk seluruh domain
        'domain' => '',               // Current domain only
        'secure' => $isSecure,        // Hanya kirim via HTTPS jika tersedia
        'httponly' => true,           // Tidak bisa diakses via JavaScript (prevent XSS)
        'samesite' => 'Strict'        // Prevent CSRF attacks
    ];
    
    // Set session cookie parameters
    session_set_cookie_params($cookieParams);
    
    // Additional session security settings
    ini_set('session.use_strict_mode', 1);      // Reject uninitialized session IDs
    ini_set('session.use_only_cookies', 1);     // Only use cookies for session ID
    ini_set('session.use_trans_sid', 0);        // Disable URL-based session ID
    
    // Custom session name - DIKOMENTARI untuk menghindari konflik dengan cookie lama
    // Jika diaktifkan, user harus clear cookies browser terlebih dahulu
    // session_name('NOVA_SESSION');
}

/**
 * Helper function untuk redirect user yang sudah login ke dashboard mereka
 * PASTIKAN session_start() sudah dipanggil sebelum memanggil fungsi ini!
 * 
 * @return bool True jika user sudah login dan redirect dilakukan, false jika tidak
 */
function redirectIfLoggedIn() {
    // Pastikan session sudah aktif
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false; // Session belum aktif, skip redirect
    }
    
    // Include helpers jika belum ada
    if (!function_exists('getRedirectUrl')) {
        require_once __DIR__ . '/helpers.php';
    }
    
    // Cek apakah user sudah login
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        $role = $_SESSION['role'] ?? 'member';
        
        // Redirect berdasarkan role
        if ($role === 'admin') {
            header("Location: " . getRedirectUrl('views/admin/dashboard.php'));
            exit();
        } else {
            header("Location: " . getRedirectUrl('views/user/dashboard.php'));
            exit();
        }
        
        return true;
    }
    
    return false;
}

/**
 * Regenerate session ID untuk keamanan
 * Panggil ini setelah login berhasil untuk prevent session fixation
 */
function regenerateSessionSecurely() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
?>
