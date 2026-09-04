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

function getSyaratSurat($koneksi, $id_jenis)
{
    $query = "SELECT s.nama_syarat, s.format_file 
              FROM syarat_jenis_surat p 
              JOIN master_syarat s ON p.id_syarat = s.id_syarat 
              WHERE p.id_jenis = '$id_jenis'";

    $result = mysqli_query($koneksi, $query);
    $list_syarat = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $format = !empty($row['format_file']) ? " (" . strtoupper($row['format_file']) . ")" : "";
        $list_syarat[] = $row['nama_syarat'] . $format;
    }

    return json_encode($list_syarat);
}

$syarat_ruangan = getSyaratSurat($koneksi, 11);
$syarat_dana    = getSyaratSurat($koneksi, 12);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Surat Ormawa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css" media="screen" title="no title">
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
            <h2>Pengajuan Surat Online</h2>
        </div>

        <div class="jadwal-container">
            <div class="jadwal-info">
                <div class="jadwal-row header-row">
                    <span class="icon-box color-orange">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </span>
                    <span class="jadwal-title">Jadwal Operasional Pelayanan</span>
                </div>

                <div class="jadwal-row">
                    <span class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span class="jadwal-text">Setiap Senin s/d Jumat</span>
                </div>

                <div class="jadwal-row">
                    <span class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span class="jadwal-text">Mulai 08.00 -15.00 WIB</span>
                </div>

                <div class="jadwal-row">
                    <span class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span class="jadwal-text">TUTUP Sabtu, Minggu & Libur Nasional</span>
                </div>
            </div>

            <div class="jadwal-illustration">
                <img src="images/jadwal pelayanan.PNG" alt="Ilustrasi Jadwal">
            </div>
        </div>

        <div class="layanan-container">
            <a class="layanan-card" href="javascript:void(0)" onclick='bukaModalSyarat(
                "Peminjaman Ruangan",
                <?= $syarat_ruangan; ?>,
                "ormawa_form_peminjaman_ruangan.php?id_jenis=11"
            )'>
                <div class="layanan-content">
                    <h3>Peminjaman<br>Ruangan</h3>
                </div>
            </a>

            <a class="layanan-card" href="javascript:void(0)" onclick='bukaModalSyarat(
                "Pengajuan Dana Kegiatan",
                <?= $syarat_dana; ?>,
                "ormawa_form_pengajuan_dana.php?id_jenis=12"
            )'>
                <div class="layanan-content">
                    <h3>Pengajuan Dana<br>Kegiatan</h3>
                </div>
            </a>
        </div>

        <!-- Modal Syarat -->
        <div id="modalSyarat" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 id="modalJudulSurat">Persyaratan Pengajuan</h3>
                    <span class="btn-close" onclick="tutupModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p>Pastikan Anda telah menyiapkan dokumen berikut dalam format digital sebelum melanjutkan:</p>
                    <ul id="modalListSyarat"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-batal" onclick="tutupModal()">Batal</button>
                    <a id="btnLanjutForm" href="#" class="btn-lanjut">Lanjutkan</a>
                </div>
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

        function bukaModalSyarat(judul, daftarSyarat, urlForm) {
            document.getElementById('modalJudulSurat').innerText = 'Syarat ' + judul;

            let listContainer = document.getElementById('modalListSyarat');
            listContainer.innerHTML = '';

            daftarSyarat.forEach(function(syarat) {
                let li = document.createElement('li');
                li.innerText = syarat;
                listContainer.appendChild(li);
            });

            document.getElementById('btnLanjutForm').setAttribute('href', urlForm);
            document.getElementById('modalSyarat').style.display = 'flex';
        }

        function tutupModal() {
            document.getElementById('modalSyarat').style.display = 'none';
        }

        window.onclick = function(event) {
            let modal = document.getElementById('modalSyarat');
            if (event.target == modal) {
                tutupModal();
            }
        }

        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
        document.getElementById('my-hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.my-navbar-nav')?.classList.toggle('active');
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