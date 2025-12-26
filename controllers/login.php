<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // load model
    require_once __DIR__ . '/../models/validateLogin.php';
    // ambil data dari form, cegah XSS
    $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
    $password = $_POST['password'] ?? ''; // password tidak di-sanitize agar hash tetap valid


    // Validasi input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Semua field harus diisi!";
        header("Location: " . getRedirectUrl('views/login.php'));
        exit();
    } elseif (!preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
        $_SESSION['error'] = "Username: huruf/angka, 4-20 karakter!";
        header("Location: " . getRedirectUrl('views/login.php'));
        exit();
    } else {
        $userData = loginCheck($username, $password);

        if ($userData && is_array($userData)) {
            // Login sukses + role handling
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['role'] = $userData['status'];
            // Clear any previous error
            if (isset($_SESSION['error'])) unset($_SESSION['error']);

            // Redirect berdasarkan role
            if ($userData['status'] === 'admin') {
                header("Location: " . getRedirectUrl('views/admin/dashboard.php'));
                exit();
            } elseif ($userData['status'] === 'user' || $userData['status'] === 'member') {
                header("Location: " . getRedirectUrl('views/user/dashboard.php'));
                exit();
            }
        } else {
            $_SESSION['error'] = "Username belum terdaftar atau password salah!";
            header("Location: " . getRedirectUrl('views/login.php'));
            exit();
        }
    }
}
?>