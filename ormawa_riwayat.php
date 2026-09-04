<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_ormawa'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_ormawa = $_SESSION['id_ormawa'];

$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_escape = mysqli_real_escape_string($koneksi, $search);

$limit   = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset  = ($halaman - 1) * $limit;

$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_jenis  = isset($_GET['id_jenis']) ? trim($_GET['id_jenis']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

$search_escape        = mysqli_real_escape_string($koneksi, $search);
$filter_jenis_escape  = mysqli_real_escape_string($koneksi, $filter_jenis);
$filter_status_escape = mysqli_real_escape_string($koneksi, $filter_status);

$query_params = [];
if (!empty($search)) $query_params['search'] = $search;
if (!empty($filter_jenis)) $query_params['id_jenis'] = $filter_jenis;
if (!empty($filter_status)) $query_params['status'] = $filter_status;

$query_string = "";
if (!empty($query_params)) {
    $query_string = "&" . http_build_query($query_params);
}

$where_clause = "WHERE sp.id_ormawa = '$id_ormawa'";

if (!empty($search)) {
    $where_clause .= " AND (sp.nomor_surat LIKE '%$search_escape%' OR js.nama_surat LIKE '%$search_escape%' OR dpr.nama_kegiatan LIKE '%$search_escape%' OR sp.kode_pelacakan LIKE '%$search_escape%')";
}

if (!empty($filter_jenis)) {
    $where_clause .= " AND sp.id_jenis = '$filter_jenis_escape'";
}

if (!empty($filter_status)) {
    if ($filter_status == 'Selesai') {
        $where_clause .= " AND (sp.status_akhir = 'Selesai' OR LOWER(sp.status_keputusan) = 'disetujui')";
    } elseif ($filter_status == 'Ditolak') {
        $where_clause .= " AND (LOWER(sp.status_akhir) LIKE '%tolak%' OR LOWER(sp.status_akhir) LIKE '%perbaikan%')";
    } elseif ($filter_status == 'Diproses') {
        $where_clause .= " AND sp.status_akhir != 'Selesai' AND LOWER(sp.status_keputusan) != 'disetujui' AND LOWER(sp.status_akhir) NOT LIKE '%tolak%' AND LOWER(sp.status_akhir) NOT LIKE '%perbaikan%'";
    }
}

$query_count = mysqli_query($koneksi, "
    SELECT COUNT(sp.id_surat) as total
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    $where_clause
");
$row_count = mysqli_fetch_assoc($query_count);
$total_data = $row_count['total'];
$total_halaman = ceil($total_data / $limit);

$query_riwayat = mysqli_query($koneksi, "
    SELECT
        sp.id_surat,
        sp.nomor_surat,
        sp.tanggal_pengajuan,
        sp.waktu_verif_pimpinan, 
        sp.status_akhir,
        sp.status_keputusan,
        sp.kode_pelacakan,
        js.nama_surat,
        o.jenis_organisasi,
        dpr.nama_kegiatan AS kegiatan_gedung,
        dpr.ruangan_yang_diajukan,
        dpr.tanggal_mulai,
        COALESCE(dpr.catatan, dpd.catatan) AS catatan
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    $where_clause
    ORDER BY sp.waktu_verif_pimpinan DESC
    LIMIT $limit OFFSET $offset
");

$q_jenis = mysqli_query($koneksi, "
    SELECT DISTINCT js.id_jenis, js.nama_surat 
    FROM jenis_surat js
    JOIN surat_pengajuan sp ON js.id_jenis = sp.id_jenis
    WHERE sp.id_ormawa = '$id_ormawa'
    ORDER BY js.nama_surat ASC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Permohonan Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="ormawa_beranda.php">Beranda</a>
            <a href="ormawa_beranda.php#services">Pengajuan Surat</a>
            <a href="ormawa_beranda.php#status-info">Status & Informasi</a>
            <a href="ormawa_riwayat.php">Riwayat Permohonan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>
                <div id="user-dropdown" class="dropdown-menu">
                    <a href="mhs_profile.php" class="user-info-link-mhs">
                        <div class="user-info-mhs">
                            <span class="user-name-mhs"><?= htmlspecialchars($namaLengkap) ?></span>
                            <span class="user-role-mhs"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                        </div>
                    </a>
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
            <h2>Riwayat Pengajuan Surat</h2>
        </div>

        <div class="table-wrapper" id="template-surat">
            <form method="GET" action="" class="filter-container">
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" id="searchSurat" class="search-input"
                        placeholder="Cari nomor, jenis surat, kegiatan, kode lacak..."
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

                <!-- Filter Status -->
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="Diproses" <?= ($filter_status == 'Diproses') ? 'selected' : ''; ?>>Sedang Diproses</option>
                    <option value="Selesai" <?= ($filter_status == 'Selesai') ? 'selected' : ''; ?>>Selesai / Disetujui</option>
                    <option value="Ditolak" <?= ($filter_status == 'Ditolak') ? 'selected' : ''; ?>>Ditolak / Perbaikan</option>
                </select>

                <button type="submit" class="btn-filter-submit">Cari</button>

                <?php if (!empty($search) || !empty($filter_jenis) || !empty($filter_status)): ?>
                    <a href="?" class="btn-filter-reset">Reset</a>
                <?php endif; ?>
            </form>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal & Waktu</th>
                        <th>Kode Lacak</th>
                        <th>Nomor Surat</th>
                        <th>Jenis Surat</th>
                        <th>Status Akhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) { ?>
                        <?php
                        $no = $offset + 1;
                        while ($row = mysqli_fetch_assoc($query_riwayat)) {
                            $status_asli = $row['status_akhir'];
                            $jenis_org = $row['jenis_organisasi'] ?? 'Ormawa';

                            $tampil_status = $status_asli;
                            if ($jenis_org == 'Ormawa') {
                                $tampil_status = str_ireplace('Pembina', 'Kaprodi', $status_asli);
                            }

                            $status_lower = strtolower($status_asli);

                            if ($status_lower == 'selesai' || strtolower($row['status_keputusan'] ?? '') == 'disetujui') {
                                $badge_class = 'status-selesai';
                            } elseif (strpos($status_lower, 'ditolak') !== false || strpos($status_lower, 'perbaikan') !== false) {
                                $badge_class = 'status-ditolak';
                            } else {
                                $badge_class = 'status-proses';
                            }
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <?php
                                    if (!empty($row['waktu_verif_pimpinan']) && $row['waktu_verif_pimpinan'] !== '0000-00-00 00:00:00') {
                                        echo date('d-m-Y H:i', strtotime($row['waktu_verif_pimpinan'])) . ' WIB';
                                    } else {
                                        echo date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])) . ' WIB';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['kode_pelacakan'])): ?>
                                        <code class="badge-kode-lacak"><?= htmlspecialchars($row['kode_pelacakan']); ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($row['nomor_surat']) ? htmlspecialchars($row['nomor_surat']) : '<span class="text-muted">-</span>'; ?></td>
                                <td><b><?= htmlspecialchars($row['nama_surat']); ?></b></td>
                                <td>
                                    <span class="badge-status <?= $badge_class; ?>">
                                        <?= htmlspecialchars($tampil_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="ormawa_riwayat_detail.php?id=<?= $row['id_surat']; ?>" class="btn-aksi">Detail</a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                Belum ada riwayat permohonan surat yang sesuai filter.
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

        function bukaModal(id) {
            document.getElementById('modalDetail').style.display = 'flex';
            fetch('get_detail_surat.php?id=' + id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('kontenDetail').innerHTML = data;
                });
        }

        function tutupModal() {
            document.getElementById('modalDetail').style.display = 'none';
        }

        function confirmLogout(event, url) {
            event.preventDefault();
            Swal.fire({
                title: 'Yakin ingin keluar?',
                text: "Anda harus login kembali untuk mengakses layanan akademik.",
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