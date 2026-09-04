<?php
session_start();
include "koneksi.php";

$kode_pencarian = isset($_GET['kode']) ? mysqli_real_escape_string($koneksi, strtoupper(trim($_GET['kode']))) : '';

if (empty($kode_pencarian)) {
    header("Location: mhs_input_lacak.php");
    exit;
}

$query_lacak = mysqli_query($koneksi, "
    SELECT
        sp.id_surat,
        sp.nomor_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir,
        sp.posisi_sekarang,
        sp.status_pimpinan,
        sp.file_surat_final,
        sp.kode_pelacakan,
        sp.waktu_verif_admin, 
        sp.waktu_verif_pimpinan, 
        sp.waktu_selesai, 
        COALESCE(dsr.status_pb1, 'N/A') AS status_pb1,
        COALESCE(dsr.status_pb2, 'N/A') AS status_pb2,
        COALESCE(dak.status_pa, 'N/A') AS status_pa,
        COALESCE(dpr.waktu_pembina, dpd.waktu_pembina) AS waktu_pembina,
        dsr.waktu_pb1, 
        dsr.waktu_pb2, 
        dak.waktu_pa,
        js.nama_surat
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    LEFT JOIN detail_sk_lulus dsl ON sp.id_surat = dsl.id_surat
    LEFT JOIN detail_skmk dsk ON sp.id_surat = dsk.id_surat
    
    WHERE sp.kode_pelacakan = '$kode_pencarian'
    LIMIT 1
");

if (mysqli_num_rows($query_lacak) == 0) {
    $_SESSION['error'] = 'Kode Pelacakan tidak valid atau tidak ditemukan.';
    header("Location: mhs_input_lacak.php");
    exit;
}

// ==========================================
// FUNGSI TIMELINE MHS & ORMAWA
// ==========================================
function tanggalIndonesia($tanggal)
{
    $bulan = [1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    $time = strtotime($tanggal);
    return date("d", $time) . " " . $bulan[(int)date("m", $time)] . " " . date("Y", $time);
}

function getStepTimestamp($step, $row)
{
    $step = strtolower(trim($step));
    $time = null;

    switch ($step) {
        case "mahasiswa":
        case "pengajuan ormawa":
        case "pemohon":
            $time = $row['tanggal_pengajuan'];
            break;
        case "dosen pembimbing 2":
            $time = $row['waktu_pb2'];
            break;
        case "dosen pembimbing 1":
            $time = $row['waktu_pb1'];
            break;
        case "pembimbing akademik":
            $time = $row['waktu_pa'];
            break;
        case "pembina ormawa":
            $time = $row['waktu_pembina'];
            break;
        case "admin":
            $time = $row['waktu_verif_admin'];
            break;
        case "wakil dekan 1":
        case "wakil dekan 2":
        case "kasubbag tu":
        case "pimpinan":
            $time = $row['waktu_verif_pimpinan'];
            break;
        case "penomoran":
            $time = $row['waktu_selesai'];
            break;
        case "penjadwalan":
            $time = $row['waktu_selesai'];
            break;
        case "selesai":
            $time = $row['waktu_selesai'];
            break;
    }

    if (empty($time) || $time == '0000-00-00 00:00:00') {
        return "";
    }

    $bulanSingkat = [1 => "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
    $t = strtotime($time);

    $tanggal = date("d", $t) . " " . $bulanSingkat[(int)date("m", $t)] . " " . date("Y", $t);
    $jam = date("H:i", $t) . " WIB";

    return "<span class='time-date'>$tanggal</span><span class='time-hour'>$jam</span>";
}

function getTimeline($namaSurat)
{
    $nama = strtolower($namaSurat);
    if (strpos($nama, 'dana') !== false) return ["Pengajuan Ormawa", "Pembina Ormawa", "Admin", "Wakil Dekan 2", "Penjadwalan", "Selesai"];
    if (strpos($nama, 'ruangan') !== false || strpos($nama, 'peminjaman') !== false) return ["Pengajuan Ormawa", "Pembina Ormawa", "Admin", "Kasubbag TU", "Penjadwalan", "Selesai"];
    if (strpos($nama, "riset") !== false) return ["Mahasiswa", "Dosen Pembimbing 2", "Dosen Pembimbing 1", "Admin", "Wakil Dekan 1", "Penomoran", "Selesai"];

    if (strpos($nama, "magang") !== false || strpos($nama, "pkl") !== false || strpos($nama, "lulus") !== false || strpos($nama, "masih kuliah") !== false || strpos($nama, "skmk") !== false) {
        return ["Mahasiswa", "Admin", "Wakil Dekan 1", "Penomoran", "Selesai"];
    }

    if (strpos($nama, "aktif") !== false) return ["Mahasiswa", "Pembimbing Akademik", "Admin", "Wakil Dekan 1", "Penomoran", "Selesai"];
    return ["Pemohon", "Admin", "Pimpinan", "Penomoran", "Selesai"];
}

function getIcon($step)
{
    switch (strtolower($step)) {
        case "mahasiswa":
            return "fa-user-graduate";
        case "pengajuan ormawa":
            return "fa-users";
        case "dosen pembimbing 1":
        case "dosen pembimbing 2":
        case "pembimbing akademik":
            return "fa-chalkboard-user";
        case "pembina ormawa":
            return "fa-user-tie";
        case "admin":
            return "fa-desktop";
        case "wakil dekan 1":
        case "wakil dekan 2":
        case "kasubbag tu":
        case "pimpinan":
            return "fa-building-columns";
        case "penjadwalan":
            return "fa-calendar-days";
        case "penomoran":
            return "fa-stamp";
        case "selesai":
            return "fa-circle-check";
        default:
            return "fa-circle";
    }
}

function getPosisiDariStatus($statusAkhir, $namaSurat)
{
    $status = strtolower($statusAkhir);
    if (strpos($status, 'selesai') !== false) return "Selesai";
    if (strpos($status, 'penomoran') !== false) return "Penomoran";
    if (strpos($status, 'pembimbing akademik') !== false || strpos($status, 'pa') !== false) return "Pembimbing Akademik";
    if (strpos($status, 'dospem 1') !== false || strpos($status, 'pembimbing 1') !== false) return "Dosen Pembimbing 1";
    if (strpos($status, 'dospem 2') !== false || strpos($status, 'pembimbing 2') !== false) return "Dosen Pembimbing 2";
    if (strpos($status, 'admin') !== false || strpos($status, 'tata usaha') !== false) return "Admin";
    if (strpos($status, 'pimpinan') !== false || strpos($status, 'dekan') !== false || strpos($status, 'wadek') !== false) return "Wakil Dekan 1";
    return "Mahasiswa";
}

function getProgressUniversal($timeline, $posisiSekarang, $statusAkhir, $namaSurat)
{
    $nama = strtolower($namaSurat);
    $currentStep = 0;

    if (strpos($nama, 'dana') !== false || strpos($nama, 'ruangan') !== false || strpos($nama, 'peminjaman') !== false) {
        $posisiClean = trim(strtolower((string)$posisiSekarang));
        $statusClean = trim(strtolower((string)$statusAkhir));

        if (strpos($statusClean, 'penjadwalan') !== false || strpos($statusClean, 'jadwal') !== false) {
            $idx = array_search('Penjadwalan', $timeline);
            if ($idx !== false) $currentStep = $idx;
        } elseif (strpos($posisiClean, 'kasubbag') !== false || strpos($statusClean, 'kasubbag') !== false) {
            $idx = array_search('Kasubbag TU', $timeline);
            if ($idx !== false) $currentStep = $idx;
        } elseif (strpos($posisiClean, 'wadek 2') !== false || strpos($statusClean, 'wadek 2') !== false || strpos($statusClean, 'wakil dekan 2') !== false) {
            $idx = array_search('Wakil Dekan 2', $timeline);
            if ($idx !== false) $currentStep = $idx;
        } elseif (strpos($posisiClean, 'admin') !== false || (strpos($statusClean, 'admin') !== false && strpos($statusClean, 'ditolak') === false)) {
            $idx = array_search('Admin', $timeline);
            if ($idx !== false) $currentStep = $idx;
        } elseif (strpos($posisiClean, 'pembina') !== false || strpos($statusClean, 'pembina') !== false) {
            $idx = array_search('Pembina Ormawa', $timeline);
            if ($idx !== false) $currentStep = $idx;
        }
    } else {
        $posisi_saat_ini = getPosisiDariStatus($statusAkhir, $namaSurat);
        foreach ($timeline as $i => $step) {
            if (strtolower(trim($step)) == strtolower(trim($posisi_saat_ini))) {
                $currentStep = $i;
                break;
            }
        }
    }

    if (strpos(strtolower(trim($statusAkhir)), 'selesai') !== false) {
        $currentStep = count($timeline) - 1;
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
    <title>Hasil Lacak Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png">
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
    <nav class="my-navbar">
        <a href="#" id="my-hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="index.php" class="my-navbar-logo">
            <img src="images/logo2.png" alt="navbar-logo" ;">
        </a>
    </nav>

    <section class="container-lacak">
        <div class="riwayat-permohonan-header">
            <h2>Hasil Pelacakan</h2>
            <p class="text-kode-lacak">
                Kode Lacak: <strong class="highlight-kode"><?= htmlspecialchars($kode_pencarian); ?></strong>
            </p>
        </div>

        <?php while ($row = mysqli_fetch_assoc($query_lacak)): ?>
            <?php
            $timeline = getTimeline($row['nama_surat']);
            list($currentStep, $persen) = getProgressUniversal($timeline, $row['posisi_sekarang'], $row['status_akhir'], $row['nama_surat']);
            ?>

            <div class="card-surat">
                <div class="card-header">
                    <div class="card-info">
                        <h3><i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($row['nama_surat']) ?></h3>
                    </div>
                    <div class="tanggal">
                        <i class="fa-solid fa-calendar-days"></i> <?= tanggalIndonesia($row['tanggal_pengajuan']) ?> &nbsp;|&nbsp;
                        <i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($row['tanggal_pengajuan'])) ?> WIB
                    </div>
                </div>

                <div class="status-sekarang">
                    <h4>Status Saat Ini</h4>
                    <p><?= htmlspecialchars($row['status_akhir']) ?></p>
                </div>

                <div class="progress">
                    <div class="progress-bar" style="width:<?= $persen ?>%;"></div>
                </div>

                <div class="progress-text">Progress <?= round($persen) ?>%</div>

                <div class="timeline">
                    <?php foreach ($timeline as $index => $step): ?>
                        <?php
                        $class = "pending";
                        if ($index < $currentStep) {
                            $class = "done";
                        } elseif ($index == $currentStep) {
                            $class = "active";
                        }

                        $waktu_step = getStepTimestamp($step, $row);
                        ?>
                        <div class="step <?= $class ?>">
                            <div class="circle">
                                <?php if ($class == "done"): ?>
                                    <i class="fa-solid fa-check"></i>
                                <?php else: ?>
                                    <i class="fa-solid <?= getIcon($step) ?>"></i>
                                <?php endif; ?>
                            </div>

                            <div class="step-info">
                                <span class="step-name"><?= $step ?></span>
                                <?php if (!empty($waktu_step)): ?>
                                    <div class="step-time">
                                        <?= $waktu_step ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <div class="lacak-ulang-container">
            <a href="mhs_input_lacak.php" class="btn-lacak-ulang">
                <i class="fa-solid fa-magnifying-glass-location"></i> Lacak Surat Lainnya
            </a>
        </div>
    </section>

    <footer class="footer-form-minimal">
        <p>&copy; 2026 SIPATU FST UIN RIL | Dibuat oleh Ghania Ridha Khairiah.</p>
    </footer>

    <script>
        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
        document.getElementById('my-hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.my-navbar-nav')?.classList.toggle('active');
        });
    </script>
</body>

</html>