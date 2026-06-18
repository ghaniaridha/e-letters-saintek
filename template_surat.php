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
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Template Surat</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>


    <div class="top-action">
        <a href="mhs_dashboard.php" class="btn-kembali">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>
    </div>

    <section class="services" style="padding-top:20px;">
        <div class="services-header">
            <h2>Template Surat</h2>
            <h2 class="highlight">Akademik FST</h2>
        </div>

        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
            <div class="template-container">
                <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                    <div class="template-card">
                        <div class="template-icon">
                            <i class="fa-solid fa-file-word"></i>
                        </div>

                        <div class="template-info">
                            <h3><?= htmlspecialchars($row['nama_surat']); ?></h3>
                            <p>Kode Surat: <?= htmlspecialchars($row['kode_surat']); ?></p>
                        </div>

                        <div class="template-actions">
                            <a href="uploads/template_surat/<?= htmlspecialchars($row['file_template']); ?>"
                                target="_blank"
                                class="btn-view"
                                title="Lihat File">
                                <i class="fa-solid fa-eye"></i>
                            </a>

                            <a href="uploads/template_surat/<?= htmlspecialchars($row['file_template']); ?>"
                                download
                                class="btn-download"
                                title="Download File">
                                <i class="fa-solid fa-download"></i>
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="empty-template">
                <i class="fa-solid fa-folder-open"></i>
                <p>Belum ada template surat.</p>
            </div>
        <?php } ?>
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
    </section>

</body>

</html>