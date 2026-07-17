<?php
session_start();
include "koneksi.php";

// Proteksi halaman: pastikan yang login adalah Ormawa
if (!isset($_SESSION['id_ormawa'])) {
    echo "<script>
            alert('Silakan login terlebih dahulu');
            window.location='index.php';
          </script>";
    exit;
}

$id_ormawa = $_SESSION['id_ormawa'];

// Mengambil data profil Ormawa untuk keperluan inisial avatar
$data_ormawa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ormawa WHERE id_ormawa = '$id_ormawa'"));


// Update Query Anda di bagian atas ormawa_riwayat.php
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
    <title>Riwayat Permohonan Surat Ormawa</title>
    
    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .riwayat-section {
            padding: 120px 7% 80px; 
            min-height: 80vh;
            background-color: #f8fafc;
        }
        .riwayat-header {
            margin-bottom: 30px;
        }
        .riwayat-header h1 {
            font-size: 28px;
            color: #1e293b;
            margin: 0 0 8px 0;
        }
        .riwayat-header p {
            color: #64748b;
            margin: 0;
        }
        .table-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 24px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            padding: 14px 16px;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 16px;
            color: #334155;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 9999px;
            text-align: center;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #0284c7;
            color: #ffffff;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .btn-view:hover {
            background-color: #0369a1;
        }
        .text-muted {
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>

<body>

    <!-- NAVBAR UTAMA -->
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="ormawa_beranda.php">Beranda</a>
            <a href="ormawa_beranda.php#services">Layanan</a>
            <a href="ormawa_beranda.php#status-info">Informasi</a>
            <a href="ormawa_lacak.php">Lacak Surat</a>
            <a href="ormawa_riwayat.php">Riwayat Permohonan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <?php
                $namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
                $idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
                $role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

                $inisial = '';
                $namaParts = explode(' ', $namaLengkap);
                if (!empty($namaParts)) {
                    $inisial = strtoupper(substr($namaParts[0], 0, 1));
                }
                ?>
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>
                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap) ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                    </div>
                    <div class="divider"></div>
                    <a href="logout.php" class="logout-btn" onclick="confirmLogout(event, this.href)">
                        <span>Logout</span>
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- KONTEN UTAMA RIWAYAT UNIVERSAL -->
    <section class="riwayat-section">
        <div class="riwayat-header">
            <h1>Riwayat Pengajuan Surat <?= htmlspecialchars($data_ormawa['nama_ormawa'] ?? 'Organisasi'); ?></h1>
            <p>Daftar seluruh arsip permohonan surat (Peminjaman Gedung & Dana) yang telah selesai diproses.</p>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Jenis Surat</th>
                        <th>Keterangan / Ruangan</th>
                        <th>Tanggal Pelaksanaan</th>
                        <th>Catatan</th>
                        <th>Status Akhir</th>
                        <th width="150" style="text-align: center;">Arsip Dokumen</th>
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
    
    // Tentukan tampilan
    if ($isDitolak) {
        $statusTampil = "Ditolak";
        $badge_class = "badge-danger";
    } elseif ($isDisetujui) {
        $statusTampil = "Disetujui";
        $badge_class = "badge-success";
    } else {
        $statusTampil = htmlspecialchars($row['status_akhir']); // Default
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
    </tr>
<?php } ?>
    <?php } else { ?>
        <tr>
            <!-- UBAH COLSPAN MENJADI 8 -->
            <td colspan="8" style="text-align: center; padding: 40px 0; color: #94a3b8;">
                <i class="fa-solid fa-box-open" style="font-size: 36px; margin-bottom: 10px; display: block;"></i>
                Belum ada arsip permohonan surat yang selesai atau ditolak.
            </td>
        </tr>
    <?php } ?>
</tbody>
            </table>
        </div>
    </section>

    <!-- FOOTER UTAMA -->
    <footer class="footer-section">
        <div class="footer-wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M1200,0H0V60.4C138.85,108.62,298.54,125,441.77,105.81,595.6,85.19,705.51,20.89,864,24.7c124.62,3,212.87,41.4,336,65.7V0Z" class="shape-fill"></path>
            </svg>
        </div>
        <div class="footer-container">
            <div class="footer-col info-col">
                <h3>SIPATU FST</h3>
                <p>Sistem Informasi Manajemen Persuratan Fakultas Sains dan Teknologi UIN Raden Intan Lampung.</p>
                <div class="contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Jl. Endro Suratmin No.38, Sukarame, Kec. Sukarame, Kota Bandar Lampung, Lampung 35131</span>
                </div>
            </div>

            <div class="footer-col map-col">
                <h4>Lokasi Kami</h4>
                <div class="map-wrapper">
                    <iframe
                        src="https://maps.google.com/maps?q=Gedung%20Fakultas%20Sains%20dan%20Teknologi%20Tower%201%20UIN%20Raden%20Intan%20Lampung&t=&z=17&ie=UTF8&iwloc=&output=embed"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            <div class="footer-col contact-col">
                <h4>INFORMASI & KONTAK</h4>
                <div class="contact-item">
                    <i class="fa-brands fa-instagram"></i>
                    <a href="https://www.instagram.com/saintek.radenintan" target="_blank" class="footer-clickable-link">
                        <span>saintek.radenintan</span>
                    </a>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-globe"></i>
                    <a href="https://saintek.radenintan.ac.id" target="_blank" class="footer-clickable-link">
                        <span>saintek.radenintan.ac.id</span>
                    </a>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-clock"></i>
                    <span>Senin - Jumat: 08.00 - 16.00 WIB</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 Fakultas Sains dan Teknologi UIN RIL. Dibuat oleh Ghania Ridha Khairiah.</p>
        </div>
    </footer>

    <!-- JAVASCRIPT NAVBAR & DROPDOWN -->
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
    </script>
</body>
</html>