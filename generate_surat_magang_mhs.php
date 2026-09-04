<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
$id_surat_edit = isset($_POST['id_surat']) ? (int)$_POST['id_surat'] : 0;

$id_jenis               = $_POST['id_jenis'];
$semester               = mysqli_real_escape_string($koneksi, $_POST['semester']);
$tanggal_mulai_magang   = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai_magang']);
$tanggal_selesai_magang = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai_magang']);
$lokasi_magang          = mysqli_real_escape_string($koneksi, $_POST['lokasi_magang']);
$surat_ditujukan        = mysqli_real_escape_string($koneksi, $_POST['surat_ditujukan']);

$folder_upload = "uploads/dokumen_hss/";
if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0777, true);
    chmod($folder_upload, 0777);
}

function uploadFile($field, $folder_upload, $allowed_ext, $allowed_mime)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] != 0) {
        return null;
    }

    $nama_asli = $_FILES[$field]['name'];
    $tmp_file = $_FILES[$field]['tmp_name'];
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_asli = finfo_file($finfo, $tmp_file);
    finfo_close($finfo);

    if (!in_array($ext, $allowed_ext) || !in_array($mime_asli, $allowed_mime)) {
        echo "<script>alert('Format file " . $field . " tidak valid atau dimanipulasi!'); history.back();</script>";
        exit;
    }

    $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
        echo "<script>alert('Gagal upload file " . $field . "'); history.back();</script>";
        exit;
    }

    return $nama_baru;
}

$ext_umum = ['pdf', 'jpg', 'jpeg', 'png'];
$mime_umum = ['application/pdf', 'image/jpeg', 'image/png'];

$file_ktm       = uploadFile('ktm', $folder_upload, $ext_umum, $mime_umum);
$file_bukti_ukt = uploadFile('bukti_ukt', $folder_upload, $ext_umum, $mime_umum);
$file_khs       = uploadFile('khs', $folder_upload, $ext_umum, $mime_umum);

$tanggal_pengajuan = date('Y-m-d H:i:s');

// ==========================================
// JIKA MODE EDIT / REVISI
// ==========================================
if ($id_surat_edit > 0) {
    $query_utama = "
        UPDATE surat_pengajuan 
        SET tanggal_pengajuan = '$tanggal_pengajuan', 
            status_akhir = 'Menunggu Admin', 
            tujuan_admin = 'admin2', 
            alasan_penolakan = NULL 
        WHERE id_surat = '$id_surat_edit' AND id_mhs = '$id_mhs'
    ";

    if (mysqli_query($koneksi, $query_utama)) {
        // Update detail magang
        $query_detail = "
            UPDATE detail_surat_magang 
            SET semester = '$semester', 
                tanggal_mulai_magang = '$tanggal_mulai_magang', 
                tanggal_selesai_magang = '$tanggal_selesai_magang', 
                lokasi_magang = '$lokasi_magang', 
                surat_ditujukan = '$surat_ditujukan' 
            WHERE id_surat = '$id_surat_edit'
        ";
        mysqli_query($koneksi, $query_detail);

        if ($file_ktm) {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_ktm', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat_edit' AND id_syarat = '4'");
        }
        if ($file_bukti_ukt) {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_bukti_ukt', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat_edit' AND id_syarat = '2'");
        }
        if ($file_khs) {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_khs', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat_edit' AND id_syarat = '3'");
        }

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Revisi pengajuan surat berhasil dikirim ulang ke admin!';
        header("Location: preview_surat_magang_mhs.php?id=$id_surat_edit");
        exit;
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = 'Gagal memperbarui permohonan surat.';
        header("Location: mhs_riwayat.php");
        exit;
    }
}
// ==========================================
// JIKA PENGAJUAN BARU
// ==========================================
else {
    if (!$file_ktm || !$file_bukti_ukt || !$file_khs) {
        echo "<script>alert('Semua dokumen pendukung wajib diupload untuk pengajuan baru!'); history.back();</script>";
        exit;
    }

    $dokumen_hash = hash('sha256', $id_mhs . $id_jenis . time());
    $karakter = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $kode_pelacakan = 'TRK-' . substr(str_shuffle($karakter), 0, 6);

    $query_utama = "
        INSERT INTO surat_pengajuan 
        (id_mhs, id_jenis, nomor_surat, file_surat_final, tanggal_pengajuan, status_akhir, status_pimpinan, tujuan_admin, dokumen_hash, kode_pelacakan)
        VALUES 
        ('$id_mhs', '$id_jenis', '', '', '$tanggal_pengajuan', 'Menunggu Admin', 'Menunggu', 'admin2', '$dokumen_hash', '$kode_pelacakan')
    ";

    if (mysqli_query($koneksi, $query_utama)) {
        $id_surat = mysqli_insert_id($koneksi);

        $query_detail = "
            INSERT INTO detail_surat_magang 
            (id_surat, semester, tanggal_mulai_magang, tanggal_selesai_magang, lokasi_magang, surat_ditujukan)
            VALUES 
            ('$id_surat', '$semester', '$tanggal_mulai_magang', '$tanggal_selesai_magang', '$lokasi_magang', '$surat_ditujukan')
        ";

        if (mysqli_query($koneksi, $query_detail)) {
            $lampiran_values = [];
            $lampiran_values[] = "('$id_surat', '4', '$file_ktm')";
            $lampiran_values[] = "('$id_surat', '2', '$file_bukti_ukt')";
            $lampiran_values[] = "('$id_surat', '3', '$file_khs')";

            $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES " . implode(", ", $lampiran_values);
            mysqli_query($koneksi, $query_lampiran);

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Pengajuan berhasil! Lihat kode lacak pada halaman riwayat.';
            header("Location: preview_surat_magang_mhs.php?id=$id_surat");
            exit;
        }
    }

    $_SESSION['status'] = 'error';
    $_SESSION['pesan']  = 'Sistem gagal memproses pengajuan surat.';
    header("Location: mhs_beranda.php");
    exit;
}
