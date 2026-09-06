<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

// Query search dan pagination
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

$search        = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_jenis  = isset($_GET['id_jenis']) ? mysqli_real_escape_string($koneksi, trim($_GET['id_jenis'])) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, trim($_GET['status'])) : '';

$query_params = [];
if (!empty($search)) $query_params['search'] = $search;
if (!empty($filter_jenis)) $query_params['id_jenis'] = $filter_jenis;
if (!empty($filter_status)) $query_params['status'] = $filter_status;

$query_string = "";
if (!empty($query_params)) {
    $query_string = "&" . http_build_query($query_params);
}

$whereClause = "WHERE sp.id_mhs = '$id_mhs'";

if (!empty($search)) {
    $whereClause .= " AND (sp.kode_pelacakan LIKE '%$search%' OR js.nama_surat LIKE '%$search%')";
}

if (!empty($filter_jenis)) {
    $whereClause .= " AND sp.id_jenis = '$filter_jenis'";
}

if (!empty($filter_status)) {
    if ($filter_status == 'Selesai') {
        $whereClause .= " AND sp.status_akhir = 'Selesai'";
    } elseif ($filter_status == 'Ditolak') {
        $whereClause .= " AND LOWER(sp.status_akhir) LIKE '%tolak%'";
    } elseif ($filter_status == 'Diproses') {
        $whereClause .= " AND sp.status_akhir != 'Selesai' AND LOWER(sp.status_akhir) NOT LIKE '%tolak%'";
    }
}

$count_query = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total 
    FROM surat_pengajuan sp 
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis 
    $whereClause
");

$count_row = mysqli_fetch_assoc($count_query);
$total_data = $count_row['total'];
$total_halaman = ceil($total_data / $limit);
$halaman = $page;

$query_riwayat = mysqli_query($koneksi, "
    SELECT 
        sp.*, 
        js.nama_surat,
        dsr.status_pb1, 
        dsr.status_pb2,
        dak.status_pa
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    $whereClause
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
    <title>Riwayat Pengajuan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if (isset($_SESSION['pesan'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['status']; ?>',
                title: '<?= ($_SESSION['status'] == "success") ? "Berhasil!" : "Gagal!"; ?>',
                text: <?= json_encode($_SESSION['pesan']); ?>,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>
        <?php
        unset($_SESSION['pesan']);
        unset($_SESSION['status']);
        ?>
    <?php endif; ?>

    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="mhs_beranda.php#home">Beranda</a>
            <a href="mhs_beranda.php#services">Pengajuan Surat</a>
            <a href="mhs_beranda.php#status-info">Status & Informasi</a>
            <a href="mhs_riwayat.php">Riwayat Pengajuan</a>
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
                        placeholder="Cari Kode Lacak / Nama Surat..."
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

                <!-- Filter Status Surat -->
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="Diproses" <?= ($filter_status == 'Diproses') ? 'selected' : ''; ?>>Sedang Diproses</option>
                    <option value="Selesai" <?= ($filter_status == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                    <option value="Ditolak" <?= ($filter_status == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
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
                        <th>Jenis Surat</th>
                        <th>Status Akhir</th>
                        <th>File Final</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) { ?>
                        <?php
                        $no = $offset + 1;
                        while ($row = mysqli_fetch_assoc($query_riwayat)) {
                            $tanggal = date('d-m-Y H:i', strtotime($row['tanggal_pengajuan']));
                            $status = $row['status_akhir'];
                            $status_lower = strtolower($status);

                            if ($status == 'Selesai') {
                                $badge_class = 'status-selesai';
                            } elseif (strpos($status_lower, 'tolak') !== false) {
                                $badge_class = 'status-ditolak';
                            } elseif (strpos($status_lower, 'perbaikan') !== false || strpos($status_lower, 'dikembalikan') !== false) {
                                $badge_class = 'status-warning';
                            } else {
                                $badge_class = 'status-proses';
                            }
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $tanggal; ?></td>
                                <td>
                                    <span class="badge-kode-lacak">
                                        <?= htmlspecialchars($row['kode_pelacakan'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>

                                <td>
                                    <span class="badge-status <?= $badge_class; ?>">
                                        <?= htmlspecialchars($status); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($row['file_surat_final'])) { ?>
                                        <?php
                                        $namaSurat = strtolower($row['nama_surat']);

                                        if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                                            $linkUnduh = "generate_surat_magang_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=mhs";
                                        } elseif (strpos($namaSurat, 'aktif') !== false) {
                                            $linkUnduh = "generate_sk_aktif_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=mhs";
                                        } elseif (strpos($namaSurat, 'lulus') !== false) {
                                            $linkUnduh = "generate_sk_lulus_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=mhs";
                                        } elseif (strpos($namaSurat, 'masih aktif') !== false || strpos($namaSurat, 'masih') !== false) {
                                            $linkUnduh = "generate_skmk_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=mhs";
                                        } else {
                                            $linkUnduh = "generate_surat_riset_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=mhs";
                                        }
                                        ?>

                                        <a href="<?= $linkUnduh; ?>" target="_blank" class="btn-file-surat">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </a>

                                    <?php } elseif (strpos(strtolower($row['status_akhir']), 'tolak') !== false) { ?>
                                        <span class="text-rejected">Pengajuan Ditolak</span>
                                    <?php } else { ?>
                                        <span class="text-unavailable">Belum Tersedia</span>
                                    <?php } ?>
                                </td>

                                <td>
                                    <a href="mhs_riwayat_detail.php?id=<?= $row['id_surat']; ?>" class="btn-aksi">Detail</a>
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
        //fungsi dropdown menu user
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