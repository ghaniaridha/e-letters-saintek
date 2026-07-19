<?php
session_start();
include "koneksi.php";

// Proteksi halaman: pastikan yang login adalah Ormawa
if (!isset($_SESSION['id_ormawa'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_ormawa = $_SESSION['id_ormawa'];

$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Organisasi';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Ormawa';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

// QUERY UTAMA: Mengambil surat Ormawa yang sedang berjalan (Bukan Selesai / Ditolak)
$query_lacak = mysqli_query($koneksi, "
    SELECT
        sp.id_surat,
        sp.nomor_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir,
        sp.posisi_sekarang,
        js.nama_surat
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON js.id_jenis = sp.id_jenis
    WHERE sp.id_ormawa = '$id_ormawa'
    AND sp.status_akhir <> 'Selesai'
    AND sp.status_akhir NOT LIKE 'Ditolak%'
    ORDER BY sp.tanggal_pengajuan DESC
");

// Fungsi format tanggal Indonesia
function tanggalIndonesia($tanggal)
{
    $bulan = [
        1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];
    $time = strtotime($tanggal);
    return date("d", $time) . " " . $bulan[(int)date("m", $time)] . " " . date("Y", $time);
}

// Fungsi struktur alur langkah khusus Ormawa (Mendukung Gedung & Dana)
function getTimelineOrmawa($namaSurat)
{
    $nama = strtolower($namaSurat);
    
    if (strpos($nama, 'dana') !== false) {
        // Alur jika Surat Bantuan Dana Ormawa
        return [
            "Pengajuan Ormawa",
            "Pembina Ormawa",
            "Admin",
            "Wakil Dekan 3",
            "Selesai"
        ];
    } else {
        // Alur jika Surat Peminjaman Ruangan / Gedung
        return [
            "Pengajuan Ormawa",
            "Pembina Ormawa",
            "Admin",
            "Kasubbag Umum",
            "Selesai"
        ];
    }
}

// Fungsi pemetaan ikon FontAwesome pendukung timeline
function getIconOrmawa($step)
{
    switch (strtolower($step)) {
        case "pengajuan ormawa":
            return "fa-users";
        case "pembina ormawa":
            return "fa-user-tie";
        case "admin":
            return "fa-desktop";
        case "kasubbag umum":
            return "fa-user-check";
        case "wakil dekan 3":
            return "fa-building-columns";
        case "selesai":
            return "fa-circle-check";
        default:
            return "fa-circle";
    }
}

// Fungsi kalkulasi persentase bar progres
function getProgressOrmawa($timeline, $posisiSekarang, $statusAkhir)
{
    $currentStep = 0;
    $posisiClean = trim(strtolower($posisiSekarang));
    $statusClean = trim(strtolower($statusAkhir));

    // Cek kecocokan berdasarkan posisi_sekarang atau status_akhir bypass
    foreach ($timeline as $i => $step) {
        $stepClean = strtolower(trim($step));
        
        if ($posisiClean == 'pembina' && $stepClean == 'pembina ormawa') {
            $currentStep = $i;
            break;
        }
        if ($posisiClean == 'admin' && $stepClean == 'admin') {
            $currentStep = $i;
            break;
        }
        if ((strpos($statusClean, 'kasubbag') !== false || $posisiClean == 'kasubbag') && $stepClean == 'kasubbag umum') {
            $currentStep = $i;
            break;
        }
        if ((strpos($statusClean, 'wadek 3') !== false || $posisiClean == 'wadek 3') && $stepClean == 'wakil dekan 3') {
            $currentStep = $i;
            break;
        }
    }

    // Jika status terdeteksi baru diajukan awal
    if ($currentStep == 0 && strpos($statusClean, 'pembina') !== false) {
        $currentStep = 1;
    }

    $totalStep = count($timeline) - 1;
    $persen = ($totalStep <= 0) ? 0 : ($currentStep / $totalStep) * 100;

    return [$currentStep, $persen];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Surat Ormawa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png">
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        /* Penyelarasan style kontainer lacak agar rapi di bawah navbar fixed */
        .container-lacak {
            padding: 120px 7% 80px;
            min-height: 85vh;
            background-color: #f8fafc;
        }
        .riwayat-permohonan-header {
            margin-bottom: 35px;
        }
        .card-surat {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 25px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 15px;
            background: transparent;
        }
        .card-info h3 {
            font-size: 18px;
            color: #1e293b;
            margin: 0;
            font-weight: 600;
        }
        .card-info h3 i {
            margin-right: 8px;
            color: #0284c7;
        }
        .tanggal {
            font-size: 13px;
            color: #64748b;
        }
        .status-sekarang {
            margin: 20px 0;
        }
        .status-sekarang h4 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-sekarang p {
            font-size: 16px;
            color: #0f172a;
            font-weight: 600;
            margin: 0;
        }
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e2e8f0;
            margin-bottom: 8px;
        }
        .progress-bar {
            background-color: #0284c7;
        }
        .progress-text {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 25px;
        }
        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 10px;
        }
        .step {
            text-align: center;
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .step .circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e2e8f0;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-bottom: 8px;
            z-index: 2;
            transition: all 0.3s ease;
        }
        .step span {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        /* State Warna Progres */
        .step.done .circle {
            background-color: #10b981;
            color: #ffffff;
        }
        .step.done span {
            color: #10b981;
        }
        .step.active .circle {
            background-color: #0284c7;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.2);
        }
        .step.active span {
            color: #0284c7;
            font-weight: 600;
        }
        .card-kosong {
            text-align: center;
            background: #fff;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            color: #94a3b8;
        }
        .card-kosong i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }
    </style>
</head>

<body>

    <!-- NAVBAR FIXED ORMAWA -->
    <nav class="my-navbar">
        <a href="#" class="my-navbar-logo">
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="my-navbar-nav">
            <a href="ormawa_beranda.php#home">Beranda</a>
            <a href="ormawa_beranda.php#services">Pengajuan Surat</a>
            <a href="ormawa_beranda.php#status-info">Status & Informasi</a>
            <a href="ormawa_lacak.php">Lacak Surat</a>
            <a href="ormawa_riwayat.php">Riwayat Permohonan</a>
        </div>

        <div class="my-navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>

                <div id="user-dropdown" class="my-dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap) ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- AREA TIMELINE LACAK SURAT -->
    <section class="container-lacak">
        <div class="riwayat-permohonan-header">
            <h2>Lacak Pengajuan Surat Organisasi</h2>
            <p>Pantau perkembangan alur verifikasi berkas surat aktif Anda.</p>
        </div>

        <?php if (mysqli_num_rows($query_lacak) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query_lacak)): ?>
                <?php
                $timeline = getTimelineOrmawa($row['nama_surat']);
                list($currentStep, $persen) = getProgressOrmawa($timeline, $row['posisi_sekarang'], $row['status_akhir']);
                ?>

                <div class="card-surat">
                    <div class="card-header">
                        <div class="card-info">
                            <h3>
                                <i class="fa-solid fa-file-signature"></i>
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
                        <h4>Posisi & Status Terakhir</h4>
                        <p><?= htmlspecialchars($row['status_akhir']) ?></p>
                    </div>

                    <div class="progress">
                        <div class="progress-bar" style="width:<?= $persen ?>%;"></div>
                    </div>

                    <div class="progress-text">
                        Progres Validasi Berkas <?= round($persen) ?>%
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
                                        <i class="fa-solid <?= getIconOrmawa($step) ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <span><?= $step ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="card-kosong">
                <i class="fa-solid fa-folder-open"></i>
                <h3>Tidak ada permohonan surat yang sedang diproses aktif</h3>
            </div>
        <?php endif; ?>
    </section>

    <!-- SCRIPT DROPDOWN NAVBAR -->
    <script>
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