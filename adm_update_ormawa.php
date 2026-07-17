<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location:index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id = mysqli_real_escape_string($koneksi, $_POST['id_ormawa']);

    $nama_ormawa = mysqli_real_escape_string(
        $koneksi,
        $_POST['nama_ormawa']
    );

    $username = mysqli_real_escape_string(
        $koneksi,
        $_POST['username']
    );

    $id_prodi = mysqli_real_escape_string(
        $koneksi,
        $_POST['id_prodi']
    );

    $id_pembina = mysqli_real_escape_string(
        $koneksi,
        $_POST['id_pembina']
    );

    $update = mysqli_query($koneksi,"
        UPDATE ormawa
        SET
            nama_ormawa = '$nama_ormawa',
            username = '$username',
            id_prodi = '$id_prodi',
            id_pembina = '$id_pembina'
        WHERE id_ormawa = '$id'
    ");

    if($update){

        $_SESSION['status'] = "success";
        $_SESSION['pesan']  = "Data ormawa berhasil diperbarui.";

    }else{

        $_SESSION['status'] = "error";
        $_SESSION['pesan']  = "Gagal memperbarui data ormawa.";

    }

}else{

    $_SESSION['status'] = "error";
    $_SESSION['pesan']  = "Akses tidak valid.";

}

header("Location: adm_kelola_ormawa.php");
exit;
?>