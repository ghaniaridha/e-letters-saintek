<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    echo "<script>alert('Silakan login sebagai pimpinan'); window.location='index.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'] ?? 0;

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'pimpinan';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'pimpinan';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'akademik';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// ==========================================
// PENGATURAN PARAMETER & FILTER
// ==========================================
$batas   = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$mulai   = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$kategori      = isset($_GET['kategori']) ? $_GET['kategori'] : 'mhs';
$search        = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_jenis  = isset($_GET['id_jenis']) ? mysqli_real_escape_string($koneksi, trim($_GET['id_jenis'])) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, trim($_GET['status'])) : '';

$data_get = $_GET;
unset($data_get['page']);
$query_string = !empty($data_get) ? '&' . http_build_query($data_get) : '';

$where_clause = " WHERE 1=1 ";

$where_clause .= " AND (
    sp.status_akhir LIKE '%Wadek%' OR 
    sp.status_akhir LIKE '%Dekan%' OR 
    sp.status_akhir LIKE '%Kasubbag%' OR 
    sp.status_akhir LIKE '%Penomoran%' OR 
    sp.status_akhir LIKE '%Penjadwalan%' OR 
    sp.status_akhir = 'Selesai' OR 
    sp.status_akhir = 'Ditolak Pimpinan' OR 
    sp.status_pimpinan IN ('Disetujui', 'Ditolak')
)";

if ($kategori == 'ormawa') {
    $where_clause .= " AND sp.id_ormawa IS NOT NULL ";
} else {
    $where_clause .= " AND sp.id_mhs IS NOT NULL ";
}

if (!empty($search)) {
    $where_clause .= " AND (
        m.nama_mhs LIKE '%$search%' OR 
        m.npm LIKE '%$search%' OR 
        o.nama_ormawa LIKE '%$search%' OR 
        js.nama_surat LIKE '%$search%' OR 
        sp.status_akhir LIKE '%$search%' OR 
        sp.nomor_surat LIKE '%$search%'
    )";
}

if (!empty($filter_jenis)) {
    $where_clause .= " AND sp.id_jenis = '$filter_jenis' ";
}

if (!empty($filter_status)) {
    if ($filter_status == 'Proses') {
        $where_clause .= " AND sp.status_akhir != 'Selesai' AND LOWER(sp.status_akhir) NOT LIKE '%tolak%' ";
    } elseif ($filter_status == 'Selesai') {
        $where_clause .= " AND sp.status_akhir = 'Selesai' ";
    } elseif ($filter_status == 'Ditolak') {
        $where_clause .= " AND (LOWER(sp.status_akhir) LIKE '%tolak%' OR sp.status_pimpinan = 'Ditolak') ";
    }
}

$kategori_condition = ($kategori == 'ormawa') ? " AND sp.id_ormawa IS NOT NULL " : " AND sp.id_mhs IS NOT NULL ";
$q_jenis = mysqli_query($koneksi, "
    SELECT DISTINCT js.id_jenis, js.nama_surat 
    FROM jenis_surat js
    JOIN surat_pengajuan sp ON js.id_jenis = sp.id_jenis
    WHERE 1=1 $kategori_condition
    ORDER BY js.nama_surat ASC
");

$query_hitung = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON js.id_jenis = sp.id_jenis
    $where_clause
");
$row_hitung = mysqli_fetch_assoc($query_hitung);
$total_data = $row_hitung['total'];
$total_halaman = ceil($total_data / $batas);

$query_tracking = mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        o.nama_ormawa,
        js.nama_surat
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON js.id_jenis = sp.id_jenis
    $where_clause
    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $mulai, $batas
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Surat FST</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="pimpinan_beranda.php#home">Beranda</a>
            <a href="pimpinan_verif.php">Disposisi & Verifikasi</a>
            <a href="pimpinan_beranda.php#riwayat">Informasi</a>
            <a href="pimpinan_riwayat.php">Riwayat Verifikasi</a>

            <div class="nav-dropdown">
                <a href="#" class="<?= basename($_SERVER['PHP_SELF']) == 'pimpinan_tracking.php' ? 'active' : ''; ?>">
                    Tracking <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <div class="dropdown-content">
                    <a href="pimpinan_tracking.php?kategori=akademik">Surat Akademik</a>
                    <a href="pimpinan_tracking.php?kategori=ormawa">Surat Organisasi</a>
                </div>
            </div>
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

                    <div class="divider"></div>

                    <a href="logout.php" class="logout-btn" onclick="confirmLogout(event, this.href)">
                        <span>Keluar</span>
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section id="daftar-surat" class="daftar-surat">
        <div class="daftar-surat-header">
            <h2>Tracking Surat <?= ($kategori == 'ormawa') ? 'Organisasi' : 'Akademik'; ?><br>Fakultas Sains dan Teknologi</h2>
        </div>

        <div class="table-wrapper" id="template-surat">
            <form method="GET" action="" class="filter-container-sec">
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori); ?>">

                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" id="searchSurat" class="search-input"
                        placeholder="Cari kata kunci surat..."
                        value="<?= htmlspecialchars($search); ?>">
                </div>

                <!-- Filter Jenis Surat -->
                <select name="id_jenis" class="filter-select" onchange="this.form.submit()">
                    <option value="">-- Semua Jenis Surat --</option>
                    <?php while ($j = mysqli_fetch_assoc($q_jenis)): ?>
                        <option value="<?= $j['id_jenis']; ?>" <?= ($filter_jenis == $j['id_jenis']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($j['nama_surat']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Filter Status Berkas -->
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="Proses" <?= ($filter_status == 'Proses') ? 'selected' : ''; ?>>Dalam Proses</option>
                    <option value="Selesai" <?= ($filter_status == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                    <option value="Ditolak" <?= ($filter_status == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                </select>

                <button type="submit" class="btn-filter-submit">Cari</button>

                <?php if (!empty($search) || !empty($filter_jenis) || !empty($filter_status)): ?>
                    <a href="?kategori=<?= urlencode($kategori); ?>" class="btn-filter-reset">Reset</a>
                <?php endif; ?>
            </form>

            <table class="riwayat-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th><?= ($kategori == 'ormawa') ? 'Nama Organisasi' : 'Nama Mahasiswa & NPM'; ?></th>
                        <th>Jenis Surat</th>
                        <th>Status Akhir</th>
                        <th>Posisi Saat Ini</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query_tracking && mysqli_num_rows($query_tracking) > 0) { ?>
                        <?php
                        $no = $mulai + 1;
                        while ($row = mysqli_fetch_assoc($query_tracking)) {
                            $warna = '#f59e0b';
                            if ($row['status_akhir'] == 'Selesai') {
                                $warna = '#10b981';
                            } elseif (strpos($row['status_akhir'], 'Ditolak') !== false) {
                                $warna = '#ef4444';
                            }

                            if ($kategori == 'ormawa') {
                                $pemohon_teks = '<b>' . htmlspecialchars($row['nama_ormawa'] ?? '-') . '</b>';
                            } else {
                                $pemohon_teks = '<b>' . htmlspecialchars($row['nama_mhs'] ?? '-') . '</b><br><small class="text-muted-sec">NPM. ' . htmlspecialchars($row['npm'] ?? '-') . '</small>';
                            }

                            $status_akhir_lower = strtolower($row['status_akhir']);
                            $posisi_saat_ini = $row['posisi_sekarang'];

                            if ($row['status_akhir'] == 'Selesai') {
                                $posisi_saat_ini = 'Selesai';
                            } elseif (strpos($status_akhir_lower, 'ditolak') !== false) {
                                $posisi_saat_ini = 'Ditolak';
                            } elseif (empty($posisi_saat_ini)) {
                                $posisi_saat_ini = $row['status_akhir'];
                            }
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><?= $pemohon_teks; ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td>
                                    <span class="badge-riwayat" style="background:<?= $warna; ?>;">
                                        <?= htmlspecialchars($row['status_akhir']); ?>
                                    </span>
                                </td>
                                <td><b><?= htmlspecialchars($posisi_saat_ini); ?></b></td>
                                <td>
                                    <a href="pimpinan_detail.php?id=<?= $row['id_surat']; ?>&asal=tracking" class="btn-aksi">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" class="empty-table-cell">
                                <i class="fa-solid fa-folder-open"></i>
                                <p>Tidak ada data tracking yang sesuai filter.</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if ($total_halaman > 1): ?>
                <div class="pagination-container">
                    <ul class="pagination">
                        <?php if ($halaman > 1): ?>
                            <li><a href="?page=<?= $halaman - 1 ?><?= $query_string ?>">Sebelumnya</a></li>
                        <?php else: ?>
                            <li class="disabled"><span>Sebelumnya</span></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <?php if ($i == $halaman): ?>
                                <li class="active"><span><?= $i ?></span></li>
                            <?php else: ?>
                                <li><a href="?page=<?= $i ?><?= $query_string ?>"><?= $i ?></a></li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($halaman < $total_halaman): ?>
                            <li><a href="?page=<?= $halaman + 1 ?><?= $query_string ?>">Selanjutnya</a></li>
                        <?php else: ?>
                            <li class="disabled"><span>Selanjutnya</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
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

        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
        document.getElementById('my-hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.my-navbar-nav')?.classList.toggle('active');
        });

        function confirmLogout(event, url) {
            event.preventDefault();

            Swal.fire({
                title: 'Yakin ingin keluar?',
                text: 'Anda harus login kembali untuk mengakses layanan akademik.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                heightAuto: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    </script>
</body>

</html>