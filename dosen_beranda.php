<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='index.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'] ?? 0;

$is_pembina = false;
$cek_pembina = mysqli_query($koneksi, "SELECT id_ormawa FROM ormawa WHERE id_pembina = '$id_dosen'");

if ($cek_pembina && mysqli_num_rows($cek_pembina) > 0) {
    $is_pembina = true;
}

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Dosen';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Dosen';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$qMenunggu = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM surat_pengajuan sp
    JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    WHERE 
    -- Skenario A: Jika dia adalah Pembimbing 2
    (dsr.id_pb2 = '$id_dosen' AND dsr.status_pb2 = 'Menunggu')
    OR
    -- Skenario B: Jika dia adalah Pembimbing 1, dia baru bisa melihatnya SETELAH Pembimbing 2 setuju
    (dsr.id_pb1 = '$id_dosen' AND dsr.status_pb2 = 'Disetujui' AND dsr.status_pb1 = 'Menunggu')
");

$qDisetujui = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM surat_pengajuan sp
    JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    WHERE 
    (dsr.id_pb2 = '$id_dosen' AND dsr.status_pb2 = 'Disetujui')
    OR
    (dsr.id_pb1 = '$id_dosen' AND dsr.status_pb1 = 'Disetujui')
");

$qDitolak = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM surat_pengajuan sp
    JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    WHERE 
    (dsr.id_pb2 = '$id_dosen' AND dsr.status_pb2 = 'Ditolak')
    OR
    (dsr.id_pb1 = '$id_dosen' AND dsr.status_pb1 = 'Ditolak')
");

$menunggu = mysqli_fetch_assoc($qMenunggu)['total'] ?? 0;
$disetujui = mysqli_fetch_assoc($qDisetujui)['total'] ?? 0;
$ditolak = mysqli_fetch_assoc($qDitolak)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
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
            <a href="#home">Beranda</a>

            <?php if ($is_pembina): ?>
                <div class="nav-dropdown">
                    <a href="#" class="navbar-nav">Verifikasi Permohonan<i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="dosen_permohonan_akademik.php">Akademik</a>
                        <a href="dosen_permohonan_ormawa.php">Ormawa</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="dosen_permohonan_akademik.php">Verifikasi Permohonan</a>
            <?php endif; ?>

            <a href="#riwayat">Informasi Persuratan</a>

            <?php if ($is_pembina): ?>
                <div class="nav-dropdown">
                    <a href="#" class="navbar-nav">Riwayat Verifikasi<i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="dosen_riwayat_akademik.php">Akademik</a>
                        <a href="dosen_riwayat_ormawa.php">Ormawa</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="dosen_riwayat_akademik.php">Riwayat Verifikasi</a>
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

    <section class="hero" id="home">
        <main class="content">
            <h2>Halo, <?= htmlspecialchars($namaLengkap); ?></h2>
            <h1>Selamat datang di Dashboard Dosen Akademik FST UIN RIL</h1>
        </main>
    </section>

    <section id="riwayat" class="riwayat-section">
        <div class="wave-divider">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"
                    class="shape-fill"></path>
            </svg>
        </div>

        <section id="status-info" class="status-info-section">
            <div class="wave-divider">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
                </svg>
            </div>

            <div class="status-info-header">
                <h2>Informasi <span class="text-orange">Pengajuan</span></h2>
            </div>

            <div class="dashboard-container">
                <div class="status-grid">
                    <div class="status-box">
                        <span class="status-count" style="color:#f59e0b;"><?= $menunggu; ?></span>
                        <h4>Menunggu</h4>
                    </div>

                    <div class="status-box">
                        <span class="status-count" style="color:#10b981;"><?= $disetujui; ?></span>
                        <h4>Disetujui</h4>
                    </div>

                    <div class="status-box">
                        <span class="status-count" style="color:#ef4444;"><?= $ditolak; ?></span>
                        <h4>Ditolak</h4>
                    </div>

                    <div class="status-box">
                        <span class="status-count" style="color:#3b82f6;"><?= $menunggu + $disetujui + $ditolak; ?></span>
                        <h4>Total</h4>
                    </div>
                </div>

                <div class="action-box">
                    <div class="action-icon">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <h3>Verifikasi Permohonan</h3>
                    <p>Lihat dan verifikasi permohonan surat mahasiswa yang membutuhkan persetujuan Anda.</p>
                    <a href="dosen_permohonan.php" class="btn-action">
                        Verifikasi Permohonan <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="action-box">
                    <div class="action-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h3>Riwayat Verifikasi</h3>
                    <p>Lihat daftar surat yang sudah pernah Anda setujui atau tolak sebelumnya.</p>
                    <a href="dosen_riwayat.php" class="btn-action">
                        Lihat Riwayat <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>

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
        </script>
        <script>
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