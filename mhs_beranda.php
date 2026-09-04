<?php
session_start();
include "koneksi.php";

$id_mhs = $_SESSION['id_mhs'];
if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

// Query untuk menghitung jumlah status (Grid Status - Informasi)
$query_count = mysqli_query($koneksi, "
    SELECT 
        -- Kategori Menunggu (semua yang berawalan 'Menunggu' kecuali menunggu surat balasan)
        SUM(CASE WHEN status_akhir LIKE 'Menunggu%' AND status_akhir != 'Menunggu Surat Balasan' THEN 1 ELSE 0 END) AS jml_menunggu,
        
        -- Kategori Disetujui (Surat sudah disetujui pimpinan, misal sedang 'Menunggu Surat Balasan')
        SUM(CASE WHEN status_akhir = 'Menunggu Surat Balasan' THEN 1 ELSE 0 END) AS jml_disetujui,
        
        -- Kategori Ditolak
        SUM(CASE WHEN status_akhir LIKE 'Ditolak%' THEN 1 ELSE 0 END) AS jml_ditolak,
        
        -- Kategori Selesai
        SUM(CASE WHEN status_akhir = 'Selesai' THEN 1 ELSE 0 END) AS jml_selesai
    FROM surat_pengajuan
    WHERE id_mhs = '$id_mhs'
");

$count = mysqli_fetch_assoc($query_count);

$menunggu = $count['jml_menunggu'] ?? 0;
$disetujui = $count['jml_disetujui'] ?? 0;
$ditolak = $count['jml_ditolak'] ?? 0;
$selesai = $count['jml_selesai'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="#home">Beranda</a>
            <a href="#services">Pengajuan Surat</a>
            <a href="#status-info">Status & Informasi</a>
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

    <section class="hero" id="home">
        <main class="content">
            <h2>Halo, <?= $namaLengkap ?></h2>
            <h1>Selamat Datang di Sistem Informasi Persuratan Terpadu FST UIN RIL</h1>
        </main>
    </section>

    <section id="services" class="services">
        <div class="services-header">
            <h2>Layanan Akademik</h2>
            <h2 class="highlight">FST UIN RIL</h2>
        </div>

        <div class="services-container">
            <div class="service-box">
                <div class="icon-left">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <div class="text-middle">
                    <h3>Pengajuan Surat Daring</h3>
                    <p>Ajukan permohonan surat administrasi Anda secara daring, mudah, dan dapat dilacak.</p>
                </div>
                <div class="btn-right">
                    <a href="mhs_daftar_surat_akademik.php"><i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="services-container">
            <div class="service-box">
                <div class="icon-left">
                    <i class="fa-solid fa-file-word"></i>
                </div>
                <div class="text-middle">
                    <h3>Templat Surat Akademik</h3>
                    <p>Unduh dokumen baku surat akademik untuk mempermudah berbagai keperluan administrasi Anda.</p>
                </div>
                <div class="btn-right">
                    <a href="mhs_template_surat.php"><i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section id="status-info" class="status-info-section">
        <div class="wave-divider">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
        </div>

        <div class="status-info-header">
            <h2><span class="text-orange">Informasi</span><br> Pengajuan Surat</h2>
        </div>

        <div class="dashboard-container">
            <div class="status-grid">
                <div class="status-box">
                    <span class="status-count count-menunggu"><?= $menunggu; ?></span>
                    <h4>Menunggu</h4>
                </div>

                <div class="status-box">
                    <span class="status-count count-disetujui"><?= $disetujui; ?></span>
                    <h4>Disetujui</h4>
                </div>

                <div class="status-box">
                    <span class="status-count count-ditolak"><?= $ditolak; ?></span>
                    <h4>Ditolak</h4>
                </div>

                <div class="status-box">
                    <span class="status-count count-selesai"><?= $selesai; ?></span>
                    <h4>Selesai</h4>
                </div>
            </div>

            <div class="action-box">
                <div class="action-icon">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h3>Riwayat Pengajuan</h3>
                <p>Lihat detail riwayat seluruh surat yang pernah Anda ajukan sebelumnya.</p>
                <a href="mhs_riwayat.php" class="btn-action">Lihat Riwayat <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="action-box">
                <div class="action-icon">
                    <i class="fa-solid fa-magnifying-glass-location"></i>
                </div>
                <h3>Lacak Surat</h3>
                <p>Pantau posisi terkini dan kemajuan proses pengajuan surat Anda secara langsung.</p>
                <a href="mhs_input_lacak.php" class="btn-action">Lacak Surat <i class="fa-solid fa-arrow-right"></i></a>
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
        // fungsi dropdown menu pengguna
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
    </script>
</body>

</html>