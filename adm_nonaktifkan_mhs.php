<?php
include "koneksi.php";

$success = false;
if (isset($_GET['npm'])) {
    $npm = $_GET['npm'];
    $stmt = $koneksi->prepare("UPDATE mahasiswa SET status = 2 WHERE npm = ?");
    $stmt->bind_param("s", $npm);
    $success = $stmt->execute();
    $stmt->close();
} else {
    header("Location: adm_kelola_mhs.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Nonaktif Akun Mahasiswa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <script>
        Swal.fire({
            icon: '<?= $success ? "success" : "error" ?>',
            title: '<?= $success ? "Berhasil!" : "Gagal!" ?>',
            text: '<?= $success ? "Akun mahasiswa telah dinonaktifkan." : "Terjadi kesalahan, gagal menonaktifkan akun." ?>',
            showConfirmButton: false,
            timer: 3000
        }).then(() => {
            window.location = 'adm_kelola_mhs.php';
        });
    </script>

</body>

</html>