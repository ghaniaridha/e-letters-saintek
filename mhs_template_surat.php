<?php
session_start();
include "koneksi.php";

$query = mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    WHERE file_template IS NOT NULL
    AND file_template != ''
    ORDER BY nama_surat ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Format Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="mhs_beranda.php#home">Beranda</a>
            <a href="mhs_beranda.php#services">Pengajuan Surat</a>
            <a href="mhs_beranda.php#status-info">Status & Informasi</a>
            <a href="mhs_lacak.php">Lacak Surat</a>
            <a href="mhs_riwayat.php">Riwayat Pengajuan</a>
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
                </div>
            </div>
        </div>
    </nav>

    <section id="daftar-surat" class="daftar-surat">
        <div class="daftar-surat-header">
            <h2>Templat Surat Akademik FST</h2>
        </div>

        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Jenis Surat</th>
                            <th>Keterangan</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>

                                <td><?= htmlspecialchars($row['deskripsi'] ?? '-'); ?></td>

                                <td>
                                    <a href="uploads/template_surat/<?= htmlspecialchars($row['file_template']); ?>"
                                        download
                                        class="btn-download-blue">
                                        Download
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="3" class="empty-table-cell">
                                <i class="fa-solid fa-folder-open"></i>
                                <p>Belum ada template surat.</p>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
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
                    <span>Jl. Letkol H. Endro Suratmin, Sukarame, Bandar Lampung.</span>
                </div>
            </div>

            <div class="footer-col links-col">
                <h4>Tautan Cepat</h4>
                <ul>
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#services">Layanan Akademik</a></li>
                    <li><a href="#status-info">Lacak Surat</a></li>
                    <li><a href="mhs_riwayat.php">Riwayat Permohonan</a></li>
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
    </script>
</body>

</html>