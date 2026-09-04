<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
$id_jenis = $_POST['id_jenis'];
// CEK APABILA SEDANG MODE REVISI
$id_surat = isset($_POST['id_surat']) ? (int)$_POST['id_surat'] : 0;
$is_revisi = ($id_surat > 0);

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

function uploadFile($field, $folder_upload, $allowed_ext, $allowed_mime, $is_revisi)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] == 4) {
        if ($is_revisi) {
            return null;
        } else {
            echo "<script>alert('File berkas permohonan wajib diupload'); history.back();</script>";
            exit;
        }
    }

    if ($_FILES[$field]['error'] != 0) {
        echo "<script>alert('Terjadi kesalahan saat mengunggah file.'); history.back();</script>";
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

function getIdSyarat($koneksi, $nama_syarat)
{
    $nama_syarat_clean = mysqli_real_escape_string($koneksi, $nama_syarat);
    $q = mysqli_query($koneksi, "SELECT id_syarat FROM master_syarat WHERE nama_syarat LIKE '%$nama_syarat_clean%' LIMIT 1");
    $row = mysqli_fetch_assoc($q);
    return $row['id_syarat'] ?? null;
}

$ext_umum = ['pdf', 'jpg', 'jpeg', 'png'];
$mime_umum = ['application/pdf', 'image/jpeg', 'image/png'];
$id_syarat_cuti = getIdSyarat($koneksi, 'SK Cuti');

$file_cuti = uploadFile('sk_cuti', $folder_upload, $ext_umum, $mime_umum, $is_revisi);


// -------------------------------------------------------------
// JIKA MODE REVISI (UPDATE)
// -------------------------------------------------------------
if ($is_revisi) {
    $query_update_utama = "
        UPDATE surat_pengajuan 
        SET status_akhir = 'Menunggu Pembimbing Akademik', catatan = NULL, alasan_penolakan = NULL
        WHERE id_surat = '$id_surat' AND id_mhs = '$id_mhs'
    ";
    mysqli_query($koneksi, $query_update_utama);

    $query_update_detail = "
        UPDATE detail_aktif_kuliah 
        SET semester = '$semester', lama_cuti = '$lama_cuti', ta_mulai_cuti = '$ta_mulai_cuti', 
            ta_selesai_cuti = '$ta_selesai_cuti', tahun_akademik = '$tahun_akademik', id_pa = '$id_pa', 
            status_pa = 'Menunggu', catatan_pa = NULL
        WHERE id_surat = '$id_surat'
    ";

    if (mysqli_query($koneksi, $query_update_detail)) {

        if ($file_cuti !== null && $id_syarat_cuti) {
            $cek_lampiran = mysqli_query($koneksi, "SELECT id_lampiran FROM lampiran_pengajuan WHERE id_surat = '$id_surat' AND id_syarat = '$id_syarat_cuti'");
            if (mysqli_num_rows($cek_lampiran) > 0) {
                mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_cuti', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat' AND id_syarat = '$id_syarat_cuti'");
            } else {
                mysqli_query($koneksi, "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES ('$id_surat', '$id_syarat_cuti', '$file_cuti')");
            }
        } else {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET status_validasi = 'Menunggu' WHERE id_surat = '$id_surat'");
        }

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Revisi pengajuan berhasil dikirim!';
        header("Location: mhs_riwayat.php");
        exit;
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = 'Gagal memproses revisi surat.';
        header("Location: mhs_riwayat.php");
        exit;
    }
}

// -------------------------------------------------------------
// JIKA MODE BARU (INSERT)
// -------------------------------------------------------------
else {
    $tanggal = date('Y-m-d H:i:s');
    $karakter = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $kode_pelacakan = 'TRK-' . substr(str_shuffle($karakter), 0, 6);

    $query_utama = "INSERT INTO surat_pengajuan (id_mhs, id_jenis, nomor_surat, file_surat_final, dokumen_hash, tanggal_pengajuan, status_akhir, status_pimpinan, kode_pelacakan) 
                    VALUES ('$id_mhs', '$id_jenis', '', '', '', '$tanggal', 'Menunggu Pembimbing Akademik', 'Menunggu', '$kode_pelacakan')";

    if (mysqli_query($koneksi, $query_utama)) {
        $id_surat = mysqli_insert_id($koneksi);

        $dokumen_hash = hash('sha256', $id_surat . $id_mhs . time());
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

        $query_detail = "INSERT INTO detail_aktif_kuliah 
                         (id_surat, semester, lama_cuti, ta_mulai_cuti, ta_selesai_cuti, tahun_akademik, id_pa, status_pa) 
                         VALUES 
                         ('$id_surat', '$semester', '$lama_cuti', '$ta_mulai_cuti', '$ta_selesai_cuti', '$tahun_akademik', '$id_pa', 'Menunggu')";

        if (mysqli_query($koneksi, $query_detail)) {

            if ($id_syarat_cuti && $file_cuti !== null) {
                $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES ('$id_surat', '$id_syarat_cuti', '$file_cuti')";
                mysqli_query($koneksi, $query_lampiran);
            }

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Pengajuan berhasil! Lihat dan salin kode lacak anda pada halaman riwayat.';
            header("Location: preview_sk_aktif_mhs.php?id=$id_surat");
            exit;
        } else {
            $_SESSION['status'] = 'error';
            $_SESSION['pesan']  = 'Sistem gagal memproses pengajuan surat detail.';
            header("Location: mhs_beranda.php");
            exit;
        }
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = 'Sistem gagal memproses pengajuan surat utama.';
        header("Location: mhs_beranda.php");
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