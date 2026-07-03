<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>
        alert('Silakan login terlebih dahulu');
        window.location='login.php';
    </script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];

$query_lacak = mysqli_query($koneksi, "
SELECT
    sp.id_surat,
    sp.nomor_surat,
    sp.tanggal_pengajuan,
    sp.status_akhir,
    sp.status_dospem1,
    sp.status_dospem2,
    sp.status_pimpinan,
    sp.posisi_sekarang,
    sp.urutan_sekarang,
    sp.file_surat_final,
    sp.dokumen_hash,
    js.nama_surat
FROM surat_pengajuan sp
JOIN jenis_surat js
ON js.id_jenis = sp.id_jenis
WHERE sp.id_mhs = '$id_mhs'
AND sp.status_akhir <> 'Selesai'
AND sp.status_akhir NOT LIKE 'Ditolak%'
ORDER BY sp.tanggal_pengajuan DESC
");

/* =====================================================
   FORMAT TANGGAL
===================================================== */

function tanggalIndonesia($tanggal)
{
    $bulan = [
        1 => "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember"
    ];

    $time = strtotime($tanggal);

    return date("d", $time) . " " .
        $bulan[(int)date("m", $time)] . " " .
        date("Y", $time);
}

/* =====================================================
   TIMELINE BERDASARKAN JENIS SURAT
===================================================== */

function getTimeline($namaSurat)
{
    $nama = strtolower($namaSurat);

    // Surat Riset
    if (strpos($nama, "riset") !== false) {

        return [
            "Mahasiswa",
            "Dosen Pembimbing 1",
            "Dosen Pembimbing 2",
            "Admin",
            "Wakil Dekan 1",
            "Selesai"
        ];
    }

    // Surat Permohonan Magang
    if (
        strpos($nama, "magang") !== false ||
        strpos($nama, "pkl") !== false
    ) {

        return [
            "Mahasiswa",
            "Admin",
            "Dekan",
            "Selesai"
        ];
    }

    // Surat Aktif Kuliah
    if (strpos($nama, "aktif") !== false) {

        return [
            "Mahasiswa",
            "Pembimbing Akademik",
            "Admin",
            "Wakil Dekan 1",
            "Selesai"
        ];
    }

    // Surat Ormawa
    if (strpos($nama, "ormawa") !== false) {

        return [
            "Sekretaris Ormawa",
            "Ketua Ormawa",
            "Pembina",
            "Admin",
            "Wakil Dekan 1",
            "Selesai"
        ];
    }

    // Default
    return [
        "Mahasiswa",
        "Admin",
        "Selesai"
    ];
}

/* =====================================================
   ICON TIMELINE
===================================================== */

function getIcon($step)
{
    switch (strtolower($step)) {

        case "mahasiswa":
            return "fa-user-graduate";

        case "sekretaris ormawa":
            return "fa-user-pen";

        case "ketua ormawa":
            return "fa-users";

        case "pembina":
            return "fa-user-tie";

        case "dosen pembimbing 1":
            return "fa-chalkboard-user";

        case "dosen pembimbing 2":
            return "fa-chalkboard-user";

        case "pembimbing akademik":
            return "fa-user-check";

        case "admin":
            return "fa-desktop";

        case "dekan":
            return "fa-building-columns";

        case "wakil dekan 1":
            return "fa-building-columns";

        case "selesai":
            return "fa-circle-check";

        default:
            return "fa-circle";
    }
}

/* =====================================================
   HITUNG PROGRESS
===================================================== */

function getProgress($timeline, $posisiSekarang)
{
    $currentStep = 0;

    foreach ($timeline as $i => $step) {

        if (
            strtolower(trim($step))
            ==
            strtolower(trim($posisiSekarang))
        ) {

            $currentStep = $i;
            break;
        }
    }

    $totalStep = count($timeline) - 1;

    if ($totalStep <= 0) {
        $persen = 0;
    } else {
        $persen = ($currentStep / $totalStep) * 100;
    }

    return [
        $currentStep,
        $persen
    ];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Surat</title>
    <link rel="shortcut icon" href="images/Logo UINRIL(2).png">
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- CSS kita isi di Bagian 4 -->

</head>

<body>

<nav class="navbar">

    <a href="#" class="navbar-logo">
        <img src="images/logo2.png" alt="">
    </a>

    <div class="navbar-nav">

        <a href="mhs_beranda.php#home">Beranda</a>

        <a href="mhs_beranda.php#services">
            Pengajuan Surat
        </a>

        <a href="mhs_beranda.php#status-info">
            Status & Informasi
        </a>

        <a href="mhs_lacak.php" class="active">
            Lacak Surat
        </a>

        <a href="mhs_riwayat.php">
            Riwayat Pengajuan
        </a>

    </div>

    <div class="navbar-extra">

        <?php

        $namaLengkap = $_SESSION['nama_lengkap'];
        $idLogin     = $_SESSION['nama'];
        $role        = ucwords($_SESSION['role']);

        $inisial = strtoupper(substr($namaLengkap,0,1));

        ?>

        <div class="user-menu-container">

            <button id="user-btn" class="user-btn">

                <span class="avatar-inisial">
                    <?= $inisial ?>
                </span>

            </button>

            <div id="user-dropdown" class="dropdown-menu">

                <div class="user-info">

                    <span class="user-name">
                        <?= $namaLengkap ?>
                    </span>

                    <span class="user-role">
                        <?= $idLogin ?> - <?= $role ?>
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


<section class="container-lacak">

<div class="page-header">

    <h2>

        <i class="fa-solid fa-location-dot"></i>

        Lacak Surat

    </h2>

    <p>

        Pantau perkembangan surat yang sedang diproses.

    </p>

</div>


<?php if(mysqli_num_rows($query_lacak) > 0): ?>


<?php while($row=mysqli_fetch_assoc($query_lacak)): ?>


<?php

$timeline = getTimeline($row['nama_surat']);

$statusTimeline = $row['status_akhir'];

switch ($statusTimeline) {

    case "Menunggu Dospem 1":
        $statusTimeline = "Dosen Pembimbing 1";
        break;

    case "Menunggu Dospem 2":
        $statusTimeline = "Dosen Pembimbing 2";
        break;

    case "Menunggu Admin":
        $statusTimeline = "Admin";
        break;

    case "Menunggu Wadek 1":
        $statusTimeline = "Wakil Dekan 1";
        break;

    case "Menunggu Dekan":
        $statusTimeline = "Dekan";
        break;

    case "Selesai":
        $statusTimeline = "Selesai";
        break;

    default:
        $statusTimeline = "Mahasiswa";
}

list($currentStep, $progress) = getProgress(
    $timeline,
    $statusTimeline
);

$status = strtolower($row['status_akhir']);

$badgeClass = "badge-menunggu";

if(strpos($status,"selesai")!==false){

    $badgeClass="badge-selesai";

}

elseif(strpos($status,"tolak")!==false){

    $badgeClass="badge-ditolak";

}

elseif(strpos($status,"proses")!==false){

    $badgeClass="badge-proses";

}

?>


<div class="card-surat">

<div class="card-header">

<div class="card-info">

<h3>

<i class="fa-solid fa-file-lines"></i>

<?= htmlspecialchars($row['nama_surat']) ?>

</h3>

<div class="tanggal">

<i class="fa-solid fa-calendar-days"></i>

<?= tanggalIndonesia($row['tanggal_pengajuan']) ?>

</div>

</div>


<div class="badge-status <?= $badgeClass ?>">

<?= htmlspecialchars($row['status_akhir']) ?>

</div>

</div>



<div class="status-sekarang">

<h4>Status Saat Ini</h4>

<p>

<?= htmlspecialchars($row['status_akhir']) ?>

</p>

</div>


<div class="progress">

<div class="progress-bar"

style="width:<?= $progress ?>%;">

</div>

</div>

<div class="progress-text">

Progress <?= round($progress) ?>%

</div>



<div class="timeline">

<?php foreach($timeline as $index=>$step): ?>

<?php

$class="pending";

if($index<$currentStep){

$class="done";

}

elseif($index==$currentStep){

$class="active";

}

?>

<div class="step <?= $class ?>">

<div class="circle">

<?php

if($class=="done"){

?>

<i class="fa-solid fa-check"></i>

<?php

}else{

?>

<i class="fa-solid <?= getIcon($step) ?>"></i>

<?php

}

?>

</div>

<span>

<?= $step ?>

</span>

</div>

<?php endforeach; ?>

</div>



<div class="aksi">

<button
    type="button"
    class="btn-detail"

    data-id="<?= $row['id_surat']; ?>"
    data-nama="<?= htmlspecialchars($row['nama_surat']); ?>"
    data-tanggal="<?= tanggalIndonesia($row['tanggal_pengajuan']); ?>"
    data-status="<?= htmlspecialchars($row['status_akhir']); ?>"
    data-posisi="<?= htmlspecialchars($row['posisi_sekarang']); ?>"
    data-dospem1="<?= htmlspecialchars($row['status_dospem1']); ?>"
    data-dospem2="<?= htmlspecialchars($row['status_dospem2']); ?>"
    data-pimpinan="<?= htmlspecialchars($row['status_pimpinan']); ?>">

<i class="fa-solid fa-eye"></i>

Detail

</button>


<?php if(!empty($row['dokumen_hash'])): ?>

<a

href="verifikasi_surat.php?hash=<?= $row['dokumen_hash'] ?>"

target="_blank"

class="btn-verifikasi">

<i class="fa-solid fa-shield-halved"></i>

Verifikasi

</a>

<?php endif; ?>


</div>

</div>

<?php endwhile; ?>


<?php else: ?>


<div class="card-kosong">

<i class="fa-solid fa-folder-open"></i>

<h3>

Tidak ada surat yang sedang diproses

</h3>

<p>

Semua pengajuan telah selesai.

</p>

</div>


<?php endif; ?>

<!-- ======================================================
     MODAL DETAIL SURAT
======================================================= -->

<div class="modal" id="modalDetail">

    <div class="modal-content">

        <div class="modal-header">

            <h3>
                <i class="fa-solid fa-circle-info"></i>
                Detail Pengajuan
            </h3>

            <button
                type="button"
                class="btn-close"
                id="btnCloseModal">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

        <div class="modal-body">

            <table class="table-detail">

                <tr>
                    <td>Jenis Surat</td>
                    <td id="detailNamaSurat"></td>
                </tr>

                <tr>
                    <td>Tanggal Pengajuan</td>
                    <td id="detailTanggal"></td>
                </tr>

                <tr>
                    <td>Status Pengajuan</td>
                    <td id="detailStatus"></td>
                </tr>

                <tr>
                    <td>Posisi Saat Ini</td>
                    <td id="detailPosisi"></td>
                </tr>

                <tr>
                    <td>Status Dospem 1</td>
                    <td id="detailDospem1"></td>
                </tr>

                <tr>
                    <td>Status Dospem 2</td>
                    <td id="detailDospem2"></td>
                </tr>

                <tr>
                    <td>Status Pimpinan</td>
                    <td id="detailPimpinan"></td>
                </tr>

            </table>

        </div>

    </div>

</div>

</section>

<script>

document.addEventListener("DOMContentLoaded", function () {

    /* ==========================
       DROPDOWN USER
    =========================== */

    const userBtn = document.getElementById("user-btn");
    const dropdown = document.getElementById("user-dropdown");

    userBtn.addEventListener("click", function (e) {

        dropdown.classList.toggle("show");
        e.stopPropagation();

    });

    window.addEventListener("click", function (e) {

        if (!e.target.closest(".user-menu-container")) {

            dropdown.classList.remove("show");

        }

    });

    /* ==========================
       MODAL DETAIL
    =========================== */

    const modal = document.getElementById("modalDetail");
    const btnClose = document.getElementById("btnCloseModal");

    document.querySelectorAll(".btn-detail").forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("detailNamaSurat").innerText =
                this.dataset.nama;

            document.getElementById("detailTanggal").innerText =
                this.dataset.tanggal;

            document.getElementById("detailStatus").innerText =
                this.dataset.status;

            document.getElementById("detailPosisi").innerText =
                this.dataset.posisi;

            document.getElementById("detailDospem1").innerText =
                this.dataset.dospem1;

            document.getElementById("detailDospem2").innerText =
                this.dataset.dospem2;

            document.getElementById("detailPimpinan").innerText =
                this.dataset.pimpinan;

            modal.classList.add("show");

        });

    });

    btnClose.addEventListener("click", function () {

        modal.classList.remove("show");

    });

    window.addEventListener("click", function (e) {

        if (e.target == modal) {

            modal.classList.remove("show");

        }

    });

});

</script>

</body>

</html>