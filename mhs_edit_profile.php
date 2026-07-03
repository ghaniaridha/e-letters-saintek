<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['nama']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: index.php");
    exit;
}

$queryDosen = mysqli_query($koneksi, "SELECT id_dosen, nama_dosen FROM dosen WHERE role_akses = 'dosen' ORDER BY nama_dosen ASC");

$daftar_dosen = [];
while ($d = mysqli_fetch_assoc($queryDosen)) {
    $daftar_dosen[] = $d;
}

// 2. Ambil NPM dari Session
$npm = $_SESSION['nama'];

$query_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE npm = '$npm'");
$data = mysqli_fetch_assoc($query_mhs);

// 2. AMBIL DAFTAR PROGRAM STUDI (Untuk dropdown select)
$prodi_result = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");

// 3. AMBIL DAFTAR DOSEN (Untuk dropdown select PA dan PB)
$queryDosen = mysqli_query($koneksi, "SELECT id_dosen, nama_dosen FROM dosen WHERE role_akses = 'dosen' ORDER BY nama_dosen ASC");
$daftar_dosen = [];
while ($d = mysqli_fetch_assoc($queryDosen)) {
    $daftar_dosen[] = $d;
}

// Format status untuk ditampilkan sebagai teks readonly
$teks_status = ($data['status'] == 1) ? 'Aktif' : 'Menunggu / Nonaktif';
// 3. Proses Update Data jika form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil input dari form
    $nama_mhs = mysqli_real_escape_string($koneksi, $_POST['nama_mhs']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $id_prodi = mysqli_real_escape_string($koneksi, $_POST['id_prodi']);

    // Tangani nilai NULL untuk select box jika kosong
    $id_pa  = !empty($_POST['id_pa']) ? $_POST['id_pa'] : NULL;
    $id_pb1 = !empty($_POST['id_pb1']) ? $_POST['id_pb1'] : NULL;
    $id_pb2 = !empty($_POST['id_pb2']) ? $_POST['id_pb2'] : NULL;

    // 4. Update data (Tanpa kolom status)
    // Gunakan prepared statement agar aman dari SQL Injection
    $stmt = $koneksi->prepare("UPDATE mahasiswa SET 
        nama_mhs = ?, 
        email = ?, 
        id_prodi = ?, 
        id_pa = ?, 
        id_pb1 = ?, 
        id_pb2 = ? 
        WHERE npm = ?");

    // "ssissss" artinya: 6 string (s) dan 1 integer (i)
    // Jika kolom id_pa/pb1/pb2 di database bertipe integer, ubah bind_param-nya menjadi "ssisiii"
    $stmt->bind_param("sssssss", $nama_mhs, $email, $id_prodi, $id_pa, $id_pb1, $id_pb2, $npm);

    if ($stmt->execute()) {
        // Berhasil, beri pesan sukses
        $_SESSION['pesan'] = "Profil berhasil diperbarui!";
        $_SESSION['status'] = "success";
        header("Location: mhs_profile.php"); // Kembali ke halaman profil
        exit;
    } else {
        // Gagal
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
    <title>Edit Profil Mahasiswa</title>

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
                    <a href="#" class="user-info-link">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($namaLengkap) ?></span>
                            <span class="user-role"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                        </div>
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
                        <input type="text" class="data-value" value="<?= htmlspecialchars($data['npm'] ?? ''); ?>" readonly style="background:transparent; border:none; color:#6b7280;">
                    </div>

                    <div class="data-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_mhs" class="data-input-field" value="<?= htmlspecialchars($data['nama_mhs'] ?? ''); ?>" required>
                    </div>

                    <div class="data-group">
                        <label>Email</label>
                        <input type="email" name="email" class="data-input-field" value="<?= htmlspecialchars($data['email'] ?? ''); ?>" required>
                    </div>

                    <div class="data-group">
                        <label>Status Akun</label>
                        <p class="data-value status-text"><?= $teks_status; ?> <small style="color:#999; font-weight:normal;">(Tidak dapat diubah)</small></p>
                    </div>
                </div>

                <div class="profile-column">
                    <div class="form-section-title">Informasi Akademik</div>

                    <div class="data-group">
                        <label>Program Studi</label>
                        <select name="id_prodi" class="data-select-field" required>
                            <?php
                            // Mengambil ulang data prodi untuk pilihan dropdown
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
                            <option value="">-- Pilih Dosen PA --</option>
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
                            <option value="">-- Belum Ada --</option>
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
                            <option value="">-- Belum Ada --</option>
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
                <a href="mhs_profile.php" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </section>

    <footer class="footer-form-minimal">
        <p>&copy; 2026 SIPATU FST UIN RIL | Dibuat oleh Ghania Ridha Khairiah.</p>
    </footer>

</body>

</html>