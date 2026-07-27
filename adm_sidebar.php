<?php
$username_admin = $_SESSION['nama'] ?? '';
?>

<button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="images/logo2.png" alt="Logo SIPATU FST UIN RIL" class="logo-img">
    </div>

    <a href="adm_dashboard.php">
        <i class="fa-solid fa-house"></i> Dasbor
    </a>

    <?php if ($username_admin !== 'ADM001') : ?>
        <a href="adm_template_surat.php">
            <i class="fa-solid fa-file-word"></i> Templat Surat
        </a>
    <?php endif; ?>

    <?php if ($username_admin === 'ADM001') : ?>
        <a href="adm_permohonan_ormawa.php">
            <i class="fa-solid fa-envelope-open-text"></i> Permohonan Surat Ormawa
        </a>
    <?php else : ?>
        <a href="adm_permohonan_akademik.php">
            <i class="fa-solid fa-envelope-open-text"></i> Permohonan Surat Mhs
        </a>
    <?php endif; ?>

    <?php if ($username_admin === 'ADM001') : ?>
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

    <?php if ($username_admin === 'ADM001') : ?>
        <a href="adm_laporan_surat.php">
            <i class="fa-solid fa-file-export"></i> Laporan Surat Ormawa
        </a>
    <?php else : ?>
        <a href="adm_proses_penomoran.php">
            <i class="fa-solid fa-file-pen"></i> Proses & Penomoran
        </a>
        <a href="adm_laporan_surat.php">
            <i class="fa-solid fa-file-export"></i> Laporan Surat Keluar
        </a>
    <?php endif; ?>

    <a href="logout.php" class="logout">
        <i class="fa-solid fa-right-from-bracket"></i> Keluar
    </a>
</div>

<script>
    document.querySelector('.dropdown-btn').addEventListener('click', function() {
        this.nextElementSibling.classList.toggle('show');
        this.querySelector('.dropdown-arrow').classList.toggle('rotate-arrow');
    });

    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }
</script>