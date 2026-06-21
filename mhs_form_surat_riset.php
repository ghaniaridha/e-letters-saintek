<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_jenis = $_GET['id_jenis'] ?? 1;
$id_mhs = $_SESSION['id_mhs'];

$surat = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    WHERE id_jenis = '$id_jenis'
"));

if (!$surat) {
    echo "<script>alert('Jenis surat tidak ditemukan'); window.location='mhs_daftar_surat_akademik.php';</script>";
    exit;
}

$mhs = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM mahasiswa
    WHERE id_mhs = '$id_mhs'
"));

if (!$mhs || empty($mhs['id_prodi'])) {
    echo "<script>alert('Data prodi mahasiswa belum diatur. Hubungi admin.'); window.location='mhs_daftar_surat_akademik.php';</script>";
    exit;
}

$id_prodi = $mhs['id_prodi'];

$dosen1 = mysqli_query($koneksi, "
    SELECT *
    FROM dosen
    WHERE role_akses = 'Dosen'
    AND id_prodi = '$id_prodi'
    ORDER BY nama_dosen ASC
");

$dosen2 = mysqli_query($koneksi, "
    SELECT *
    FROM dosen
    WHERE role_akses = 'Dosen'
    AND id_prodi = '$id_prodi'
    ORDER BY nama_dosen ASC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form <?= htmlspecialchars($surat['nama_surat']); ?></title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>
    <nav class="navbar">
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
                <?php
                $namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
                $idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
                $role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

                $inisial = '';
                $namaParts = explode(' ', $namaLengkap);
                if (!empty($namaParts)) {
                    $inisial = strtoupper(substr($namaParts[0], 0, 1));
                }
                ?>
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
            <h2><?= htmlspecialchars($surat['nama_surat']); ?></h2>
            <p>Silakan lengkapi data berikut untuk membuat surat riset.</p>
        </div>

        <div class="generate-card">
            <form action="proses_generate_surat.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_jenis" value="<?= htmlspecialchars($id_jenis); ?>">

                <div class="form-group">
                    <label>Semester</label>
                    <input type="text" name="semester" placeholder="Contoh: Semester 8" required>
                </div>

                <div class="form-group align-top">
                    <label>Judul Skripsi</label>
                    <textarea name="judul_skripsi" placeholder="Masukkan judul skripsi" required></textarea>
                </div>

                <div class="form-group">
                    <label>Lokasi Penelitian</label>
                    <input type="text" name="lokasi_penelitian" placeholder="Contoh: Kantor Fakultas Sains dan Teknologi" required>
                </div>

                <div class="form-group">
                    <label>Surat Ditujukan Kepada</label>
                    <input type="text" name="surat_ditujukan" placeholder="Contoh: Kepala Instansi / Perusahaan" required>
                </div>

                <div class="form-group">
                    <label>Pembimbing I</label>
                    <select name="pembimbing_1" required>
                        <option value="">-- Pilih Pembimbing I --</option>
                        <?php if ($dosen1 && mysqli_num_rows($dosen1) > 0) { ?>
                            <?php while ($d1 = mysqli_fetch_assoc($dosen1)) { ?>
                                <option value="<?= $d1['id_dosen']; ?>">
                                    <?= htmlspecialchars($d1['nama_dosen']); ?>
                                    - NIP.
                                    <?= htmlspecialchars($d1['nip']); ?>
                                </option>
                            <?php } ?>
                        <?php } else { ?>
                            <option value="" disabled>
                                Belum ada dosen untuk prodi ini
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Pembimbing II</label>
                    <select name="pembimbing_2" required>
                        <option value="">-- Pilih Pembimbing II --</option>
                        <?php if ($dosen2 && mysqli_num_rows($dosen2) > 0) { ?>
                            <?php while ($d2 = mysqli_fetch_assoc($dosen2)) { ?>
                                <option value="<?= $d2['id_dosen']; ?>">
                                    <?= htmlspecialchars($d2['nama_dosen']); ?>
                                    - NIP.
                                    <?= htmlspecialchars($d2['nip']); ?>
                                </option>
                            <?php } ?>
                        <?php } else { ?>
                            <option value="" disabled>
                                Belum ada dosen untuk prodi ini
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e5e7eb;">

                <h3 class="section-title">Dokumen Pendukung</h3>

                <div class="form-group">
                    <label>Proposal Penelitian</label>
                    <input type="file" name="proposal_penelitian" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="form-group">
                    <label>KHS Semester Terakhir</label>
                    <input type="file" name="khs" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="form-group">
                    <label>Bukti Pembayaran UKT</label>
                    <input type="file" name="bukti_ukt" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="form-actions">
                    <a href="daftar_surat_akademik.php" class="btn-back-form" onclick="confirmBatalAjukanSurat(event, this.href)">Kembali</a>
                    <button type="submit" class="btn-generate" onclick="confirmAjukanSurat(event, this.href)">Ajukan Surat</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer-form-minimal">
        <p>&copy; 2026 Fakultas Sains dan Teknologi UIN RIL. Dibuat oleh Ghania Ridha Khairiah.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
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
    </script>
</body>

</html>