<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['npm']) || empty($_GET['npm'])) {
    header("Location: adm_kelola_mhs.php");
    exit;
}

$npm = mysqli_real_escape_string($koneksi, $_GET['npm']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_mhs = mysqli_real_escape_string($koneksi, $_POST['nama_mhs']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $status   = (int)$_POST['status'];
    $id_prodi = mysqli_real_escape_string($koneksi, $_POST['id_prodi']);

    $id_pa  = !empty($_POST['id_pa']) ? $_POST['id_pa'] : NULL;
    $id_pb1 = !empty($_POST['id_pb1']) ? $_POST['id_pb1'] : NULL;
    $id_pb2 = !empty($_POST['id_pb2']) ? $_POST['id_pb2'] : NULL;

    $stmt = $koneksi->prepare("UPDATE mahasiswa SET 
        nama_mhs = ?, email = ?, status = ?, id_prodi = ?, id_pa = ?, id_pb1 = ?, id_pb2 = ? 
        WHERE npm = ?");
    $stmt->bind_param("ssisssss", $nama_mhs, $email, $status, $id_prodi, $id_pa, $id_pb1, $id_pb2, $npm);

    if ($stmt->execute()) {
        $_SESSION['pesan'] = "Data mahasiswa berhasil diperbarui!";
        $_SESSION['status'] = "success";
        header("Location: adm_kelola_mhs.php");
        exit;
    } else {
        $error_msg = "Gagal memperbarui data.";
    }
}

$query_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE npm = '$npm'");
if (mysqli_num_rows($query_mhs) == 0) {
    header("Location: adm_kelola_mhs.php");
    exit;
}
$data = mysqli_fetch_assoc($query_mhs);

$prodi_result = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");
$queryDosen = mysqli_query($koneksi, "SELECT id_dosen, nama_dosen FROM dosen WHERE role_akses = 'dosen' ORDER BY nama_dosen ASC");

$daftar_dosen = [];
while ($d = mysqli_fetch_assoc($queryDosen)) {
    $daftar_dosen[] = $d;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Mahasiswa</title>

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
            <h2 class="edit-card-title">Ubah Data Mahasiswa</h2>
        </div>

        <form method="POST" action="" id="form-edit-mhs">
            <div class="form-grid">
                <div>
                    <div class="form-section-title">Informasi Akun</div>

                    <div class="form-group">
                        <label>NPM</label>
                        <input type="text" class="form-control readonly-input" value="<?= $data['npm']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_mhs" class="form-control" value="<?= htmlspecialchars($data['nama_mhs']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Status Akun</label>
                        <select name="status" class="form-control" required>
                            <option value="1" <?= ($data['status'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                            <option value="2" <?= ($data['status'] == 2) ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="form-section-title">Informasi Akademik</div>
                    <div class="form-group">
                        <label>Program Studi</label>
                        <select name="id_prodi" class="form-control" required>
                            <?php while ($p = mysqli_fetch_assoc($prodi_result)): ?>
                                <option value="<?= $p['id_prodi']; ?>" <?= ($data['id_prodi'] == $p['id_prodi']) ? 'selected' : ''; ?>>
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
                                <option value="<?= $dosen['id_dosen']; ?>" <?= ($data['id_pa'] == $dosen['id_dosen']) ? 'selected' : ''; ?>>
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
                                <option value="<?= $dosen['id_dosen']; ?>" <?= ($data['id_pb1'] == $dosen['id_dosen']) ? 'selected' : ''; ?>>
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
                                <option value="<?= $dosen['id_dosen']; ?>" <?= ($data['id_pb2'] == $dosen['id_dosen']) ? 'selected' : ''; ?>>
                                    <?= $dosen['nama_dosen']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="adm_kelola_mhs.php" class="btn-cancel aksi-batal">Batal</a>
                    <button type="submit" class="btn-save form-edit-mhs">Simpan Perubahan</button>
                </div>

            </div>
        </form>
    </div>
    <?php include "adm_footer.php"; ?>

    <script>
        //fungsi dropdown pilih dosen PA, Pembimbing Skripsi 1, dan Pembimbing Skripsi 2
        $(document).ready(function() {
            $('.select-cari-dosen').select2({
                placeholder: "-- Pilih Dosen --",
                allowClear: true,
                width: '100%'
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            //fungsi konfirmasi tombol batal dan kembali ke halaman sebelumnya
            const btnBatal = document.querySelectorAll('.aksi-batal');

            btnBatal.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetUrl = this.getAttribute('href');

                    Swal.fire({
                        title: 'Batalkan Perubahan?',
                        text: 'Data yang belum Anda simpan akan hilang.',
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

            //fungsi konfirmasi tombol simpan perubahan
            const formEdit = document.getElementById('form-edit-mhs');

            if (formEdit) {
                formEdit.addEventListener('submit', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Simpan Perubahan?',
                        text: 'Pastikan data mahasiswa sudah diperiksa dengan benar.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Simpan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formEdit.submit();
                        }
                    });
                });
            }

        });
    </script>

</body>

</html>