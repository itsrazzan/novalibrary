<?php
/**
 * Signup Controller
 * Handles user registration form submission
 * 
 * Flow:
 * 1. Validate POST request
 * 2. Sanitize input data
 * 3. Validate input format
 * 4. Call registerUser() from model
 * 5. Redirect based on result
 */

// Start session untuk error/success messages
session_start();

// Load helper functions untuk redirect
require_once __DIR__ . '/../config/helpers.php';

// STEP 1: Check if form submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Jika bukan POST, redirect ke register page
    $_SESSION['error'] = "Invalid request method!";
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}

// STEP 2: Load registration model
require_once __DIR__ . '/../models/validateRegister.php';

// STEP 3: Ambil dan sanitize data dari form
// htmlspecialchars() mencegah XSS attack
// trim() menghapus whitespace di awal/akhir
// ENT_QUOTES mengkonversi single dan double quotes

$username = trim(htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'));
$member_name = trim(htmlspecialchars($_POST['member_name'] ?? '', ENT_QUOTES, 'UTF-8'));
$email = trim(htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'));
$phone_number = trim(htmlspecialchars($_POST['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'));

// Password TIDAK di-sanitize agar hash tetap valid
// Password akan di-hash di model menggunakan bcrypt
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// STEP 4: Validasi input kosong
if (empty($username) || empty($password) || empty($member_name) || empty($email) || empty($phone_number)) {
    $_SESSION['error'] = "Semua field harus diisi!";
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}

// STEP 5: Validasi format username
// Regex: huruf, angka, underscore, 4-20 karakter
if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
    $_SESSION['error'] = "Username: huruf/angka/underscore, 4-20 karakter!";
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}

// STEP 6: Validasi format nama
// Regex: huruf dan spasi, 2-100 karakter
if (!preg_match('/^[a-zA-Z ]{2,100}$/', $member_name)) {
    $_SESSION['error'] = "Nama: hanya huruf dan spasi, 2-100 karakter!";
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}

// STEP 7: Validasi format email
// Menggunakan built-in PHP filter
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Format email tidak valid!";
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}

// STEP 8: Validasi format nomor telepon
// Regex: dimulai dengan 08, diikuti 8-12 digit angka (total 10-14 digit)
if (!preg_match('/^08[0-9]{8,12}$/', $phone_number)) {
    $_SESSION['error'] = "No HP: harus dimulai 08, total 10-14 digit!";
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}

// STEP 9: Validasi panjang password
// Minimum 6 karakter untuk keamanan dasar
if (strlen($password) < 6) {
    $_SESSION['error'] = "Password minimal 6 karakter!";
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}

// STEP 10: Validasi password confirmation
// Password dan confirm password harus sama
if ($password !== $confirm_password) {
    $_SESSION['error'] = "Password dan konfirmasi password tidak cocok!";
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}

// STEP 11: Call registerUser() function dari model
// Function ini akan:
// - Check duplicate username/email
// - Hash password dengan bcrypt
// - Insert ke database
// - Return array dengan success status dan message
$result = registerUser($username, $password, $member_name, $email, $phone_number);

// STEP 12: Handle result dari registration
if ($result['success']) {
    // Registration berhasil
    // Set success message di session
    $_SESSION['success'] = $result['message'];
    
    // Redirect ke login page
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
} else {
    // Registration gagal (username/email duplicate atau error lain)
    // Set error message di session
    $_SESSION['error'] = $result['message'];
    
    // Redirect kembali ke register page
    header("Location: " . getRedirectUrl('views/register.php'));
    exit();
}
?>