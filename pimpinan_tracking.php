<?php
session_start();
include "koneksi.php";

$id_dosen = $_SESSION['id_dosen'] ?? 0;
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    echo "<script>alert('Silakan login sebagai pimpinan'); window.location='index.php';</script>";
    exit;
}

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'pimpinan';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'pimpinan';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$search_sql = "";

if (!empty($search)) {
    $search_sql = " WHERE (
        m.nama_mhs LIKE '%$search%' OR 
        m.npm LIKE '%$search%' OR 
        js.nama_surat LIKE '%$search%' OR 
        sp.status_akhir LIKE '%$search%' OR 
        sp.nomor_surat LIKE '%$search%'
    )";
}

$query_tracking = mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        js.nama_surat,
        COALESCE(dsr.status_pb1, '-') AS status_pb1,
        COALESCE(dsr.status_pb2, '-') AS status_pb2
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    $search_sql 
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Disposisi</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="pimpinan_beranda.php#home">Beranda</a>
            <a href="pimpinan_verif.php">Disposisi & Verifikasi</a>
            <a href="pimpinan_beranda.php#riwayat">Informasi</a>
            <a href="pimpinan_riwayat.php">Riwayat Verifikasi</a>
            <a href="pimpinan_tracking.php">Tracking</a>
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
        <div class="daftar-surat-header">
            <h2>Tracking Surat<br>Fakultas Sains dan Teknologi UINRIL</h2>
        </div>

        <div class="table-wrapper" id="template-surat">
            <form method="GET" action="" class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="search" id="searchSurat" class="search-input"
                    placeholder="Cari..."
                    value="<?= htmlspecialchars($search); ?>">
                <button type="submit" style="display: none;"></button>
            </form>
            <table class="riwayat-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Mahasiswa</th>
                        <th>NPM</th>
                        <th>Jenis Surat</th>
                        <th>Dospem 1</th>
                        <th>Dospem 2</th>
                        <th>Pimpinan</th>
                        <th>Status Akhir</th>
                        <th>Posisi Saat Ini</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query_tracking && mysqli_num_rows($query_tracking) > 0) { ?>
                        <?php $no = 1;
                        while ($row = mysqli_fetch_assoc($query_tracking)) { ?>
                            <?php
                            $warna = '#f59e0b';

                            if ($row['status_akhir'] == 'Selesai') {
                                $warna = '#10b981';
                            } elseif (strpos($row['status_akhir'], 'Ditolak') !== false) {
                                $warna = '#ef4444';
                            }
                            ?>

                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                <td><?= htmlspecialchars($row['npm']); ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td><?= htmlspecialchars($row['status_pb1'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row['status_pb2'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row['status_pimpinan'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge-riwayat" style="background:<?= $warna; ?>;">
                                        <?= htmlspecialchars($row['status_akhir']); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['posisi_sekarang'] ?? $row['status_akhir']); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="10" style="text-align:center; padding:20px;">
                                Belum ada data tracking surat.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userBtn = document.getElementById('user-btn');
            const dropdown = document.getElementById('user-dropdown');

            userBtn.addEventListener('click', function(event) {
                dropdown.classList.toggle('show');
                event.stopPropagation();
            });

            window.addEventListener('click', function(event) {
                if (!event.target.matches('#user-btn') && !event.target.closest('#user-btn')) {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                }
            });
        });
    </script>
</body>

</html>