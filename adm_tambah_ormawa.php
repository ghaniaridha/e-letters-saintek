<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_admin'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_ormawa      = trim(mysqli_real_escape_string($koneksi, $_POST['nama_ormawa']));
    $singkatan_ormawa = trim(mysqli_real_escape_string($koneksi, $_POST['singkatan_ormawa']));
    $username         = trim(mysqli_real_escape_string($koneksi, $_POST['username']));
    $jenis_organisasi = mysqli_real_escape_string($koneksi, $_POST['jenis_organisasi']);
    $status           = 1;

    $id_prodi   = null;
    $id_pembina = null;

    if ($jenis_organisasi == 'Ormawa') {
        $id_prodi = (int)$_POST['id_prodi'];
    } elseif ($jenis_organisasi == 'UKM') {
        $id_pembina = (int)$_POST['id_pembina'];
    }

    $cek_user = mysqli_query($koneksi, "SELECT username FROM ormawa WHERE username = '$username'");

    if (mysqli_num_rows($cek_user) > 0) {
        $_SESSION['pesan']  = "Gagal! Username '$username' sudah terdaftar.";
        $_SESSION['status'] = "error";
    } else {
        $password_hash = password_hash($username, PASSWORD_DEFAULT);

        $stmt = $koneksi->prepare("INSERT INTO ormawa (username, password, status, nama_ormawa, singkatan_ormawa, jenis_organisasi, id_prodi, id_pembina) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssisssii", $username, $password_hash, $status, $nama_ormawa, $singkatan_ormawa, $jenis_organisasi, $id_prodi, $id_pembina);

        if ($stmt->execute()) {
            $_SESSION['pesan']  = "Data $jenis_organisasi berhasil ditambahkan!";
            $_SESSION['status'] = "success";
            header("Location: adm_kelola_ormawa.php");
            exit;
        } else {
            $_SESSION['pesan']  = "Terjadi kesalahan sistem: " . $stmt->error;
            $_SESSION['status'] = "error";
        }
    }
}

$q_prodi = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");
$q_dosen = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Organisasi</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=1.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title">
                <h1>Tambah Data Ormawa / UKM</h1>
            </div>

            <div class="form-wrapper">
                <form action="" method="POST" id="form-tambah-ormawa">

                    <div class="form-group">
                        <label>Nama Lengkap Organisasi</label>
                        <input type="text" name="nama_ormawa" class="form-control" placeholder="Contoh: Himpunan Mahasiswa Sistem Informasi" required>
                    </div>

                    <div class="form-group">
                        <label>Singkatan / Akronim Organisasi</label>
                        <input type="text" name="singkatan_ormawa" class="form-control" placeholder="Contoh: HIMASI / UKM KSR" required>
                    </div>

                    <div class="form-group">
                        <label>Username Login</label>
                        <input type="text" name="username" class="form-control" placeholder="Contoh: hima_si" required>
                        <small class="form-hint">*Username ini akan digunakan sebagai kata sandi awal (default) untuk login Organisasi.</small>
                    </div>

                    <div class="form-group mb-25">
                        <label>Jenis Organisasi</label>
                        <select name="jenis_organisasi" id="jenis_organisasi" class="form-control" required onchange="togglePenanggungJawab()">
                            <option value="" disabled selected>-- Pilih Jenis Organisasi --</option>
                            <option value="Ormawa">Ormawa (HIMA / HMPS)</option>
                            <option value="UKM">UKM (Unit Kegiatan Mahasiswa)</option>
                        </select>
                    </div>

                    <div class="form-group mb-25 d-none" id="group_prodi">
                        <label>Program Studi Naungan (Kaprodi)</label>
                        <select name="id_prodi" id="id_prodi" class="form-control">
                            <option value="" disabled selected>-- Pilih Program Studi --</option>
                            <?php while ($p = mysqli_fetch_assoc($q_prodi)): ?>
                                <option value="<?= $p['id_prodi']; ?>"><?= htmlspecialchars($p['nama_prodi']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group mb-25 d-none" id="group_pembina">
                        <label>Dosen Pembina (Khusus UKM)</label>
                        <select name="id_pembina" id="id_pembina" class="form-control select-cari-dosen">
                            <option value=""></option>
                            <?php while ($d = mysqli_fetch_assoc($q_dosen)): ?>
                                <option value="<?= $d['id_dosen']; ?>">
                                    <?= htmlspecialchars($d['nama_dosen']); ?> (NIP. <?= htmlspecialchars($d['nip']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="adm_kelola_ormawa.php" class="btn-secondary aksi-batal">Kembali</a>
                        <button type="submit" class="btn-primary">
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        $(document).ready(function() {
            $('.select-cari-dosen').select2({
                placeholder: "-- Pilih Dosen Pembina --",
                allowClear: true,
                width: '100%'
            });
        });

        function togglePenanggungJawab() {
            const jenis = document.getElementById('jenis_organisasi').value;

            const groupProdi = document.getElementById('group_prodi');
            const idProdi = document.getElementById('id_prodi');

            const groupPembina = document.getElementById('group_pembina');
            const idPembina = document.getElementById('id_pembina');

            if (jenis === 'Ormawa') {
                groupProdi.style.display = 'block';
                idProdi.setAttribute('required', 'required');

                groupPembina.style.display = 'none';
                idPembina.removeAttribute('required');
                idPembina.value = '';

            } else if (jenis === 'UKM') {
                groupPembina.style.display = 'block';
                idPembina.setAttribute('required', 'required');

                groupProdi.style.display = 'none';
                idProdi.removeAttribute('required');
                idProdi.value = '';

            } else {
                groupProdi.style.display = 'none';
                groupPembina.style.display = 'none';
                idProdi.removeAttribute('required');
                idPembina.removeAttribute('required');
            }
        }

        //SCRIPT UNTUK POPUP KONFIRMASI(BATAL & SIMPAN)
        document.addEventListener('DOMContentLoaded', function() {

            // Logika untuk tombol Batal / Kembali
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

            // Logika untuk form Submit (Simpan Data)
            const formTambah = document.getElementById('form-tambah-ormawa');

            if (formTambah) {
                formTambah.addEventListener('submit', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Simpan Data?',
                        text: 'Pastikan informasi organisasi sudah sesuai.',
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