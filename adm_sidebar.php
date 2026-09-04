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
            <i class="fa-solid fa-envelope-open-text"></i> Permohonan Surat
        </a>
    <?php else : ?>
        <a href="adm_permohonan_akademik.php">
            <i class="fa-solid fa-envelope-open-text"></i> Permohonan Surat
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
                <li><a href="adm_kelola_dosen.php">Kelola Dosen & TK</a></li>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($username_admin === 'ADM001') : ?>
        <a href="adm_riwayat_ormawa.php">
            <i class="fa-solid fa-calendar-check"></i> Proses & Atur Jadwal
        </a>
        <a href="adm_laporan_ormawa.php">
            <i class="fa-solid fa-file-export"></i> Laporan Surat Ormawa
        </a>
    <?php else : ?>
        <a href="adm_riwayat_review.php">
            <i class="fa-solid fa-file-pen"></i> Proses & Penomoran
        </a>
        <a href="adm_laporan_surat.php">
            <i class="fa-solid fa-file-export"></i> Laporan Surat Keluar
        </a>
    <?php endif; ?>

    <a href="adm_statistik.php">
        <i class="fa-solid fa-chart-line"></i> Statistik Layanan
    </a>

    <a href="logout.php" class="logout" onclick="confirmLogout(event, this.href)">
        <i class="fa-solid fa-right-from-bracket"></i> Keluar
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const dropdownBtn = document.querySelector('.dropdown-btn');
    if (dropdownBtn) {
        dropdownBtn.addEventListener('click', function() {
            this.nextElementSibling.classList.toggle('show');

            const arrow = this.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.classList.toggle('rotate-arrow');
            }
        });
    }

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

    // Fungsi Konfirmasi Logout
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