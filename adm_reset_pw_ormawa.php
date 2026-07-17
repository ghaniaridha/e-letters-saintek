<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

$data = mysqli_fetch_assoc(
    mysqli_query($koneksi,"
        SELECT username
        FROM ormawa
        WHERE id_ormawa='$id'
    ")
);

$passwordBaru = password_hash(
    $data['username'],
    PASSWORD_DEFAULT
);

mysqli_query($koneksi,"
    UPDATE ormawa
    SET password='$passwordBaru'
    WHERE id_ormawa='$id'
");

$_SESSION['status'] = 'success';
$_SESSION['pesan'] =
    'Password berhasil direset menjadi username ormawa';

header("Location: adm_kelola_ormawa.php");
exit;
?>