<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];

$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$id_jenis = $_GET['id_jenis'] ?? 3;
$surat = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    WHERE id_jenis = '$id_jenis'
"));

if (!$surat) {
    echo "<script>alert('Jenis surat tidak ditemukan'); window.location='mhs_daftar_surat_akademik.php';</script>";
    exit;
}

$mhs = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT m.*, p.nama_prodi 
    FROM mahasiswa m
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    WHERE m.id_mhs = '$id_mhs'
"));

if (!$mhs || empty($mhs['id_prodi'])) {
    echo "<script>alert('Data prodi mahasiswa belum diatur. Hubungi admin.'); window.location='mhs_daftar_surat_akademik.php';</script>";
    exit;
}

$id_pa = $mhs['id_pa'];
$q_pa = mysqli_query($koneksi, "SELECT id_dosen, nama_dosen, nip FROM dosen WHERE id_dosen = '$id_pa'");
$pa = mysqli_fetch_assoc($q_pa);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form <?= htmlspecialchars($surat['nama_surat']); ?></title>

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
            <h1><?= htmlspecialchars($surat['nama_surat']); ?></h1>
            <p>Silakan lengkapi data berikut untuk membuat surat keterangan aktif kuliah kembali setelah cuti.</p>
        </div>

        <div class="generate-card">
            <form action="generate_sk_aktif_kuliah_kembali.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_jenis" value="<?= htmlspecialchars($id_jenis); ?>">

                <div class="form-group">
                    <label>Nama</label>
                    <input type="text"
                        class="form-control input-readonly"
                        value="<?= htmlspecialchars($mhs['nama_mhs']); ?>"
                        readonly>
                    <input type="hidden" name="nama" value="<?= htmlspecialchars($mhs['nama_mhs']); ?>">
                </div>

                <div class="form-group">
                    <label>NPM</label>
                    <input type="text"
                        class="form-control input-readonly"
                        value="<?= htmlspecialchars($mhs['npm']); ?>"
                        readonly>
                    <input type="hidden" name="npm" value="<?= htmlspecialchars($mhs['npm']); ?>">
                </div>

                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" class="form-control flex-1" required>
                        <option value="" disabled selected>Pilih Semester</option>
                        <?php
                        for ($i = 3; $i <= 10; $i++) {
                            echo "<option value=\"$i\">$i</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Program Studi</label>
                    <input type="text"
                        class="form-control input-readonly flex-2"
                        value="<?= htmlspecialchars($mhs['nama_prodi'] ?? 'Sistem Informasi'); ?>"
                        readonly>

                    <input type="hidden" name="id_prodi" value="<?= $data_mhs['id_prodi'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Lama Cuti</label>
                    <input type="text" name="lama_cuti" placeholder="Contoh: 1 Semester" required>
                </div>

                <div class="form-group">
                    <label>Tahun Ajaran Mulai Cuti</label>
                    <input type="text" name="tahun_akademik_ganjil_cuti" placeholder="Contoh: 2025/2026" required>
                </div>

                <div class="form-group">
                    <label>Tahun Ajaran Selesai Cuti</label>
                    <input type="text" name="tahun_akademik_genap_cuti" placeholder="Contoh: 2026/2027" required>
                </div>

                <div class="form-group">
                    <label>Periode Aktif Kembali</label>
                    <div class="input-group-flex">
                        <select name="semester_akademik" class="form-control flex-1" required>
                            <option value="">-- Pilih Semester --</option>
                            <option value="Gasal">Semester Ganjil</option>
                            <option value="Genap">Semester Genap</option>
                        </select>

                        <input type="text" name="tahun_akademik" class="form-control flex-1" placeholder="Contoh: 2026/2027" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pembimbing Akademik</label>
                    <?php if ($pa) { ?>
                        <input type="text" class="form-control input-readonly" value="<?= htmlspecialchars($pa['nama_dosen']); ?>" readonly>
                        <input type="hidden" name="id_pa" value="<?= $pa['id_dosen']; ?>">
                    <?php } else { ?>
                        <input type="text" class="form-control input-error-readonly" value="Belum ada Pembimbing I" readonly>
                        <input type="hidden" name="id_pa" value="">
                    <?php } ?>
                </div>

                <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e5e7eb;">

                <h3 class="section-title">Dokumen Pendukung</h3>

                <div class="form-group">
                    <label>Surat Keterangan Cuti</label>
                    <input type="file" name="sk_cuti" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="form-actions">
                    <a href="mhs_daftar_surat_akademik.php" class="btn-back-form" onclick="confirmBatalAjukanSurat(event, this.href)">Kembali</a>
                    <button type="submit" class="btn-generate">Ajukan Surat</button>
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

        function confirmBatalAjukanSurat(event, url) {
            event.preventDefault();
            Swal.fire({
                title: 'Batalkan pengisian formulir?',
                text: "Perubahan yang Anda lakukan tidak akan tersimpan.",
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

        function confirmAjukanSurat(event) {
            event.preventDefault();
            const form = event.target;

            Swal.fire({
                title: 'Konfirmasi Pengajuan Surat',
                text: "Pastikan semua data dan dokumen pendukung yang Anda unggah sudah benar. Data yang telah dikirim tidak dapat diubah kembali.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e3a8a',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Ajukan Surat',
                cancelButtonText: 'Periksa Kembali',
                heightAuto: false
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
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