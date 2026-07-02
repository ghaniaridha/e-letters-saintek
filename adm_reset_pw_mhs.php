<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['npm'])) {
    $npm = mysqli_real_escape_string($koneksi, $_GET['npm']);
    $hashed_password = password_hash($npm, PASSWORD_DEFAULT);

    $stmt = $koneksi->prepare("UPDATE mahasiswa SET password = ? WHERE npm = ?");
    $stmt->bind_param("ss", $hashed_password, $npm);

    if ($stmt->execute()) {
        $_SESSION['pesan'] = "Kata sandi berhasil diatur ulang menjadi: " . $npm;
        $_SESSION['status'] = "success";
    } else {
        $_SESSION['pesan'] = "Gagal mengatur ulang kata sandi.";
        $_SESSION['status'] = "error";
    }
}

header("Location: adm_kelola_mhs.php");
exit;
