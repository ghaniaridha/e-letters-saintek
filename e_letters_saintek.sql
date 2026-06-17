-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 17 Jun 2026 pada 09.01
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_letters_saintek`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `npa` varchar(25) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level_admin` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `npa`, `nama_admin`, `password`, `level_admin`) VALUES
(1, 'ADM001', 'Deni', '$2y$10$6/zZfjVFM.sgmkDyGerbjunSZXafMBo71CeBY6BjriGzFB3AdQL4.', 'Super Admin'),
(2, 'ADM002', 'Alan', '$2y$10$vHX4qJN2aUI9/Qw/RmYDoOvP3/TSKqMseV1/OqVlKU5ii/3bcDV/O', 'Admin Fakultas');

-- --------------------------------------------------------

--
-- Struktur dari tabel `dosen`
--

CREATE TABLE `dosen` (
  `id_dosen` int(11) NOT NULL,
  `nip` varchar(25) NOT NULL,
  `nama_dosen` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `jabatan` varchar(50) NOT NULL,
  `role_akses` varchar(20) NOT NULL,
  `kode_ttd_qr` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `dosen`
--

INSERT INTO `dosen` (`id_dosen`, `nip`, `nama_dosen`, `password`, `jabatan`, `role_akses`, `kode_ttd_qr`) VALUES
(1, '19910817201801101', 'Wawan Gunawan, M.Kom', '$2y$10$tyCcysUvLt.Lc/5iN5MT8e7S.g9XIz8UHQbfeT2Qj/gWjuQdJmuyu', 'Dosen', 'Dosen', '19910817201801101'),
(2, '199003072023211020', 'Mezan el-Khaeri Kesuma, S.Kom., M.T.I', '$2y$10$oPvSRhE0hYCHxoPObQd6/ONDiTzIMfr34Y6dFIl4SO32vxOYULPH.', 'Dosen', 'Dosen', '19910817201801101'),
(3, '197611302005012006', 'Dr. SOVIA MAS AYU, MA', '$2y$10$M3qq3Jdym/1HqHCoU550UOfh4jhoCtDcSq1byIuOZ.K8fi6nBDhf2', 'Dekan FST', 'Pimpinan', '197611302005012006');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_surat`
--

CREATE TABLE `jenis_surat` (
  `id_jenis` int(11) NOT NULL,
  `nama_surat` varchar(100) NOT NULL,
  `kode_surat` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jenis_surat`
--

INSERT INTO `jenis_surat` (`id_jenis`, `nama_surat`, `kode_surat`) VALUES
(1, 'Surat Pra-Riset', '/Un.16/DST/PP.009//'),
(2, 'Surat Riset', 'B-/Un.16/DST/PP.009/');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lampiran_pengajuan`
--

CREATE TABLE `lampiran_pengajuan` (
  `id_lampiran` int(11) NOT NULL,
  `id_surat` int(11) NOT NULL,
  `id_syarat` int(11) NOT NULL,
  `nama_file_upload` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mhs` int(11) NOT NULL,
  `npm` varchar(15) NOT NULL,
  `nama_mhs` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `prodi` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mhs`, `npm`, `nama_mhs`, `password`, `prodi`, `email`) VALUES
(1, '2271020021', 'Ghania Ridha Khairiah', '$2y$10$PDmkL0YA56vY.U37zxcoo.16AvjfrhvUrIhacvXYVUsTXGGZze746', 'Sistem Informasi', 'itsghaniakh@gmail.com');

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_disposisi`
--

CREATE TABLE `riwayat_disposisi` (
  `id_disposisi` int(11) NOT NULL,
  `id_surat` int(11) NOT NULL,
  `pengirim` varchar(50) NOT NULL,
  `penerima` varchar(50) NOT NULL,
  `waktu_disposisi` datetime NOT NULL,
  `intruksi_catatan` text NOT NULL,
  `status_tindakan` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `surat_pengajuan`
--

CREATE TABLE `surat_pengajuan` (
  `id_surat` int(11) NOT NULL,
  `id_mhs` int(11) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `nomor_surat` varchar(50) NOT NULL,
  `tanggal_pengajuan` datetime NOT NULL,
  `status_akhir` varchar(50) NOT NULL,
  `file_surat_final` varchar(255) NOT NULL,
  `dokumen_hash` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `syarat_surat`
--

CREATE TABLE `syarat_surat` (
  `id_syarat` int(11) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `nama_syarat` varchar(100) NOT NULL,
  `format_file` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indeks untuk tabel `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id_dosen`);

--
-- Indeks untuk tabel `jenis_surat`
--
ALTER TABLE `jenis_surat`
  ADD PRIMARY KEY (`id_jenis`);

--
-- Indeks untuk tabel `lampiran_pengajuan`
--
ALTER TABLE `lampiran_pengajuan`
  ADD PRIMARY KEY (`id_lampiran`);

--
-- Indeks untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mhs`);

--
-- Indeks untuk tabel `riwayat_disposisi`
--
ALTER TABLE `riwayat_disposisi`
  ADD PRIMARY KEY (`id_disposisi`);

--
-- Indeks untuk tabel `surat_pengajuan`
--
ALTER TABLE `surat_pengajuan`
  ADD PRIMARY KEY (`id_surat`);

--
-- Indeks untuk tabel `syarat_surat`
--
ALTER TABLE `syarat_surat`
  ADD PRIMARY KEY (`id_syarat`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id_dosen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `jenis_surat`
--
ALTER TABLE `jenis_surat`
  MODIFY `id_jenis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `lampiran_pengajuan`
--
ALTER TABLE `lampiran_pengajuan`
  MODIFY `id_lampiran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mhs` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `riwayat_disposisi`
--
ALTER TABLE `riwayat_disposisi`
  MODIFY `id_disposisi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `surat_pengajuan`
--
ALTER TABLE `surat_pengajuan`
  MODIFY `id_surat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `syarat_surat`
--
ALTER TABLE `syarat_surat`
  MODIFY `id_syarat` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
