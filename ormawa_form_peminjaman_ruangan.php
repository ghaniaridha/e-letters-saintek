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
    SELECT *
    FROM ormawa
    WHERE id_ormawa='$id_ormawa'
"));

$id_pembina = $ormawa['id_pembina'];
$q_pembina = mysqli_query($koneksi, "SELECT id_dosen, nama_dosen, nip FROM dosen WHERE id_dosen = '$id_pembina'");
$pembina = mysqli_fetch_assoc($q_pembina);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman Ruangan</title>

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
            <a href="mhs_beranda.php#home">Beranda</a>
            <a href="mhs_beranda.php#services">Pengajuan Surat</a>
            <a href="mhs_beranda.php#status-info">Status & Informasi</a>
            <a href="mhs_lacak.php">Lacak Surat</a>
            <a href="mhs_riwayat.php">Riwayat Pengajuan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
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

    <div class="generate-wrapper">
        <div class="page-header">
            <h1>Peminjaman Ruangan</h1>
            <p>Lengkapi data berikut untuk melakukan permohonan peminjaman ruangan.</p>
        </div>

        <div class="generate-card">
            <form action="preview_peminjaman_ruangan.php" method="POST" enctype="multipart/form-data" onsubmit="konfirmasiPengajuan(event)">
                <input type="hidden" name="id_jenis" value="<?= 11 ?>">
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
                    <label>Nama Kegiatan</label>
                    <input type="text" name="nama_kegiatan" required>
                </div>

                <div class="form-group">
                    <label>Nomor Surat</label>
                    <input type="text" name="nomor_surat" required placeholder="Contoh: 001/HIMSI/FST/VII/2026">
                </div>

                <div class="form-group">
                    <label>Ruangan Yang Dipinjam</label>
                    <select name="ruangan" required>
                        <option value="">
                            Pilih Ruangan
                        </option>

                        <option value="Auditorium">
                            Auditorium
                        </option>

                        <option value="Ruang Rapat Dosen Lt.2">
                            Ruang Rapat Dosen Lt.2
                        </option>

                        <option value="Lab Komputer">
                            Lab Komputer
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tanggal Kegiatan</label>
                    <input type="date" name="tanggal_kegiatan" required>
                </div>

                <div class="form-group">
                    <label>Jam Mulai</label>
                    <input type="time" name="jam_mulai" required>
                </div>

                <div class="form-group">
                    <label>Jam Selesai</label>
                    <input type="time" name="jam_selesai" required>
                </div>

                <div class="form-group">
                    <label>Dosen Pembina</label>
                    <?php if ($pembina) { ?>
                        <input type="text" class="form-control input-readonly" value="<?= htmlspecialchars($pembina['nama_dosen']); ?>" readonly>

                        <input type="hidden" name="id_pembina" value="<?= $pembina['id_dosen']; ?>">
                    <?php } else { ?>
                        <input type="text" class="form-control input-error-readonly" value="Belum ada Dosen Pembina" readonly style="color: red; font-style: italic;">
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