<?php
session_start();
include "koneksi.php";

// 1. Pengecekan Sesi Login
if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];

// 2. Menangkap dan Mengamankan Data Input (Mencegah SQL Injection)
$id_jenis               = $_POST['id_jenis'];
$semester               = mysqli_real_escape_string($koneksi, $_POST['semester']);
$tanggal_mulai_magang   = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai_magang']);
$tanggal_selesai_magang = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai_magang']);
$lokasi_magang          = mysqli_real_escape_string($koneksi, $_POST['lokasi_magang']);
$surat_ditujukan        = mysqli_real_escape_string($koneksi, $_POST['surat_ditujukan']);

// 3. Menyiapkan Data Pendukung
$tanggal_pengajuan = date('Y-m-d H:i:s');
$dokumen_hash      = hash('sha256', $id_mhs . $id_jenis . time());

/* =======================================================================
   TAHAP 1: INSERT DATA UMUM KE TABEL MASTER (surat_pengajuan)
   ======================================================================= */
$query_utama = "
    INSERT INTO surat_pengajuan 
    (id_mhs, id_jenis, tanggal_pengajuan, status_akhir, status_pimpinan, dokumen_hash)
    VALUES 
    ('$id_mhs', '$id_jenis', '$tanggal_pengajuan', 'Menunggu Admin', 'Menunggu', '$dokumen_hash')
";

if (mysqli_query($koneksi, $query_utama)) {

    // Ambil ID Surat yang baru saja digenerate oleh tabel utama
    $id_surat = mysqli_insert_id($koneksi);

    /* =======================================================================
       TAHAP 2: INSERT DATA SPESIFIK KE TABEL DETAIL (detail_surat_magang)
       ======================================================================= */
    $query_detail = "
        INSERT INTO detail_surat_magang 
        (id_surat, semester, tanggal_mulai_magang, tanggal_selesai_magang, lokasi_magang, surat_ditujukan)
        VALUES 
        ('$id_surat', '$semester', '$tanggal_mulai_magang', '$tanggal_selesai_magang', '$lokasi_magang', '$surat_ditujukan')
    ";

    if (mysqli_query($koneksi, $query_detail)) {

        // TAHAP 3: Proses pengisian lampiran (jika ada) bisa diletakkan di sini
        // ... (kode lampiran) ...

        // Simpan session semester magang untuk keperluan preview
        $_SESSION['semester_magang_' . $id_surat] = $semester;

        // Tampilkan pesan sukses dan arahkan ke halaman preview magang
        echo "<script>
            alert('Surat magang berhasil dibuat dan dikirim ke admin.');
            window.location='preview_magang.php?id=$id_surat';
        </script>";
        exit;
    } else {
        // Jika gagal insert ke tabel detail_surat_magang
        echo "<script>alert('Gagal menyimpan detail informasi magang.'); history.back();</script>";
        exit;
    }
} else {
    // Jika gagal insert ke tabel surat_pengajuan
    echo "<script>alert('Sistem gagal memproses pengajuan surat.'); history.back();</script>";
    exit;
}
