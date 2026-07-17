<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_ormawa'])) {
    echo "<script>
            alert('Silakan login terlebih dahulu');
            window.location='index.php';
          </script>";
    exit;
}

$id_ormawa = $_SESSION['id_ormawa'];

$ormawa = mysqli_fetch_assoc(mysqli_query($koneksi,"
    SELECT *
    FROM ormawa
    WHERE id_ormawa='$id_ormawa'
"));
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Pengajuan Dana Ormawa</title>

    <link rel="stylesheet"
          href="style.css?v=<?=time();?>">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>

<nav class="navbar">

    <a href="#" class="navbar-logo">
        <img src="images/logo2.png" alt="navbar-logo">
    </a>

    <div class="navbar-nav">

        <a href="ormawa_beranda.php#home">Beranda</a>

        <a href="ormawa_beranda.php#services">
            Pengajuan Surat
        </a>

        <a href="ormawa_lacak.php">
            Lacak Surat
        </a>

        <a href="ormawa_riwayat.php">
            Riwayat Pengajuan
        </a>

    </div>

    <div class="navbar-extra">

        <?php
        $namaLengkap = $ormawa['nama_ormawa'];
        $idLogin     = $ormawa['username'];
        $role        = 'ORMAWA';

        $inisial = strtoupper(substr($namaLengkap,0,1));
        ?>

        <div class="user-menu-container">

            <button id="user-btn" class="user-btn">
                <span class="avatar-inisial">
                    <?= htmlspecialchars($inisial) ?>
                </span>
            </button>

            <div id="user-dropdown" class="dropdown-menu">

                <div class="user-info">

                    <span class="user-name">
                        <?= htmlspecialchars($namaLengkap) ?>
                    </span>

                    <span class="user-role">
                        <?= htmlspecialchars($idLogin) ?> - <?= $role ?>
                    </span>

                </div>

                <div class="divider"></div>

                <a href="logout.php" class="logout-btn">
                    <span>Keluar</span>
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>

            </div>

        </div>

    </div>

</nav>

<div class="generate-wrapper">

    <div class="page-header">

        <h1>Pengajuan Dana Ormawa</h1>

        <p>
            Lengkapi data berikut untuk melakukan
            pengajuan dana kegiatan organisasi mahasiswa.
        </p>

    </div>

    <div class="generate-card">

        <form action="preview_pengajuan_dana.php"
              method="POST"
              enctype="multipart/form-data"
              onsubmit="konfirmasiPengajuan(event)">

            <input type="hidden"
                   name="id_jenis"
                   value="12">

            <input type="hidden"
                   name="id_ormawa"
                   value="<?= $id_ormawa ?>">

            <div class="form-group">
                <label>Nama Ormawa</label>

                <input type="text"
                       value="<?= $ormawa['nama_ormawa'] ?>"
                       readonly>
            </div>

            <div class="form-group">
                <label>Ketua Ormawa</label>

                <input type="text"
                       value="<?= $ormawa['nama_ketua'] ?>"
                       readonly>
            </div>

            <div class="form-group">
                <label>Sekretaris Ormawa</label>

                <input type="text"
                       value="<?= $ormawa['nama_sekretaris'] ?>"
                       readonly>
            </div>

            <div class="form-group">
                <label>Nama Kegiatan</label>

                <input type="text"
                       name="nama_kegiatan"
                       required>
            </div>

            <div class="form-group">
                <label>Tema Kegiatan</label>

                <input type="text"
                       name="tema_kegiatan"
                       required>
            </div>

            <div class="form-group">
                <label>Tanggal Kegiatan</label>

                <input type="date"
                       name="tanggal_kegiatan"
                       required>
            </div>

            <div class="form-group">
                <label>Tempat Kegiatan</label>

                <input type="text"
                       name="tempat_kegiatan"
                       required>
            </div>

            <div class="form-group">
                <label>Nominal Pengajuan Dana</label>

                <input type="number"
                       name="nominal_pengajuan"
                       min="1"
                       required>
            </div>

            <div class="form-group">
                <label>Deskripsi Kegiatan</label>

                <textarea name="deskripsi_kegiatan"
                          rows="5"
                          required></textarea>
            </div>

            <div class="form-group">

                <label>Upload Proposal Kegiatan (PDF)</label>

                <input type="file"
                       name="proposal"
                       accept=".pdf"
                       required>

            </div>

            <div class="form-actions">

                <a href="ormawa_daftar_surat.php"
                   class="btn-back-form">
                    Kembali
                </a>

                <button type="submit"
                        class="btn-generate">
                    Ajukan Pengajuan Dana
                </button>

            </div>

        </form>

    </div>

</div>

<footer class="footer-form-minimal">

    <p>
        &copy; 2026 Fakultas Sains dan Teknologi UIN RIL.
        Dibuat oleh Ghania Ridha Khairiah.
    </p>

</footer>

<script>

document.addEventListener('DOMContentLoaded', function(){

    const userBtn = document.getElementById('user-btn');
    const dropdown = document.getElementById('user-dropdown');

    userBtn.addEventListener('click', function(event){

        dropdown.classList.toggle('show');

        event.stopPropagation();

    });

    window.addEventListener('click', function(event){

        if(
            !event.target.matches('#user-btn')
            &&
            !event.target.closest('#user-btn')
        ){
            dropdown.classList.remove('show');
        }

    });

});

function konfirmasiPengajuan(e){

    e.preventDefault();

    const form = e.target;

    Swal.fire({

        title:'Ajukan Pengajuan Dana?',
        text:'Pastikan seluruh data sudah benar.',
        icon:'question',
        showCancelButton:true,
        confirmButtonColor:'#1e3a8a',
        cancelButtonColor:'#6b7280',
        confirmButtonText:'Ya, Ajukan',
        cancelButtonText:'Periksa Lagi',
        heightAuto:false

    }).then((result)=>{

        if(result.isConfirmed){
            form.submit();
        }

    });

}

</script>

</body>
</html>