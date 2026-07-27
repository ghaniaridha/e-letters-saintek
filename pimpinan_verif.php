<?php
session_start();
include "koneksi.php";

$id_dosen = $_SESSION['id_dosen'] ?? 0;
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    echo "<script>alert('Silakan login sebagai pimpinan'); window.location='index.php';</script>";
    exit;
}

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Pimpinan';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Pimpinan';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$pimpinan = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT *
    FROM dosen
    WHERE id_dosen = '$id_dosen'
"));

$jabatan = strtolower($pimpinan['jabatan'] ?? '');

if (strpos($jabatan, 'wadek 1') !== false || strpos($jabatan, 'wakil dekan 1') !== false) {

    $status_target = 'Menunggu Wadek 1';
} elseif (strpos($jabatan, 'wadek 2') !== false || strpos($jabatan, 'wakil dekan 2') !== false) {

    $status_target = 'Menunggu Wadek 2';
} elseif (strpos($jabatan, 'dekan') !== false) {

    $status_target = 'Menunggu Dekan';
} elseif (strpos($jabatan, 'kasubag') !== false) {

    $status_target = 'Menunggu Kasubag';
} else {
    $status_target = '';
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$search_sql = "";

if (!empty($search)) {
    $search_sql = " AND (
        m.nama_mhs LIKE '%$search%' OR 
        m.npm LIKE '%$search%' OR 
        p.nama_prodi LIKE '%$search%' OR 
        js.nama_surat LIKE '%$search%'
    )";
}

$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat,
        sp.id_jenis,
        sp.tanggal_pengajuan,
        sp.status_akhir,
        m.nama_mhs,
        m.npm,
        p.nama_prodi, 
        js.nama_surat
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN prodi p ON m.id_prodi = p.id_prodi 
    WHERE sp.status_akhir = '$status_target' 
    $search_sql 
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disposisi & Verifikasi</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
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
            <h2>Disposisi & Verifikasi Permohonan</h2>
        </div>

        <div class="table-wrapper" id="template-surat">
            <form method="GET" action="" class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="search" id="searchSurat" class="search-input"
                    placeholder="Cari..."
                    value="<?= htmlspecialchars($search); ?>">
                <button type="submit"></button>
            </form>

            <div class="riwayat-table">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal & Waktu Pengajuan</th>
                            <th>Mahasiswa</th>
                            <th>NPM</th>
                            <th>Prodi</th>
                            <th>Jenis Surat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                            <?php $no = 1;
                            while ($row = mysqli_fetch_assoc($query)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                    <td><?= htmlspecialchars($row['npm']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                    <td>
                                        <span class="badge-warning"><?= htmlspecialchars($row['status_akhir']); ?></span>
                                    </td>
                                    <td>
                                        <a href="pimpinan_detail.php?id=<?= $row['id_surat']; ?>" class="btn-aksi">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="8" style="text-align:center;">
                                    Tidak ada surat yang menunggu verifikasi Anda.
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