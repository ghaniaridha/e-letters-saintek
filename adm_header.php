<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Silakan login sebagai admin'); window.location='login.php';</script>";
    exit;
}
?>
