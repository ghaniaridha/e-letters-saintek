<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: adm_kelola_ormawa.php");
    exit;
}

$id = (int)$_GET['id'];
$action = $_GET['action'] ?? '';

if ($action == "setuju") {

    mysqli_query($koneksi,"
        UPDATE ormawa
        SET status = 1
        WHERE id_ormawa = '$id'
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan'] = 'Akun ormawa berhasil disetujui';

} elseif ($action == "tolak") {

    mysqli_query($koneksi,"
        DELETE FROM ormawa
        WHERE id_ormawa = '$id'
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan'] = 'Pendaftaran ormawa berhasil ditolak';
}

header("Location: adm_kelola_ormawa.php");
exit;
?>