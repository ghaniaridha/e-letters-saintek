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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_ormawa        = trim($_POST['nama_ormawa']);
    $singkatan_ormawa   = trim($_POST['singkatan_ormawa']);
    $nama_ketua         = trim($_POST['nama_ketua']);
    $npm_ketua          = trim($_POST['npm_ketua']);
    $nama_sekretaris    = trim($_POST['nama_sekretaris']);
    $npm_sekretaris     = trim($_POST['npm_sekretaris']);
    $email_ormawa       = trim($_POST['email_ormawa']);
    $kontak_ormawa      = trim($_POST['kontak_ormawa']);
    $alamat_sekretariat = trim($_POST['alamat_sekretariat']);

    if (!filter_var($email_ormawa, FILTER_VALIDATE_EMAIL) || !preg_match("/\.[a-zA-Z]{2,}$/", $email_ormawa)) {
        $_SESSION['pesan']  = "Format email organisasi tidak valid atau kurang lengkap!";
        $_SESSION['status'] = "error";
        header("Location: ormawa_edit_profile.php");
        exit;
    }

    if (!ctype_digit($npm_ketua) || !ctype_digit($npm_sekretaris) || (!empty($kontak_ormawa) && !ctype_digit($kontak_ormawa))) {
        $_SESSION['pesan']  = "NPM dan Nomor Kontak/HP harus berupa angka saja!";
        $_SESSION['status'] = "error";
        header("Location: ormawa_edit_profile.php");
        exit;
    }

    $stmt = $koneksi->prepare("UPDATE ormawa SET 
        nama_ormawa = ?, 
        singkatan_ormawa = ?, 
        nama_ketua = ?, 
        npm_ketua = ?, 
        nama_sekretaris = ?, 
        npm_sekretaris = ?, 
        email_ormawa = ?, 
        kontak_ormawa = ?, 
        alamat_sekretariat = ? 
        WHERE id_ormawa = ?");

    $stmt->bind_param("sssssssssi", $nama_ormawa, $singkatan_ormawa, $nama_ketua, $npm_ketua, $nama_sekretaris, $npm_sekretaris, $email_ormawa, $kontak_ormawa, $alamat_sekretariat, $id_ormawa);

    if ($stmt->execute()) {
        $_SESSION['pesan'] = "Profil organisasi berhasil diperbarui!";
        $_SESSION['status'] = "success";
        header("Location: ormawa_profile.php");
        exit;
    } else {
        $_SESSION['pesan'] = "Gagal memperbarui profil: " . $stmt->error;
        $_SESSION['status'] = "error";
        header("Location: ormawa_edit_profile.php");
        exit;
    }
}

$query_profil = "
    SELECT 
        o.*, 
        p.nama_prodi,
        COALESCE(d_ukm.nama_dosen, d_kaprodi.nama_dosen) AS nama_pembina
    FROM ormawa o
    LEFT JOIN prodi p ON o.id_prodi = p.id_prodi
    LEFT JOIN dosen d_ukm ON o.id_pembina = d_ukm.id_dosen
    LEFT JOIN dosen d_kaprodi ON p.id_kaprodi = d_kaprodi.id_dosen
    WHERE o.id_ormawa = '$id_ormawa'
";
$result = mysqli_query($koneksi, $query_profil);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<script>alert('Data profil tidak ditemukan'); window.location='ormawa_beranda.php';</script>";
    exit;
}

$teks_prodi_edit = (($data['jenis_organisasi'] ?? '') == 'UKM' || empty($data['nama_prodi']))
    ? 'Fakultas Sains dan Teknologi UIN Raden Intan Lampung'
    : $data['nama_prodi'];
$teks_status = ($data['status'] == 1) ? 'Aktif' : 'Menunggu / Nonaktif';
$jenis_org   = $data['jenis_organisasi'] ?? 'Ormawa';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    </ /link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="ormawa_beranda.php">Beranda</a>
            <a href="ormawa_beranda.php#services">Pengajuan Surat</a>
            <a href="ormawa_beranda.php#status-info">Status & Informasi</a>
            <a href="ormawa_riwayat.php">Riwayat Permohonan</a>
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
            <h2>Edit Profil Organisasi</h2>
        </div>

        <form class="form-wrapper" id="form-edit-mhs" method="POST">
            <div class="profile-grid-layout">

                <!-- Kolom 1: Informasi Organisasi -->
                <div class="profile-column">
                    <div class="form-section-title">Informasi Organisasi</div>

                    <div class="data-group">
                        <label>Nama Ormawa</label>
                        <input type="text" name="nama_ormawa" class="data-input-ormawa" value="<?= htmlspecialchars($data['nama_ormawa'] ?? ''); ?>" required>
                    </div>

                    <div class="data-group">
                        <label>Singkatan / Akronim</label>
                        <input type="text" name="singkatan_ormawa" class="data-input-ormawa" value="<?= htmlspecialchars($data['singkatan_ormawa'] ?? ''); ?>">
                    </div>

                    <div class="data-group">
                        <label>Email Organisasi</label>
                        <input type="email" name="email_ormawa" class="data-input-ormawa" value="<?= htmlspecialchars($data['email_ormawa'] ?? ''); ?>" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Masukkan format email lengkap dengan ekstensi domain (contoh: ormawa@gmail.com)" required>
                    </div>

                    <div class="data-group">
                        <label>Kontak / No. Telepon</label>
                        <input type="text" name="kontak_ormawa" class="data-input-ormawa" value="<?= htmlspecialchars($data['kontak_ormawa'] ?? ''); ?>" pattern="[0-9]+" title="Nomor kontak/HP hanya boleh berisi angka." onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                    </div>

                    <div class="data-group">
                        <label>Alamat Sekretariat</label>
                        <input type="text" name="alamat_sekretariat" class="data-input-ormawa" value="<?= htmlspecialchars($data['alamat_sekretariat'] ?? ''); ?>">
                    </div>

                    <div class="data-group">
                        <label>Status Akun</label>
                        <p class="data-value status-text form-text-readonly">
                            <?= $teks_status; ?>
                            <small class="readonly-note">(Tidak dapat diubah)</small>
                        </p>
                    </div>
                </div>

                <!-- Kolom 2: Kepengurusan & Akademik -->
                <div class="profile-column">
                    <div class="form-section-title">Kepengurusan & Pembina</div>

                    <div class="data-group">
                        <label>Naungan Prodi / Fakultas</label>
                        <input type="text" class="data-input-ormawa form-text-readonly" value="<?= htmlspecialchars($teks_prodi_edit); ?>" readonly>
                    </div>

                    <div class="data-group">
                        <label>Nama Ketua</label>
                        <input type="text" name="nama_ketua" class="data-input-ormawa" value="<?= htmlspecialchars($data['nama_ketua'] ?? ''); ?>" required>
                    </div>

                    <div class="data-group">
                        <label>NPM Ketua</label>
                        <input type="text" name="npm_ketua" class="data-input-ormawa" value="<?= htmlspecialchars($data['npm_ketua'] ?? ''); ?>" pattern="[0-9]+" title="NPM hanya boleh berisi angka." onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                    </div>

                    <div class="data-group">
                        <label>Nama Sekretaris</label>
                        <input type="text" name="nama_sekretaris" class="data-input-ormawa" value="<?= htmlspecialchars($data['nama_sekretaris'] ?? ''); ?>" required>
                    </div>

                    <div class="data-group">
                        <label>NPM Sekretaris</label>
                        <input type="text" name="npm_sekretaris" class="data-input-ormawa" value="<?= htmlspecialchars($data['npm_sekretaris'] ?? ''); ?>" pattern="[0-9]+" title="NPM hanya boleh berisi angka." onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                    </div>

                    <div class="data-group">
                        <label><?= $jenis_org == 'Ormawa' ? 'Kaprodi (Penanggung Jawab)' : 'Dosen Pembina'; ?></label>
                        <input type="text" class="data-input-ormawa form-text-readonly" value="<?= htmlspecialchars($data['nama_pembina'] ?? 'Belum Diatur'); ?>" readonly>
                        <small class="text-muted-small">*Data ini hanya dapat diubah oleh Administrator.</small>
                    </div>
                </div>
            </div>

            <div class="profile-action-bar">
                <a href="ormawa_profile.php" class="btn-secondary aksi-batal">Kembali</a>
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

        //fungsi dropdown pilih dosen pembina
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
                        text: 'Pastikan data profil organisasi yang Anda masukkan sudah benar.',
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
    </script>
</body>

</html>