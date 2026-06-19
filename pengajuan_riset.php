<?php
session_start();
include "koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mhs = $_SESSION['id_mhs'];
    $id_jenis = 1;
    $judul_skripsi = mysqli_real_escape_string($koneksi, $_POST['judul_skripsi']);
    $lokasi_riset = mysqli_real_escape_string($koneksi, $_POST['lokasi_riset']);
    $tujuan_surat = mysqli_real_escape_string($koneksi, $_POST['tujuan_surat']);
    $pesan = mysqli_real_escape_string($koneksi, $_POST['pesan']);
    $id_dosen = $_SESSION['id_pb1'];

    $query = "INSERT INTO pengajuan_surat 
              (id_mhs, id_jenis, judul_skripsi, lokasi_riset, tujuan_surat, id_dosen, pesan_tambahan, status_pengajuan, tgl_pengajuan) 
              VALUES 
              ('$id_mhs', '$id_jenis', '$judul_skripsi', '$lokasi_riset', '$tujuan_surat', '$id_dosen', '$pesan', 'Menunggu Persetujuan Pembimbing', NOW())";

    if (mysqli_query($koneksi, $query)) {
        $id_pengajuan_baru = mysqli_insert_id($koneksi);

        $q_syarat = mysqli_query($koneksi, "SELECT id_syarat, nama_syarat FROM syarat_surat");
        $direktori = "files_riset/";
        if (!is_dir($direktori)) mkdir($direktori, 0777, true);

        while ($row = mysqli_fetch_assoc($q_syarat)) {
            $key = $row['nama_syarat'];
            $id_syarat = $row['id_syarat'];

            if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
                $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
                $nama_file_unik = $_SESSION['npm'] . "_syarat" . $id_syarat . "_" . time() . "." . $ext;

                if (move_uploaded_file($_FILES[$key]['tmp_name'], $direktori . $nama_file_unik)) {
                    $q_lampiran = "INSERT INTO lampiran_pengajuan (id_pengajuan, id_syarat, file_path, tgl_unggah) 
                                   VALUES ('$id_pengajuan_baru', '$id_syarat', '$nama_file_unik', NOW())";
                    mysqli_query($koneksi, $q_lampiran);
                }
            }
        }

        $_SESSION['status'] = "success";
        $_SESSION['pesan'] = "Surat Permohonan Riset Berhasil Dibuat!";

        header("Location: mhs_riwayat.php");
        exit();
    } else {
        $_SESSION['status'] = "error";
        $_SESSION['pesan'] = "Gagal mengirim pengajuan: " . mysqli_error($koneksi);
        header("Location: mhs_riwayat.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Pra Riset</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/AKADEMIK FST2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="mhs_dashboard.php">Beranda</a>
            <a href="mhs_dashboard.php#services">Layanan Akademik</a>
            <a href="mhs_dashboard.php#riwayat">Informasi</a>
            <a href="mhs_riwayat.php">Riwayat Permohonan</a>
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

    <section id="daftar-surat" class="daftar-surat">
        <div class="daftar-surat-header">
            <h2>Permohonan Riset</h2>
        </div>

        <form class="permintaan" method="POST" enctype="multipart/form-data">
            <div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="nama_npm" class="col-sm-3 col-form-label">Nama/NPM</label>
                        <div class="col-sm-9">

                            <input class="form-control" type="text" id="nama_npm"
                                value="<?= htmlspecialchars(strtoupper($namaLengkap)) ?> / <?= isset($_SESSION['npm']) ? $_SESSION['npm'] : '' ?>"
                                readonly required>

                            <input type="hidden" name="nama" value="<?= htmlspecialchars(strtoupper($namaLengkap)) ?>">
                            <input type="hidden" name="npm" value="<?= isset($_SESSION['npm']) ? $_SESSION['npm'] : '' ?>">

                        </div>
                    </div>
                </div>

                <?php
                $semester = isset($_SESSION['semester']) ? $_SESSION['semester'] : '';
                $prodi = isset($_SESSION['prodi']) ? $_SESSION['prodi'] : '';
                ?>
                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="semester_prodi" class="col-sm-3 col-form-label">Semester/Program Studi</label>
                        <div class="col-sm-9">

                            <input class="form-control" type="text" id="semester_prodi"
                                value="<?= isset($_SESSION['semester']) ? $_SESSION['semester'] : '' ?> / <?= isset($_SESSION['prodi']) ? strtoupper($_SESSION['prodi']) : '' ?>"
                                readonly required>

                            <input type="hidden" name="semester" value="<?= isset($_SESSION['semester']) ? $_SESSION['semester'] : '' ?>">
                            <input type="hidden" name="prodi" value="<?= isset($_SESSION['prodi']) ? strtoupper($_SESSION['prodi']) : '' ?>">

                        </div>
                    </div>
                </div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="judul_skripsi" class="col-sm-3 col-form-label">Judul Skripsi</label>
                        <div class="col-sm-9">

                            <input class="form-control" type="text" id="judul_skripsi" name="judul_skripsi" required>

                        </div>
                    </div>
                </div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="lokasi_riset" class="col-sm-3 col-form-label">Lokasi Penelitian</label>
                        <div class="col-sm-9">

                            <input class="form-control" type="text" id="lokasi_riset" name="lokasi_riset" required>

                        </div>
                    </div>
                </div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="tujuan_surat" class="col-sm-3 col-form-label">Surat Ditujukan Kepada</label>
                        <div class="col-sm-9">

                            <input class="form-control" type="text" id="tujuan_surat" name="tujuan_surat" required>

                        </div>
                    </div>
                </div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="file_cover" class="col-sm-3 col-form-label">Unggah Cover ACC Proposal Skripsi</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="file" id="file_cover" name="file_cover" accept=".pdf" required>
                        </div>
                    </div>
                </div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="file_ukt" class="col-sm-3 col-form-label">Unggah Slip UKT Terakhir</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="file" id="file_ukt" name="file_ukt" accept=".pdf" required>
                        </div>
                    </div>
                </div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="file_khs" class="col-sm-3 col-form-label">Unggah KHS Semester Terakhir</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="file" id="file_khs" name="file_khs" accept=".pdf" required>
                        </div>
                    </div>
                </div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="file_ktm" class="col-sm-3 col-form-label">Unggah KTM</label>
                        <div class="col-sm-9">
                            <input class="form-control" type="file" id="file_ktm" name="file_ktm" accept=".pdf" required>
                        </div>
                    </div>
                </div>

                <div class="form-isolated">
                    <div class="mb-3 row align-items-center">
                        <label for="pesan" class="col-sm-3 col-form-label">Pesan Tambahan (Optional)</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="pesan" name="pesan" rows="4"
                                placeholder="Tulis pesan jika ada keterangan tambahan...">
                                    </textarea>
                        </div>
                    </div>
                </div>


                <div class="submit-container">
                    <button type="submit" class="btn-input">Kirim</button>
                </div>
        </form>
    </section>

    <footer class="footer-section">
        <div class="footer-wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M1200,0H0V60.4C138.85,108.62,298.54,125,441.77,105.81,595.6,85.19,705.51,20.89,864,24.7c124.62,3,212.87,41.4,336,65.7V0Z" class="shape-fill"></path>
            </svg>
        </div>
        <div class="footer-container">
            <div class="footer-col info-col">
                <h3>Layanan Akademik FST</h3>
                <p>Sistem Informasi Manajemen Persuratan Fakultas Sains dan Teknologi UIN Raden Intan Lampung.</p>
                <div class="contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Jl. Letkol H. Endro Suratmin, Sukarame, Bandar Lampung.</span>
                </div>
            </div>

            <div class="footer-col links-col">
                <h4>Tautan Cepat</h4>
                <ul>
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#services">Layanan Akademik</a></li>
                    <li><a href="#riwayat">Lacak</a></li>
                    <li><a href="#kalender">Riwayat Permohonan</a></li>
                </ul>
            </div>

            <div class="footer-col contact-col">
                <h4>Pusat Bantuan</h4>
                <div class="contact-item">
                    <i class="fa-solid fa-envelope"></i>
                    <span>akademik.fst@radenintan.ac.id</span>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-phone"></i>
                    <span>(0721) 1234567</span>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-clock"></i>
                    <span>Senin - Jumat: 08.00 - 16.00 WIB</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 Layanan Akademik FST UIN RIL. Dibuat oleh Ghania Ridha Khairiah.</p>
        </div>
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
        });
    </script>

</body>

</html>