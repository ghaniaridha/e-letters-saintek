<?php
session_start();
include "koneksi.php";

$id = $_GET['id'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        p.nama_prodi,                   
        dsl.tempat_lahir,
        dsl.tanggal_lahir,
        dsl.semester,                   
        dsl.tahun_akademik,
        dsl.tanggal_lulus,
        dsl.ipk,
        dsl.nilai_skripsi,
        dsl.predikat_kelulusan,
        dsl.keperluan
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi 
    LEFT JOIN detail_sk_lulus dsl ON sp.id_surat = dsl.id_surat 
    WHERE sp.id_surat = '$id'
"));

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
$tanggal_surat = date('d') . ' ' . $array_bulan[(int)date('m')] . ' ' . date('Y');

// Format TTL (Tempat, Tanggal Lahir) dengan Bulan Bahasa Indonesia
$timestamp_lahir = strtotime($data['tanggal_lahir']);
$tgl_lahir_indo = date('d', $timestamp_lahir) . ' ' . $array_bulan[(int)date('m', $timestamp_lahir)] . ' ' . date('Y', $timestamp_lahir);
$ttl = htmlspecialchars($data['tempat_lahir']) . ', ' . $tgl_lahir_indo;

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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Surat Keterangan Lulus</title>

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
            <p>Perihal : Permohonan Surat Keterangan Lulus Kuliah</p>

            <br>

            <p>Kepada Yth,</p>
            <p><strong>Dekan Fakultas Sains dan Teknologi<br>UIN Raden Intan Lampung</strong></p>
            <p>di-</p>
            <p class="indent">Bandar Lampung</p>

            <br>

            <p>Assalamu’alaikum Wr. Wb.</p>
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
                    <td class="col-label">TTL</td>
                    <td class="col-separator">:</td>
                    <td><?= $ttl; ?></td>
                </tr>

                <tr>
                    <td class="col-label">IPK</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['ipk']); ?></td>
                </tr>

                <tr>
                    <td class="col-label">Semester</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['semester']); ?></td>
                </tr>

                <tr>
                    <td class="col-label">Program Studi</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['nama_prodi']); ?></td>
                </tr>
            </table>

            <br>

            <p>
                Dengan ini mengajukan permohonan surat keterangan Lulus kuliah untuk Keperluan <strong><?= htmlspecialchars($data['keperluan']); ?></strong>.
            </p>

            <p>Sebagai bahan Pertimbangan Bapak/Ibu dengan ini saya Lampirkan :</p>

            <ol>
                <li>Transkrip Nilai yang sudah ada nilai skripsi (lengkap)</li>
                <li>Berita Acara Munaqosah</li>
                <li>Kartu Tanda Mahasiswa (KTM)</li>
                <li>Kartu Tanda Penduduk (KTP)</li>
                <li>Sertifikat Akreditasi Prodi</li>
            </ol>

            <p>Demikian Permohonan ini saya sampaikan, atas perkenan Bapak / Ibu saya ucapkan terima kasih.</p>
            <p>Wassalamu’alaikum wr. wb.</p>

            <br>

            <div class="ttd">
                <p style="margin-bottom: 20px;">Bandar Lampung, <?= $tanggal_surat; ?></p>

                <p>Pemohon</p>

                <?php if (!empty($data['dokumen_hash'])) { ?>
                    <img src="<?= $qr_url; ?>" class="qr-img" alt="QR Verifikasi">
                <?php } else { ?>
                    <br><br><br><br>
                <?php } ?>

                <p><strong><?= htmlspecialchars($data['nama_mhs']); ?></strong></p>
                <p>NPM. <?= htmlspecialchars($data['npm']); ?></p>
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
                <p class="status-desc">Permohonan SK Lulus dan dokumen pendukung sudah berhasil diunggah.</p>
            </div>

            <div class="action-buttons">
                <a href="mhs_daftar_surat_akademik.php" class="btn-action btn-outline">
                    Kembali
                </a>
                <a href="mhs_riwayat.php?id=<?= $id ?>" class="btn-action btn-fill">
                    Selesai
                </a>
            </div>
        </div>
    <?php endif; ?>
</body>

</html>