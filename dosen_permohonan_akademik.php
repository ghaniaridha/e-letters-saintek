<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='index.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'] ?? 0;

$is_pembina_ukm = false;
$cek_pembina = mysqli_query($koneksi, "SELECT id_ormawa FROM ormawa WHERE id_pembina = '$id_dosen'");
if ($cek_pembina && mysqli_num_rows($cek_pembina) > 0) {
    $is_pembina_ukm = true;
}

$is_kaprodi = false;
$cek_kaprodi = mysqli_query($koneksi, "SELECT id_prodi FROM prodi WHERE id_kaprodi = '$id_dosen'");
if ($cek_kaprodi && mysqli_num_rows($cek_kaprodi) > 0) {
    $is_kaprodi = true;
}

$punya_akses_ormawa = ($is_pembina_ukm || $is_kaprodi);

$is_pembina_akademik = false;
$cek_akademik = mysqli_query($koneksi, "SELECT id_surat FROM detail_surat_riset WHERE id_pb1 = '$id_dosen' OR id_pb2 = '$id_dosen' LIMIT 1");
if ($cek_akademik && mysqli_num_rows($cek_akademik) > 0) {
    $is_pembina_akademik = true;
}

$punya_keduanya = ($is_pembina_akademik && $punya_akses_ormawa);


$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Dosen';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Dosen';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$limit = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($halaman - 1) * $limit;

$search     = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_jenis = isset($_GET['id_jenis']) ? mysqli_real_escape_string($koneksi, trim($_GET['id_jenis'])) : '';

$query_params = [];
if (!empty($search)) $query_params['search'] = $search;
if (!empty($filter_jenis)) $query_params['id_jenis'] = $filter_jenis;

$query_string = "";
if (!empty($query_params)) {
    $query_string = "&" . http_build_query($query_params);
}

$where_clause = "
    WHERE
    (
        (dsr.id_pb2 = '$id_dosen' AND dsr.status_pb2 = 'Menunggu')
        OR
        (dsr.id_pb1 = '$id_dosen' AND dsr.status_pb2 = 'Disetujui' AND dsr.status_pb1 = 'Menunggu')
        OR
        (dak.id_pa = '$id_dosen' AND dak.status_pa = 'Menunggu')
    )
";

if (!empty($search)) {
    $where_clause .= " AND (m.nama_mhs LIKE '%$search%' OR m.npm LIKE '%$search%') ";
}
if (!empty($filter_jenis)) {
    $where_clause .= " AND sp.id_jenis = '$filter_jenis' ";
}

$query_count = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    $where_clause
");
$row_count = mysqli_fetch_assoc($query_count);
$total_data = $row_count['total'];
$total_halaman = ceil($total_data / $limit);

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
        dak.id_pa,          
        dak.status_pa       
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    $where_clause
    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $limit OFFSET $offset
");

$q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_surat ORDER BY nama_surat ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Permohonan Akademik</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="dosen-page">
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo"><img src="images/LOGO2.png" alt="navbar-logo"></a>

        <div class="navbar-nav">
            <a href="dosen_beranda.php#home">Beranda</a>

            <?php if ($punya_keduanya): ?>
                <div class="nav-dropdown">
                    <a href="#" class="navbar-nav">Verifikasi Permohonan<i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="dosen_permohonan_akademik.php">Akademik</a>
                        <a href="dosen_permohonan_ormawa.php">Ormawa</a>
                    </div>
                </div>
            <?php elseif ($punya_akses_ormawa): ?>
                <a href="dosen_permohonan_ormawa.php" class="navbar-nav">Verifikasi Permohonan</a>
            <?php else: ?>
                <a href="dosen_permohonan_akademik.php" class="navbar-nav">Verifikasi Permohonan</a>
            <?php endif; ?>

            <a href="dosen_beranda.php#riwayat">Informasi Persuratan</a>

            <?php if ($punya_keduanya): ?>
                <div class="nav-dropdown">
                    <a href="#" class="navbar-nav">Riwayat Verifikasi<i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="dosen_riwayat_akademik.php">Akademik</a>
                        <a href="dosen_riwayat_ormawa.php">Ormawa</a>
                    </div>
                </div>
            <?php elseif ($punya_akses_ormawa): ?>
                <a href="dosen_riwayat_ormawa.php" class="navbar-nav">Riwayat Verifikasi</a>
            <?php else: ?>
                <a href="dosen_riwayat_akademik.php" class="navbar-nav">Riwayat Verifikasi</a>
            <?php endif; ?>
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
        <div class="riwayat-permohonan-header">
            <h2>Verifikasi Surat Permohonan Mahasiswa</h2>
        </div>
        <div class="table-wrapper" id="template-surat">
            <form method="GET" action="" class="filter-container">
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" class="search-input" placeholder="Cari Nama Mahasiswa atau NPM..." value="<?= htmlspecialchars($search); ?>">
                </div>

                <select name="id_jenis" class="filter-select" onchange="this.form.submit()">
                    <option value="">-- Semua Jenis Surat --</option>
                    <?php while ($j = mysqli_fetch_assoc($q_jenis)): ?>
                        <option value="<?= $j['id_jenis']; ?>" <?= ($filter_jenis == $j['id_jenis']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($j['nama_surat']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" class="btn-filter-submit">Cari</button>

                <?php if (!empty($search) || !empty($filter_jenis)): ?>
                    <a href="?" class="btn-filter-reset">Reset</a>
                <?php endif; ?>
            </form>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Waktu & Tanggal</th>
                        <th>Mahasiswa</th>
                        <th>NPM</th>
                        <th>Jenis Surat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query && mysqli_num_rows($query) > 0) {
                        $no = $offset + 1;
                        while ($row = mysqli_fetch_assoc($query)) { ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                <td><?= htmlspecialchars($row['npm']); ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td><?= htmlspecialchars($row['status_akhir']); ?></td>
                                <td>
                                    <a href="dosen_detail_permohonan.php?id=<?= $row['id_surat']; ?>&asal=akademik" class="btn-aksi">Detail</a>
                                </td>
                            </tr>
                        <?php }
                    } else { ?>
                        <tr>
                            <td colspan="7" class="empty-table-cell">
                                <i class="fa-solid fa-folder-open"></i>
                                <p>Tidak ada permohonan akademik yang sesuai dengan filter.</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if (isset($total_halaman) && $total_halaman > 0): ?>
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

        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
    </script>
</body>

</html>