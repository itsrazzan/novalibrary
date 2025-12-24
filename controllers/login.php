<?php
session_start();

if (isset($_POST['submit'])) {
    // ambil data dari form, cegah XSS
    $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
    $password = trim(htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8'));

    // validasi
    if (!preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
        $error = "Username: huruf/angka, 4-20 karakter!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        require_once '../models/validateLogin.php';
        $userData = loginCheck($username, $password); // use consistent variable name

        if ($userData && is_array($userData)) {
            // Login sukses + role handling
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['status'] = $userData['status'];

            if ($userData['status'] === 'admin') {
                header("Location: ../admin/dashboard.php");
                exit();
            } elseif ($userData['status'] === 'user') {
                header("Location: ../user/dashboard.php");
                exit();
            }
        } else {
            $error = "Username belum terdaftar atau password salah!";
        }
    }
}
?>