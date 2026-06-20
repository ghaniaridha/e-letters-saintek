<?php
session_start();
include "koneksi.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/AKADEMIK FST2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="#home">Beranda</a>
            <a href="#services">Layanan Akademik</a>
            <a href="#riwayat">Informasi</a>
            <a href="mhs_riwayat.php">Riwayat Permohonan</a>
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
                        <span class="user-name"><?= ($namaLengkap) ?></span>
                        <span class="user-role"><?= $idLogin ?> - <?= $role ?></span>
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

    <section class="hero" id="home">
        <main class="content">
            <?php
            $namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
            ?>
            <h2>Halo, <?= $namaLengkap ?></h2>
            <h1>Selamat datang di layanan Akademik FST UIN RIL</h1>
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
                    <h3>Pengajuan Surat Online</h3>
                    <p>Ajukan kebutuhan administrasi surat Anda secara daring, mudah, dan dapat dilacak</p>
                </div>
                <div class="btn-right">
                    <a href="daftar_surat_akademik.php"><i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="services-container">
            <div class="service-box">
                <div class="icon-left">
                    <i class="fa-solid fa-file-word"></i>
                </div>
                <div class="text-middle">
                    <h3>Format Surat Akademik</h3>
                    <p>Unduh format baku surat akademik untuk mempermudah berbagai keperluan administrasi Anda</p>
                </div>
                <div class="btn-right">
                    <a href="template_surat.php"><i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section id="riwayat" class="riwayat-section">
        <div class="wave-divider">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
        </div>

        <div class="riwayat-header">
            <h2>Informasi <span class="text-orange">Pengajuan</span></h2>
        </div>

        <div class="dashboard-container">
            <div class="status-grid">
                <div class="status-box">
                    <span class="status-count" style="color: #f59e0b;">3</span>
                    <h4>Menunggu</h4>
                </div>

                <div class="status-box">
                    <span class="status-count" style="color: #10b981;">1</span>
                    <h4>Disetujui</h4>
                </div>

                <div class="status-box">
                    <span class="status-count" style="color: #ef4444;">0</span>
                    <h4>Ditolak</h4>
                </div>

                <div class="status-box">
                    </i> <span class="status-count" style="color: #3b82f6;">5</span>
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
                <h3>Lacak Disposisi</h3>
                <p>Pantau posisi terkini dan proses disposisi surat Anda secara real-time.</p>
                <a href="mhs_lacak.php" class="btn-action">Lacak Surat <i class="fa-solid fa-arrow-right"></i></a>
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
                <h3>Layanan Akademik FST</h3>
                <p>Sistem Informasi Manajemen Persuratan Fakultas Sains dan Teknologi UIN Raden Intan Lampung.</p>
                <div class="contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Jl. Letkol H. Endro Suratmin, Sukarame, Bandar Lampung.</span>
                </div>
            </div>

            <div class="footer-col links-col">
                <h4>Tautan Cepat</h4>
                <ul>
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#services">Layanan Akademik</a></li>
                    <li><a href="#riwayat">Lacak</a></li>
                    <li><a href="#kalender">Riwayat Permohonan</a></li>
                </ul>
            </div>

            <div class="footer-col contact-col">
                <h4>Pusat Bantuan</h4>
                <div class="contact-item">
                    <i class="fa-solid fa-envelope"></i>
                    <span>akademik.fst@radenintan.ac.id</span>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-phone"></i>
                    <span>(0721) 1234567</span>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-clock"></i>
                    <span>Senin - Jumat: 08.00 - 16.00 WIB</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 Layanan Akademik FST UIN RIL. Dibuat oleh Ghania Ridha Khairiah.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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