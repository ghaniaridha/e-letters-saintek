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
    $nama_file = "surat_balasan_magang_" . $id_surat . "_" . time() . ".html";

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

$tglMulai = !empty($data['tanggal_mulai_magang'])
    ? date('d-m-Y', strtotime($data['tanggal_mulai_magang']))
    : '____________';

$tglSelesai = !empty($data['tanggal_selesai_magang'])
    ? date('d-m-Y', strtotime($data['tanggal_selesai_magang']))
    : '____________';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Balasan Magang</title>
<link rel="stylesheet" href="style.css?v=<?= time(); ?>">
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

.tabel-mhs {
    width:100%;
    border-collapse:collapse;
    margin:15px 0;
}

.tabel-mhs th,
.tabel-mhs td {
    border:1px solid #000;
    padding:5px;
    text-align:center;
}

.ttd {
    width:280px;
    margin-left:auto;
    margin-top:30px;
    text-align:left;
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
                <img src="images/Logo UINRIL(2).png"
                     style="width:120px;">
            </td>

            <td style="text-align:center; border:none;">
                <h3 style="margin:0;">KEMENTERIAN AGAMA</h3>
                <h3 style="margin:0;">UNIVERSITAS ISLAM NEGERI RADEN INTAN LAMPUNG</h3>
                <h2 style="margin:0;">FAKULTAS SAINS DAN TEKNOLOGI</h2>

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
            <td><strong>Permohonan Magang</strong></td>
        </tr>
    </table>

    <br>

    <p>
        Kepada Yth,<br>
        <?= htmlspecialchars($data['surat_ditujukan']); ?><br>
        di<br>
        <span style="margin-left:25px;">Tempat</span>
    </p>

    <p>Assalamu’alaikum Wr. Wb.</p>

    <p class="isi">
        Dalam rangka peningkatan standar kompetensi dan kualitas mahasiswa jurusan
        <?= htmlspecialchars($data['prodi']); ?> Fakultas Sains dan Teknologi
        UIN Raden Intan Lampung dengan keterampilan manajerial yang relevan dan sesuai
        dengan perkembangan sosial dunia usaha,
    </p>

    <p class="isi">
        Maka kami mohon kepada Bapak/Ibu kiranya berkenan menerima mahasiswa kami
        melaksanakan magang pada lembaga yang Bapak/Ibu pimpin terhitung mulai tanggal
(<?= $tglMulai; ?> s/d <?= $tglSelesai; ?>).
    </p>

    <p>Adapun nama mahasiswa sebagaimana tercantum di bawah ini:</p>

    <table class="tabel-mhs">
        <thead>
            <tr>
                <th>NO.</th>
                <th>Nama</th>
                <th>NPM</th>
                <th>No. Telp</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
                <td><?= htmlspecialchars($data['npm']); ?></td>
                <td><?= htmlspecialchars($data['no_telp']); ?></td>
            </tr>
        </tbody>
    </table>

    <p>
        Demikian permohonan ini, atas perhatian dan kerjasamanya diucapkan terimakasih.
    </p>

    <p>Wassalamu’alaikum Wr. Wb.</p>

    <div class="ttd">
        <p>Dekan,</p>
        <br><br><br>
        <p>
            <strong>Prof. Ir. H. Andi Thahir, S.Psi., M.A., Ed.D</strong><br>
            NIP. 197604272007011015
        </p>
    </div>

    <br>

    <p style="font-size:13px;">
        <u>Tembusan:</u><br>
        1. Wakil Dekan Bidang Akademik;<br>
        2. Kajur/Kaprodi Pendidikan Bahasa Arab<br>
        3. Kasubag Akademik;<br>
        4. Mahasiswa yang bersangkutan
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

    <a href="pimpinan_verif.php" class="btn-print">Kembali</a>
</div>

</body>
</html>