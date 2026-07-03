<?php
include "koneksi.php";

if (isset($_GET['npm']) && isset($_GET['action'])) {
    $npm = $_GET['npm'];
    $action = $_GET['action'];

    $message = "";
    $icon = "success";
    $status = false;

    if ($action == 'setuju') {
        $update = mysqli_query($koneksi, "UPDATE mahasiswa SET status = 1 WHERE npm = '$npm'");
        if ($update) {
            $message = "Akun berhasil disetujui. Mahasiswa kini dapat login.";
            $icon = "success";
            $status = true;
        }
    } elseif ($action == 'tolak') {
        $delete = mysqli_query($koneksi, "DELETE FROM mahasiswa WHERE npm = '$npm'");
        if ($delete) {
            $message = "Pendaftaran ditolak. Data mahasiswa telah dihapus.";
            $icon = "info";
            $status = true;
        }
    }

    if ($status) {
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <link href='style.css' rel='stylesheet'>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: '$icon',
                    title: 'Berhasil!',
                    text: '$message',
                    showConfirmButton: false,
                    timer: 3000
                }).then(() => {
                    window.location = 'adm_kelola_mhs.php';
                });
            </script>
        </body>
        </html>";
    }
}
