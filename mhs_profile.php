<?php
session_start();
include "koneksi.php";

$id_mhs = $_SESSION['id_mhs'];
if (!isset($_SESSION['nama']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: index.php");
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

$npm_login = $_SESSION['nama'];
$query_profil = "
    SELECT 
        m.npm, 
        m.nama_mhs, 
        m.email, 
        m.status, 
        p.nama_prodi,
        d_pa.nama_dosen AS nama_pa,
        d_pb1.nama_dosen AS nama_pb1,
        d_pb2.nama_dosen AS nama_pb2
    FROM mahasiswa m
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN dosen d_pa ON m.id_pa = d_pa.id_dosen
    LEFT JOIN dosen d_pb1 ON m.id_pb1 = d_pb1.id_dosen
    LEFT JOIN dosen d_pb2 ON m.id_pb2 = d_pb2.id_dosen
    WHERE m.npm = '$npm_login'
";

$result = mysqli_query($koneksi, $query_profil);
$data = mysqli_fetch_assoc($result);

$teks_status = ($data['status'] == 1) ? 'Aktif' : 'Menunggu / Nonaktif';
$teks_prodi  = $data['nama_prodi'] ?? 'Belum Diatur';
$teks_pa     = $data['nama_pa'] ?? 'Belum Ditentukan';
$teks_pb1    = $data['nama_pb1'] ?? 'Belum Ada';
$teks_pb2    = $data['nama_pb2'] ?? 'Belum Ada';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya</title>

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
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="mhs_beranda.php#home">Beranda</a>
            <a href="mhs_beranda.php#services">Pengajuan Surat</a>
            <a href="mhs_beranda.php#status-info">Status & Informasi</a>
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

    <section id="daftar-surat" class="daftar-surat">
        <div class="daftar-surat-header">
            <h2>Profil Saya</h2>
        </div>

        <div class="form-wrapper">
            <div class="profile-grid-layout">

                <div class="profile-column">
                    <div class="form-section-title">Informasi Akun</div>

                    <div class="data-group">
                        <label>NPM</label>
                        <p class="data-value"><?= htmlspecialchars($data['npm'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Nama Lengkap</label>
                        <p class="data-value"><?= htmlspecialchars($data['nama_mhs'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Email</label>
                        <p class="data-value"><?= htmlspecialchars($data['email'] ?? '-'); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Status Akun</label>
                        <p class="data-value status-text"><?= $teks_status; ?></p>
                    </div>
                </div>

                <div class="profile-column">
                    <div class="form-section-title">Informasi Akademik</div>

                    <div class="data-group">
                        <label>Program Studi</label>
                        <p class="data-value"><?= htmlspecialchars($teks_prodi); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Dosen Pembimbing Akademik (PA)</label>
                        <p class="data-value"><?= htmlspecialchars($teks_pa); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Dosen Pembimbing Skripsi 1</label>
                        <p class="data-value"><?= htmlspecialchars($teks_pb1); ?></p>
                    </div>

                    <div class="data-group">
                        <label>Dosen Pembimbing Skripsi 2</label>
                        <p class="data-value"><?= htmlspecialchars($teks_pb2); ?></p>
                    </div>
                </div>

            </div>

            <div class="profile-action-bar">
                <a href="mhs_beranda.php" class="btn-secondary">Kembali</a>
                <a href="mhs_edit_profile.php" class="btn-primary">
                    Ubah Data Profil
                </a>
            </div>
        </div>
    </section>

    <footer class="footer-form-minimal">
        <p>&copy; 2026 SIPATU FST UIN RIL | Dibuat oleh Ghania Ridha Khairiah.</p>
    </footer>

    <script>
        // mengelola dropdown menu pengguna
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

        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
        document.getElementById('my-hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.my-navbar-nav')?.classList.toggle('active');
        });
    </script>
</body>

</html>