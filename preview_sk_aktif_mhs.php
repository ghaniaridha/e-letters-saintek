<?php
include "koneksi.php";
$id = (int)$_GET['id']; // Ambil ID dari URL

// Ambil data dari tabel master dan tabel detail sekaligus
$query = mysqli_query($koneksi, "
    SELECT sp.*, m.nama_mhs, m.npm, p.nama_prodi, da.*, pa.nama_dosen as nama_pa, pa.nip as nip_pa
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

$semester = $data['semester'];

$base_url = "http://192.168.1.4/e-letters-saintek";
$link_verifikasi = $base_url . "/verifikasi_surat.php?hash=" . $data['dokumen_hash'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);

$qr_pa_url = "";
if (isset($data['status_pa']) && $data['status_pa'] == 'Disetujui' && !empty($data['ttd_pa'])) {
    $link_verifikasi_pa = $base_url . "/verifikasi_surat.php?hash=" . $data['ttd_pa'];
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
    <style>
        body {
            background: #f3f4f6;
            font-family: "Times New Roman", serif;
            padding: 30px;
        }

        /* PERBAIKAN CSS: Menyesuaikan class dengan HTML dan mengatur ukuran kertas A4 */
        .surat-kertas {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 25mm;
            /* Padding standar surat */
            box-sizing: border-box;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            line-height: 1.7;
            color: black;
            font-size: 12pt;
        }

        .surat-table {
            width: 100%;
        }

        .surat-table td {
            vertical-align: top;
            padding: 4px;
        }

        .qr-img {
            width: 85px;
            height: 85px;
            margin: 10px 0;
            display: block;
        }

        .preview-actions {
            text-align: center;
            margin: 40px auto;
            max-width: 800px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }

        .btn-generate,
        .btn-back-form {
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: Arial, sans-serif;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-generate {
            background-color: #0d6efd;
            color: #fff;
        }

        .btn-generate:hover {
            background-color: #0b5ed7;
        }

        .btn-back-form {
            background-color: #ef4444;
            color: #fff;
        }

        .btn-back-form:hover {
            background-color: #dc2626;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }

            .surat-kertas {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }

            .preview-actions {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="surat-preview">
        <div class="surat-kertas">

            <p>Perihal : Permohonan aktif Kuliah Kembali</p>
            <br>
            <p>Kepada<br>
                Yth. Dekan Fakultas Sains dan Teknologi<br>
                UIN Raden Intan Lampung</p>
            <br>

            <p>Assalamu’alaikum wr. wb.</p>
            <br>
            <p>Saya yang bertanda tangan dibawah ini :</p>

            <table class="surat-table" style="margin-bottom: 20px;">
                <tr>
                    <td width="150">Nama</td>
                    <td width="15">:</td>
                    <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
                </tr>
                <tr>
                    <td>NPM</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($data['npm']); ?></td>
                </tr>
                <tr>
                    <td>Semester</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($data['semester'] ?? ''); ?></td>
                </tr>
                <tr>
                    <td>Tahun Akademik</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($data['tahun_akademik'] ?? ''); ?></td>
                </tr>
                <tr>
                    <td>Program Studi</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($data['nama_prodi']); ?></td>
                </tr>
                <tr>
                    <td>Lama Cuti</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($data['lama_cuti'] ?? ''); ?> Semester,</td>
                </tr>
                <tr>
                    <td style="vertical-align: top;">yaitu</td>
                    <td style="vertical-align: top;">:</td>
                    <td>
                        Semester Gasal tahun Akademik <?= htmlspecialchars($data['ta_mulai_cuti'] ?? '..../....'); ?> <br>
                        Semester Genap tahun Akademik <?= htmlspecialchars($data['ta_selesai_cuti'] ?? '..../....'); ?>
                    </td>
                </tr>
            </table>

            <p style="text-align: justify; line-height: 1.6;">
                Dengan ini mengajukan permohonan untuk <strong>aktif kuliah kembali</strong> pada semester:
                <strong><?= htmlspecialchars($data['semester_akademik'] ?? '..........'); ?></strong> tahun Akademik <strong><?= htmlspecialchars($data['tahun_akademik'] ?? '..../....'); ?></strong>
                bersama ini saya lampirkan fotocopy Surat Keterangan Cuti yang pernah diambil.
            </p>

            <br>
            <p>Demikian, dan atas perkenan Bapak diucapkan terima kasih.</p>
            <p>Wassalamu’alaikum Wr. Wb.</p>
            <br><br>

            <div class="ttd-area" style="display: flex; justify-content: space-between; margin-top: 30px;">

                <div style="text-align: left; width: 260px;">
                    <p>Mengetahui :</p>
                    <p>Pembimbing Akademik,</p>

                    <?php if (!empty($qr_pa_url)) { ?>
                        <img src="<?= $qr_pa_url; ?>" class="qr-img" alt="QR Verifikasi Dosen PA">
                    <?php } else { ?>
                        <br><br><br><br>
                    <?php } ?>

                    <p><strong><?= htmlspecialchars($data['nama_pa'] ?? '.......................................'); ?></strong></p>
                    <p>NIP. <?= htmlspecialchars($data['nip_pa'] ?? '.......................................'); ?></p>
                </div>

                <div style="text-align: left; width: 260px;">
                    <p>&nbsp;</p>
                    <p>Pemohon,</p>

                    <img src="<?= $qr_url; ?>" class="qr-img" alt="QR Verifikasi">

                    <p><strong><?= htmlspecialchars($data['nama_mhs']); ?></strong></p>
                    <p>NPM. <?= htmlspecialchars($data['npm']); ?></p>
                </div>

            </div>

        </div>
    </div>

    <div class="preview-actions">
        <p>Status surat: <strong><?= htmlspecialchars($data['status_akhir'] ?? 'Menunggu Verifikasi'); ?></strong></p>
        <p>Dokumen pendukung dan QR pemohon sudah dibuat.</p>

        <div class="btn-group">
            <a href="mhs_lacak.php" class="btn-generate">Kirim Pengajuan</a>
            <a href="mhs_daftar_surat_akademik.php" class="btn-back-form">Kembali</a>
        </div>
    </div>

</body>

</html>