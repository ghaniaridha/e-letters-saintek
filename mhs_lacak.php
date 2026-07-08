<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];

$query_lacak = mysqli_query($koneksi, "
SELECT
    sp.id_surat,
    sp.nomor_surat,
    sp.tanggal_pengajuan,
    sp.status_akhir,
    COALESCE(dsr.status_pb1, 'N/A') AS status_pb1,
    COALESCE(dsr.status_pb2, 'N/A') AS status_pb2,
    COALESCE(dak.status_pa, 'N/A') AS status_pa, /* Tambahan untuk status PA */
    sp.status_pimpinan,
    sp.file_surat_final,
    sp.dokumen_hash,
    js.nama_surat
FROM surat_pengajuan sp
JOIN jenis_surat js ON js.id_jenis = sp.id_jenis
LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat /* Tambahan JOIN untuk surat aktif */
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
            "Dosen Pembimbing 2", // <--- PINDAHKAN DOSPEM 2 KE URUTAN KEDUA
            "Dosen Pembimbing 1", // <--- PINDAHKAN DOSPEM 1 KE URUTAN KETIGA
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

function getPosisiDariStatus($statusAkhir, $namaSurat)
{
    $status = strtolower($statusAkhir);
    $jenis = strtolower($namaSurat);

    // Jika sudah selesai
    if (strpos($status, 'selesai') !== false) return "Selesai";

    // PERBAIKAN: Cek posisi Pembimbing Akademik (Untuk SK Aktif Kuliah)
    if (strpos($status, 'pembimbing akademik') !== false || strpos($status, 'pa') !== false) {
        return "Pembimbing Akademik";
    }

    // Cek posisi Dospem
    if (strpos($status, 'dospem 1') !== false || strpos($status, 'pembimbing 1') !== false) {
        return "Dosen Pembimbing 1";
    }

    if (strpos($status, 'dospem 2') !== false || strpos($status, 'pembimbing 2') !== false) {
        return "Dosen Pembimbing 2";
    }

    // Cek posisi Admin
    if (strpos($status, 'admin') !== false || strpos($status, 'tata usaha') !== false) {
        return "Admin";
    }

    // Cek posisi Pimpinan (Dekan/Wadek)
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

    // Posisi default saat baru diajukan
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
                // 1. Ambil struktur timeline berdasarkan jenis surat
                $timeline = getTimeline($row['nama_surat']);

                // 2. Terjemahkan status akhir menjadi posisi timeline menggunakan fungsi baru
                // (Ini menggantikan blok Switch Case yang panjang tadi)
                $posisi_saat_ini = getPosisiDariStatus($row['status_akhir'], $row['nama_surat']);

                // 3. Hitung persentase progress (Panggil satu kali saja)
                list($currentStep, $persen) = getProgress($timeline, $posisi_saat_ini);

                // 4. Logika penentuan warna Badge
                // Kita buat variabel khusus yang di-lowercase agar strpos() bekerja maksimal
                $status_lower = strtolower($row['status_akhir']);
                $badgeClass = "badge-menunggu"; // Default

                if (strpos($status_lower, "selesai") !== false) {
                    $badgeClass = "badge-selesai";
                } elseif (strpos($status_lower, "tolak") !== false) {
                    $badgeClass = "badge-ditolak";
                } elseif (strpos($status_lower, "proses") !== false || strpos($status_lower, "setuju") !== false) {
                    $badgeClass = "badge-proses";
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
                        <div class="progress-bar" style="width:<?= $persen ?>%;">
                        </div>
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
                                    <?php
                                    if ($class == "done") {
                                    ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php
                                    } else {
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
                        <button type="button" class="btn btn-primary btn-sm btn-detail"
                            data-bs-toggle="modal"
                            data-bs-target="#modalDetail"
                            data-namasurat="<?= $row['nama_surat'] ?>"
                            data-tanggal="<?= tanggalIndonesia($row['tanggal_pengajuan']) ?>"
                            data-status="<?= $row['status_akhir'] ?>"
                            data-posisi="<?= $posisi_saat_ini ?>"
                            data-dospem1="<?= $row['status_pb1'] ?>"
                            data-dospem2="<?= $row['status_pb2'] ?>"
                            data-pa="<?= $row['status_pa'] ?>" data-pimpinan="<?= $row['status_pimpinan'] ?>">
                            <i class="fa-solid fa-eye"></i> Detail Pengajuan
                        </button>
                        <?php if (!empty($row['dokumen_hash'])): ?>
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

        <!--MODAL DETAIL SURAT-->
        <div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                    <div class="modal-header" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0;">
                        <h3>
                            <i class="fa-solid fa-circle-info" style="color: #0d6efd; margin-right: 8px;"></i>
                            Detail Pengajuan
                        </h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body" style="padding: 20px;">
                        <table class="table-detail">
                            <tr id="row-jenis">
                                <td>Jenis Surat</td>
                                <td id="detailNamaSurat"></td>
                            </tr>
                            <tr id="row-tanggal">
                                <td>Tanggal Pengajuan</td>
                                <td id="detailTanggal"></td>
                            </tr>
                            <tr id="row-status">
                                <td>Status Pengajuan</td>
                                <td id="detailStatus"></td>
                            </tr>
                            <tr id="row-posisi">
                                <td>Posisi Saat Ini</td>
                                <td id="detailPosisi"></td>
                            </tr>

                            <tr id="row-dospem1">
                                <td>Status Dospem 1</td>
                                <td id="detailDospem1"></td>
                            </tr>
                            <tr id="row-dospem2">
                                <td>Status Dospem 2</td>
                                <td id="detailDospem2"></td>
                            </tr>

                            <tr id="row-pa" style="display: none;">
                                <td>Status PA</td>
                                <td id="detailPA"></td>
                            </tr>

                            <tr id="row-pimpinan">
                                <td>Status Pimpinan</td>
                                <td id="detailPimpinan"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

            /*modal detail*/
            $(document).ready(function() {
                // Saat tombol dengan class btn-detail diklik
                $('.btn-detail').on('click', function() {
                    // 1. Ambil data dari tombol
                    let namaSurat = $(this).data('namasurat');
                    let tanggal = $(this).data('tanggal');
                    let status = $(this).data('status');
                    let posisi = $(this).data('posisi');
                    let dospem1 = $(this).data('dospem1');
                    let dospem2 = $(this).data('dospem2');
                    let pa = $(this).data('pa'); // Tangkap data PA
                    let pimpinan = $(this).data('pimpinan');

                    // 2. Suntikkan data umum ke Modal
                    $('#detailNamaSurat').text(namaSurat);
                    $('#detailTanggal').text(tanggal);
                    $('#detailStatus').text(status);
                    $('#detailPosisi').text(posisi);
                    $('#detailPimpinan').text(pimpinan);

                    // 3. LOGIKA DINAMIS MENAMPILKAN BARIS TABEL
                    let jenis = namaSurat.toLowerCase();

                    if (jenis.indexOf('riset') !== -1) {
                        // Tampilan Surat Riset: Munculkan Dospem, Sembunyikan PA
                        $('#row-dospem1').show();
                        $('#row-dospem2').show();
                        $('#row-pa').hide();

                        $('#detailDospem1').text(dospem1);
                        $('#detailDospem2').text(dospem2);

                    } else if (jenis.indexOf('aktif') !== -1) {
                        // Tampilan SK Aktif Kuliah: Munculkan PA, Sembunyikan Dospem
                        $('#row-dospem1').hide();
                        $('#row-dospem2').hide();
                        $('#row-pa').show();

                        $('#detailPA').text(pa);

                    } else if (jenis.indexOf('magang') !== -1 || jenis.indexOf('pkl') !== -1) {
                        // Tampilan Surat Magang: Sembunyikan semua (Langsung Admin/Dekan)
                        $('#row-dospem1').hide();
                        $('#row-dospem2').hide();
                        $('#row-pa').hide();
                    } else {
                        // Default (Jaga-jaga untuk jenis surat baru)
                        $('#row-dospem1').hide();
                        $('#row-dospem2').hide();
                        $('#row-pa').hide();
                    }
                });
            });
        });
    </script>
</body>

</html>