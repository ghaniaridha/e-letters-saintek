<div class="sidebar">
    <div class="sidebar-logo">
        <h2>Admin FST</h2>
        <p>Layanan Akademik</p>
    </div>

    <a href="adm_dashboard.php">
        <i class="fa-solid fa-house"></i> Dashboard
    </a>

    <a href="admin_template_surat.php">
        <i class="fa-solid fa-file-word"></i> Template Surat
    </a>

    <a href="admin_permohonan.php">
        <i class="fa-solid fa-envelope-open-text"></i> Permohonan Surat
    </a>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn">
            <i class="fa-solid fa-users"></i> Kelola Pengguna
            <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </a>

        <ul class="dropdown-menu">
            <li><a href="adm_kelola_mhs.php">Kelola Mahasiswa</a></li>
            <li><a href="adm_kelola_ormawa.php">Kelola Ormawa</a></li>
            <li><a href="adm_kelola_dosen.php">Kelola Dosen</a></li>
        </ul>
    </div>

    <a href="admin_riwayat_review.php">
        <i class="fa-solid fa-clock-rotate-left"></i>
        Riwayat Review
    </a>

    <a href="admin_laporan_surat.php">
        <i class="fa-solid fa-file-export"></i> Laporan Surat Keluar
    </a>

    <a href="logout.php" class="logout">
        <i class="fa-solid fa-right-from-bracket"></i> Keluar
    </a>
</div>

<script>
    document.querySelector('.dropdown-btn').addEventListener('click', function() {
        // Toggle menu
        this.nextElementSibling.classList.toggle('show');

        // Toggle rotasi panah
        this.querySelector('.dropdown-arrow').classList.toggle('rotate-arrow');
    });
</script>