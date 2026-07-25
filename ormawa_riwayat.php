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

$query_riwayat = mysqli_query($koneksi, "
SELECT
    sp.id_surat,
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

WHERE sp.id_ormawa = '$id_ormawa'
AND sp.status_keputusan != 'Menunggu'

ORDER BY sp.tanggal_pengajuan DESC
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
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal & Waktu</th>
                        <th>Jenis Surat</th>
                        <th>Status Akhir</th>
                        <th>File Surat Peangajuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) { ?>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query_riwayat)) {
                            $statusKeputusan = strtolower($row['status_keputusan']);

                            $isDitolak = ($statusKeputusan == 'ditolak');
                            $isDisetujui = ($statusKeputusan == 'disetujui');

                            if ($isDitolak) {
                                $statusTampil = "Ditolak";
                                $badge_class = "badge-danger";
                            } elseif ($isDisetujui) {
                                $statusTampil = "Disetujui";
                                $badge_class = "badge-success";
                            } else {
                                $statusTampil = htmlspecialchars($row['status_akhir']);
                                $badge_class = "badge-warning";
                            }

                            $isDana = (strpos(strtolower($row['nama_surat']), 'dana') !== false);
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><b><?= htmlspecialchars($row['nama_surat']); ?></b></td>

                                <td>
                                    <?php if ($isDana) { ?>
                                        <i class="fa-solid fa-money-bill-wave" style="color: #10b981; margin-right: 4px;"></i> Pengajuan Dana
                                    <?php } else { ?>
                                        <i class="fa-solid fa-building" style="color: #64748b; margin-right: 4px;"></i> <?= htmlspecialchars($row['ruangan_yang_diajukan'] ?? '-'); ?>
                                    <?php } ?>
                                </td>

                                <td><?= !empty($row['tanggal_mulai']) ? date('d-m-Y', strtotime($row['tanggal_mulai'])) : '-'; ?></td>
                                <td><?= htmlspecialchars($row['catatan'] ?? '-'); ?></td>

                                <td>
                                    <span class="badge <?= $badge_class; ?>">
                                        <?= $statusTampil; ?>
                                    </span>
                                </td>

                                <td style="text-align: center;">
                                    <?php if ($isDitolak) { ?>
                                        <span class="text-muted">Tidak Tersedia</span>
                                    <?php } else { ?>
                                        <?php
                                        $linkCetak = "preview_peminjaman_ruangan.php?id=" . $row['id_surat'];

                                        if ($isDana) {
                                            $linkCetak = "preview_pengajuan_dana.php?id=" . $row['id_surat'] . "&mode=view";
                                        }
                                        ?>
                                        <a href="<?= $linkCetak; ?>" target="_blank" class="btn-view">
                                            <i class="fa-solid fa-print"></i> Cetak
                                        </a>
                                    <?php } ?>
                                </td>

                                <td>
                                    <button onclick="bukaModal(<?= $row['id_surat']; ?>)">Detail</button>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada riwayat permohonan surat.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <div id="modalDetail" class="modal-overlay">
        <div class="modal-box">
            <span class="modal-close" onclick="tutupModal()">&times;</span>
            <h3>Detail Pengajuan</h3>
            <div id="kontenDetail">
                Memuat data...
            </div>
        </div>
    </div>

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
        document.getElementById('my-hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.my-navbar-nav')?.classList.toggle('active');
        });
    </script>
</body>

</html>