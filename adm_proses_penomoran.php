<?php
session_start();
include "koneksi.php";

if (isset($_GET['id']) && isset($_GET['metode'])) {
    $id_surat = mysqli_real_escape_string($koneksi, $_GET['id']);
    $metode   = $_GET['metode'];

    $query_cek = mysqli_query($koneksi, "
        SELECT sp.id_surat, js.nama_surat 
        FROM surat_pengajuan sp
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_surat = '$id_surat'
    ");

    $data = mysqli_fetch_assoc($query_cek);

    if (!$data) {
        echo "<script>alert('Data tidak ditemukan!'); window.location='adm_riwayat_review.php';</script>";
        exit;
    }

    $namaSurat = strtolower($data['nama_surat']);
    $nomor_final = "";

    // PROSES PENENTUAN NOMOR SURAT
    if ($metode == 'manual') {
        // Manual --> tangkap nomor dari inputan URL yang dikirim SweetAlert
        $nomor_input = isset($_GET['nomor']) ? mysqli_real_escape_string($koneksi, $_GET['nomor']) : '';
        $nomor_final = $nomor_input;
    } else if ($metode == 'auto') {
        // Otomatis --> sistem yang akan membuatkan format.
        $bulan_romawi = array("", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
        $bulan = $bulan_romawi[date('n')];
        $tahun = date('Y');

        $id_format = str_pad($id_surat, 3, "0", STR_PAD_LEFT);

        $nomor_final = "B-" . $id_format . "/Un.16/FST/PP.009/" . $bulan . "/" . $tahun;
    }

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan 
        SET nomor_surat = '$nomor_final' 
        WHERE id_surat = '$id_surat'
    ");

    if (strpos($namaSurat, 'aktif') !== false) {
        header("Location: generate_sk_aktif_resmi.php?id=$id_surat&view=true&asal=review");
        exit;
    } elseif (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
        header("Location: generate_surat_magang_resmi.php?id=$id_surat&view=true&asal=review");
        exit;
    } else {
        header("Location: generate_surat_riset_resmi.php?id=$id_surat&view=true&asal=review");
        exit;
    }
} else {
    header("Location: adm_riwayat_review.php");
    exit;
}
