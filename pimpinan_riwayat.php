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

$query_dosen = mysqli_query($koneksi, "SELECT jabatan FROM dosen WHERE id_dosen = '$id_dosen' LIMIT 1");
$data_dosen = mysqli_fetch_assoc($query_dosen);
$jabatan_pimpinan = strtolower($data_dosen['jabatan'] ?? '');
$jabatan_lower = strtolower($jabatan_pimpinan);

$filter_jabatan = "";
$is_ormawa_role = false;

if (strpos($jabatan_lower, 'wadek 2') !== false || strpos($jabatan_lower, 'wakil dekan 2') !== false || strpos($jabatan_lower, 'kasubag') !== false || strpos($jabatan_lower, 'kasubbag') !== false) {
    $is_ormawa_role = true;
    $filter_jabatan = " AND (LOWER(js.nama_surat) LIKE '%dana%' OR LOWER(js.nama_surat) LIKE '%ruangan%')";
} else {
    if (strpos($jabatan_lower, 'dekan') !== false && strpos($jabatan_lower, 'wakil') === false && strpos($jabatan_lower, 'wadek') === false) {
        $filter_jabatan = " AND (LOWER(js.nama_surat) NOT LIKE '%magang%' 
                                  AND LOWER(js.nama_surat) NOT LIKE '%pkl%' 
                                  AND LOWER(js.nama_surat) NOT LIKE '%riset%' 
                                  AND LOWER(js.nama_surat) NOT LIKE '%aktif%'
                                  AND LOWER(js.nama_surat) NOT LIKE '%lulus%' 
                                  AND LOWER(js.nama_surat) NOT LIKE '%masih kuliah%'
                                  AND LOWER(js.nama_surat) NOT LIKE '%skmk%'
                                  AND LOWER(js.nama_surat) NOT LIKE '%dana%'
                                  AND LOWER(js.nama_surat) NOT LIKE '%ruangan%')";
    } else {
        $filter_jabatan = " AND (LOWER(js.nama_surat) LIKE '%magang%' 
                                  OR LOWER(js.nama_surat) LIKE '%pkl%' 
                                  OR LOWER(js.nama_surat) LIKE '%riset%' 
                                  OR LOWER(js.nama_surat) LIKE '%aktif%'
                                  OR LOWER(js.nama_surat) LIKE '%lulus%'
                                  OR LOWER(js.nama_surat) LIKE '%masih kuliah%'
                                  OR LOWER(js.nama_surat) LIKE '%skmk%')";
    }
}

$batas   = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$mulai   = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$search        = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_jenis  = isset($_GET['id_jenis']) ? mysqli_real_escape_string($koneksi, trim($_GET['id_jenis'])) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, trim($_GET['status'])) : '';

$where_extra = "";

if (!empty($search)) {
    $where_extra .= " AND (
        m.nama_mhs LIKE '%$search%' OR 
        m.npm LIKE '%$search%' OR 
        p.nama_prodi LIKE '%$search%' OR 
        o.nama_ormawa LIKE '%$search%' OR
        js.nama_surat LIKE '%$search%' OR
        sp.status_akhir LIKE '%$search%'
    )";
}

if (!empty($filter_jenis)) {
    $where_extra .= " AND sp.id_jenis = '$filter_jenis'";
}

if (!empty($filter_status)) {
    if ($filter_status == 'Disetujui') {
        $where_extra .= " AND (sp.status_pimpinan = 'Disetujui' OR sp.status_akhir = 'Selesai')";
    } elseif ($filter_status == 'Ditolak') {
        $where_extra .= " AND (sp.status_pimpinan = 'Ditolak' OR LOWER(sp.status_akhir) LIKE '%tolak%')";
    }
}

$query_params = [];
if (!empty($search)) $query_params['search'] = $search;
if (!empty($filter_jenis)) $query_params['id_jenis'] = $filter_jenis;
if (!empty($filter_status)) $query_params['status'] = $filter_status;

$query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';

$query_hitung = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    WHERE (
        sp.status_pimpinan IN ('Disetujui', 'Ditolak')
        OR (sp.ttd_pimpinan IS NOT NULL AND sp.ttd_pimpinan != '')
        OR sp.status_akhir LIKE '%Penjadwalan%'
        OR sp.status_akhir = 'Selesai'
    )
    $filter_jabatan
    $where_extra
");
$row_hitung = mysqli_fetch_assoc($query_hitung);
$total_data = $row_hitung['total'];
$total_halaman = ceil($total_data / $batas);

$query_riwayat = mysqli_query($koneksi, "
    SELECT
        sp.*, m.nama_mhs, m.npm, p.nama_prodi, o.nama_ormawa, js.nama_surat,
        dpr.tanggal_mulai as tgl_ruangan, dpr.jam_mulai as jam_ruangan,
        dpd.tanggal_jadwal as tgl_dana, dpd.jam_mulai as jam_dana
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    WHERE (
        sp.status_pimpinan IN ('Disetujui', 'Ditolak')
        OR (sp.ttd_pimpinan IS NOT NULL AND sp.ttd_pimpinan != '')
        OR sp.status_akhir LIKE '%Penjadwalan%'
        OR sp.status_akhir = 'Selesai'
    )
    $filter_jabatan
    $where_extra
    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $mulai, $batas
");

$q_jenis = mysqli_query($koneksi, "
    SELECT DISTINCT js.id_jenis, js.nama_surat 
    FROM jenis_surat js
    JOIN surat_pengajuan sp ON js.id_jenis = sp.id_jenis
    WHERE 1=1 $filter_jabatan
    ORDER BY js.nama_surat ASC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Disposisi & Verifikasi</title>

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
            <h2>Riwayat Disposisi & Verifikasi</h2>
        </div>

        <div class="table-wrapper" id="template-surat">
            <form method="GET" action="" class="filter-container-sec">
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" id="searchSurat" class="search-input"
                        placeholder="Cari nama, NPM, prodi, atau jenis surat..."
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

                <!-- Filter Status Keputusan -->
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">-- Semua Keputusan --</option>
                    <option value="Disetujui" <?= ($filter_status == 'Disetujui') ? 'selected' : ''; ?>>Disetujui / Selesai</option>
                    <option value="Ditolak" <?= ($filter_status == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                </select>

                <button type="submit" class="btn-filter-submit">Cari</button>

                <?php if (!empty($search) || !empty($filter_jenis) || !empty($filter_status)): ?>
                    <a href="?" class="btn-filter-reset">Reset</a>
                <?php endif; ?>
            </form>

            <table class="riwayat-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal & Waktu</th>
                        <?php if ($is_ormawa_role): ?>
                            <th>Nama Organisasi</th>
                        <?php else: ?>
                            <th>Mahasiswa</th>
                            <th>NPM</th>
                            <th>Prodi</th>
                        <?php endif; ?>
                        <th>Jenis Surat</th>
                        <th>Status Akhir</th>
                        <th><?= $is_ormawa_role ? 'Keterangan' : 'File Final'; ?></th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) { ?>
                        <?php
                        $no = $mulai + 1;
                        while ($row = mysqli_fetch_assoc($query_riwayat)) {
                            $warna = '#0d6efd';

                            if (strpos($row['status_akhir'], 'Ditolak') !== false) {
                                $warna = '#ef4444';
                            } elseif ($row['status_akhir'] != 'Selesai') {
                                $warna = '#f59e0b';
                            }

                            $isOrmawa = !empty($row['id_ormawa']);
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>

                                <?php if ($is_ormawa_role): ?>
                                    <td><b><?= htmlspecialchars($row['nama_ormawa'] ?? '-'); ?></b></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($row['nama_mhs'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['npm'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?></td>
                                <?php endif; ?>

                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td>
                                    <span class="badge-riwayat" style="background:<?= $warna; ?>;">
                                        <?= htmlspecialchars($row['status_akhir']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_ket = $row['status_pimpinan'];

                                    if (empty($status_ket)) {
                                        if ($row['status_akhir'] == 'Selesai') {
                                            $status_ket = 'Disetujui';
                                        } else {
                                            $status_ket = 'Belum Disetujui';
                                        }
                                    }

                                    $warna_ket = '#94a3b8';
                                    if (strtolower($status_ket) == 'disetujui') {
                                        $warna_ket = '#10b981';
                                    } elseif (strtolower($status_ket) == 'ditolak') {
                                        $warna_ket = '#ef4444';
                                    }

                                    $isDisetujui = (strtolower($status_ket) == 'disetujui');

                                    if (!$is_ormawa_role && $isDisetujui && !$isOrmawa) {
                                        $namaSurat = strtolower($row['nama_surat']);

                                        if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                                            $linkUnduh = "generate_surat_magang_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=riwayat_pimpinan";
                                        } elseif (strpos($namaSurat, 'aktif') !== false) {
                                            $linkUnduh = "generate_sk_aktif_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=riwayat_pimpinan";
                                        } else {
                                            $linkUnduh = "generate_surat_riset_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=riwayat_pimpinan";
                                        }
                                    ?>
                                        <a href="<?= $linkUnduh; ?>" target="_blank" class="btn-file-surat">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </a>
                                    <?php } else { ?>
                                        <span class="badge-riwayat" style="background:<?= $warna_ket; ?>;">
                                            <?= htmlspecialchars($status_ket); ?>
                                        </span>

                                        <?php
                                        if ($isDisetujui && !$isOrmawa) {
                                            $namaSurat = strtolower($row['nama_surat']);
                                            if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                                                $linkUnduh = "generate_surat_magang_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=riwayat_pimpinan";
                                            } elseif (strpos($namaSurat, 'aktif') !== false) {
                                                $linkUnduh = "generate_sk_aktif_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=riwayat_pimpinan";
                                            } else {
                                                $linkUnduh = "generate_surat_riset_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=riwayat_pimpinan";
                                            }
                                        ?>
                                            <div style="margin-top: 8px;">
                                                <a href="<?= $linkUnduh; ?>" target="_blank" class="btn-file-surat">
                                                    <i class="fa-solid fa-file-lines"></i>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="pimpinan_detail.php?id=<?= $row['id_surat']; ?>&asal=riwayat" class="btn-aksi">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="<?= $is_ormawa_role ? '8' : '10'; ?>" class="empty-table-cell">
                                <i class="fa-solid fa-folder-open"></i>
                                <p>Belum ada riwayat disposisi/verifikasi yang sesuai filter.</p>
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
        document.getElementById('my-hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.my-navbar-nav')?.classList.toggle('active');
        });
    </script>
</body>

</html>