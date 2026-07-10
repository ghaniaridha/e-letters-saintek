<?php
session_start();
include "koneksi.php";

$id_mhs = $_SESSION['id_mhs'];
if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

$id_jenis = $_POST['id_jenis'];
$semester         = (int)($_POST['semester'] ?? 0);
$lama_cuti        = mysqli_real_escape_string($koneksi, $_POST['lama_cuti'] ?? '');
$ta_mulai_cuti    = mysqli_real_escape_string($koneksi, $_POST['tahun_akademik_ganjil_cuti'] ?? '');
$ta_selesai_cuti  = mysqli_real_escape_string($koneksi, $_POST['tahun_akademik_genap_cuti'] ?? '');
$tahun_akademik   = mysqli_real_escape_string($koneksi, $_POST['tahun_akademik'] ?? '');
$id_pa            = (int)($_POST['id_pa'] ?? 0);

if ($id_pa <= 0) {
    echo "<script>alert('Gagal: Data Pembimbing Akademik tidak ditemukan. Pastikan PA sudah diatur.'); history.back();</script>";
    exit;
}

$folder_upload = "uploads/dokumen_hss/";

if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0777, true);
}

function uploadFile($field, $folder_upload)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] != 0) {
        echo "<script>alert('File berkas permohonan wajib diupload'); history.back();</script>";
        exit;
    }

    $nama_asli = $_FILES[$field]['name'];
    $tmp_file = $_FILES[$field]['tmp_name'];
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        echo "<script>alert('Format file lampiran harus PDF, JPG, JPEG, atau PNG'); history.back();</script>";
        exit;
    }

    // Penamaan file dinamis berdasarkan field input form
    $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
        echo "<script>alert('Gagal mengunggah file berkas.'); history.back();</script>";
        exit;
    }

    return $nama_baru;
}

$file_cuti = uploadFile('sk_cuti', $folder_upload);

$tanggal = date('Y-m-d H:i:s');

$query_utama = "INSERT INTO surat_pengajuan (id_mhs, id_jenis, nomor_surat, tanggal_pengajuan, status_akhir, status_pimpinan) 
                VALUES ('$id_mhs', '$id_jenis', '', '$tanggal', 'Menunggu Pembimbing Akademik', 'Menunggu')";

if (mysqli_query($koneksi, $query_utama)) {
    $id_surat = mysqli_insert_id($koneksi);

    // Generate dan Update Dokumen Hash untuk keperluan pelacakan QR Code dan validasi link keamanan
    $dokumen_hash = hash('sha256', $id_surat . $id_mhs . time());
    mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

    /* =======================================================================
       TAHAP 2: INSERT DATA KE TABEL DETAIL (detail_aktif_kuliah)
       ======================================================================= */
    $query_detail = "INSERT INTO detail_aktif_kuliah 
                     (id_surat, semester, lama_cuti, ta_mulai_cuti, ta_selesai_cuti, tahun_akademik, id_pa, status_pa) 
                     VALUES 
                     ('$id_surat', '$semester', '$lama_cuti', '$ta_mulai_cuti', '$ta_selesai_cuti', '$tahun_akademik', '$id_pa', 'Menunggu')";

    if (mysqli_query($koneksi, $query_detail)) {

        /* =======================================================================
           TAHAP 3: INSERT DATA BERKAS KE TABEL LAMPIRAN (lampiran_pengajuan)
           ======================================================================= */
        function getIdSyarat($koneksi, $nama_syarat)
        {
            $nama_syarat_clean = mysqli_real_escape_string($koneksi, $nama_syarat);
            $q = mysqli_query($koneksi, "SELECT id_syarat FROM master_syarat WHERE nama_syarat LIKE '%$nama_syarat_clean%' LIMIT 1");
            $row = mysqli_fetch_assoc($q);
            return $row['id_syarat'] ?? null;
        }

        // Ambil ID Syarat dinamis berdasarkan nama berkas syarat di master_syarat Anda
        $id_syarat_cuti = getIdSyarat($koneksi, 'SK Cuti');

        // Jika ID syarat ditemukan di master_syarat, jalankan query insert lampiran
        if ($id_syarat_cuti) {
            $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) 
                               VALUES ('$id_surat', '$id_syarat_cuti', '$file_cuti')";
            mysqli_query($koneksi, $query_lampiran);
        }

        echo "<script>alert('Surat Aktif Kuliah Kembali berhasil diajukan.'); window.location='mhs_preview_sk_aktif.php?id=$id_surat';</script>";
        exit;
    } else {
        // Rollback data master pengajuan jika penyimpanan tabel detail mengalami kegagalan
        mysqli_query($koneksi, "DELETE FROM surat_pengajuan WHERE id_surat = '$id_surat'");
        echo "<script>alert('Gagal menyimpan rincian detail berkas surat aktif.'); history.back();</script>";
        exit;
    }
} else {
    echo "<script>alert('Sistem gagal memproses pengajuan utama.'); history.back();</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>

<body>
</body>

</html>