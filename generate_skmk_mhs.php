<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
$id_surat_edit = isset($_POST['id_surat']) ? (int)$_POST['id_surat'] : 0;

$id_jenis       = $_POST['id_jenis'];
$semester       = mysqli_real_escape_string($koneksi, $_POST['semester']);
$tahun_akademik = mysqli_real_escape_string($koneksi, $_POST['tahun_akademik']);
$keperluan      = mysqli_real_escape_string($koneksi, $_POST['keperluan']);
$nama_ortu      = mysqli_real_escape_string($koneksi, $_POST['nama_ortu']);
$nip_ortu       = mysqli_real_escape_string($koneksi, $_POST['nip_ortu']);
$instansi_ortu  = mysqli_real_escape_string($koneksi, $_POST['instansi_ortu']);
$alamat_ortu    = mysqli_real_escape_string($koneksi, $_POST['alamat_ortu']);

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

$file_kk       = uploadFile('kk', $folder_upload, $ext_umum, $mime_umum);
$file_sk_ortu  = uploadFile('sk_ortu', $folder_upload, $ext_umum, $mime_umum);
$file_ktm_ktp  = uploadFile('ktm_ktp', $folder_upload, $ext_umum, $mime_umum);
$file_slip_spp = uploadFile('slip_spp', $folder_upload, $ext_umum, $mime_umum);
$file_khs      = uploadFile('khs', $folder_upload, $ext_umum, $mime_umum);

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
        // Update detail SKMK
        $query_detail = "
            UPDATE detail_skmk 
            SET semester = '$semester', 
                tahun_akademik = '$tahun_akademik', 
                keperluan = '$keperluan', 
                nama_ortu = '$nama_ortu', 
                nip_ortu = '$nip_ortu', 
                instansi_ortu = '$instansi_ortu', 
                alamat_ortu = '$alamat_ortu'
            WHERE id_surat = '$id_surat_edit'
        ";
        mysqli_query($koneksi, $query_detail);

        if ($file_kk) {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_kk', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat_edit' AND id_syarat = '11'");
        }
        if ($file_sk_ortu) {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_sk_ortu', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat_edit' AND id_syarat = '12'");
        }
        if ($file_ktm_ktp) {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_ktm_ktp', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat_edit' AND id_syarat = '4'");
        }
        if ($file_slip_spp) {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_slip_spp', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat_edit' AND id_syarat = '2'");
        }
        if ($file_khs) {
            mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_khs', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat_edit' AND id_syarat = '3'");
        }

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Revisi pengajuan SK Masih Kuliah berhasil dikirim ulang ke admin!';
        header("Location: preview_skmk_mhs.php?id=$id_surat_edit");
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
    if (!$file_kk || !$file_sk_ortu || !$file_ktm_ktp || !$file_slip_spp || !$file_khs) {
        echo "<script>alert('Ke-5 dokumen pendukung wajib diupload untuk pengajuan baru!'); history.back();</script>";
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
            INSERT INTO detail_skmk 
            (id_surat, semester, tahun_akademik, keperluan, nama_ortu, nip_ortu, instansi_ortu, alamat_ortu)
            VALUES 
            ('$id_surat', '$semester', '$tahun_akademik', '$keperluan', '$nama_ortu', '$nip_ortu', '$instansi_ortu', '$alamat_ortu')
        ";

        if (mysqli_query($koneksi, $query_detail)) {
            $lampiran_values = [];
            // CATATAN: Ubah angka '10', '11', dst. sesuai ID di master_syarat Anda
            $lampiran_values[] = "('$id_surat', '11', '$file_kk')";
            $lampiran_values[] = "('$id_surat', '12', '$file_sk_ortu')";
            $lampiran_values[] = "('$id_surat', '4', '$file_ktm_ktp')";
            $lampiran_values[] = "('$id_surat', '2', '$file_slip_spp')";
            $lampiran_values[] = "('$id_surat', '3', '$file_khs')";

            $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES " . implode(", ", $lampiran_values);
            mysqli_query($koneksi, $query_lampiran);

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Pengajuan SK Masih Kuliah berhasil! Lihat kode lacak pada halaman riwayat.';
            header("Location: preview_skmk_mhs.php?id=$id_surat");
            exit;
        }
    }

    $_SESSION['status'] = 'error';
    $_SESSION['pesan']  = 'Sistem gagal memproses pengajuan surat.';
    header("Location: mhs_beranda.php");
    exit;
}
