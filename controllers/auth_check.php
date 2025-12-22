<?php
// auth_check.php

// Pastikan session dimulai sebelum kode apa pun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek status login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: auth/login.php");
    exit();
}
?>