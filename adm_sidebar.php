<?php
// Pastikan variabel username admin sudah terbaca dari session login (contoh: adm001)
$username_admin = $_SESSION['nama'] ?? '';
?>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="images/logo2.png" alt="Logo SIPATU FST UIN RIL" class="logo-img">
    </div>

    <!-- Menu Dasbor & Templat Surat tetap bisa diakses kedua admin -->
    <a href="adm_dashboard.php">
        <i class="fa-solid fa-house"></i> Dasbor
    </a>

    <!-- MENU INI HANYA MUNCUL UNTUK ADMIN AKADEMIK (ADMIN 2) -->
    <?php if ($username_admin !== 'ADM001') : ?>
        <a href="adm_template_surat.php">
            <i class="fa-solid fa-file-word"></i> Templat Surat
        </a>
    <?php endif; ?>

    <!-- PERBEDAAN FITUR PERMOHONAN SURAT -->
    <?php if ($username_admin === 'ADM001') : ?>
        <a href="adm_permohonan.php">
            <i class="fa-solid fa-envelope-open-text"></i> Permohonan Surat Ormawa
        </a>
    <?php else : ?>
        <a href="adm_permohonan.php">
            <i class="fa-solid fa-envelope-open-text"></i> Permohonan Surat Mhs
        </a>
    <?php endif; ?>

    <!-- PERBEDAAN FITUR KELOLA PENGGUNA -->
    <?php if ($username_admin === 'ADM001') : ?>
        <!-- Admin 1: Hanya bisa mengelola data Ormawa & Dosen (Pembina) -->
        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn">
                <i class="fa-solid fa-users"></i> Kelola Pengguna
                <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
            </a>
            <ul class="sidebar-dropdown-menu">
                <li><a href="adm_kelola_ormawa.php">Kelola Ormawa</a></li>
            </ul>
        </div>
    <?php else : ?>
        <!-- Admin 2: Hanya bisa mengelola data Mahasiswa & Dosen (PA/Dospem) -->
        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn">
                <i class="fa-solid fa-users"></i> Kelola Pengguna
                <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
            </a>
            <ul class="sidebar-dropdown-menu">
                <li><a href="adm_kelola_mhs.php">Kelola Mahasiswa</a></li>
                <li><a href="adm_kelola_dosen.php">Kelola Dosen</a></li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- PERBEDAAN FITUR RIWAYAT & LAPORAN -->
    <?php if ($username_admin === 'ADM001') : ?>
        <a href="adm_riwayat_review.php">
            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Surat Ormawa
        </a>
        <a href="adm_laporan_surat.php">
            <i class="fa-solid fa-file-export"></i> Laporan Surat Ormawa
        </a>
    <?php else : ?>
        <a href="adm_riwayat_review.php">
            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Surat Mhs
        </a>
        <a href="adm_laporan_surat.php">
            <i class="fa-solid fa-file-export"></i> Laporan Surat Mhs
        </a>
    <?php endif; ?>

    <a href="logout.php" class="logout">
        <i class="fa-solid fa-right-from-bracket"></i> Keluar
    </a>
</div>

<script>
    // fungsi dropdown menu sidebar bawaan kode lama Anda
    document.querySelector('.dropdown-btn').addEventListener('click', function() {
        this.nextElementSibling.classList.toggle('show');
        this.querySelector('.dropdown-arrow').classList.toggle('rotate-arrow');
    });
</script>