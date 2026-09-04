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

$id_surat = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data_edit = null;

if ($id_surat > 0) {
    $q_edit = mysqli_query($koneksi, "
        SELECT sp.*, dsl.* 
        FROM surat_pengajuan sp
        JOIN detail_sk_lulus dsl ON sp.id_surat = dsl.id_surat
        WHERE sp.id_surat = '$id_surat' AND sp.id_mhs = '$id_mhs' AND sp.status_akhir = 'Perbaikan'
    ");
    $data_edit = mysqli_fetch_assoc($q_edit);

    if (!$data_edit) {
        echo "<script>alert('Data permohonan tidak ditemukan atau tidak dalam status perbaikan.'); window.location='mhs_riwayat.php';</script>";
        exit;
    }
    $id_jenis = $data_edit['id_jenis'];
} else {
    $id_jenis = $_GET['id_jenis'] ?? 14;
}

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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data_edit ? 'Edit / Revisi ' : 'Form '; ?><?= htmlspecialchars($surat['nama_surat']); ?></title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
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

    <div class="generate-wrapper">
        <div class="page-header">
            <h1><?= htmlspecialchars($surat['nama_surat']); ?></h1>
            <p><?= $data_edit ? 'Silakan perbaiki data atau dokumen yang salah sesuai catatan admin.' : 'Silakan lengkapi data berikut untuk membuat permohonan SK Lulus.'; ?></p>
        </div>

        <div class="generate-card">
            <form action="generate_sk_lulus_mhs.php" method="POST" enctype="multipart/form-data" onsubmit="confirmAjukanSurat(event)">
                <input type="hidden" name="id_jenis" value="<?= htmlspecialchars($id_jenis); ?>">

                <?php if ($data_edit): ?>
                    <input type="hidden" name="id_surat" value="<?= $data_edit['id_surat']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" class="form-control input-readonly" value="<?= htmlspecialchars($mhs['nama_mhs']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>NPM</label>
                    <input type="text" class="form-control input-readonly" value="<?= htmlspecialchars($mhs['npm']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Program Studi</label>
                    <input type="text" class="form-control input-readonly" value="<?= htmlspecialchars($mhs['nama_prodi'] ?? 'Sistem Informasi'); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <div class="input-group-flex align-center">
                        <input type="text" name="tempat_lahir" placeholder="Contoh: Bandar Lampung" value="<?= htmlspecialchars($data_edit['tempat_lahir'] ?? ''); ?>" required>

                        <label class="label-inline">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($data_edit['tanggal_lahir'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Semester Lulus</label>
                    <div class="input-group-flex align-center">
                        <select name="semester" required>
                            <option value="" disabled <?= empty($data_edit['semester']) ? 'selected' : ''; ?>>Pilih Semester</option>
                            <?php
                            $sem_selected = $data_edit['semester'] ?? '';
                            for ($i = 7; $i <= 14; $i++) {
                                $sel = ($sem_selected == $i) ? 'selected' : '';
                                echo "<option value=\"$i\" $sel>$i</option>";
                            }
                            ?>
                        </select>

                        <label class="label-inline">Tahun Akademik</label>
                        <input type="text" name="tahun_akademik" placeholder="Contoh: 2025/2026" value="<?= htmlspecialchars($data_edit['tahun_akademik'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tanggal Lulus <br><small class="label-note">(Sesuai Munaqasah)</small></label>
                    <div class="input-group-flex align-center">
                        <input type="date" name="tanggal_lulus" value="<?= htmlspecialchars($data_edit['tanggal_lulus'] ?? ''); ?>" required>

                        <label class="label-inline">IPK Terakhir</label>
                        <input type="number" step="0.01" name="ipk" placeholder="Contoh: 3.71" value="<?= htmlspecialchars($data_edit['ipk'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nilai Skripsi</label>
                    <div class="input-group-flex align-center">
                        <select name="nilai_skripsi" required>
                            <option value="" disabled <?= empty($data_edit['nilai_skripsi']) ? 'selected' : ''; ?>>Pilih Nilai</option>
                            <option value="A" <?= ($data_edit['nilai_skripsi'] ?? '') == 'A' ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?= ($data_edit['nilai_skripsi'] ?? '') == 'B' ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?= ($data_edit['nilai_skripsi'] ?? '') == 'C' ? 'selected' : ''; ?>>C</option>
                        </select>

                        <label class="label-inline">Predikat</label>
                        <select name="predikat_kelulusan" required>
                            <option value="" disabled <?= empty($data_edit['predikat_kelulusan']) ? 'selected' : ''; ?>>Pilih Predikat</option>
                            <option value="Pujian (Cumlaude)" <?= ($data_edit['predikat_kelulusan'] ?? '') == 'Pujian (Cumlaude)' ? 'selected' : ''; ?>>Pujian (Cumlaude)</option>
                            <option value="Sangat Memuaskan" <?= ($data_edit['predikat_kelulusan'] ?? '') == 'Sangat Memuaskan' ? 'selected' : ''; ?>>Sangat Memuaskan</option>
                            <option value="Memuaskan" <?= ($data_edit['predikat_kelulusan'] ?? '') == 'Memuaskan' ? 'selected' : ''; ?>>Memuaskan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tujuan / Keperluan SK Lulus</label>
                    <select name="keperluan" class="form-control" required>
                        <option value="" disabled <?= empty($data_edit['keperluan']) ? 'selected' : ''; ?>>Pilih Keperluan</option>
                        <option value="Melamar Pekerjaan" <?= ($data_edit['keperluan'] ?? '') == 'Melamar Pekerjaan' ? 'selected' : ''; ?>>Melamar Pekerjaan</option>
                        <option value="Melanjutkan Studi" <?= ($data_edit['keperluan'] ?? '') == 'Melanjutkan Studi' ? 'selected' : ''; ?>>Melanjutkan Studi</option>
                        <option value="Lainnya" <?= ($data_edit['keperluan'] ?? '') == 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>

                <hr class="hr-separator">

                <h3 class="section-title">Dokumen Pendukung</h3>
                <?php if ($data_edit): ?>
                    <small class="text-alert">* Kosongkan file jika tidak ingin mengubah dokumen yang sudah diunggah sebelumnya.</small>
                <?php endif; ?>

                <div class="form-group">
                    <label>1. Transkrip Nilai<br><small class="label-note">(Sudah Ada Nilai Skripsi)</small></label>
                    <input type="file" name="transkrip_nilai" accept=".pdf,.jpg,.jpeg,.png" <?= $data_edit ? '' : 'required'; ?>>
                </div>

                <div class="form-group">
                    <label>2. Berita Acara Munaqosah</label>
                    <input type="file" name="berita_acara" accept=".pdf,.jpg,.jpeg,.png" <?= $data_edit ? '' : 'required'; ?>>
                </div>

                <div class="form-group">
                    <label>3. Kartu Tanda Mahasiswa<br><small class="label-note">(KTM)</small></label>
                    <input type="file" name="ktm" accept=".pdf,.jpg,.jpeg,.png" <?= $data_edit ? '' : 'required'; ?>>
                </div>

                <div class="form-group">
                    <label>4. Kartu Tanda Penduduk <br><small class="label-note">(KTP)</small></label>
                    <input type="file" name="ktp" accept=".pdf,.jpg,.jpeg,.png" <?= $data_edit ? '' : 'required'; ?>>
                </div>

                <div class="form-group">
                    <label>5. Sertifikat Akreditasi Prodi</label>
                    <input type="file" name="akreditasi" accept=".pdf,.jpg,.jpeg,.png" <?= $data_edit ? '' : 'required'; ?>>
                </div>

                <div class="form-actions">
                    <?php
                    $link_kembali = $data_edit ? "mhs_riwayat_detail.php?id=" . $id_surat : "mhs_daftar_surat_akademik.php";
                    ?>
                    <a href="#" onclick="confirmBatalAjukanSurat(event, '<?= $link_kembali; ?>')" class="btn-back-form">
                        Kembali
                    </a>

                    <button type="submit" class="btn-generate">
                        <?= $data_edit ? 'Kirim Ulang Revisi' : 'Ajukan Surat'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer-form-minimal">
        <p>&copy; 2026 Fakultas Sains dan Teknologi UIN RIL. Dibuat oleh Ghania Ridha Khairiah.</p>
    </footer>

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
                text: "Perubahan yang dilakukan tidak akan tersimpan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Kembali Mengisi',
                heightAuto: false
            }).then((result) => {
                if (result.isConfirmed) window.location.href = url;
            });
        }

        function confirmAjukanSurat(event) {
            event.preventDefault();
            const form = event.target;
            Swal.fire({
                title: 'Konfirmasi Pengajuan Surat',
                text: "Pastikan semua data diri kelulusan dan 5 dokumen pendukung sudah benar.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e3a8a',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Periksa Kembali'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
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
    </script>
</body>

</html>