<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
$id_jenis = $_POST['id_jenis'];
// CEK APABILA MODE REVISI
$id_surat = isset($_POST['id_surat']) ? (int)$_POST['id_surat'] : 0;
$is_revisi = ($id_surat > 0);

$semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
$judul_skripsi = mysqli_real_escape_string($koneksi, $_POST['judul_skripsi']);
$lokasi_penelitian = mysqli_real_escape_string($koneksi, $_POST['lokasi_penelitian']);
$surat_ditujukan = mysqli_real_escape_string($koneksi, $_POST['surat_ditujukan']);
$pembimbing_1 = $_POST['pembimbing_1'];
$pembimbing_2 = !empty($_POST['pembimbing_2']) ? $_POST['pembimbing_2'] : 'NULL';
$pembimbing_1_val = ($pembimbing_1 === 'NULL' || empty($pembimbing_1)) ? "NULL" : "'$pembimbing_1'";
$pembimbing_2_val = ($pembimbing_2 === 'NULL') ? "NULL" : "'$pembimbing_2'";

$folder_upload = __DIR__ . "/uploads/dokumen_hss/";
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
            echo "<script>alert('File " . $field . " wajib diupload'); history.back();</script>";
            exit;
        }
    }

    if ($_FILES[$field]['error'] != 0) {
        echo "<script>alert('Terjadi kesalahan saat upload file " . $field . "'); history.back();</script>";
        exit;
    }

    $nama_asli = $_FILES[$field]['name'];
    $tmp_file = $_FILES[$field]['tmp_name'];
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_asli = finfo_file($finfo, $tmp_file);
    finfo_close($finfo);

    if (!in_array($ext, $allowed_ext) || !in_array($mime_asli, $allowed_mime)) {
        echo "<script>alert('Format file " . $field . " tidak valid atau file telah dimanipulasi!'); history.back();</script>";
        exit;
    }

    $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
        echo "<script>alert('Gagal upload file " . $field . "'); history.back();</script>";
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

$ext_proposal = ['pdf'];
$mime_proposal = ['application/pdf'];
$ext_umum = ['pdf', 'jpg', 'jpeg', 'png'];
$mime_umum = ['application/pdf', 'image/jpeg', 'image/png'];

// UPLOAD FILE
$proposal_penelitian = uploadFile('proposal_penelitian', $folder_upload, $ext_proposal, $mime_proposal, $is_revisi);
$khs = uploadFile('khs', $folder_upload, $ext_umum, $mime_umum, $is_revisi);
$bukti_ukt = uploadFile('bukti_ukt', $folder_upload, $ext_umum, $mime_umum, $is_revisi);

$id_syarat_proposal = getIdSyarat($koneksi, 'Proposal Penelitian');
$id_syarat_khs      = getIdSyarat($koneksi, 'KHS Semester Lalu');
$id_syarat_ukt      = getIdSyarat($koneksi, 'Bukti Pembayaran UKT Terakhir');

// -------------------------------------------------------------
// JIKA MODE REVISI (UPDATE)
// -------------------------------------------------------------
if ($is_revisi) {
    $query_update_utama = "
        UPDATE surat_pengajuan 
        SET status_akhir = 'Menunggu Dospem 2', catatan = NULL, alasan_penolakan = NULL
        WHERE id_surat = '$id_surat' AND id_mhs = '$id_mhs'
    ";
    mysqli_query($koneksi, $query_update_utama);

    $query_update_detail = "
        UPDATE detail_surat_riset 
        SET semester = '$semester', judul_skripsi = '$judul_skripsi', lokasi_penelitian = '$lokasi_penelitian', 
            surat_ditujukan = '$surat_ditujukan', id_pb1 = $pembimbing_1_val, id_pb2 = $pembimbing_2_val,
            status_pb1 = 'Menunggu', status_pb2 = 'Menunggu', catatan_pb1 = NULL, catatan_pb2 = NULL
        WHERE id_surat = '$id_surat'
    ";

    if (mysqli_query($koneksi, $query_update_detail)) {

        function prosesRevisiLampiran($koneksi, $id_surat, $id_syarat, $file_baru)
        {
            if ($id_syarat) {
                if ($file_baru !== null) {
                    $cek_lampiran = mysqli_query($koneksi, "SELECT id_lampiran FROM lampiran_pengajuan WHERE id_surat = '$id_surat' AND id_syarat = '$id_syarat'");
                    if (mysqli_num_rows($cek_lampiran) > 0) {
                        mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET file_upload = '$file_baru', status_validasi = 'Menunggu' WHERE id_surat = '$id_surat' AND id_syarat = '$id_syarat'");
                    } else {
                        mysqli_query($koneksi, "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES ('$id_surat', '$id_syarat', '$file_baru')");
                    }
                } else {
                    mysqli_query($koneksi, "UPDATE lampiran_pengajuan SET status_validasi = 'Menunggu' WHERE id_surat = '$id_surat' AND id_syarat = '$id_syarat'");
                }
            }
        }

        prosesRevisiLampiran($koneksi, $id_surat, $id_syarat_proposal, $proposal_penelitian);
        prosesRevisiLampiran($koneksi, $id_surat, $id_syarat_khs, $khs);
        prosesRevisiLampiran($koneksi, $id_surat, $id_syarat_ukt, $bukti_ukt);

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Revisi pengajuan berhasil dikirim!';
        header("Location: mhs_riwayat.php");
        exit;
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = 'Gagal memproses revisi surat riset.';
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

    $query_utama = "
        INSERT INTO surat_pengajuan 
        (id_mhs, id_jenis, nomor_surat, file_surat_final, dokumen_hash, tanggal_pengajuan, status_akhir, status_pimpinan, kode_pelacakan)
        VALUES 
        ('$id_mhs', '$id_jenis', '', '', '', '$tanggal', 'Menunggu Dospem 2', 'Menunggu', '$kode_pelacakan')
    ";

    if (mysqli_query($koneksi, $query_utama)) {
        $id_surat = mysqli_insert_id($koneksi);

        $dokumen_hash = hash('sha256', $id_surat . $id_mhs . time());
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

        $query_detail = "
        INSERT INTO detail_surat_riset 
        (id_surat, semester, judul_skripsi, lokasi_penelitian, surat_ditujukan, id_pb2, id_pb1, status_pb1, status_pb2)
        VALUES 
        ('$id_surat', '$semester', '$judul_skripsi', '$lokasi_penelitian', '$surat_ditujukan', $pembimbing_2_val, $pembimbing_1_val, 'Menunggu', 'Menunggu')
        ";
        mysqli_query($koneksi, $query_detail);

        $lampiran_values = [];
        if ($id_syarat_proposal && $proposal_penelitian !== null) {
            $lampiran_values[] = "('$id_surat', '$id_syarat_proposal', '$proposal_penelitian')";
        }
        if ($id_syarat_khs && $khs !== null) {
            $lampiran_values[] = "('$id_surat', '$id_syarat_khs', '$khs')";
        }
        if ($id_syarat_ukt && $bukti_ukt !== null) {
            $lampiran_values[] = "('$id_surat', '$id_syarat_ukt', '$bukti_ukt')";
        }

        if (count($lampiran_values) > 0) {
            $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES " . implode(", ", $lampiran_values);
            mysqli_query($koneksi, $query_lampiran);
        }

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Pengajuan berhasil! Lihat dan salin kode lacak anda pada halaman riwayat.';
        header("Location: preview_surat_riset_mhs.php?id=$id_surat");
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
<html lang="id">

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