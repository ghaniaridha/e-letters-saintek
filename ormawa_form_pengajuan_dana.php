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

$ormawa = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT o.*, 
           p.id_kaprodi, 
           d_kaprodi.nama_dosen AS nama_kaprodi, 
           d_kaprodi.nip AS nip_kaprodi,
           d_pembina.id_dosen AS id_dosen_pembina,
           d_pembina.nama_dosen AS nama_dosen_pembina, 
           d_pembina.nip AS nip_dosen_pembina
    FROM ormawa o
    LEFT JOIN prodi p ON o.id_prodi = p.id_prodi
    LEFT JOIN dosen d_kaprodi ON p.id_kaprodi = d_kaprodi.id_dosen
    LEFT JOIN dosen d_pembina ON o.id_pembina = d_pembina.id_dosen
    WHERE o.id_ormawa='$id_ormawa'
"));

if ($ormawa['jenis_organisasi'] == 'Ormawa') {
    $id_penanggung_jawab = $ormawa['id_kaprodi'];
    $nama_penanggung_jawab = $ormawa['nama_kaprodi'];
} else {
    $id_penanggung_jawab = $ormawa['id_pembina'];
    $nama_penanggung_jawab = $ormawa['nama_dosen_pembina'];
}

$id_pembina = $ormawa['id_pembina'];
$q_pembina = mysqli_query($koneksi, "SELECT id_dosen, nama_dosen, nip FROM dosen WHERE id_dosen = '$id_pembina'");
$pembina = mysqli_fetch_assoc($q_pembina);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengajuan Dana</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="#home">Beranda</a>
            <a href="#services">Pengajuan Surat</a>
            <a href="#status-info">Status & Informasi</a>
            <a href="ormawa_riwayat.php">Riwayat Pengajuan</a>
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

    <div class="generate-wrapper">
        <div class="page-header">
            <h1>Pengajuan Dana Kegiatan</h1>
            <p>Lengkapi data berikut untuk melakukan pengajuan dana kegiatan organisasi kemahasiswaan.</p>
        </div>

        <div class="generate-card">
            <form action="preview_pengajuan_dana.php" method="POST" enctype="multipart/form-data" onsubmit="konfirmasiPengajuan(event)">
                <input type="hidden" name="id_jenis" value="12">
                <input type="hidden" name="id_ormawa" value="<?= $id_ormawa ?>">

                <div class="form-group">
                    <label>Nama Ormawa</label>
                    <input type="text" value="<?= $ormawa['nama_ormawa'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Ketua Ormawa</label>
                    <input type="text" value="<?= $ormawa['nama_ketua'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Sekretaris Ormawa</label>
                    <input type="text" value="<?= $ormawa['nama_sekretaris'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Nomor Surat</label>
                    <input type="text" name="nomor_surat" required placeholder="Contoh: 001/HIMSI/FST/VII/2026">
                </div>

                <div class="form-group">
                    <label>Nama Kegiatan</label>
                    <input type="text" name="nama_kegiatan" required>
                </div>

                <div class="form-group">
                    <label>Tempat Kegiatan</label>
                    <input type="text" name="tempat_kegiatan" required>
                </div>

                <div class="form-group">
                    <label>Tanggal Kegiatan</label>
                    <input type="date" name="tanggal_kegiatan" required>
                </div>

                <div class="form-group">
                    <label>Penanggung Jawab Organisasi</label>
                    <?php if (!empty($nama_penanggung_jawab)) { ?>
                        <input type="text" class="form-control input-readonly" value="<?= htmlspecialchars($nama_penanggung_jawab); ?>" readonly>
                        <input type="hidden" name="id_pembina" value="<?= $id_penanggung_jawab; ?>">
                    <?php } else { ?>
                        <input type="text" class="form-control input-error-readonly" value="Penanggung Jawab / Kaprodi belum diatur" readonly>
                        <input type="hidden" name="id_pembina" value="">
                    <?php } ?>
                </div>

                <hr class="hr-separator">

                <h3 class="section-title">Dokumen Pendukung</h3>

                <div class="form-group">
                    <label>Proposal Kegiatan</label>
                    <input type="file" name="proposal" accept=".pdf" required>
                </div>

                <div class="form-actions">
                    <a href="ormawa_daftar_surat.php" class="btn-back-form" onclick="confirmBatalAjukanSurat(event, this.href)">Kembali</a>
                    <button type="submit" class="btn-generate">Buat Surat</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer-form-minimal">
        <p>&copy; 2026 SIPATU FST UIN RIL | Dibuat oleh Ghania Ridha Khairiah.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            //fungsi dropdown user navbar
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

            //fungsi validasi input file (keamanan)
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.hasAttribute('accept')) {
                        const acceptedTypes = this.getAttribute('accept').split(',');
                        const fileName = this.value.toLowerCase();

                        const isValid = acceptedTypes.some(ext => fileName.endsWith(ext.trim()));

                        if (!isValid && fileName !== "") {
                            Swal.fire({
                                icon: 'error',
                                title: 'Format Tidak Valid!',
                                text: 'Silakan masukkan format: ' + acceptedTypes.join(", "),
                                showConfirmButton: true,
                                confirmButtonText: 'Mengerti',
                                confirmButtonColor: '#1e3a8a'
                            });

                            this.value = '';
                        }
                    }
                });
            });
        });

        //fungsi konfirmasi button kembali
        function confirmBatalAjukanSurat(event, url) {
            event.preventDefault();
            Swal.fire({
                title: 'Batalkan pengisian formulir?',
                text: "Perubahan yang dilakukan tidak akan tersimpan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Kembali Mengisi',
                heightAuto: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }

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