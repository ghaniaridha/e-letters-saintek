<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_surat = $_GET['id'] ?? '';

// 1. Ambil data surat beserta detail aktif kuliah
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        sp.*,
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        js.nama_surat,
        da.semester,
        da.lama_cuti,
        da.ta_mulai_cuti,
        da.ta_selesai_cuti,
        da.tahun_akademik
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN detail_aktif_kuliah da ON sp.id_surat = da.id_surat
    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='pimpinan_verif.php';</script>";
    exit;
}

// 2. Logika Penomoran Surat (Jika belum ada)
if (empty($data['nomor_surat'])) {
    $tahun = date('Y');

    // Kueri ini sekarang menghitung TOTAL surat yang sudah selesai secara global, 
    // tanpa mempedulikan jenis suratnya.
    $cekNomor = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT COUNT(*) AS total
        FROM surat_pengajuan
        WHERE status_akhir = 'Selesai'
        AND YEAR(tanggal_pengajuan) = '$tahun'
    "));

    // Tambahkan 1 karena kita sedang membuat nomor untuk surat baru
    $nomorUrut = str_pad($cekNomor['total'] + 1, 3, '0', STR_PAD_LEFT);

    // Sesuaikan format nomor surat Anda
    $nomorSurat = "B-" . $nomorUrut . "/Un.16/DST/PP.009/" . $tahun;

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET nomor_surat = '$nomorSurat'
        WHERE id_surat = '$id_surat'
    ");
} else {
    $nomorSurat = $data['nomor_surat'];
}

if (isset($_POST['kirim_balasan'])) {
    // 1. Tentukan nama file final (gunakan ID surat agar unik)
    $nama_file = "surat_resmi_sk_aktif_" . $id_surat . "_" . time() . ".pdf";

    // 2. Pastikan file_surat_final diperbarui
    $update_query = mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET 
            status_akhir = 'Selesai',
            status_pimpinan = 'Disetujui',
            file_surat_final = '$nama_file' 
        WHERE id_surat = '$id_surat'
    ");

    if ($update_query) {
        echo "<script>
            alert('Surat berhasil disetujui dan disimpan.');
            window.location='pimpinan_verif.php';
        </script>";
    } else {
        echo "<script>alert('Gagal memperbarui database.');</script>";
    }
    exit;
}

$tanggalSurat = date('d-m-Y');
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode("http://192.168.18.174/localhost/e-letters-saintek/verifikasi_surat.php?hash=" . $data['dokumen_hash']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>SK Resmi Aktif Kuliah Kembali</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <style>
        body {
            font-family: "Times New Roman", serif;
            padding: 30px;
            background: #f3f4f6;
        }

        .surat {
            background: white;
            max-width: 850px;
            margin: auto;
            padding: 45px 60px;
            line-height: 1.5;
            color: black;
        }

        .kop {
            text-align: center;
            border-bottom: 2px solid black;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .ttd {
            width: 300px;
            margin-left: auto;
            margin-top: 30px;
        }

        .action {
            max-width: 850px;
            margin: 20px auto;
            text-align: right;
        }

        @media print {
            .action {
                display: none;
            }

            body {
                background: white;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="surat">
        <div class="kop">
            <table style="width:100%">
                <tr>
                    <td style="width:100px"><img src="images/Logo UINRIL(2).png" style="width:90px;"></td>
                    <td style="text-align:center;">
                        <h3 style="margin:0;">KEMENTERIAN AGAMA</h3>
                        <h3 style="margin:0;">UNIVERSITAS ISLAM NEGERI RADEN INTAN LAMPUNG</h3>
                        <h2 style="margin:0;">FAKULTAS SAINS DAN TEKNOLOGI</h2>
                        <small>Jln. Letkol H. Endro Suratmin Sukarame I, Bandar Lampung 35131</small>
                    </td>
                </tr>
            </table>
        </div>

        <div style="text-align: center;">
            <h3 style="margin:0; text-decoration:underline;">SURAT AKTIF KULIAH KEMBALI</h3>
            <p>Nomor: <?= $nomorSurat; ?></p>
        </div>

        <p>Yang bertandatangan di bawah ini :</p>
        <table style="width:100%; margin-left:20px;">
            <tr>
                <td width="150">Nama</td>
                <td>: Prof. Ir. H. Andi Thahir, S.Psi., M.A., Ed.D</td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>: 197604272007011015</td>
            </tr>
            <tr>
                <td>Pangkat/Gol</td>
                <td>: Pembina Utama Madya/ (IV/d)</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: Dekan Fakultas Sains dan Teknologi UIN Raden Intan Lampung</td>
            </tr>
        </table>

        <p>Dengan ini menerangkan dengan sesungguhnya bahwa :</p>
        <table style="width:100%; margin-left:20px;">
            <tr>
                <td width="150">Nama</td>
                <td>: <?= htmlspecialchars($data['nama_mhs']); ?></td>
            </tr>
            <tr>
                <td>NPM</td>
                <td>: <?= htmlspecialchars($data['npm']); ?></td>
            </tr>
            <tr>
                <td>Jurusan</td>
                <td>: <?= htmlspecialchars($data['nama_prodi']); ?></td>
            </tr>
            <tr>
                <td>Semester</td>
                <td>: <?= htmlspecialchars($data['semester']); ?></td>
            </tr>
        </table>

        <p>Adalah benar mahasiswa Fakultas Sains dan Teknologi UIN Raden Intan Lampung. Surat keterangan ini diberikan untuk <b>Kuliah Kembali</b> pada semester <?= $data['semester']; ?> Tahun Akademik <?= $data['tahun_akademik']; ?>, berdasarkan surat Cuti Kuliah.</p>

        <p>Demikian surat keterangan ini dibuat untuk diperhatikan dan dilaksanakan sebagaimana mestinya.</p>

        <div style="margin-left:500px;">
            <p>Bandar Lampung, <?= $tanggalSurat; ?></p>
            <p>Dekan,</p>
            <img src="<?= $qr_url; ?>" style="width:85px; height:85px;" alt="QR Code">
            <p><b>Prof. Ir. H. Andi Thahir, S.Psi., M.A., Ed.D</b><br>NIP. 197604272007011015</p>
        </div>

        <div style="margin-top:20px; font-size:12px;">
            <p>Tembusan Yth.:<br>
                1. Dekan Fakultas Sains dan Teknologi UIN Raden Intan Lampung;<br>
                2. Kabag Keuangan UIN Raden Intan Lampung;<br>
                3. Kabag Akademik & Kemahasiswaan UIN Raden Intan Lampung;<br>
                4. Ketua Jurusan <?= htmlspecialchars($data['nama_prodi']); ?>;<br>
                5. Pembimbing Akademik</p>
        </div>
    </div>

    <div class="action">
        <button onclick="window.print()" class="btn-print">
            Download Surat (PDF)
        </button>

        <?php if ($_SESSION['role'] == 'pimpinan') { ?>
            <form action="generate_sk_aktif_resmi.php?id=<?= $id_surat; ?>" method="POST">
                <button type="submit" name="kirim_balasan" class="btn-approve">
                    Setujui & Kirim ke Mahasiswa
                </button>
            </form>
        <?php } ?>
    </div>
</body>

</html>