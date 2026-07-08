<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='login.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'];
$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Dosen';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Dosen';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

// PERBAIKAN QUERY
$query = mysqli_query($koneksi, "
    SELECT 
        sp.*,
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        js.nama_surat,
        dsr.id_pb1,
        dsr.id_pb2,
        dsr.status_pb1,
        dsr.status_pb2,
        dak.id_pa,          -- Ambil data ID PA
        dak.status_pa       -- Ambil data status PA
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN prodi p ON m.id_prodi = p.id_prodi
    
    -- JOIN ke tabel detail riset
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    
    -- PERBAIKAN 1: Tambahkan JOIN ke tabel detail aktif kuliah
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    
    WHERE
    (
        -- SKENARIO A: Dosen ini adalah Pembimbing 2 Riset (Verifikator Pertama)
        dsr.id_pb2 = '$id_dosen'
        AND dsr.status_pb2 = 'Menunggu'
    )
    OR
    (
        -- SKENARIO B: Dosen ini adalah Pembimbing 1 Riset (Verifikator Kedua)
        dsr.id_pb1 = '$id_dosen'
        AND dsr.status_pb2 = 'Disetujui'
        AND dsr.status_pb1 = 'Menunggu'
    )
    OR
    (
        -- PERBAIKAN 2: SKENARIO C: Dosen ini adalah Pembimbing Akademik (Aktif Kuliah)
        dak.id_pa = '$id_dosen'
        AND dak.status_pa = 'Menunggu'
    )
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body class="dosen-page">
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="dosen_beranda.php">Beranda</a>
            <a href="dosen_permohonan.php">Verifikasi Permohonan</a>
            <a href="dosen_beranda.php#riwayat">Informasi</a>
            <a href="dosen_riwayat.php">Riwayat Verifikasi</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial); ?></span>
                </button>

                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap); ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin); ?> - <?= htmlspecialchars($role); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section id="daftar-surat" class="daftar-surat">
        <div class="riwayat-permohonan-header">
            <h2>Verifikasi Surat Permohonan Mahasiswa</h2>
        </div>

        <div class="table-wrapper" id="template-surat">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchSurat" class="search-input" placeholder="Cari Permohonan surat...">
            </div>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Mahasiswa</th>
                        <th>NPM</th>
                        <th>Jenis Surat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($query && mysqli_num_rows($query) > 0) {
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query)) { ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                <td><?= htmlspecialchars($row['npm']); ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td><?= htmlspecialchars($row['status_akhir']); ?></td>
                                <td>
                                    <a href="dosen_detail_permohonan.php?id=<?= $row['id_surat']; ?>" class="btn btn-detail">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" class="empty-table-cell">
                                <i class="fa-solid fa-folder-open"></i>
                                <p>Belum permohonan yang menunggu verifikasi.</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const userBtn = document.getElementById("user-btn");
            const dropdown = document.getElementById("user-dropdown");

            userBtn.addEventListener("click", function(e) {
                dropdown.classList.toggle("show");
                e.stopPropagation();
            });

            window.addEventListener("click", function(e) {
                if (!e.target.closest(".user-menu-container")) {
                    dropdown.classList.remove("show");
                }
            });
        });
    </script>
</body>

</html>