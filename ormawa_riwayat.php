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

$limit = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($halaman - 1) * $limit;

$where_clause = "WHERE sp.id_ormawa = '$id_ormawa' AND sp.status_keputusan != 'Menunggu'";

if (!empty($search)) {
    $where_clause .= " AND (sp.nomor_surat LIKE '%$search_escape%' OR js.nama_surat LIKE '%$search_escape%' OR dpr.nama_kegiatan LIKE '%$search_escape%')";
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

$query_string = !empty($search) ? '&search=' . urlencode($search) : '';

$query_riwayat = mysqli_query($koneksi, "
    SELECT
        sp.id_surat,
        sp.nomor_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir,
        sp.status_keputusan,
        js.nama_surat,
        dpr.nama_kegiatan AS kegiatan_gedung,
        dpr.ruangan_yang_diajukan,
        dpr.tanggal_mulai,
        COALESCE(dpr.catatan, dpd.catatan) AS catatan
    FROM surat_pengajuan sp
    JOIN jenis_surat js
    ON sp.id_jenis = js.id_jenis

    LEFT JOIN detail_peminjaman_ruangan dpr
    ON sp.id_surat = dpr.id_surat

    LEFT JOIN detail_pengajuan_dana dpd
    ON sp.id_surat = dpd.id_surat

    $where_clause

    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $limit OFFSET $offset
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
            <a href="ormawa_lacak.php">Lacak Surat</a>
            <a href="ormawa_riwayat.php">Riwayat Permohonan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>
                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap) ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section id="daftar-surat" class="daftar-surat">
        <div class="daftar-surat-header">
            <h2>Riwayat Pengajuan Surat</h2>
        </div>

        <div class="table-wrapper" id="template-surat">
            <form method="GET" action="" class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="search" id="searchSurat" class="search-input"
                    placeholder="Cari nomor surat atau jenis surat..."
                    value="<?= htmlspecialchars($search); ?>">
                <button type="submit"></button>
            </form>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal & Waktu</th>
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
                            $status = $row['status_akhir'];

                            if ($status == 'Selesai') {
                                $badge_class = 'status-selesai';
                            } elseif (strpos($status, 'Ditolak') !== false) {
                                $badge_class = 'status-ditolak';
                            } else {
                                $badge_class = 'status-proses';
                            }

                            $isDitolak = (strpos(strtolower($status), 'ditolak') !== false);
                            $isDana = (strpos(strtolower($row['nama_surat']), 'dana') !== false);
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><?= !empty($row['nomor_surat']) ? htmlspecialchars($row['nomor_surat']) : '<span class="text-muted">-</span>'; ?></td>
                                <td><b><?= htmlspecialchars($row['nama_surat']); ?></b></td>

                                <td>
                                    <span class="badge-status <?= $badge_class; ?>">
                                        <?= htmlspecialchars($status); ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="ormawa_riwayat_detail.php?id=<?= $row['id_surat']; ?>" class="btn-aksi">Detail</a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada riwayat permohonan surat yang ditemukan.
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

        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
    </script>
</body>

</html>