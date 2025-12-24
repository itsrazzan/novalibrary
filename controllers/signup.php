<?php
if (isset($_POST['submit'])) {
    // ambil data dari form, cegah XSS
    $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
    $password = trim(htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8'));
    $member_name = trim(htmlspecialchars($_POST['member_name'], ENT_QUOTES, 'UTF-8'));
    $email = trim(htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'));
    $phone_number = trim(htmlspecialchars($_POST['phone_number'], ENT_QUOTES, 'UTF-8'));  
}

// #1 VALIDASI REGEX SEDERHANA
    if(!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        $error = "Username: huruf/angka/_, 4-20 char";
    }
    elseif(!preg_match('/^[a-zA-Z ]{2,50}$/', $member_name)) {
        $error = "Nama: huruf + spasi, max 50 char";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email format salah!";
    }
    elseif(!preg_match('/^08[0-9]{8,12}$/', $phone_number)) {
        $error = "No HP: 08xxxxxxxxx (10-13 digit)";
    }
    elseif(strlen($password) < 6) {
        $error = "Password min 6 karakter";
    }

?>