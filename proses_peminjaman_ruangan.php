<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_ormawa'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ormawa_form_peminjaman_ruangan.php");
    exit;
}

$id_surat_edit    = isset($_POST['id_surat']) ? (int)$_POST['id_surat'] : 0;
$id_ormawa        = $_POST['id_ormawa'];
$id_jenis         = $_POST['id_jenis'];
$id_pembina       = isset($_POST['id_pembina']) ? mysqli_real_escape_string($koneksi, $_POST['id_pembina']) : '';
$nomor_surat      = mysqli_real_escape_string($koneksi, $_POST['nomor_surat'] ?? '');
$nama_kegiatan    = mysqli_real_escape_string($koneksi, $_POST['nama_kegiatan']);
$ruangan          = mysqli_real_escape_string($koneksi, $_POST['ruangan']);
$tanggal_kegiatan = $_POST['tanggal_kegiatan'];
$jam_mulai        = $_POST['jam_mulai'];
$jam_selesai      = $_POST['jam_selesai'];
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
        // Cek apakah data detail sudah ada atau update
        $cek_detail = mysqli_query($koneksi, "SELECT id_surat FROM detail_peminjaman_ruangan WHERE id_surat = '$id_surat_edit'");
        if (mysqli_num_rows($cek_detail) > 0) {
            $query_detail = "
                UPDATE detail_peminjaman_ruangan 
                SET nomor_surat = '$nomor_surat',
                    nama_kegiatan = '$nama_kegiatan', 
                    tanggal_mulai = '$tanggal_kegiatan', 
                    tanggal_selesai = '$tanggal_kegiatan', 
                    jam_mulai = '$jam_mulai', 
                    jam_selesai = '$jam_selesai', 
                    ruangan_yang_diajukan = '$ruangan'
                    " . (!empty($proposal) ? ", proposal = '$proposal'" : "") . "
                WHERE id_surat = '$id_surat_edit'
            ";
            mysqli_query($koneksi, $query_detail);
        } else {
            // Jika belum ada, lakukan insert detail
            mysqli_query($koneksi, "
                INSERT INTO detail_peminjaman_ruangan (id_surat, nomor_surat, nama_kegiatan, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, ruangan_yang_diajukan, proposal)
                VALUES ('$id_surat_edit', '$nomor_surat', '$nama_kegiatan', '$tanggal_kegiatan', '$tanggal_kegiatan', '$jam_mulai', '$jam_selesai', '$ruangan', '$proposal')
            ");
        }

        // Catat ke riwayat disposisi
        mysqli_query($koneksi, "
            INSERT INTO riwayat_disposisi (id_surat, pengirim, penerima, waktu_disposisi, intruksi_catatan, status_tindakan)
            VALUES ('$id_surat_edit', 'ORMAWA', 'PEMBINA', NOW(), 'Revisi pengajuan peminjaman ruangan dikirim ulang.', 'MENUNGGU')
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Revisi pengajuan peminjaman ruangan berhasil dikirim ulang!';
        header("Location: ormawa_riwayat.php?id=$id_surat_edit");
        exit;
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = 'Gagal memperbarui permohonan peminjaman.';
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

    // Generate Kode Pelacakan Unik
    $karakter = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $kode_pelacakan = 'TRK-' . substr(str_shuffle($karakter), 0, 6);

    $query = mysqli_query($koneksi, "
        INSERT INTO surat_pengajuan
        (id_jenis, id_ormawa, id_pembina, nomor_surat, file_surat_final, dokumen_hash, tanggal_pengajuan, status_akhir, posisi_sekarang, urutan_sekarang, kode_pelacakan)
        VALUES
        ('$id_jenis', '$id_ormawa', '$id_pembina', '$nomor_surat', '', '', '$tanggal_pengajuan', '$status_awal', '$posisi', '3', '$kode_pelacakan')
    ");

    if (!$query) {
        die("Gagal menyimpan surat pengajuan : " . mysqli_error($koneksi));
    }

    $id_surat = mysqli_insert_id($koneksi);
    $dokumen_hash = hash('sha256', $id_surat . $id_ormawa . time());
    mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

    $detail = mysqli_query($koneksi, "
        INSERT INTO detail_peminjaman_ruangan
        (id_surat, nomor_surat, nama_kegiatan, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, ruangan_yang_diajukan, proposal)
        VALUES
        ('$id_surat', '$nomor_surat', '$nama_kegiatan', '$tanggal_kegiatan', '$tanggal_kegiatan', '$jam_mulai', '$jam_selesai', '$ruangan', '$proposal')
    ");

    if (!$detail) {
        mysqli_query($koneksi, "DELETE FROM surat_pengajuan WHERE id_surat = '$id_surat'");
        die("Gagal menyimpan detail peminjaman : " . mysqli_error($koneksi));
    }

    mysqli_query($koneksi, "
        INSERT INTO riwayat_disposisi (id_surat, pengirim, penerima, waktu_disposisi, intruksi_catatan, status_tindakan)
        VALUES ('$id_surat', 'ORMAWA', 'PEMBINA', NOW(), 'Pengajuan peminjaman ruangan menunggu persetujuan pembina.', 'MENUNGGU')
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan']  = 'Surat permohonan peminjaman ruangan berhasil dikirim.';
    header("Location: ormawa_riwayat.php?id=$id_surat");
    exit;
}
