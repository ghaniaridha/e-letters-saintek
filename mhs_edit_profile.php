<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['nama']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: index.php");
    exit;
}

$npm = $_SESSION['nama'];
$query_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE npm = '$npm'");
$data = mysqli_fetch_assoc($query_mhs);

$prodi_result = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");

$queryDosen = mysqli_query($koneksi, "SELECT id_dosen, nama_dosen FROM dosen WHERE role_akses = 'dosen' ORDER BY nama_dosen ASC");
$daftar_dosen = [];
while ($d = mysqli_fetch_assoc($queryDosen)) {
    $daftar_dosen[] = $d;
}

$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$teks_status = ($data['status'] == 1) ? 'Aktif' : 'Menunggu / Nonaktif';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_mhs = mysqli_real_escape_string($koneksi, $_POST['nama_mhs']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $id_prodi = mysqli_real_escape_string($koneksi, $_POST['id_prodi']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/\.[a-zA-Z]{2,}$/", $email)) {
        $_SESSION['pesan']  = "Format email tidak valid atau kurang lengkap! Pastikan menggunakan ekstensi domain (contoh: .com).";
        $_SESSION['status'] = "error";

        header("Location: mhs_edit_profile.php");
        exit;
    }

    $id_pa  = !empty($_POST['id_pa']) ? $_POST['id_pa'] : NULL;
    $id_pb1 = !empty($_POST['id_pb1']) ? $_POST['id_pb1'] : NULL;
    $id_pb2 = !empty($_POST['id_pb2']) ? $_POST['id_pb2'] : NULL;

    $stmt = $koneksi->prepare("UPDATE mahasiswa SET 
        nama_mhs = ?, 
        email = ?, 
        id_prodi = ?, 
        id_pa = ?, 
        id_pb1 = ?, 
        id_pb2 = ? 
        WHERE npm = ?");

    $stmt->bind_param("sssssss", $nama_mhs, $email, $id_prodi, $id_pa, $id_pb1, $id_pb2, $npm);

    if ($stmt->execute()) {
        $_SESSION['pesan'] = "Profil berhasil diperbarui!";
        $_SESSION['status'] = "success";
        header("Location: mhs_profile.php");
        exit;
    } else {
        $_SESSION['pesan'] = "Gagal memperbarui profil: " . $stmt->error;
        $_SESSION['status'] = "error";
        header("Location: mhs_edit_profile.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Saya</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

    <section id="daftar-surat" class="daftar-surat">
        <div class="daftar-surat-header">
            <h2>Edit Profil Saya</h2>
        </div>

        <form method="POST" id="form-edit-mhs" class="form-wrapper">
            <div class="profile-grid-layout">

                <div class="profile-column">
                    <div class="form-section-title">Informasi Akun</div>

                    <div class="data-group">
                        <label>NPM</label>
                        <p class="data-value form-text-readonly">
                            <?= htmlspecialchars($data['npm'] ?? ''); ?>
                            <small class="readonly-note">(Tidak dapat diubah)</small>
                        </p>
                    </div>

                    <div class="data-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_mhs" class="data-input-field" value="<?= htmlspecialchars($data['nama_mhs'] ?? ''); ?>" required>
                    </div>

                    <div class="data-group">
                        <label>Email</label>
                        <input type="email"
                            name="email"
                            class="data-input-field"
                            value="<?= htmlspecialchars($data['email'] ?? ''); ?>"
                            pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$"
                            title="Masukkan format email yang valid dan lengkap (contoh: emailanda@gmail.com)"
                            required>
                    </div>

                    <div class="data-group">
                        <label>Status Akun</label>
                        <p class="data-value status-text form-text-readonly">
                            <?= $teks_status; ?>
                            <small class="readonly-note">(Tidak dapat diubah)</small>
                        </p>
                    </div>
                </div>

                <div class="profile-column">
                    <div class="form-section-title">Informasi Akademik</div>

                    <div class="data-group">
                        <label>Program Studi</label>
                        <select name="id_prodi" class="data-select-field" required>
                            <?php
                            $q_prodi = mysqli_query($koneksi, "SELECT * FROM prodi");
                            while ($p = mysqli_fetch_assoc($q_prodi)): ?>
                                <option value="<?= $p['id_prodi']; ?>" <?= ($data['id_prodi'] == $p['id_prodi']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($p['nama_prodi']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="data-group">
                        <label>Dosen Pembimbing Akademik (PA)</label>
                        <select name="id_pa" class="data-select-field select-cari-dosen">
                            <option value="">-- Pilih Dosen --</option>
                            <?php foreach ($daftar_dosen as $dosen): ?>
                                <option value="<?= $dosen['id_dosen']; ?>" <?= ($data['id_pa'] == $dosen['id_dosen']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($dosen['nama_dosen']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="data-group">
                        <label>Dosen Pembimbing Skripsi 1</label>
                        <select name="id_pb1" class="data-select-field select-cari-dosen">
                            <option value="">-- Pilih Dosen --</option>
                            <?php foreach ($daftar_dosen as $dosen): ?>
                                <option value="<?= $dosen['id_dosen']; ?>" <?= ($data['id_pb1'] == $dosen['id_dosen']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($dosen['nama_dosen']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="data-group">
                        <label>Dosen Pembimbing Skripsi 2</label>
                        <select name="id_pb2" class="data-select-field select-cari-dosen">
                            <option value="">-- Pilih Dosen --</option>
                            <?php foreach ($daftar_dosen as $dosen): ?>
                                <option value="<?= $dosen['id_dosen']; ?>" <?= ($data['id_pb2'] == $dosen['id_dosen']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($dosen['nama_dosen']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="profile-action-bar">
                <a href="mhs_profile.php" class="btn-secondary aksi-batal">Batal</a>
                <button type="submit" class="btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
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

        //fungsi dropdown pilih dosen PA, Pembimbing Skripsi 1, dan Pembimbing Skripsi 2
        $(document).ready(function() {
            $('.select-cari-dosen').select2({
                placeholder: "-- Pilih Dosen --",
                allowClear: true,
                width: '100%'
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            //fungsi konfirmasi tombol batal
            const btnBatal = document.querySelector('.aksi-batal');
            if (btnBatal) {
                btnBatal.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');

                    Swal.fire({
                        title: 'Batalkan Perubahan?',
                        text: 'Data yang belum Anda simpan akan hilang.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Batalkan',
                        cancelButtonText: 'Kembali Edit',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = href;
                        }
                    });
                });
            }

            //fungsi konfirmasi tombol simpan
            const formEdit = document.getElementById('form-edit-mhs');
            if (formEdit) {
                formEdit.addEventListener('submit', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Simpan Perubahan?',
                        text: 'Pastikan data profil yang Anda masukkan sudah benar.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formEdit.submit();
                        }
                    });
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