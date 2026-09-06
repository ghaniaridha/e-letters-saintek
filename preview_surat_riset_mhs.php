<?php
session_start();
include "koneksi.php";

$id = $_GET['id'] ?? '';
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        dsr.semester,
        dsr.judul_skripsi,
        dsr.lokasi_penelitian,
        dsr.surat_ditujukan,
        dsr.status_pb1, 
        dsr.status_pb2,
        dsr.ttd_pb1, 
        dsr.ttd_pb2, 
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        d1.nama_dosen AS nama_dospem1,
        d1.nip AS nip_dospem1,
        d2.nama_dosen AS nama_dospem2,
        d2.nip AS nip_dospem2
    FROM surat_pengajuan sp
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN dosen d1 ON dsr.id_pb1 = d1.id_dosen
    LEFT JOIN dosen d2 ON dsr.id_pb2 = d2.id_dosen
    WHERE sp.id_surat = '$id'
"));
if (!$data) {
    echo "Data surat tidak ditemukan.";
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

$semester = $data['semester'] ?? '-';

$host = $_SERVER['HTTP_HOST'];
if (strpos($host, '.') !== false && strpos($host, 'localhost') === false && !filter_var($host, FILTER_VALIDATE_IP)) {
    $base_url = "https://" . $host;
} else {
    $base_url = "http://" . $host . "/e-letters-saintek";
}

$qr_mhs = "";
if (!empty($data['dokumen_hash'])) {
    $link_mhs = $base_url . "/mhs_qr_verif.php?hash=" . $data['dokumen_hash'];
    $qr_mhs = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_mhs);
}

$qr_dospem1 = "";
if (isset($data['status_pb1']) && $data['status_pb1'] == 'Disetujui') {
    $hash_ttd1 = $data['ttd_pb1'];

    if (empty($hash_ttd1)) {
        $hash_ttd1 = hash('sha256', $id . $data['nip_dospem1'] . 'ttd1' . time());
        mysqli_query($koneksi, "UPDATE detail_surat_riset SET ttd_pb1 = '$hash_ttd1' WHERE id_surat = '$id'");
    }

    $link_dospem1 = $base_url . "/dosepem_qr_verif.php?hash=" . $hash_ttd1;
    $qr_dospem1 = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_dospem1);
}

$qr_dospem2 = "";
if (isset($data['status_pb2']) && $data['status_pb2'] == 'Disetujui') {
    $hash_ttd2 = $data['ttd_pb2'];

    if (empty($hash_ttd2)) {
        $hash_ttd2 = hash('sha256', $id . $data['nip_dospem2'] . 'ttd2' . time());
        mysqli_query($koneksi, "UPDATE detail_surat_riset SET ttd_pb2 = '$hash_ttd2' WHERE id_surat = '$id'");
    }

    $link_dospem2 = $base_url . "/dosepem_qr_verif.php?hash=" . $hash_ttd2;
    $qr_dospem2 = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_dospem2);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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
            <p>Perihal : Permohonan Surat Rekomendasi Riset</p>

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
                    <td class="col-label">Nama / NPM</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['nama_mhs']); ?> / <?= htmlspecialchars($data['npm']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Semester / Program Studi</td>
                    <td class="col-separator">:</td>
                    <td>
                        <?= htmlspecialchars($data['semester']); ?> /
                        <?= htmlspecialchars($data['nama_prodi']); ?>
                    </td>
                </tr>
                <tr>
                    <td class="col-label">Judul Skripsi</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['judul_skripsi']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Lokasi Penelitian</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['lokasi_penelitian']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Surat Ditujukan Kepada</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['surat_ditujukan']); ?></td>
                </tr>
            </table>

            <br>

            <p>
                Bermaksud memohon surat Rekomendasi Riset dari pihak Fakultas,
                sebagai bahan pertimbangan Bapak/Ibu, saya lampirkan:
            </p>

            <ol class="surat-list">
                <li>Proposal Penelitian</li>
                <li>Foto Copy Slip pembayaran UKT Terakhir</li>
                <li>KHS Semester terakhir</li>
            </ol>

            <p>Atas perhatian Bapak/Ibu, saya ucapkan terima kasih</p>
            <p>Wassalamu’alaikum Wr. Wb.</p>

            <div class="surat-date">
                Bandar Lampung, <?= $tgl_surat_indo; ?>
            </div>

            <div class="surat-signatures-3">
                <div class="signature-block-3">
                    <p>Mengetahui,</p>
                    <p>Pembimbing I</p>
                    <?php if (!empty($qr_dospem1)) { ?>
                        <img src="<?= $qr_dospem1; ?>" class="qr-img" alt="QR Dospem 1">
                    <?php } else { ?>
                        <div class="qr-placeholder"></div>
                    <?php } ?>
                    <p><b><?= htmlspecialchars($data['nama_dospem1']); ?></b></p>
                    <p>NIP. <?= htmlspecialchars($data['nip_dospem1']); ?></p>
                </div>

                <div class="signature-block-3">
                    <p>&nbsp;</p>
                    <p>Pembimbing II</p>
                    <?php if (!empty($qr_dospem2)) { ?>
                        <img src="<?= $qr_dospem2; ?>" class="qr-img" alt="QR Dospem 2">
                    <?php } else { ?>
                        <div class="qr-placeholder"></div>
                    <?php } ?>
                    <p><b><?= htmlspecialchars($data['nama_dospem2']); ?></b></p>
                    <p>NIP. <?= htmlspecialchars($data['nip_dospem2']); ?></p>
                </div>

                <div class="signature-block-3">
                    <p>&nbsp;</p>
                    <p>Pemohon</p>
                    <?php if (!empty($qr_mhs)) { ?>
                        <img src="<?= $qr_mhs; ?>" class="qr-img" alt="QR Mahasiswa">
                    <?php } else { ?>
                        <div class="qr-placeholder"></div>
                    <?php } ?>
                    <p><b><?= htmlspecialchars($data['nama_mhs']); ?></b></p>
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