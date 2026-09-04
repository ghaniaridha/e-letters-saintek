<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location:index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $nama_ormawa = mysqli_real_escape_string($koneksi, $_GET['nama']);
    $singkatan = mysqli_real_escape_string($koneksi, $_GET['singkatan']);
    $username = mysqli_real_escape_string($koneksi, $_GET['username']);

    $update_pembina_query = "";
    if (!empty($_GET['id_pembina'])) {
        $id_pembina = mysqli_real_escape_string($koneksi, $_GET['id_pembina']);
        $update_pembina_query = ", id_pembina = '$id_pembina'";
    }

    $query = "UPDATE ormawa SET 
                nama_ormawa = '$nama_ormawa',
                singkatan_ormawa = '$singkatan',
                username = '$username'
                $update_pembina_query
              WHERE id_ormawa = '$id'";

    $update = mysqli_query($koneksi, $query);

    if ($update) {
        $_SESSION['status'] = "success";
        $_SESSION['pesan']  = "Data organisasi berhasil diperbarui.";
    } else {
        $_SESSION['status'] = "error";
        $_SESSION['pesan']  = "Gagal memperbarui data: " . mysqli_error($koneksi);
    }
} else {
    $_SESSION['status'] = "error";
    $_SESSION['pesan']  = "Akses tidak valid.";
}

header("Location: adm_kelola_ormawa.php");
exit;
