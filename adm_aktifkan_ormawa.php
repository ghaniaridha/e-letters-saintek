<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

mysqli_query($koneksi,"
    UPDATE ormawa
    SET status = 1
    WHERE id_ormawa = '$id'
");

$_SESSION['status'] = 'success';
$_SESSION['pesan'] = 'Akun ormawa berhasil diaktifkan kembali';

header("Location: adm_kelola_ormawa.php");
exit;
?>