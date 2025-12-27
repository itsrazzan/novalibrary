<?php
/**
 * Google OAuth Callback Controller
 * Handles Google Sign-In response dan user authentication
 * 
 * Flow:
 * 1. Receive Google ID token dari frontend
 * 2. Verify token dengan Google API
 * 3. Check if user exists (by google_id atau email)
 * 4. Login existing user ATAU create new user
 * 5. Set session dan redirect ke dashboard
 */

// Start session
session_start();

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/GoogleAuth.php';

// STEP 1: Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method!";
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
}

// STEP 2: Get Google ID token dari POST request
$credential = $_POST['credential'] ?? '';

if (empty($credential)) {
    $_SESSION['error'] = "Google authentication failed. Please try again.";
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
}

// STEP 3: Initialize database connection dan GoogleAuth model
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    $_SESSION['error'] = "Database connection failed!";
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
}

$googleAuth = new GoogleAuth($db);

// STEP 4: Verify Google ID token
$googleUser = $googleAuth->verifyGoogleToken($credential);

if (!$googleUser) {
    // Token verification failed
    $_SESSION['error'] = "Google authentication failed. Invalid token.";
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
}

// Extract user data dari Google
$googleId = $googleUser['google_id'];
$email = $googleUser['email'];
$name = $googleUser['name'];

// STEP 5: Check if user already exists by Google ID
$existingUser = $googleAuth->findUserByGoogleId($googleId);

if ($existingUser) {
    // User sudah pernah login via Google sebelumnya
    // Direct login
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $existingUser['id'];
    $_SESSION['username'] = $existingUser['username'];
    $_SESSION['role'] = $existingUser['status'];
    $_SESSION['auth_provider'] = 'google';
    
    // Clear any previous error
    if (isset($_SESSION['error'])) unset($_SESSION['error']);
    
    // Redirect based on role (always 'member' untuk Google users)
    if ($existingUser['status'] === 'admin') {
        header("Location: " . getRedirectUrl('views/admin/dashboard.php'));
    } else {
        header("Location: " . getRedirectUrl('views/user/dashboard.php'));
    }
    exit();
}

// STEP 6: User belum pernah login via Google
// Check if email already exists (user mungkin sudah signup via form biasa)
$existingEmailUser = $googleAuth->findUserByEmail($email);

if ($existingEmailUser) {
    // Email sudah terdaftar (via signup biasa)
    // Link Google account ke existing user
    
    $linkSuccess = $googleAuth->linkGoogleAccount($existingEmailUser['id'], $googleId);
    
    if ($linkSuccess) {
        // Link berhasil, login user
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $existingEmailUser['id'];
        $_SESSION['username'] = $existingEmailUser['username'];
        $_SESSION['role'] = $existingEmailUser['status'];
        $_SESSION['auth_provider'] = 'google';
        
        // Clear any previous error
        if (isset($_SESSION['error'])) unset($_SESSION['error']);
        
        // Redirect based on role
        if ($existingEmailUser['status'] === 'admin') {
            header("Location: " . getRedirectUrl('views/admin/dashboard.php'));
        } else {
            header("Location: " . getRedirectUrl('views/user/dashboard.php'));
        }
        exit();
    } else {
        // Link gagal
        $_SESSION['error'] = "Failed to link Google account. Please try again.";
        header("Location: " . getRedirectUrl('views/login.php'));
        exit();
    }
}

// STEP 7: User baru (belum pernah signup dan belum pernah login via Google)
// Create new user account
$newUser = $googleAuth->createGoogleUser($googleId, $email, $name);

if ($newUser) {
    // User creation berhasil, login user
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $newUser['id'];
    $_SESSION['username'] = $newUser['username'];
    $_SESSION['role'] = $newUser['status']; // Always 'member'
    $_SESSION['auth_provider'] = 'google';
    
    // Clear any previous error
    if (isset($_SESSION['error'])) unset($_SESSION['error']);
    
    // Redirect ke user dashboard (Google users always member)
    header("Location: " . getRedirectUrl('views/user/dashboard.php'));
    exit();
} else {
    // User creation gagal
    $_SESSION['error'] = "Failed to create account. Please try again or use email signup.";
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
}
?>
