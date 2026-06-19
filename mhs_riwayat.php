<?php
session_start();
include "koneksi.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php
    if (isset($_SESSION['pesan'])) {
        $alertType = ($_SESSION['status'] == "success") ? "success" : "error";
        echo "<script>
                Swal.fire({
                    icon: '$alertType',
                    title: '" . ($_SESSION['status'] == "success" ? "Berhasil!" : "Gagal!") . "',
                    text: '" . $_SESSION['pesan'] . "',
                    timer: 2000, 
                    showConfirmButton: false 
                });
              </script>";
        unset($_SESSION['pesan']);
        unset($_SESSION['status']);
    }
    ?>

    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/AKADEMIK FST2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="mhs_dashboard.php">Beranda</a>
            <a href="mhs_dashboard.php#services">Layanan Akademik</a>
            <a href="mhs_dashboard.php#riwayat">Informasi</a>
            <a href="mhs_riwayat.php">Riwayat Permohonan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <?php
                $namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
                $idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
                $role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

                $inisial = '';
                $namaParts = explode(' ', $namaLengkap);
                if (!empty($namaParts)) {
                    $inisial = strtoupper(substr($namaParts[0], 0, 1));
                }
                ?>
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>
                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= ($namaLengkap) ?></span>
                        <span class="user-role"><?= $idLogin ?> - <?= $role ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section id="riwayat-permohonan" class="riwayat-permohonan">
        <div class="riwayat-permohonan-header">
            <h2>Riwayat Permohonan</h2>
        </div>
</body>

</html>