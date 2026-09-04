<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_ormawa'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ormawa_form_pengajuan_dana.php");
    exit;
}

$id_surat_edit    = isset($_POST['id_surat']) ? (int)$_POST['id_surat'] : 0;
$id_ormawa        = mysqli_real_escape_string($koneksi, $_POST['id_ormawa']);
$id_jenis         = mysqli_real_escape_string($koneksi, $_POST['id_jenis']);
$id_pembina       = mysqli_real_escape_string($koneksi, $_POST['id_pembina']);
$nomor_surat      = mysqli_real_escape_string($koneksi, $_POST['nomor_surat'] ?? '');
$nama_kegiatan    = mysqli_real_escape_string($koneksi, $_POST['nama_kegiatan']);
$tempat_kegiatan  = mysqli_real_escape_string($koneksi, $_POST['tempat_kegiatan']);
$tanggal_kegiatan = mysqli_real_escape_string($koneksi, $_POST['tanggal_kegiatan']);
$proposal         = isset($_POST['file_proposal_terupload']) ? mysqli_real_escape_string($koneksi, $_POST['file_proposal_terupload']) : '';

$tanggal_pengajuan = date("Y-m-d H:i:s");

// ==========================================
// JIKA MODE EDIT / REVISI
// ==========================================
if ($id_surat_edit > 0) {
    $query_utama = "
        UPDATE surat_pengajuan 
        SET tanggal_pengajuan = '$tanggal_pengajuan', 
            status_akhir = 'Menunggu Persetujuan Pembina', 
            posisi_sekarang = 'Pembina',
            urutan_sekarang = '3',
            id_pembina = '$id_pembina'
        WHERE id_surat = '$id_surat_edit' AND id_ormawa = '$id_ormawa'
    ";

    if (mysqli_query($koneksi, $query_utama)) {
        $cek_detail = mysqli_query($koneksi, "SELECT id_surat FROM detail_pengajuan_dana WHERE id_surat = '$id_surat_edit'");
        if (mysqli_num_rows($cek_detail) > 0) {
            $query_detail = "
                UPDATE detail_pengajuan_dana 
                SET nomor_surat = '$nomor_surat',
                    nama_kegiatan = '$nama_kegiatan',
                    tanggal_kegiatan = '$tanggal_kegiatan', 
                    tempat_kegiatan = '$tempat_kegiatan'
                    " . (!empty($proposal) ? ", proposal = '$proposal'" : "") . "
                WHERE id_surat = '$id_surat_edit'
            ";
            mysqli_query($koneksi, $query_detail);
        } else {
            mysqli_query($koneksi, "
                INSERT INTO detail_pengajuan_dana (id_surat, nomor_surat, nama_kegiatan, tanggal_kegiatan, tempat_kegiatan, proposal)
                VALUES ('$id_surat_edit', '$nomor_surat', '$nama_kegiatan', '$tanggal_kegiatan', '$tempat_kegiatan', '$proposal')
            ");
        }

        mysqli_query($koneksi, "
            INSERT INTO riwayat_disposisi (id_surat, pengirim, penerima, waktu_disposisi, intruksi_catatan, status_tindakan)
            VALUES ('$id_surat_edit', 'ORMAWA', 'PEMBINA', NOW(), 'Revisi pengajuan dana kegiatan dikirim ulang.', 'MENUNGGU')
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Revisi permohonan pengajuan dana berhasil dikirim ulang!';
        header("Location: ormawa_lacak.php?id=$id_surat_edit");
        exit;
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = 'Gagal memperbarui permohonan pengajuan dana.';
        header("Location: ormawa_riwayat.php");
        exit;
    }
}
// ==========================================
// JIKA PENGAJUAN BARU
// ==========================================
else {
    $status_awal = "Menunggu Persetujuan Pembina";
    $posisi      = "Pembina";
    $urutan      = 3;

    $karakter = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $kode_pelacakan = 'TRK-' . substr(str_shuffle($karakter), 0, 6);

    $query = mysqli_query($koneksi, "
        INSERT INTO surat_pengajuan
        (id_jenis, id_ormawa, id_pembina, nomor_surat, file_surat_final, dokumen_hash, tanggal_pengajuan, status_akhir, posisi_sekarang, urutan_sekarang, kode_pelacakan)
        VALUES
        ('$id_jenis', '$id_ormawa', '$id_pembina', '$nomor_surat', '', '', '$tanggal_pengajuan', '$status_awal', '$posisi', '$urutan', '$kode_pelacakan')
    ");

    if (!$query) {
        die("Gagal menyimpan surat pengajuan : " . mysqli_error($koneksi));
    }

    $id_surat = mysqli_insert_id($koneksi);
    $dokumen_hash = hash('sha256', $id_surat . $id_ormawa . time());
    mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

    $detail = mysqli_query($koneksi, "
        INSERT INTO detail_pengajuan_dana
        (id_surat, nomor_surat, nama_kegiatan, tanggal_kegiatan, tempat_kegiatan, proposal)
        VALUES
        ('$id_surat', '$nomor_surat', '$nama_kegiatan', '$tanggal_kegiatan', '$tempat_kegiatan', '$proposal')
    ");

    if (!$detail) {
        die("Gagal menyimpan detail pengajuan dana : " . mysqli_error($koneksi));
    }

    mysqli_query($koneksi, "
        INSERT INTO riwayat_disposisi (id_surat, pengirim, penerima, waktu_disposisi, intruksi_catatan, status_tindakan)
        VALUES ('$id_surat', 'ORMAWA', 'PEMBINA', NOW(), 'Pengajuan dana menunggu persetujuan pembina.', 'MENUNGGU')
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan']  = 'Surat permohonan pengajuan dana kegiatan berhasil dikirim.';
    header("Location: ormawa_riwayat.php?id=$id_surat");
    exit;
}
