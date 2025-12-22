<?php
session_start();


if(isset($_POST['submit'])){
    //ambil data dari form, cegah sql injection
    $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES,'UTF-8'));
    $password = trim(htmlspecialchars($_POST['password'], ENT_QUOTES,'UTF-8'));

    //fitur regex untuk username dan password
     if(!preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
        $error = "Username: huruf/angka, 4-20 karakter!";
        goto end;
    } elseif(strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
        goto end;
    } else {
        //panggil model validateLogin jika lolos validasi regex
        require_once '../models/validateLogin.php';
    };

    //cek username dan password, panggil models
    $userdata = loginCheck($username, $password);

    //jika login berhasil
    if($userData) {
        // Login sukses + role handling
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['status'] = $userData['status']; // 'user' atau 'admin'
    }

    if ($userData['status'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit();
    } elseif ($userData['status'] === 'user') {
        header("Location: ../user/dashboard.php");
        exit();
    } else {
        $error = "Username belum terdaftar atau password salah!";
    }
}
?>