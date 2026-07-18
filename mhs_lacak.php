<?php
session_start();
include "koneksi.php";

$id_mhs = $_SESSION['id_mhs'];
if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$query_lacak = mysqli_query($koneksi, "
SELECT
    sp.id_surat,
    sp.nomor_surat,
    sp.tanggal_pengajuan,
    sp.status_akhir,
    COALESCE(dsr.status_pb1, 'N/A') AS status_pb1,
    COALESCE(dsr.status_pb2, 'N/A') AS status_pb2,
    COALESCE(dak.status_pa, 'N/A') AS status_pa, 
    sp.status_pimpinan,
    sp.file_surat_final,
    sp.dokumen_hash,
    js.nama_surat
FROM surat_pengajuan sp
JOIN jenis_surat js ON js.id_jenis = sp.id_jenis
LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat 
WHERE sp.id_mhs = '$id_mhs'
AND sp.status_akhir <> 'Selesai'
AND sp.status_akhir NOT LIKE 'Ditolak%'
ORDER BY sp.tanggal_pengajuan DESC
");

//fungsi tanggal
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


//fungsi berdasarkan jenis surat
function getTimeline($namaSurat)
{
    $nama = strtolower($namaSurat);
    // Surat Riset
    if (strpos($nama, "riset") !== false) {

        return [
            "Mahasiswa",
            "Dosen Pembimbing 2",
            "Dosen Pembimbing 1",
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

//fungsi icon timeline
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

//fungsi hitung progres
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

//fungsi medapatkan status surat
function getPosisiDariStatus($statusAkhir, $namaSurat)
{
    $status = strtolower($statusAkhir);
    $jenis = strtolower($namaSurat);

    if (strpos($status, 'selesai') !== false) return "Selesai";

    if (strpos($status, 'pembimbing akademik') !== false || strpos($status, 'pa') !== false) {
        return "Pembimbing Akademik";
    }

    //cek posisi Dospem
    if (strpos($status, 'dospem 1') !== false || strpos($status, 'pembimbing 1') !== false) {
        return "Dosen Pembimbing 1";
    }

    if (strpos($status, 'dospem 2') !== false || strpos($status, 'pembimbing 2') !== false) {
        return "Dosen Pembimbing 2";
    }

    //cek posisi Admin
    if (strpos($status, 'admin') !== false || strpos($status, 'tata usaha') !== false) {
        return "Admin";
    }

    //cek posisi Pimpinan (Dekan/Wadek)
    if (
        strpos($status, 'pimpinan') !== false ||
        strpos($status, 'dekan') !== false ||
        strpos($status, 'wadek') !== false
    ) {
        if (strpos($jenis, 'magang') !== false || strpos($jenis, 'pkl') !== false) {
            return "Dekan";
        } else {
            return "Wakil Dekan 1";
        }
    }

    //posisi default saat baru diajukan
    return "Mahasiswa";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <nav class="my-navbar">
        <a href="#" class="my-navbar-logo">
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="my-navbar-nav">
            <a href="mhs_beranda.php#home">Beranda</a>
            <a href="mhs_beranda.php#services">Pengajuan Surat</a>
            <a href="mhs_beranda.php#status-info">Status & Informasi</a>
            <a href="mhs_lacak.php">Lacak Surat</a>
            <a href="mhs_riwayat.php">Riwayat Pengajuan</a>
        </div>

        <div class="my-navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>

                <div id="user-dropdown" class="my-dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= ($namaLengkap) ?></span>
                        <span class="user-role"><?= $idLogin ?> - <?= $role ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="container-lacak">
        <div class="riwayat-permohonan-header">
            <h2>Lacak Surat</h2>
            <p>Pantau perkembangan surat yang sedang diajukan.</p>
        </div>

        <?php if (mysqli_num_rows($query_lacak) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query_lacak)): ?>
                <?php
                $timeline = getTimeline($row['nama_surat']);
                $posisi_saat_ini = getPosisiDariStatus($row['status_akhir'], $row['nama_surat']);
                list($currentStep, $persen) = getProgress($timeline, $posisi_saat_ini);
                ?>

                <div class="card-surat">
                    <div class="card-header">
                        <div class="card-info">
                            <h3>
                                <i class="fa-solid fa-file-lines"></i>
                                <?= htmlspecialchars($row['nama_surat']) ?>
                            </h3>
                        </div>

                        <div class="tanggal">
                            <i class="fa-solid fa-calendar-days"></i>
                            <?= tanggalIndonesia($row['tanggal_pengajuan']) ?>
                            &nbsp;|&nbsp;
                            <i class="fa-regular fa-clock"></i>
                            <?= date('H:i', strtotime($row['tanggal_pengajuan'])) ?> WIB
                        </div>
                    </div>

                    <div class="status-sekarang">
                        <h4>Status Saat Ini</h4>
                        <p>
                            <?= htmlspecialchars($row['status_akhir']) ?>
                        </p>
                    </div>

                    <div class="progress">
                        <div class="progress-bar" style="width:<?= $persen ?>%;"></div>
                    </div>

                    <div class="progress-text">
                        Progress <?= round($persen) ?>%
                    </div>

                    <div class="timeline">
                        <?php foreach ($timeline as $index => $step): ?>
                            <?php
                            $class = "pending";
                            if ($index < $currentStep) {
                                $class = "done";
                            } elseif ($index == $currentStep) {
                                $class = "active";
                            }
                            ?>

                            <div class="step <?= $class ?>">
                                <div class="circle">
                                    <?php if ($class == "done"): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php else: ?>
                                        <i class="fa-solid <?= getIcon($step) ?>"></i>
                                    <?php endif; ?>
                                </div>

                                <span>
                                    <?= $step ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class="card-kosong">
                <i class="fa-solid fa-folder-open"></i>
                <h3>
                    Tidak ada surat yang sedang diproses
                </h3>
            </div>

        <?php endif; ?>
    </section>

    <footer class="footer-form-minimal">
        <p>&copy; 2026 SIPATU FST UIN RIL | Dibuat oleh Ghania Ridha Khairiah.</p>
    </footer>

    <script>
        /*fungsi dropdown user*/
        document.addEventListener("DOMContentLoaded", function() {
            const userBtn = document.getElementById("user-btn");
            const dropdown = document.getElementById("user-dropdown");

            userBtn.addEventListener("click", function(e) {
                dropdown.classList.toggle("show");
                e.stopPropagation();
            });

            window.addEventListener("click", function(e) {
                if (!e.target.closest(".user-menu-container")) {
                    dropdown.classList.remove("show");
                }
            });
        });
    </script>
</body>

</html>