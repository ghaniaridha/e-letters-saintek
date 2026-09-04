<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $npm      = mysqli_real_escape_string($koneksi, $_POST['npm']);
    $nama_mhs = mysqli_real_escape_string($koneksi, $_POST['nama_mhs']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $status   = (int)$_POST['status'];
    $id_prodi = mysqli_real_escape_string($koneksi, $_POST['id_prodi']);

    if (!ctype_digit($npm)) {
        $error_msg = "Format NPM tidak valid! NPM hanya boleh berisi angka.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/\.[a-zA-Z]{2,}$/", $email)) {
        $error_msg = "Format email tidak valid atau kurang lengkap! Pastikan menggunakan ekstensi domain (contoh: .com).";
    } else {
        $id_pa  = !empty($_POST['id_pa']) ? $_POST['id_pa'] : NULL;
        $id_pb1 = !empty($_POST['id_pb1']) ? $_POST['id_pb1'] : NULL;
        $id_pb2 = !empty($_POST['id_pb2']) ? $_POST['id_pb2'] : NULL;

        $cek_npm = mysqli_query($koneksi, "SELECT npm FROM mahasiswa WHERE npm = '$npm'");

        if (mysqli_num_rows($cek_npm) > 0) {
            $error_msg = "Gagal! NPM $npm sudah terdaftar di sistem.";
        } else {
            $password_default = password_hash($npm, PASSWORD_DEFAULT);

            $stmt = $koneksi->prepare("INSERT INTO mahasiswa (npm, nama_mhs, password, email, status, id_prodi, id_pa, id_pb1, id_pb2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssissss", $npm, $nama_mhs, $password_default, $email, $status, $id_prodi, $id_pa, $id_pb1, $id_pb2);

            if ($stmt->execute()) {
                $_SESSION['pesan'] = "Data mahasiswa berhasil ditambahkan!";
                $_SESSION['status'] = "success";
                header("Location: adm_kelola_mhs.php");
                exit;
            } else {
                $error_msg = "Terjadi kesalahan saat menambahkan data.";
            }
        }
    }
}
$prodi_result = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");
$queryDosen = mysqli_query($koneksi, "SELECT id_dosen, nama_dosen FROM dosen WHERE role_akses = 'dosen' ORDER BY nama_dosen ASC");

$daftar_dosen = [];
while ($d = mysqli_fetch_assoc($queryDosen)) {
    $daftar_dosen[] = $d;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Mahasiswa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <?php include "adm_sidebar.php"; ?>

    <div class="edit-container">
        <div class="edit-card-header">
            <h2 class="edit-card-title">Tambah Data Mahasiswa</h2>
        </div>

        <?php if (isset($error_msg)): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Peringatan',
                    text: '<?= $error_msg; ?>',
                    confirmButtonText: 'Tutup'
                });
            </script>
        <?php endif; ?>

        <form method="POST" action="" id="form-tambah-mhs">
            <div class="form-grid">
                <div>
                    <div class="form-section-title">Informasi Akun</div>

                    <div class="form-group">
                        <label>NPM</label>
                        <input type="text"
                            name="npm"
                            class="form-control"
                            placeholder="Masukkan NPM"
                            pattern="[0-9]+"
                            title="NPM hanya boleh berisi angka."
                            onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                            required>
                        <small class="form-help-text">*NPM akan digunakan sebagai kata sandi awal mahasiswa.</small>
                    </div>

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_mhs" class="form-control" placeholder="Masukkan Nama Lengkap" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email"
                            name="email"
                            class="form-control"
                            placeholder="Masukkan email"
                            value="<?= htmlspecialchars($data['email'] ?? ''); ?>"
                            pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$"
                            title="Masukkan format email yang valid dan lengkap (contoh: emailanda@gmail.com)"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Status Akun</label>
                        <select name="status" class="form-control" required>
                            <option value="1">Aktif</option>
                            <option value="2">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="form-section-title">Informasi Akademik</div>
                    <div class="form-group">
                        <label>Program Studi</label>
                        <select name="id_prodi" class="form-control" required>
                            <option value="">-- Pilih Program Studi --</option>
                            <?php while ($p = mysqli_fetch_assoc($prodi_result)): ?>
                                <option value="<?= $p['id_prodi']; ?>">
                                    <?= htmlspecialchars($p['nama_prodi']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dosen Pembimbing Akademik (PA)</label>
                        <select name="id_pa" class="form-control select-cari-dosen">
                            <option value="">-- Pilih Dosen PA --</option>
                            <?php foreach ($daftar_dosen as $dosen): ?>
                                <option value="<?= $dosen['id_dosen']; ?>">
                                    <?= $dosen['nama_dosen']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dosen Pembimbing Skripsi 1</label>
                        <select name="id_pb1" class="form-control select-cari-dosen">
                            <option value="">-- Pilih Dosen Pembimbing Skripsi 1 --</option>
                            <?php foreach ($daftar_dosen as $dosen): ?>
                                <option value="<?= $dosen['id_dosen']; ?>">
                                    <?= $dosen['nama_dosen']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dosen Pembimbing Skripsi 2</label>
                        <select name="id_pb2" class="form-control select-cari-dosen">
                            <option value="">-- Pilih Dosen Pembimbing 2 --</option>
                            <?php foreach ($daftar_dosen as $dosen): ?>
                                <option value="<?= $dosen['id_dosen']; ?>">
                                    <?= $dosen['nama_dosen']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="adm_kelola_mhs.php" class="btn-cancel aksi-batal">Batal</a>
                    <button type="submit" class="btn-save form-tambah-mhs">Simpan Data</button>
                </div>
            </div>
        </form>
    </div>
    <?php include "adm_footer.php"; ?>

    <script>
        $(document).ready(function() {
            $('.select-cari-dosen').select2({
                placeholder: "-- Pilih Dosen --",
                allowClear: true,
                width: '100%'
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const btnBatal = document.querySelectorAll('.aksi-batal');

            btnBatal.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetUrl = this.getAttribute('href');

                    Swal.fire({
                        title: 'Batalkan Pengisian?',
                        text: 'Data yang sedang Anda ketik tidak akan disimpan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Batalkan!',
                        cancelButtonText: 'Tetap di sini'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = targetUrl;
                        }
                    });
                });
            });

            const formTambah = document.getElementById('form-tambah-mhs');

            if (formTambah) {
                formTambah.addEventListener('submit', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Simpan Data?',
                        text: 'Pastikan informasi mahasiswa sudah sesuai.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Simpan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formTambah.submit();
                        }
                    });
                });
            }

        });
    </script>
</body>

</html>