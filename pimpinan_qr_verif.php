<?php
session_start();
include "koneksi.php";

function formatTanggalIndo($tanggal_db)
{
    $nama_bulan = [
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

    $timestamp = strtotime($tanggal_db);
    $hari = date('d', $timestamp);
    $bulan = $nama_bulan[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);

    return "$hari $bulan $tahun, $jam";
}

$hash = isset($_GET['hash']) ? mysqli_real_escape_string($koneksi, $_GET['hash']) : '';

if (!empty($hash)) {
    $query_surat = mysqli_query($koneksi, "
    SELECT 
        sp.nomor_surat,
        sp.dokumen_hash AS ttd_hash, 
        sp.tanggal_pengajuan AS waktu_ttd, 
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        js.nama_surat,
        dsr.surat_ditujukan
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    WHERE sp.dokumen_hash = '$hash' 
    AND (sp.status_akhir = 'Selesai' OR sp.status_balasan = 'Disetujui')
");

    $data_pimpinan = mysqli_fetch_assoc($query_surat);
} else {
    $data_pimpinan = false;
}

if ($data_pimpinan) {
    $namaSurat = strtolower($data_pimpinan['nama_surat']);
    $kondisi_jabatan = "";

    if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
        $kondisi_jabatan = "jabatan LIKE '%dekan%' AND jabatan NOT LIKE '%wakil%' AND jabatan NOT LIKE '%wadek%'";
    } elseif (strpos($namaSurat, 'aktif') !== false || strpos($namaSurat, 'riset') !== false) {
        $kondisi_jabatan = "(jabatan LIKE '%wadek 1%' OR jabatan LIKE '%wakil dekan 1%')";
    } elseif (strpos($namaSurat, 'nama_surat_lain_disini') !== false) {
        $kondisi_jabatan = "(jabatan LIKE '%wadek 2%' OR jabatan LIKE '%wakil dekan 2%')";
    } elseif (strpos($namaSurat, 'nama_surat_kasubbag') !== false) {
        $kondisi_jabatan = "(jabatan LIKE '%kasubbag tu%' OR jabatan LIKE '%kepala subbagian%')";
    } else {
        $kondisi_jabatan = "jabatan LIKE '%dekan%' AND jabatan NOT LIKE '%wakil%'";
    }

    $query_dosen = mysqli_query($koneksi, "
        SELECT 
            nama_dosen AS nama_pimpinan, 
            nip, 
            jabatan 
        FROM dosen 
        WHERE $kondisi_jabatan 
        LIMIT 1
    ");

    if ($dosen = mysqli_fetch_assoc($query_dosen)) {
        $data_pimpinan = array_merge($data_pimpinan, $dosen);
    } else {
        $data_pimpinan['nama_pimpinan'] = 'Data Pimpinan Belum Diatur';
        $data_pimpinan['nip'] = '-';
        $data_pimpinan['jabatan'] = 'Pimpinan Fakultas';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi QR Code</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="verif.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>
    <div class="verifikasi-card">
        <?php if ($data_pimpinan) { ?>
            <div class="status-box valid">
                <i class="fa-solid fa-circle-check"></i>
                <h2>DOKUMEN SAH & TERVERIFIKASI</h2>
                <p class="status-message-resmi">
                    Dokumen ini merupakan surat resmi yang sah dan telah ditandatangani secara elektronik
                    melalui Sistem Pengelolaan Surat Terpadu (SIPATU) Fakultas Sains dan Teknologi UIN Raden Intan Lampung.
                </p>
            </div>

            <div class="info-group">
                <h4>Informasi Penandatangan</h4>
                <p><strong>Nama :</strong> <?= htmlspecialchars($data_pimpinan['nama_pimpinan']); ?></p>
                <p><strong>NIP :</strong> <?= htmlspecialchars($data_pimpinan['nip']); ?></p>
                <p><strong>Jabatan :</strong> <?= htmlspecialchars($data_pimpinan['jabatan']); ?> </p>
                <p><strong>Waktu Penandatanganan :</strong> <?= formatTanggalIndo($data_pimpinan['waktu_ttd']); ?> WIB</p>
            </div>

            <div class="info-group">
                <h4>Identitas Dokumen</h4>
                <p><strong>Nomor Surat :</strong> <?= htmlspecialchars($data_pimpinan['nomor_surat']); ?></p>
                <p><strong>Perihal :</strong> <?= htmlspecialchars($data_pimpinan['nama_surat']); ?></p>
                <p><strong>Ditujukan Kepada :</strong> <?= htmlspecialchars($data_pimpinan['surat_ditujukan']); ?></p>
            </div>

            <div class="info-group">
                <h4>Subjek / Keterangan Dokumen</h4>
                <p><strong>Atas Nama :</strong> <?= htmlspecialchars($data_pimpinan['nama_mhs']); ?></p>
                <p><strong>NPM :</strong> <?= htmlspecialchars($data_pimpinan['npm']); ?></p>
                <p><strong>Program Studi :</strong> <?= htmlspecialchars($data_pimpinan['nama_prodi']); ?></p>
                <p><strong>Kode Unik Dokumen :</strong> <span class="hash-code"><?= substr($data_pimpinan['ttd_hash'], 0, 16); ?>...</span></p>
            </div>

            <div class="footer-meta">
                <strong>Waktu Pindai (Real-time):</strong>
                <?php
                $bulan_indo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                echo date('d') . ' ' . $bulan_indo[date('n') - 1] . ' ' . date('Y, H:i:s');
                ?> WIB
            </div>

        <?php } else { ?>
            <div class="status-box invalid">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h2>DOKUMEN TIDAK VALID</h2>
                <p>Peringatan! Data persetujuan tidak ditemukan di pangkalan data kami. Dokumen ini mungkin telah direkayasa, tidak sah, atau telah ditarik kembali.</p>
            </div>
        <?php } ?>
    </div>
</body>

</html>