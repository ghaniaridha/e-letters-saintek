<?php
session_start();
include "koneksi.php";

$id = (int)$_GET['id'];

$query = mysqli_query($koneksi, "
    SELECT sp.*, 
    m.nama_mhs, 
    m.npm, 
    p.nama_prodi, 
    da.*, 
    pa.nama_dosen as nama_pa, pa.nip as nip_pa
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN detail_aktif_kuliah da ON sp.id_surat = da.id_surat
    LEFT JOIN dosen pa ON da.id_pa = pa.id_dosen
    WHERE sp.id_surat = '$id'
");
$data = mysqli_fetch_assoc($query);
if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='mhs_lacak.php';</script>";
    exit;
}

$array_bulan = [
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
];

// Format Tanggal Surat Tanda Tangan
$timestamp_ttd = strtotime($data['tanggal_pengajuan']);
$tgl_surat_indo = date('d', $timestamp_ttd) . ' ' . $array_bulan[(int)date('m', $timestamp_ttd)] . ' ' . date('Y', $timestamp_ttd);

$semester = $data['semester'];

$host = $_SERVER['HTTP_HOST'];
$local_ip = gethostbyname(gethostname());

if ($host == 'localhost' || $host == '127.0.0.1') {
    $host = $local_ip;
}
if (strpos($host, '.') !== false && strpos($host, 'localhost') === false && !filter_var($host, FILTER_VALIDATE_IP)) {
    $base_url = "https://" . $host;
} else {
    $base_url = "http://" . $host . "/e-letters-saintek";
}
$link_verifikasi = $base_url . "/mhs_qr_verif.php?hash=" . $data['dokumen_hash'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);

$qr_pa_url = "";
if (isset($data['status_pa']) && $data['status_pa'] == 'Disetujui' && !empty($data['ttd_pa'])) {
    $link_verifikasi_pa = $base_url . "/dosepem_qr_verif.php?hash=" . $data['ttd_pa'];
    $qr_pa_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi_pa);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="preview.css?v=<?= time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if (isset($_SESSION['pesan'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['status']; ?>',
                title: '<?= ($_SESSION['status'] == "success") ? "Berhasil!" : "Gagal!"; ?>',
                text: <?= json_encode($_SESSION['pesan']); ?>,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>
        <?php
        unset($_SESSION['pesan']);
        unset($_SESSION['status']);
        ?>
    <?php endif; ?>

    <div class="surat-wrapper">
        <div class="surat-document">
            <p>Perihal : Permohonan aktif Kuliah Kembali</p>

            <br>

            <p>Kepada Yth,</p>
            <p><strong>Dekan Fakultas Sains dan Teknologi<br>UIN Raden Intan Lampung</strong></p>
            <p>di-</p>
            <p class="indent">Tempat</p>

            <br>

            <p>Assalamu’alaikum wr. wb.</p>
            <p>Saya yang bertanda tangan dibawah ini :</p>

            <table class="surat-table">
                <tr>
                    <td class="col-label">Nama</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">NPM</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['npm']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Semester</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['semester'] ?? ''); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Tahun Akademik</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['tahun_akademik'] ?? ''); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Program Studi</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['nama_prodi']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Lama Cuti</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['lama_cuti'] ?? ''); ?></td>
                </tr>
                <tr>
                    <td class="col-label">yaitu</td>
                    <td class="col-separator">:</td>
                    <td>
                        Semester Gasal tahun Akademik <?= htmlspecialchars($data['ta_mulai_cuti'] ?? '..../....'); ?> <br>
                        Semester Genap tahun Akademik <?= htmlspecialchars($data['ta_selesai_cuti'] ?? '..../....'); ?>
                    </td>
                </tr>
            </table>

            <br>

            <p>
                Dengan ini mengajukan permohonan untuk <strong>aktif kuliah kembali</strong> pada semester:
                <strong><?= htmlspecialchars($data['semester_akademik'] ?? '..........'); ?></strong> tahun Akademik <strong><?= htmlspecialchars($data['tahun_akademik'] ?? '..../....'); ?></strong>
                bersama ini saya lampirkan Surat Keterangan Cuti yang pernah diambil.
            </p>

            <br>

            <p>Demikian, dan atas perkenan Bapak/Ibu diucapkan terima kasih.</p>
            <p>Wassalamu’alaikum Wr. Wb.</p>
            <br><br>

            <div class="surat-signatures">
                <div class="signature-block">
                    <p>&nbsp;</p>
                    <p>Mengetahui</p>
                    <p>Pembimbing Akademik,</p>

                    <?php if (!empty($qr_pa_url)) { ?>
                        <img src="<?= $qr_pa_url; ?>" class="qr-img" alt="QR Verifikasi Dosen PA">
                    <?php } else { ?>
                        <br><br><br><br>
                    <?php } ?>

                    <p><strong><?= htmlspecialchars($data['nama_pa'] ?? '.......................................'); ?></strong></p>
                    <p>NIP. <?= htmlspecialchars($data['nip_pa'] ?? '.......................................'); ?></p>
                </div>

                <div class="signature-block">
                    <p>Bandar Lampung, <?= $tgl_surat_indo; ?></p>
                    <p>&nbsp;</p>
                    <p>Pemohon,</p>

                    <img src="<?= $qr_url; ?>" class="qr-img" alt="QR Verifikasi">

                    <p><strong><?= htmlspecialchars($data['nama_mhs']); ?></strong></p>
                    <p>NPM. <?= htmlspecialchars($data['npm']); ?></p>
                </div>
            </div>

            <div class="clearfix"></div>
        </div>
    </div>

    <?php
    if (!isset($_GET['mode']) || $_GET['mode'] !== 'view'):
    ?>
        <div class="preview-actions">
            <div class="status-info">
                <p>Status Surat: <span class="badge-status"><?= htmlspecialchars($data['status_akhir'] ?? 'Menunggu Verifikasi'); ?></span></p>
                <p class="status-desc">Surat Permohonan dan dokumen pendukung sudah berhasil diunggah.</p>
            </div>

            <div class="action-buttons">
                <a href="mhs_daftar_surat_akademik.php" class="btn-action btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <a href="mhs_riwayat.php?id=<?= $id ?>" class="btn-action btn-fill">
                    Selesai
                </a>
            </div>
        </div>
    <?php endif; ?>
</body>

</html>