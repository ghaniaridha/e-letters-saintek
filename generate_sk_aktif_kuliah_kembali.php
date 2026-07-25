<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
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
    chmod($folder_upload, 0777);
}

function uploadFile($field, $folder_upload, $allowed_ext, $allowed_mime)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] != 0) {
        echo "<script>alert('File berkas permohonan wajib diupload'); history.back();</script>";
        exit;
    }

    $nama_asli = $_FILES[$field]['name'];
    $tmp_file = $_FILES[$field]['tmp_name'];
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_asli = finfo_file($finfo, $tmp_file);
    finfo_close($finfo);

    if (!in_array($ext, $allowed_ext) || !in_array($mime_asli, $allowed_mime)) {
        echo "<script>alert('Format file lampiran tidak valid atau file telah dimanipulasi!'); history.back();</script>";
        exit;
    }

    $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
        echo "<script>alert('Gagal mengunggah file berkas.'); history.back();</script>";
        exit;
    }

    return $nama_baru;
}

$ext_umum = ['pdf', 'jpg', 'jpeg', 'png'];
$mime_umum = ['application/pdf', 'image/jpeg', 'image/png'];

$file_cuti = uploadFile('sk_cuti', $folder_upload, $ext_umum, $mime_umum);

$tanggal = date('Y-m-d H:i:s');

$query_utama = "INSERT INTO surat_pengajuan (id_mhs, id_jenis, nomor_surat, file_surat_final, dokumen_hash, tanggal_pengajuan, status_akhir, status_pimpinan) 
                VALUES ('$id_mhs', '$id_jenis', '', '', '', '$tanggal', 'Menunggu Pembimbing Akademik', 'Menunggu')";

if (mysqli_query($koneksi, $query_utama)) {
    $id_surat = mysqli_insert_id($koneksi);

    $dokumen_hash = hash('sha256', $id_surat . $id_mhs . time());
    mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

    $query_detail = "INSERT INTO detail_aktif_kuliah 
                     (id_surat, semester, lama_cuti, ta_mulai_cuti, ta_selesai_cuti, tahun_akademik, id_pa, status_pa) 
                     VALUES 
                     ('$id_surat', '$semester', '$lama_cuti', '$ta_mulai_cuti', '$ta_selesai_cuti', '$tahun_akademik', '$id_pa', 'Menunggu')";

    if (mysqli_query($koneksi, $query_detail)) {

        function getIdSyarat($koneksi, $nama_syarat)
        {
            $nama_syarat_clean = mysqli_real_escape_string($koneksi, $nama_syarat);
            $q = mysqli_query($koneksi, "SELECT id_syarat FROM master_syarat WHERE nama_syarat LIKE '%$nama_syarat_clean%' LIMIT 1");
            $row = mysqli_fetch_assoc($q);
            return $row['id_syarat'] ?? null;
        }

        $id_syarat_cuti = getIdSyarat($koneksi, 'SK Cuti');

        if ($id_syarat_cuti) {
            $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) 
                               VALUES ('$id_surat', '$id_syarat_cuti', '$file_cuti')";
            mysqli_query($koneksi, $query_lampiran);
        }

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'SK Aktif Kuliah Kembali berhasil dibuat dan diajukan.';
        header("Location: preview_sk_aktif_mhs.php?id=$id_surat");
        exit;
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = 'Sistem gagal memproses pengajuan surat.';
        $halaman_sebelumnya = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'mhs_beranda.php';
        header("Location: " . $halaman_sebelumnya);
        exit;
    }
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