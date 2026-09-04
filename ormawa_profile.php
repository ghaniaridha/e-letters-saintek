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

$query_profil = "
    SELECT 
        o.*, 
        p.nama_prodi,
        COALESCE(d_ukm.nama_dosen, d_kaprodi.nama_dosen) AS nama_pembina,
        COALESCE(d_ukm.nip, d_kaprodi.nip) AS nip_pembina
    FROM ormawa o
    LEFT JOIN prodi p ON o.id_prodi = p.id_prodi
    LEFT JOIN dosen d_ukm ON o.id_pembina = d_ukm.id_dosen
    LEFT JOIN dosen d_kaprodi ON p.id_kaprodi = d_kaprodi.id_dosen
    WHERE o.id_ormawa = '$id_ormawa'
";

$result = mysqli_query($koneksi, $query_profil);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<script>alert('Data profil tidak ditemukan'); window.location='ormawa_beranda.php';</script>";
    exit;
}

if (($data['jenis_organisasi'] ?? '') == 'UKM' || empty($data['nama_prodi'])) {
    $teks_prodi = 'Fakultas Sains dan Teknologi UIN Raden Intan Lampung';
} else {
    $teks_prodi = $data['nama_prodi'];
}
$teks_status      = ($data['status'] == 1) ? 'Aktif' : 'Menunggu / Nonaktif';
$teks_pembina     = $data['nama_pembina'] ?? 'Belum Ditentukan';
$teks_nip_pembina = $data['nip_pembina'] ?? '-';
$jenis_org        = $data['jenis_organisasi'] ?? 'Ormawa';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Organisasi</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if (isset($_SESSION['pesan'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['status']; ?>',
                title: '<?= ($_SESSION['status'] == "success") ? "Berhasil!" : "Gagal!"; ?>',
                text: <?= json_encode($_SESSION['pesan']); ?>,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>
        <?php
        unset($_SESSION['pesan']);
        unset($_SESSION['status']);
        ?>
    <?php endif; ?>

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
            <h2>Profil Organisasi Mahasiswa</h2>
        </div>

        <div class="form-wrapper">
            <div class="profile-grid-layout">

                <!-- Kolom 1: Informasi Umum Ormawa -->
                <div class="profile-column">
                    <div class="form-section-title">Informasi Organisasi</div>

                    <div class="data-group">
                        <label>Nama Organisasi</label>
                        <p class="data-value"><?= htmlspecialchars($data['nama_ormawa'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Singkatan / Akronim</label>
                        <p class="data-value"><?= htmlspecialchars($data['singkatan_ormawa'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Email Organisasi</label>
                        <p class="data-value"><?= htmlspecialchars($data['email_ormawa'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Kontak / No. Telepon</label>
                        <p class="data-value"><?= htmlspecialchars($data['kontak_ormawa'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Alamat Sekretariat</label>
                        <p class="data-value"><?= htmlspecialchars($data['alamat_sekretariat'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Status Akun</label>
                        <p class="data-value status-text"><?= $teks_status; ?></p>
                    </div>
                </div>

                <!-- Kolom 2: Kepengurusan & Akademik -->
                <div class="profile-column">
                    <div class="form-section-title">Kepengurusan & Pembina</div>

                    <div class="data-group">
                        <label>Naungan Prodi / Fakultas</label>
                        <p class="data-value"><?= htmlspecialchars($teks_prodi); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Nama Ketua</label>
                        <p class="data-value"><?= htmlspecialchars($data['nama_ketua'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>NPM Ketua</label>
                        <p class="data-value"><?= htmlspecialchars($data['npm_ketua'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Nama Sekretaris</label>
                        <p class="data-value"><?= htmlspecialchars($data['nama_sekretaris'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>NPM Sekretaris</label>
                        <p class="data-value"><?= htmlspecialchars($data['npm_sekretaris'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Penanggung Jawab Organisasi</label>
                        <p class="data-value"><?= htmlspecialchars($teks_pembina); ?> (NIP. <?= htmlspecialchars($teks_nip_pembina); ?>)</p>
                    </div>
                </div>
            </div>

            <div class="profile-action-bar">
                <a href="ormawa_beranda.php" class="btn-secondary">Kembali</a>
                <a href="ormawa_edit_profile.php" class="btn-primary">
                    Ubah Data Profil
                </a>
            </div>
        </div>
    </section>

    <footer class="footer-form-minimal">
        <p>&copy; 2026 SIPATU FST UIN RIL | Dibuat oleh Ghania Ridha Khairiah.</p>
    </footer>

    <script>
        // Mengelola dropdown menu pengguna
        document.addEventListener('DOMContentLoaded', function() {
            const userBtn = document.getElementById('user-btn');
            const dropdown = document.getElementById('user-dropdown');

            if (userBtn && dropdown) {
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
            }
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