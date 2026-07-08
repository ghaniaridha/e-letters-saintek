<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
$id_jenis = $_POST['id_jenis'];
$semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
$judul_skripsi = mysqli_real_escape_string($koneksi, $_POST['judul_skripsi']);
$lokasi_penelitian = mysqli_real_escape_string($koneksi, $_POST['lokasi_penelitian']);
$surat_ditujukan = mysqli_real_escape_string($koneksi, $_POST['surat_ditujukan']);
$pembimbing_1 = $_POST['pembimbing_1'];
$pembimbing_2 = !empty($_POST['pembimbing_2']) ? $_POST['pembimbing_2'] : 'NULL';

$folder_upload = "uploads/dokumen_hss/";

if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0777, true);
}

function uploadFile($field, $folder_upload)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] != 0) {
        echo "<script>alert('File " . $field . " wajib diupload'); history.back();</script>";
        exit;
    }

    $nama_asli = $_FILES[$field]['name'];
    $tmp_file = $_FILES[$field]['tmp_name'];
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        echo "<script>alert('Format file harus PDF, JPG, JPEG, atau PNG'); history.back();</script>";
        exit;
    }

    $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
        echo "<script>alert('Gagal upload file " . $field . "'); history.back();</script>";
        exit;
    }

    return $nama_baru;
}

$proposal_penelitian = uploadFile('proposal_penelitian', $folder_upload);
$khs = uploadFile('khs', $folder_upload);
$bukti_ukt = uploadFile('bukti_ukt', $folder_upload);

$tanggal = date('Y-m-d H:i:s');

$query_utama = "
    INSERT INTO surat_pengajuan 
    (id_mhs, id_jenis, nomor_surat, tanggal_pengajuan, status_akhir, status_pimpinan)
    VALUES 
    ('$id_mhs', '$id_jenis', '', '$tanggal', 'Menunggu Dospem 2', 'Menunggu')
";

if (mysqli_query($koneksi, $query_utama)) {
    // Ambil ID Surat yang baru saja digenerate oleh tabel utama
    $id_surat = mysqli_insert_id($koneksi);

    // Generate dan Update Dokumen Hash untuk keperluan validasi QR Code
    $dokumen_hash = hash('sha256', $id_surat . $id_mhs . time());
    mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

    /* =======================================================================
       TAHAP 2: INSERT DATA KE TABEL DETAIL (detail_surat_riset)
       Memasukkan data spesifik permohonan riset menggunakan id_surat sebagai kunci relasi.
       ======================================================================= */
    $pembimbing_1_val = ($pembimbing_1 === 'NULL') ? "NULL" : "'$pembimbing_1'";

    $query_detail = "
    INSERT INTO detail_surat_riset 
    (id_surat, semester, judul_skripsi, lokasi_penelitian, surat_ditujukan, id_pb2, id_pb1, status_pb1, status_pb2)
    VALUES 
    ('$id_surat', '$semester', '$judul_skripsi', '$lokasi_penelitian', '$surat_ditujukan', '$pembimbing_2', $pembimbing_1_val, 'Menunggu', 'Menunggu')
    ";
    mysqli_query($koneksi, $query_detail);

    /* =======================================================================
       TAHAP 3: INSERT DATA BERKAS KE TABEL LAMPIRAN (lampiran_pengajuan)
       Fungsi pembantu di bawah bertugas mencari id_syarat secara dinamis 
       dari master_syarat agar sinkron dengan file yang diunggah.
       ======================================================================= */
    function getIdSyarat($koneksi, $nama_syarat)
    {
        $nama_syarat_clean = mysqli_real_escape_string($koneksi, $nama_syarat);
        $q = mysqli_query($koneksi, "SELECT id_syarat FROM master_syarat WHERE nama_syarat LIKE '%$nama_syarat_clean%' LIMIT 1");
        $row = mysqli_fetch_assoc($q);
        return $row['id_syarat'] ?? null;
    }

    // Dapatkan masing-masing id_syarat dari tabel master_syarat
    $id_syarat_proposal = getIdSyarat($koneksi, 'Proposal Penelitian');
    $id_syarat_khs      = getIdSyarat($koneksi, 'KHS Semester Lalu');
    $id_syarat_ukt      = getIdSyarat($koneksi, 'Bukti Pembayaran UKT Terakhir');

    // Susun query insert jamak untuk tabel lampiran_pengajuan
    $lampiran_values = [];
    if ($id_syarat_proposal) {
        $lampiran_values[] = "('$id_surat', '$id_syarat_proposal', '$proposal_penelitian')";
    }
    if ($id_syarat_khs) {
        $lampiran_values[] = "('$id_surat', '$id_syarat_khs', '$khs')";
    }
    if ($id_syarat_ukt) {
        $lampiran_values[] = "('$id_surat', '$id_syarat_ukt', '$bukti_ukt')";
    }

    if (count($lampiran_values) > 0) {
        $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES " . implode(", ", $lampiran_values);
        mysqli_query($koneksi, $query_lampiran);
    }

    // Persiapan data tautan verifikasi QR Code seperti rancangan awal Anda
    $link_verifikasi = "http://192.168.1.4/e-letters-saintek/verifikasi_surat.php?hash=" . $dokumen_hash;
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);

    echo "<script>
            alert('Surat permohonan izin riset berhasil diajukan dan sedang menunggu review.'); 
            window.location='preview_surat.php?id=$id_surat';
          </script>";
    exit;
} else {
    echo "<script>alert('Sistem gagal memproses pengajuan surat.'); history.back();</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>

<body>

    <div class="surat-preview">
        <div class="surat-kertas">

            <p>Perihal : Permohonan Surat Rekomendasi Riset</p>

            <br>

            <p>Kepada Yth,</p>
            <p><strong>Dekan Fakultas Sains dan Teknologi</strong></p>
            <p><strong>UIN Raden Intan Lampung</strong></p>
            <p>di-</p>
            <p style="margin-left:40px;">Bandar Lampung</p>

            <br>

            <p>Assalamu’alaikum wr. wb.</p>
            <p>Saya yang bertanda tangan dibawah ini :</p>

            <table class="surat-table">
                <tr>
                    <td>Nama / NPM</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($mhs['nama_mhs']); ?> / <?= htmlspecialchars($mhs['npm']); ?></td>
                </tr>
                <tr>
                    <td>Semester / Program Studi</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($semester); ?> / <?= htmlspecialchars($mhs['nama_prodi']); ?></td>
                </tr>
                <tr>
                    <td>Judul Skripsi</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($_POST['judul_skripsi']); ?></td>
                </tr>
                <tr>
                    <td>Lokasi Penelitian</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($_POST['lokasi_penelitian']); ?></td>
                </tr>
                <tr>
                    <td>Surat Ditujukan Kepada</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($_POST['surat_ditujukan']); ?></td>
                </tr>
            </table>

            <br>

            <p>
                Bermaksud memohon surat Rekomendasi Riset dari pihak Fakultas,
                sebagai bahan pertimbangan Bapak, saya lampirkan:
            </p>

            <ol>
                <li>Proposal Penelitian</li>
                <li>Foto Copy Slip pembayaran UKT Terakhir</li>
                <li>KHS Semester terakhir</li>
            </ol>

            <p>Atas perhatian Bapak, saya ucapkan terima kasih</p>
            <p>Wassalamu’alaikum Wr. Wb.</p>

            <br>

            <p style="text-align:right;">Bandar Lampung, <?= date('d-m-Y'); ?></p>

            <div class="ttd-area">
                <div>
                    <p>Mengetahui,</p>
                    <p>Pembimbing I</p>
                    <br><br><br>
                    <p><strong><?= htmlspecialchars($dospem1['nama_dosen']); ?></strong></p>
                    <p>NIP. <?= htmlspecialchars($dospem1['nip']); ?></p>
                </div>

                <div>
                    <p>&nbsp;</p>
                    <p>Pembimbing II</p>
                    <br><br><br>
                    <p><strong><?= htmlspecialchars($dospem2['nama_dosen']); ?></strong></p>
                    <p>NIP. <?= htmlspecialchars($dospem2['nip']); ?></p>

                </div>

                <div>
                    <p>&nbsp;</p>
                    <p>Pemohon</p>

                    <img src="<?= $qr_url; ?>" width="85" height="85" alt="QR Verifikasi">

                    <p><strong><?= htmlspecialchars($mhs['nama_mhs']); ?></strong></p>
                    <p><?= htmlspecialchars($mhs['npm']); ?></p>
                </div>
            </div>
        </div>

        <div class="preview-actions">
            <p>Status surat: <strong>Menunggu Dospem 2</strong></p>
            <p>Dokumen pendukung dan QR pemohon sudah dibuat.</p>

            <div class="btn-group">
                <a href="mhs_riwayat.php" class="btn-generate">Kirim Pengajuan</a>
                <a href="mhs_daftar_surat_akademik.php" class="btn-back-form" style="background:#ef4444; color:#fff;">Kembali</a>
            </div>
        </div>
    </div>

</body>

</html>