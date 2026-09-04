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

// Variabel penentu pimpinan mengurus Ormawa atau Akademik
$is_ormawa_role = false;

if (strpos($jabatan, 'wadek 1') !== false || strpos($jabatan, 'wakil dekan 1') !== false) {
    $status_target = 'Menunggu Wadek 1';
} elseif (strpos($jabatan, 'wadek 2') !== false || strpos($jabatan, 'wakil dekan 2') !== false) {
    $status_target = 'Menunggu Disposisi Wadek 2';
    $is_ormawa_role = true;
} elseif (strpos($jabatan, 'dekan') !== false) {
    $status_target = 'Menunggu Dekan';
} elseif (strpos($jabatan, 'kasubag') !== false || strpos($jabatan, 'kasubbag') !== false) {
    $status_target = 'Menunggu Disposisi Kasubbag TU';
    $is_ormawa_role = true;
} else {
    $status_target = '';
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$search_sql = "";

// ==========================================
// PENGATURAN PAGINASI & PARAMETER FILTER
// ==========================================
$batas   = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$mulai   = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$search       = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_jenis = isset($_GET['id_jenis']) ? mysqli_real_escape_string($koneksi, trim($_GET['id_jenis'])) : '';
$filter_prodi = isset($_GET['id_prodi']) ? mysqli_real_escape_string($koneksi, trim($_GET['id_prodi'])) : '';

$data_get = $_GET;
unset($data_get['page']);
$query_string = !empty($data_get) ? '&' . http_build_query($data_get) : '';

// Query untuk Dropdown Filter Jenis Surat
$q_jenis = mysqli_query($koneksi, "
    SELECT DISTINCT js.id_jenis, js.nama_surat 
    FROM jenis_surat js
    JOIN surat_pengajuan sp ON js.id_jenis = sp.id_jenis
    WHERE sp.status_akhir = '$status_target'
    ORDER BY js.nama_surat ASC
");

// Query untuk Dropdown Filter Prodi (Khusus Akademik)
if (!$is_ormawa_role) {
    $q_prodi = mysqli_query($koneksi, "
        SELECT DISTINCT p.id_prodi, p.nama_prodi 
        FROM prodi p
        JOIN mahasiswa m ON p.id_prodi = m.id_prodi
        JOIN surat_pengajuan sp ON m.id_mhs = sp.id_mhs
        WHERE sp.status_akhir = '$status_target'
        ORDER BY p.nama_prodi ASC
    ");
}

// LOGIKA QUERY SQL & FILTER
if ($is_ormawa_role) {
    // ---- QUERY KHUSUS WADEK 2 & KASUBBAG TU (ORMAWA) ----
    $where_sql = " WHERE sp.status_akhir = '$status_target' ";

    if (!empty($search)) {
        $where_sql .= " AND (o.nama_ormawa LIKE '%$search%' OR js.nama_surat LIKE '%$search%') ";
    }
    if (!empty($filter_jenis)) {
        $where_sql .= " AND sp.id_jenis = '$filter_jenis' ";
    }

    // 1. Hitung total data
    $query_hitung = mysqli_query($koneksi, "
        SELECT COUNT(*) AS total
        FROM surat_pengajuan sp
        JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        $where_sql 
    ");
    $row_hitung = mysqli_fetch_assoc($query_hitung);
    $total_data = $row_hitung['total'];
    $total_halaman = ceil($total_data / $batas);

    // 2. Ambil data dengan LIMIT
    $query = mysqli_query($koneksi, "
        SELECT 
            sp.id_surat, sp.id_jenis, sp.tanggal_pengajuan, sp.status_akhir,
            o.nama_ormawa, js.nama_surat
        FROM surat_pengajuan sp
        JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        $where_sql 
        ORDER BY sp.tanggal_pengajuan ASC
        LIMIT $mulai, $batas
    ");
} else {
    // ---- QUERY KHUSUS WADEK 1 & DEKAN (AKADEMIK) ----
    $where_sql = " WHERE sp.status_akhir = '$status_target' ";

    if (!empty($search)) {
        $where_sql .= " AND (m.nama_mhs LIKE '%$search%' OR m.npm LIKE '%$search%' OR p.nama_prodi LIKE '%$search%' OR js.nama_surat LIKE '%$search%') ";
    }
    if (!empty($filter_jenis)) {
        $where_sql .= " AND sp.id_jenis = '$filter_jenis' ";
    }
    if (!empty($filter_prodi)) {
        $where_sql .= " AND m.id_prodi = '$filter_prodi' ";
    }

    // 1. Hitung total data
    $query_hitung = mysqli_query($koneksi, "
        SELECT COUNT(*) AS total
        FROM surat_pengajuan sp
        JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
        JOIN prodi p ON m.id_prodi = p.id_prodi 
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        $where_sql 
    ");
    $row_hitung = mysqli_fetch_assoc($query_hitung);
    $total_data = $row_hitung['total'];
    $total_halaman = ceil($total_data / $batas);

    // 2. Ambil data dengan LIMIT
    $query = mysqli_query($koneksi, "
        SELECT 
            sp.id_surat, sp.id_jenis, sp.tanggal_pengajuan, sp.status_akhir,
            m.nama_mhs, m.npm, p.nama_prodi, js.nama_surat
        FROM surat_pengajuan sp
        JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
        JOIN prodi p ON m.id_prodi = p.id_prodi 
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        $where_sql 
        ORDER BY sp.tanggal_pengajuan ASC
        LIMIT $mulai, $batas
    ");
}
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <h2>Disposisi & Verifikasi Permohonan</h2>
        </div>

        <div class="table-wrapper" id="template-surat">
            <form method="GET" action="" class="filter-container-sec">

                <!-- Kotak Pencarian -->
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" id="searchSurat" class="search-input"
                        placeholder="Cari kata kunci..."
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

                <!-- Filter Prodi (Hanya muncul jika role Akademik) -->
                <?php if (!$is_ormawa_role): ?>
                    <select name="id_prodi" class="filter-select" onchange="this.form.submit()">
                        <option value="">-- Semua Prodi --</option>
                        <?php while ($pr = mysqli_fetch_assoc($q_prodi)): ?>
                            <option value="<?= $pr['id_prodi']; ?>" <?= ($filter_prodi == $pr['id_prodi']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($pr['nama_prodi']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                <?php endif; ?>

                <!-- Tombol Aksi -->
                <button type="submit" class="btn-filter-submit">Cari</button>

                <?php if (!empty($search) || !empty($filter_jenis) || !empty($filter_prodi)): ?>
                    <a href="?" class="btn-filter-reset">Reset</a>
                <?php endif; ?>

            </form>

            <div class="riwayat-table">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal & Waktu Pengajuan</th>

                            <?php if ($is_ormawa_role): ?>
                                <th>Nama Organisasi</th>
                            <?php else: ?>
                                <th>Mahasiswa</th>
                                <th>NPM</th>
                                <th>Prodi</th>
                            <?php endif; ?>

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

                                    <?php if ($is_ormawa_role): ?>
                                        <td><b><?= htmlspecialchars($row['nama_ormawa']); ?></b></td>
                                    <?php else: ?>
                                        <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                        <td><?= htmlspecialchars($row['npm']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                                    <?php endif; ?>

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
                                <td colspan="<?= $is_ormawa_role ? '6' : '8'; ?>" class="empty-table-cell">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <p>Tidak ada surat yang menunggu disposisi/verifikasi Anda.</p>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

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

        // Fungsi untuk menampilkan konfirmasi sebelum logout
        function confirmLogout(event, url) {
            event.preventDefault();
            Swal.fire({
                title: 'Yakin ingin keluar?',
                text: "Anda harus masuk kembali untuk mengakses halaman ini.",
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

        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
    </script>
</body>

</html>