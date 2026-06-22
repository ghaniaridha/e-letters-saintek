<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_surat = $_GET['id'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        m.prodi,
        m.no_telp,
        js.nama_surat
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='pimpinan_verif.php';</script>";
    exit;
}

if (isset($_POST['kirim_balasan'])) {
    $nama_file = "surat_balasan_fakultas_" . $id_surat . "_" . time() . ".html";

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET
            status_akhir = 'Selesai',
            status_balasan = 'Disetujui',
            file_surat_balasan = '$nama_file',
            file_surat_final = '$nama_file'
        WHERE id_surat = '$id_surat'
    ");

    echo "<script>
        alert('Surat balasan berhasil disetujui dan dikirim ke mahasiswa.');
        window.location='pimpinan_verif.php';
    </script>";
    exit;
}

if (empty($data['nomor_surat'])) {
    $tahun = date('Y');

    $cekNomor = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT COUNT(*) AS total
        FROM surat_pengajuan
        WHERE status_akhir IN ('Menunggu Surat Balasan', 'Selesai')
        AND YEAR(tanggal_pengajuan) = '$tahun'
    "));

    $nomorUrut = str_pad($cekNomor['total'], 3, '0', STR_PAD_LEFT);
    $nomorSurat = "B-" . $nomorUrut . "/Un.16/DST/PP.009/" . $tahun;

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET nomor_surat = '$nomorSurat'
        WHERE id_surat = '$id_surat'
    ");
} else {
    $nomorSurat = $data['nomor_surat'];
}

$tanggalSurat = date('d-m-Y');

$base_url = "http://localhost/e-letters-saintek";
$link_verifikasi = $base_url . "/verifikasi_surat.php?hash=" . $data['dokumen_hash'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Balasan Fakultas</title>

<style>
body {
    background:#f3f4f6;
    font-family:"Times New Roman", serif;
    padding:30px;
}

.surat {
    background:white;
    max-width:850px;
    margin:auto;
    padding:45px 60px;
    color:black;
    line-height:1.5;
}

.kop {
    text-align:center;
    padding-bottom:8px;
    margin-bottom:12px;
}

.kop h3, .kop h2 {
    margin:0;
    line-height:1.2;
}

.kop small {
    font-size:12px;
}

.info-surat td {
    padding:2px 5px;
    vertical-align:top;
}

.isi {
    text-align:justify;
}

.ttd {
    width:280px;
    margin-left:auto;
    margin-top:30px;
    page-break-inside:avoid;
    break-inside:avoid;
}

.action {
    max-width:850px;
    margin:20px auto;
    display:flex;
    gap:10px;
    justify-content:flex-end;
}

.btn-print {
    background:#f59e0b;
    color:white;
    padding:10px 16px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    text-decoration:none;
}

.btn-approve {
    background:#2563eb;
    color:white;
    padding:10px 16px;
    border-radius:8px;
    border:none;
    cursor:pointer;
}

@media print {
    body {
        background:white;
        padding:0;
    }

    .action {
        display:none;
    }

    .surat {
        box-shadow:none;
        padding:30px 50px;
    }
}
</style>
</head>

<body>

<div class="surat">

    <div class="kop">
        <table style="width:100%; border:none;">
            <tr>
                <td style="width:120px; text-align:center; border:none;">
                    <img src="images/Logo UINRIL(2).png" style="width:120px;">
                </td>

                <td style="text-align:center; border:none;">
                    <h3>KEMENTERIAN AGAMA</h3>
                    <h3>UNIVERSITAS ISLAM NEGERI RADEN INTAN LAMPUNG</h3>
                    <h2>FAKULTAS SAINS DAN TEKNOLOGI</h2>
                    <small>
                        Jln. Letkol H. Endro Suratmin Sukarame I, Bandar Lampung 35131<br>
                        Website : www.radenintan.ac.id
                    </small>
                </td>
            </tr>
        </table>

        <hr style="border:2px solid black; margin-top:10px;">
    </div>

    <table class="info-surat">
        <tr>
            <td>Nomor</td>
            <td>:</td>
            <td><?= htmlspecialchars($nomorSurat); ?></td>
            <td style="width:180px;"></td>
            <td>Bandar Lampung, <?= $tanggalSurat; ?></td>
        </tr>
        <tr>
            <td>Sifat</td>
            <td>:</td>
            <td>Penting</td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>-</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td><strong><?= htmlspecialchars($data['nama_surat']); ?></strong></td>
        </tr>
    </table>

    <br>

    <p>
        Kepada Yth,<br>
        <?= htmlspecialchars($data['surat_ditujukan'] ?? 'Pimpinan Instansi'); ?><br>
        di<br>
        <span style="margin-left:25px;">Tempat</span>
    </p>

    <p>Assalamu’alaikum Wr. Wb.</p>

    <p class="isi">
        Sehubungan dengan permohonan mahasiswa Fakultas Sains dan Teknologi
        UIN Raden Intan Lampung, dengan ini kami mengajukan permohonan kepada
        Bapak/Ibu untuk dapat memberikan izin pelaksanaan kegiatan penelitian/riset
        kepada mahasiswa berikut:
    </p>

    <table style="width:100%; margin:15px 0;">
        <tr>
            <td width="180">Nama</td>
            <td width="10">:</td>
            <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
        </tr>
        <tr>
            <td>NPM</td>
            <td>:</td>
            <td><?= htmlspecialchars($data['npm']); ?></td>
        </tr>
        <tr>
            <td>Program Studi</td>
            <td>:</td>
            <td><?= htmlspecialchars($data['prodi']); ?></td>
        </tr>
        <tr>
            <td>Judul Skripsi</td>
            <td>:</td>
            <td><?= htmlspecialchars($data['judul_skripsi'] ?? '-'); ?></td>
        </tr>
        <tr>
            <td>Lokasi Penelitian</td>
            <td>:</td>
            <td><?= htmlspecialchars($data['lokasi_penelitian'] ?? '-'); ?></td>
        </tr>
    </table>

    <p class="isi">
        Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerja sama
        Bapak/Ibu, kami ucapkan terima kasih.
    </p>

    <p>Wassalamu’alaikum Wr. Wb.</p>

    <div class="ttd">
        <p>Wakil Dekan Bidang Akademik,</p>

        <?php if (!empty($data['dokumen_hash'])) { ?>
            <img src="<?= $qr_url; ?>" style="width:85px; height:85px;" alt="QR Verifikasi">
        <?php } else { ?>
            <br><br><br>
        <?php } ?>

        <p>
            <strong>Dr. SOVIA MAS AYU, MA</strong><br>
            NIP. 197611302005012006
        </p>
    </div>

    <br>

    <p style="font-size:13px;">
        <u>Tembusan:</u><br>
        1. Dekan Fakultas Sains dan Teknologi;<br>
        2. Ketua Program Studi;<br>
        3. Kasubag Akademik;<br>
        4. Mahasiswa yang bersangkutan.
    </p>

</div>

<div class="action">
    <button onclick="window.print()" class="btn-print">
        Download Surat (PDF)
    </button>

    <?php if ($_SESSION['role'] == 'pimpinan') { ?>
        <form method="POST">
            <button type="submit" name="kirim_balasan" class="btn-approve">
                Setujui & Kirim ke Mahasiswa
            </button>
        </form>
    <?php } ?>

</div>

</body>
</html>