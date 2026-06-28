<?php
include "koneksi.php";

if (isset($_GET['npm'])) {
    $npm = $_GET['npm'];

    $stmt = $koneksi->prepare("UPDATE mahasiswa SET status = ? WHERE npm = ?");
    $status = STATUS_AKTIF;

    $stmt->bind_param("is", $status, $npm);

    $success = $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Aktifkan Akun Mahasiswa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <script>
        <?php if ($success) { ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Akun berhasil diaktifkan kembali.',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'adm_kelola_mhs.php';
            });
        <?php } else { ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat mengaktifkan akun.',
                confirmButtonText: 'Kembali'
            }).then(() => {
                window.location.href = 'adm_kelola_mhs.php';
            });
        <?php } ?>
    </script>
</body>

</html>