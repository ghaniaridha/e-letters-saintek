<?php
session_start();
include "koneksi.php";

// Tentukan apakah halaman dibuka dalam mode 'Lihat' oleh Dosen/Admin
$view_mode = false;
$status_pembina = ""; // Definisikan awal untuk status verifikasi pembina

if (isset($_GET['id'])) {
    // 1. SKENARIO JIKA DIBUKA OLEH DOSEN (Membawa parameter ID via GET)
    $id_surat = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Ambil data gabungan pengajuan dan detail peminjaman
    $query_tampil = mysqli_query($koneksi, "
        SELECT sp.*, dpd.*, o.id_ormawa
        FROM surat_pengajuan sp
        JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
        JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        WHERE sp.id_surat = '$id_surat'
    ");
    $data_kombinasi = mysqli_fetch_assoc($query_tampil);

    if (!$data_kombinasi) {
        die("Data pengajuan tidak ditemukan.");
    }

    // Petakan data database ke variabel yang dipakai oleh layout cetak surat Anda
    $id_ormawa        = $data_kombinasi['id_ormawa'];
    $nama_kegiatan    = $data_kombinasi['nama_kegiatan'];
    $tema_kegiatan      = $data_kombinasi['tema_kegiatan'];
    $tempat_kegiatan    = $data_kombinasi['tempat_kegiatan'];
    $nominal_pengajuan  = $data_kombinasi['nominal_pengajuan'];
    $deskripsi_kegiatan = $data_kombinasi['deskripsi_kegiatan'];
    $tanggal_kegiatan   = $data_kombinasi['tanggal_kegiatan'];
    
    // Ambil status akhir untuk validasi QR Code Pembina nanti
    $status_pembina   = $data_kombinasi['status_akhir'];

    $view_mode = true; // Kunci mode view aktif

} else {
    // 2. SKENARIO JIKA BARU DIISI OLEH ORMAWA (Membawa POST & Validasi Session)
    if (!isset($_SESSION['id_ormawa'])) {
        header("Location:index.php");
        exit;
    }

    // Pastikan ada data form yang dikirim
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        header("Location: ormawa_form_pengajuan_dana.php");
        exit;
    }

    $id_ormawa = $_SESSION['id_ormawa'];

    // --- PROSES UPLOAD BERKAS DI PREVIEW (HANYA UNTUK ORMAWA) ---
    $nama_proposal = "";
    if(isset($_FILES['proposal']) && $_FILES['proposal']['error'] == 0){
        $folder = "uploads/proposal/";
        if(!is_dir($folder)) mkdir($folder, 0777, true);
        $ext = pathinfo($_FILES['proposal']['name'], PATHINFO_EXTENSION);
        $nama_proposal = "proposal_" . time() . "_" . rand(1000,9999) . "." . $ext;
        move_uploaded_file($_FILES['proposal']['tmp_name'], $folder.$nama_proposal);
    }

    // Tangkap input teks dari POST untuk dirender ke layout surat
    $nama_kegiatan      = htmlspecialchars($_POST['nama_kegiatan']);
    $tema_kegiatan      = htmlspecialchars($_POST['tema_kegiatan']);
    $tempat_kegiatan    = htmlspecialchars($_POST['tempat_kegiatan']);
    $deskripsi_kegiatan = htmlspecialchars($_POST['deskripsi_kegiatan']);
    $tanggal_kegiatan   = htmlspecialchars($_POST['tanggal_kegiatan']);
}

// 3. AMBIL DATA IDENTITAS ORMAWA & PEMBINA (Berlaku untuk mode POST maupun GET)
$ormawa = mysqli_fetch_assoc(mysqli_query($koneksi,"
    SELECT o.*, d.nama_dosen AS nama_pembina,
           d.nip AS nip_pembina,
           d.kode_ttd_qr AS qr_pembina
    FROM ormawa o
    LEFT JOIN dosen d ON o.id_pembina=d.id_dosen
    WHERE o.id_ormawa='$id_ormawa'
"));

// Penanganan Hari otomatis menggunakan variabel $tanggal_kegiatan yang sudah dinamis
$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$hari_index = date('w', strtotime($tanggal_kegiatan ?? 'now'));
$hari_indo = $hari_array[$hari_index];

// --- ADAPTASI GENERATE BASE URL ---
$host = $_SERVER['HTTP_HOST'];
$local_ip = gethostbyname(gethostname()); 

if ($host == 'localhost' || $host == '127.0.0.1') {
    $host = $local_ip;
}
$base_url = "http://" . $host . "/e-letters-saintek";

// Generate QR Code untuk Ketua dan Sekretaris
$link_ketua = $base_url . "/verifikasi.php?hash=" . ($ormawa['kode_ttd_qr_ketua'] ?? '');
$qr_ketua = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_ketua);

$link_sekretaris = $base_url . "/verifikasi.php?hash=" . ($ormawa['kode_ttd_qr_sekretaris'] ?? '');
$qr_sekretaris = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_sekretaris);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Preview Surat Pengajuan Dana</title>
<link rel="stylesheet" href="style.css?v=<?=time();?>">
<style>
    body {
        background: #f0f0f0;
        font-family: "Times New Roman", Times, serif;
        font-size: 16px;
        line-height: 1.5;
        color: #000;
    }

    .preview-wrapper {
        width: 210mm;
        min-height: 297mm;
        margin: 30px auto;
        background: white;
        padding: 20mm;
        box-shadow: 0 0 20px rgba(0,0,0,.1);
        box-sizing: border-box;
    }

    /* KOP SURAT */
    .kop {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .logo-kiri img,
    .logo-kanan img {
        width: 95px;
        height: auto;
    }

    .judul-kop {
        flex: 1;
        text-align: center;
        padding: 0 10px;
    }

    .judul-kop h3 {
        margin: 0;
        font-size: 18px;
        font-weight: bold;
        text-transform: uppercase;
        line-height: 1.2;
    }

    .judul-kop p {
        margin: 5px 0 0 0;
        font-size: 12px;
        line-height: 1.3;
    }

    .garis-kop {
        border: none;
        border-top: 3px solid #000;
        border-bottom: 1px solid #000;
        height: 3px;
        margin-top: 5px;
        margin-bottom: 25px;
    }

    /* DETAIL SURAT */
    .detail-surat {
        width: 100%;
        margin-bottom: 25px;
        border-collapse: collapse;
    }

    .detail-surat td {
        vertical-align: top;
        padding: 2px 0;
    }

    /* ISI SURAT */
    .tujuan-surat {
        margin-bottom: 20px;
    }

    .salutation {
        margin-bottom: 15px;
    }

    .paragraf {
        text-align: justify;
        text-indent: 40px;
        margin-bottom: 15px;
    }

    .table-info {
        width: 85%;
        margin: 15px auto;
        border-collapse: collapse;
    }

    .table-info td {
        padding: 4px 0;
        vertical-align: top;
    }

    /* TANDA TANGAN */
    .tanggal-surat {
        text-align: right;
        margin-top: 30px;
        margin-bottom: 15px;
        padding-right: 20px;
    }

    .ttd-container {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
    }

    .ttd-box {
        width: 45%;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .ttd-box img {
        width: 90px;
        height: 90px;
        margin: 10px 0;
    }

    .ttd-pembina {
        width: 100%;
        text-align: center;
        margin-top: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .ttd-pembina img {
        width: 90px;
        height: 90px;
        margin: 10px 0;
    }

    .qr-placeholder {
        width: 90px;
        height: 90px;
        border: 1px dashed #aaa;
        margin: 10px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: #777;
        font-style: italic;
        background: #fafafa;
    }

    .nama-ttd {
        font-weight: bold;
        text-decoration: underline;
    }

    /* ACTION BUTTONS */
    .action {
        margin-top: 50px;
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    .action button {
        padding: 10px 20px;
        font-size: 15px;
        cursor: pointer;
        border: 1px solid #777;
        background: #fff;
        border-radius: 4px;
    }

    .action button[type="submit"] {
        background: #007bff;
        color: #fff;
        border-color: #007bff;
    }

    @media print {
        body { background: white; }
        .preview-wrapper { box-shadow: none; margin: 0; padding: 0; }
        .action { display: none; }
    }
</style>
</head>
<body>

<div class="preview-wrapper">

    <!-- KOP SURAT DINAMIS SESUAI ORMAWA -->
    <div class="kop">
        <div class="logo-kiri">
            <img src="images/Logo UINRIL(2).png" alt="Logo UIN">
        </div>
        <div class="judul-kop">
            <h3><?= strtoupper(htmlspecialchars($ormawa['nama_ormawa'] ?? 'HIMPUNAN MAHASISWA')); ?></h3>
            <h3>FAKULTAS SAINS DAN TEKNOLOGI</h3>
            <h3>UNIVERSITAS ISLAM NEGERI RADEN INTAN LAMPUNG</h3>
            <p>Sekretariat: <?= htmlspecialchars($ormawa['alamat_sekretariat'] ?? 'Jl. Letkol H. Endro Suratmin Sukarame Bandar Lampung'); ?><br>
            No. Telp: <?= htmlspecialchars($ormawa['kontak_ormawa'] ?? '-'); ?> Email: <?= htmlspecialchars($ormawa['email_ormawa'] ?? '-'); ?></p>
        </div>
        <div class="logo-kanan">
            <?php if (!empty($ormawa['logo_ormawa']) && file_exists("images/" . $ormawa['logo_ormawa'])): ?>
                <img src="images/<?= htmlspecialchars($ormawa['logo_ormawa']); ?>" alt="Logo Ormawa">
            <?php else: ?>
                <img src="images/Logo UINRIL(2).png" alt="Logo Default">
            <?php endif; ?>
        </div>
    </div>

    <div class="garis-kop"></div>

    <!-- DETAIL NOMOR & PERIHAL -->
    <table class="detail-surat">
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>-</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td><b><u>Permohonan Bantuan Dana </u></b></td>
        </tr>
    </table>

    <!-- TUJUAN SURAT -->
    <div class="tujuan-surat">
        Kepada Yth.<br>
        <b>KASUBBAG UMUM FAKULTAS SAINTEK</b><br>
        Di -<br>
        <span style="padding-left: 20px;">Tempat</span>
    </div>

    <!-- ISI SURAT -->
    <div class="salutation">Assalammu'alaikum Warahmatullahi Wabarakatuh</div>

    <div class="paragraf">
        Teriring salam dan do'a semoga Allah SWT. senantistan melimpahkan rahmat dan hidayah-Nya kepada kita semua dalam menjalankan aktivitas sehari-hari.
    </div>

    <div class="paragraf">
        Sehubungan akan dilaksanakannya kegiatan <b><?= htmlspecialchars($nama_kegiatan ?? '') ?></b> oleh <?= htmlspecialchars($ormawa['nama_ormawa'] ?? '') ?> Fakultas Sains Dan Teknologi UIN Raden Intan Lampung, yang akan dilaksanakan pada:
    </div>

    <!-- DETAIL WAKTU & TEMPAT -->
   <table class="table-info">
    <tr>
        <td width="160">Nama Kegiatan</td>
        <td width="15">:</td>
        <td><?= htmlspecialchars($nama_kegiatan) ?></td>
    </tr>

    <tr>
        <td>Tema Kegiatan</td>
        <td>:</td>
        <td><?= htmlspecialchars($tema_kegiatan) ?></td>
    </tr>

    <tr>
        <td>Tanggal Kegiatan</td>
        <td>:</td>
        <td><?= $hari_indo ?>,
            <?= date('d F Y', strtotime($tanggal_kegiatan)) ?>
        </td>
    </tr>

    <tr>
        <td>Tempat Kegiatan</td>
        <td>:</td>
        <td><?= htmlspecialchars($tempat_kegiatan) ?></td>
    </tr>
</table>

    <div class="paragraf">
        Maka dengan ini kami mengajukan permohonan bantuan dana kepada WAKIL DEKAN II untuk mendukung pelaksanaan kegiatan tersebut dengan rincian:
    </div>

    <div class="paragraf">
        Demikian surat permohonan ini kami sampaikan, atas perhatian dan perizinannya kami ucapkan terima kasih.
    </div>

    <div class="salutation" style="margin-top: 20px;">Wassalammu'alaikum Warahmatullahi Wabarakatuh</div>

    <!-- TANGGAL & TANDA TANGAN -->
    <div class="tanggal-surat">
        Bandar Lampung, <?= date('d F Y') ?>
    </div>

    <!-- Baris TTD Pengurus Inti -->
    <div class="ttd-container">
        <div class="ttd-box">
            Ketua Umum <?= htmlspecialchars($ormawa['singkatan_ormawa'] ?? 'Ormawa') ?>
            <img src="<?= $qr_ketua ?>" alt="QR Ketua">
            <span class="nama-ttd"><?= htmlspecialchars($ormawa['nama_ketua'] ?? '') ?></span>
            <span>NPM. <?= htmlspecialchars($ormawa['npm_ketua'] ?? '') ?></span>
        </div>

        <div class="ttd-box">
            Sekretaris Umum
            <img src="<?= $qr_sekretaris ?>" alt="QR Sekretaris">
            <span class="nama-ttd"><?= htmlspecialchars($ormawa['nama_sekretaris'] ?? '') ?></span>
            <span>NPM. <?= htmlspecialchars($ormawa['npm_sekretaris'] ?? '') ?></span>
        </div>
    </div>

     <!-- Baris TTD Mengetahui Pembina -->
    <div class="ttd-pembina">
        <div>Mengetahui,</div>
        <div style="font-weight: bold; margin-bottom: 5px;">DOSEN PEMBINA <?= strtoupper(htmlspecialchars($ormawa['singkatan_ormawa'] ?? 'ORMAWA')) ?></div>
        
        <?php 
            // Validasi QR Code Pembina: QR muncul jika ada file QR-nya di database
            if ($view_mode && !empty($ormawa['qr_pembina'])) { 
                $link_pembina = $base_url . "/verifikasi.php?hash=" . $ormawa['qr_pembina'];
                $qr_pembina_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_pembina);
            ?>
                <img src="<?= $qr_pembina_url ?>" alt="QR Pembina">
            <?php } else { ?>
                <div class="qr-placeholder">QR Tidak Tersedia</div>
        <?php } ?>

        <span class="nama-ttd"><?= htmlspecialchars($ormawa['nama_pembina'] ?? '-') ?></span>
        <span>NIP. <?= htmlspecialchars($ormawa['nip_pembina'] ?? '-') ?></span>
    </div>

    <!-- TOMBOL AKSI: HANYA MUNCUL JIKA BUKAN DIINTIP DOSEN -->
    <?php if (!$view_mode) { ?>
        <div class="action">
            <button type="button" onclick="history.back()">Edit Kembali</button>
            <form action="proses_pengajuan_dana.php" method="POST">

    <!-- id ormawa dari session -->
    <input type="hidden"
           name="id_ormawa"
           value="<?= $id_ormawa ?>">

    <!-- id jenis surat -->
    <input type="hidden"
           name="id_jenis"
           value="<?= $_POST['id_jenis'] ?>">

    <!-- id pembina -->
    <input type="hidden"
           name="id_pembina"
           value="<?= $ormawa['id_pembina'] ?>">

    <!-- file proposal yang sudah diupload -->
    <input type="hidden"
           name="file_proposal_terupload"
           value="<?= $nama_proposal ?>">

    <!-- kirim semua data form -->
    <input type="hidden"
           name="nama_kegiatan"
           value="<?= htmlspecialchars($nama_kegiatan) ?>">

    <input type="hidden"
           name="tema_kegiatan"
           value="<?= htmlspecialchars($tema_kegiatan) ?>">

    <input type="hidden"
           name="tanggal_kegiatan"
           value="<?= htmlspecialchars($tanggal_kegiatan) ?>">

    <input type="hidden"
           name="tempat_kegiatan"
           value="<?= htmlspecialchars($tempat_kegiatan) ?>">

    <input type="hidden"
           name="nominal_pengajuan"
           value="<?= htmlspecialchars($_POST['nominal_pengajuan']) ?>">

    <input type="hidden"
           name="deskripsi_kegiatan"
           value="<?= htmlspecialchars($deskripsi_kegiatan) ?>">

    <button type="submit">
        Ajukan Surat
    </button>

</form>
        </div>
    <?php } ?>

</div>

</body>
</html>