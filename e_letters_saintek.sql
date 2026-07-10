-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Jul 2026 pada 20.02
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
-- Struktur dari tabel `alur_surat`
--

CREATE TABLE `alur_surat` (
  `id_alur` int(11) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `urutan` int(11) NOT NULL,
  `role_tujuan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `alur_surat`
--

INSERT INTO `alur_surat` (`id_alur`, `id_jenis`, `urutan`, `role_tujuan`) VALUES
(1, 1, 1, 'dospem1'),
(2, 1, 2, 'dospem2'),
(3, 1, 3, 'admin'),
(4, 1, 4, 'wadek1'),
(5, 1, 5, 'dekan'),
(6, 4, 1, 'admin'),
(7, 4, 2, 'dekan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_aktif_kuliah`
--

CREATE TABLE `detail_aktif_kuliah` (
  `id_surat` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `lama_cuti` varchar(255) NOT NULL,
  `ta_mulai_cuti` varchar(255) NOT NULL,
  `ta_selesai_cuti` varchar(255) NOT NULL,
  `semester_akademik` enum('Ganjil','Genap','','') NOT NULL,
  `tahun_akademik` varchar(20) NOT NULL,
  `id_pa` int(11) NOT NULL,
  `status_pa` enum('Menunggu','Disetujui','Ditolak','') NOT NULL DEFAULT 'Menunggu',
  `ttd_pa` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_surat_magang`
--

CREATE TABLE `detail_surat_magang` (
  `id_surat` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `tanggal_mulai_magang` date NOT NULL,
  `tanggal_selesai_magang` date NOT NULL,
  `lokasi_magang` varchar(255) NOT NULL,
  `surat_ditujukan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_surat_riset`
--

CREATE TABLE `detail_surat_riset` (
  `id_surat` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `judul_skripsi` text NOT NULL,
  `lokasi_penelitian` varchar(255) NOT NULL,
  `surat_ditujukan` varchar(255) NOT NULL,
  `id_pb1` int(11) NOT NULL,
  `id_pb2` int(11) NOT NULL,
  `status_pb1` enum('Menunggu','Disetujui','Ditolak','') NOT NULL DEFAULT 'Menunggu',
  `status_pb2` enum('Menunggu','Disetujui','Ditolak','') NOT NULL DEFAULT 'Menunggu',
  `ttd_pb1` varchar(255) DEFAULT NULL,
  `ttd_pb2` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_surat_riset`
--

INSERT INTO `detail_surat_riset` (`id_surat`, `semester`, `judul_skripsi`, `lokasi_penelitian`, `surat_ditujukan`, `id_pb1`, `id_pb2`, `status_pb1`, `status_pb2`, `ttd_pb1`, `ttd_pb2`) VALUES
(81, 8, 'Perancangan Sistem Pengelolaan Surat Berbasis Web dengan Fitur Tracking Disposisi Menggunakan Metode Waterfall (Studi Kasus Fakultas Sains dan Teknologi UIN Raden Intan Lampung)', 'Fakultas Sains dan Teknologi', 'Kasubbag TU', 7, 8, 'Disetujui', 'Disetujui', 'c92c2320d643034a82c3b9b00faaeb2b8776b978ae67e97b6b48d88b474dbbbe', 'd903d5b39f8e91881d99f87fc2e24b35d5c53495ecb133d5dd39cd7d7b161856'),
(82, 8, 'test2', 'test2', 'test2', 7, 8, 'Menunggu', 'Disetujui', NULL, 'c13502dbc2e8eef6c9799a32664d90993175886b275c38e6be27e291afc5c235'),
(83, 6, 'test3', 'test3', 'test3', 7, 8, 'Disetujui', 'Disetujui', '000187b51be294cab4abad5d1c60866695ad30c0321f463f0061564c43de3473', '40ff6a0982ad8d08c5813d9141ba52f56841c51b845244b1da271ba4865e9685'),
(84, 8, 'test4', 'test4', 'test4', 7, 8, 'Menunggu', 'Menunggu', NULL, NULL);

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
(1, '197611302005012006', 'Dr. Sovia Mas Ayu, MA', '$2y$10$M3qq3Jdym/1HqHCoU550UOfh4jhoCtDcSq1byIuOZ.K8fi6nBDhf2', 'Dekan', 'Pimpinan', '197611302005012006'),
(2, '198704042015032005', 'Rosida Rakhmawati. M, S.Pd., M.Pd.,Ph.D', '$2y$10$xPvojUjErriyhF6Dwq.9ruMCXQzGGdD3feB2kC61Wqe4Poa5LThC.', 'Wakil Dekan 1', 'Pimpinan', '198704042015032005'),
(3, '197504122003121002', 'Dr. Mujib, M.Pd.', '$2y$10$uvT7UJV23NURobUOXGntme5aDATBqX/DrarwYdiNNOOZGspsEVK6O', 'Wakil Dekan 2', 'Pimpinan', '197504122003121002'),
(4, '198511052014121001', 'Dr. Mairizal Salehudin Siatan, S.E., M.M.', '$2y$10$exnV1LrMoYRsN7npGbY08O7qOLCI1K5JeHnzK4VftSimX3L2YglEm', 'Plt. Kasubbag Tata Usaha ', 'Pimpinan', '198511052014121001'),
(5, '19720801200604102', 'Indra Gunawan, M.T', '$2y$10$3kz2X0Lt/JLzPoCK3C3kfOaB1c8mf8WLHnp8wSDnZApwM5Slpkvpi', 'Dosen', 'Dosen', '19720801200604102'),
(7, '19910817201801101', 'Wawan Gunawan, M.Kom', '$2y$10$tyCcysUvLt.Lc/5iN5MT8e7S.g9XIz8UHQbfeT2Qj/gWjuQdJmuyu', 'Dosen', 'Dosen', '19910817201801101'),
(8, '199003072023211020', 'Mezan el-Khaeri Kesuma, S.Kom., M.T.I', '$2y$10$oPvSRhE0hYCHxoPObQd6/ONDiTzIMfr34Y6dFIl4SO32vxOYULPH.', 'Dosen', 'Dosen', '19910817201801101');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_surat`
--

CREATE TABLE `jenis_surat` (
  `id_jenis` int(11) NOT NULL,
  `nama_surat` varchar(100) NOT NULL,
  `kode_surat` varchar(20) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `file_template` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `isi_template` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jenis_surat`
--

INSERT INTO `jenis_surat` (`id_jenis`, `nama_surat`, `kode_surat`, `deskripsi`, `file_template`, `status`, `isi_template`) VALUES
(1, 'Permohonan Riset', 'B-/Un.16/DST/PP.009/', 'Surat izin resmi untuk penelitian atau pengambilan data tugas akhir/skripsi di suatu instansi.', '1781886236_Surat-Keterangan-Riset (1).docx', 1, 'SURAT PERMOHONAN RISET\r\n\r\nKepada Yth.\r\nPimpinan Instansi\r\n\r\nDengan hormat,\r\n\r\nMahasiswa berikut:\r\n\r\nNama : {nama_mhs}\r\nNPM : {npm}\r\nProgram Studi : {prodi}\r\n\r\nMengajukan permohonan Pra-Riset untuk:\r\n\r\n{keperluan}\r\n\r\nDemikian surat ini dibuat untuk digunakan sebagaimana mestinya.\r\n\r\nBandar Lampung, {tanggal}\r\n\r\nFakultas Sains dan Teknologi\r\nUIN Raden Intan Lampung'),
(2, 'Permohonan Pra-Riset', 'B-/Un.16/DST/PP.009/', 'Surat pengantar untuk observasi atau studi pendahuluan sebelum riset utama.', '1782233294_Surat-Permohonan-Pra-riset-Mahasiswa.docx', 1, NULL),
(3, 'SK Aktif Kuliah Kembali', 'B-/UN.16/WD.I/PP.009', 'Surat untuk mengaktifkan kembali status mahasiswa setelah selesai masa cuti.', '1782232687_blanko-aktif-kuliah-kembali-setelah-cuti-1.docx', 1, NULL),
(4, 'Izin Magang', 'B-/Un.16/DST/PP.009/', 'Surat pengantar resmi dari kampus untuk pelaksanaan magang atau kerja praktik.', '1782232712_Surat-Izin-Magang.docx', 1, NULL),
(5, 'SK Masih Kuliah', 'B-/Un.16/KT/PP.009//', 'Surat bukti mahasiswa aktif untuk keperluan tunjangan orang tua, BPJS, beasiswa, dll.', '1782233748_Surat-Keterangan-Masih-Kuliah-Tunjangan-Orang-Tua.docx', 1, NULL),
(6, 'Permohonan Cuti Akademik', 'B-/Un.16/DST/PP.009/', 'Surat pengajuan izin berhenti kuliah sementara secara resmi.', '1782234652_Form-Suket-Mohon-Cuti-Mhs-2.docx', 1, NULL),
(7, 'Pengajuan Beasiswa', 'B-/Un.16/DST/PP.009/', 'Surat rekomendasi internal untuk program beasiswa.', '1782234635_Surat-Keterangan-Pengajuan-Beasiswa.docx', 1, NULL),
(10, 'Permohonan Bebas UKT Sementara', 'B-/Un.16/DST/PP.009/', 'Formulir pengajuan pembebasan biaya Uang Kuliah Tunggal (UKT) semetara sebagai syarat munaqosyah.', '1783187958_Permohonan-Nota-Dinas-Bebas-UKT.docx', 1, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `lampiran_pengajuan`
--

CREATE TABLE `lampiran_pengajuan` (
  `id_lampiran` int(11) NOT NULL,
  `id_surat` int(11) NOT NULL,
  `id_syarat` int(11) NOT NULL,
  `file_upload` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lampiran_pengajuan`
--

INSERT INTO `lampiran_pengajuan` (`id_lampiran`, `id_surat`, `id_syarat`, `file_upload`) VALUES
(56, 81, 1, 'proposal_penelitian_1783589335_5555.pdf'),
(57, 81, 3, 'khs_1783589335_6934.jpeg'),
(58, 81, 2, 'bukti_ukt_1783589335_4463.jpeg'),
(59, 82, 1, 'proposal_penelitian_1783658871_1543.pdf'),
(60, 82, 3, 'khs_1783658871_1622.pdf'),
(61, 82, 2, 'bukti_ukt_1783658871_6419.pdf'),
(62, 83, 1, 'proposal_penelitian_1783660054_3883.pdf'),
(63, 83, 3, 'khs_1783660054_6936.pdf'),
(64, 83, 2, 'bukti_ukt_1783660054_9721.pdf'),
(65, 84, 1, 'proposal_penelitian_1783704104_9328.pdf'),
(66, 84, 3, 'khs_1783704104_1364.jpeg'),
(67, 84, 2, 'bukti_ukt_1783704104_8573.jpeg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mhs` int(11) NOT NULL,
  `npm` varchar(15) NOT NULL,
  `nama_mhs` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `id_prodi` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `id_pa` int(11) DEFAULT NULL,
  `id_pb1` int(11) DEFAULT NULL,
  `id_pb2` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mhs`, `npm`, `nama_mhs`, `password`, `email`, `id_prodi`, `status`, `id_pa`, `id_pb1`, `id_pb2`) VALUES
(1, '2271020021', 'Ghania Ridha Khairiah', '$2y$10$zqieeKYFWWVtZVDD5EvmduoJER1By8Y9i2pjpQV2aZjhW2D97PPvi', 'itsghaniakh@gmail.com', 1, 1, 5, 7, 8),
(2, '2271020028', 'Imam Rizki', '$2y$10$BaOu7p3cy5YQBA3CPd99uOulEZRanR305QIPCERWnEWYtx72rDUN.', 'imamrizki@gmail.com', 1, 1, 5, 7, 8),
(4, '2271020013', 'Dewi Nadila', '$2y$10$VeCczYcN2/9U6AKme2zknu9Gwi350SFUlspz3v3xa40M0f/oIL37O', 'dewinadila@gmail.com', 1, 1, NULL, NULL, NULL),
(6, '2271020015', 'Dinda Salsabilla', '$2y$10$aLwP//YKjVYDdabdvCj4TOXmD/Caq0Vwj4XlNPGjuCkFoiCmzqi3a', 'dindasalsa@gmail.com', 1, 1, NULL, 7, 8),
(15, '2271020023', 'Khalea Welwichia ', '$2y$10$35XYLdRy99hb8Cm8V9u.g.nn/6jdMKOqis1g/3g3aCeFHLPNmrRSC', 'khal123@gmail.com', 2, 0, NULL, NULL, NULL),
(16, '2271020022', 'Ghazi Abid Al azam', '$2y$10$ZJAud/qYEiGZrCmyS1Xs/.kvc6TzMYFsRXYsJ63Rr7T7a45QOJwtu', 'ghaziabid@gmail.com', 1, 1, NULL, NULL, NULL),
(17, '2271020025', 'Helen Rizka Aulia', '$2y$10$8R/eyEx0u0c1/0sCZiRyjOGTDU.igs2Yq2j7s/7FrF78ajtqbcHAi', 'helen@gmail.com', 1, 0, 5, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_syarat`
--

CREATE TABLE `master_syarat` (
  `id_syarat` int(11) NOT NULL,
  `nama_syarat` varchar(100) NOT NULL,
  `format_file` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `master_syarat`
--

INSERT INTO `master_syarat` (`id_syarat`, `nama_syarat`, `format_file`) VALUES
(1, 'Proposal Penelitian', 'pdf'),
(2, 'Bukti Pembayaran UKT Terakhir', 'pdf,jpg,png'),
(3, 'KHS Semester Lalu', 'pdf,jpg,png'),
(4, 'Kartu Tanda Mahasiswa (KTM)', 'pdf,jpg,png'),
(5, 'SK Cuti', 'pdf,jpg,png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ormawa`
--

CREATE TABLE `ormawa` (
  `id_ormawa` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_ormawa` varchar(100) NOT NULL,
  `nama_ketua` varchar(100) NOT NULL,
  `npm_ketua` varchar(20) NOT NULL,
  `nama_sekretaris` varchar(100) NOT NULL,
  `npm_sekretaris` varchar(20) NOT NULL,
  `id_pembina` int(11) NOT NULL,
  `id_prodi` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ormawa`
--

INSERT INTO `ormawa` (`id_ormawa`, `username`, `password`, `nama_ormawa`, `nama_ketua`, `npm_ketua`, `nama_sekretaris`, `npm_sekretaris`, `id_pembina`, `id_prodi`) VALUES
(1, 'hima_si', '$2y$10$BXTtGDBr7GHuNwNzYJpbi..6mzf8ByUWMkttdlaKm/5tMhDFWZY7K', 'HIMASI', 'Imam Rizki', '2271020028', 'Ghania Ridha Khairiah', '2271020021', 1, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `prodi`
--

CREATE TABLE `prodi` (
  `id_prodi` int(11) NOT NULL,
  `nama_prodi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `prodi`
--

INSERT INTO `prodi` (`id_prodi`, `nama_prodi`) VALUES
(1, 'Sistem Informasi'),
(2, 'Biologi Murni'),
(3, 'Sains Data'),
(4, 'Kimia Murni');

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
  `dokumen_hash` varchar(100) NOT NULL,
  `status_pimpinan` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  `status_balasan` varchar(50) DEFAULT 'Tidak Ada',
  `ttd_pimpinan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `surat_pengajuan`
--

INSERT INTO `surat_pengajuan` (`id_surat`, `id_mhs`, `id_jenis`, `nomor_surat`, `tanggal_pengajuan`, `status_akhir`, `file_surat_final`, `dokumen_hash`, `status_pimpinan`, `status_balasan`, `ttd_pimpinan`) VALUES
(81, 1, 1, 'B-001/Un.16/DST/PP.009/2026', '2026-07-09 16:28:55', 'Selesai', 'surat_resmi_izin_riset81_1783691081.pdf', '1393baaf904fdb3709ae16995ff755b5f5e7747572a05d230b02bd72d3be56a8', 'Disetujui', 'Disetujui', '3e51c005d695d15f2e9e8cb43ee96f8481e7ffbee70b1cbe9254b453b8e7ff8f'),
(82, 1, 1, '', '2026-07-10 11:47:51', 'Menunggu Dospem 1', '', '4c95ef9cf80daa5b4bf919fca597a909bd32845de8b75789d0e11c09f57a783e', 'Menunggu', 'Tidak Ada', NULL),
(83, 1, 1, 'B-002/Un.16/DST/PP.009/2026', '2026-07-10 12:07:34', 'Selesai', 'surat_resmi_izin_riset83_1783702176.pdf', '3497818fb665e95515bf5ffd729d8c53f757d6a7459a8b7547c09128cb58bf44', 'Disetujui', 'Disetujui', '15c0af6c5e489c4df85bb837d903d4a4e7967b09461a8dd58156a1610c474d26'),
(84, 1, 1, '', '2026-07-11 00:21:44', 'Menunggu Dospem 2', '', '670518f0008b86aaaa123a04ebd1c5f7efb6708c51030c3a1cbf5e98afa999e7', 'Menunggu', 'Tidak Ada', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `syarat_jenis_surat`
--

CREATE TABLE `syarat_jenis_surat` (
  `id_pivot` int(11) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `id_syarat` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `syarat_jenis_surat`
--

INSERT INTO `syarat_jenis_surat` (`id_pivot`, `id_jenis`, `id_syarat`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 4, 4),
(5, 4, 2),
(6, 4, 3),
(7, 3, 5);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indeks untuk tabel `alur_surat`
--
ALTER TABLE `alur_surat`
  ADD PRIMARY KEY (`id_alur`);

--
-- Indeks untuk tabel `detail_aktif_kuliah`
--
ALTER TABLE `detail_aktif_kuliah`
  ADD PRIMARY KEY (`id_surat`),
  ADD KEY `fk_aktif_kuliah_id_pa` (`id_pa`);

--
-- Indeks untuk tabel `detail_surat_magang`
--
ALTER TABLE `detail_surat_magang`
  ADD PRIMARY KEY (`id_surat`);

--
-- Indeks untuk tabel `detail_surat_riset`
--
ALTER TABLE `detail_surat_riset`
  ADD PRIMARY KEY (`id_surat`),
  ADD KEY `fk_riset_id_pb1` (`id_pb1`),
  ADD KEY `fk_riset_id_pb2` (`id_pb2`);

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
  ADD PRIMARY KEY (`id_lampiran`),
  ADD KEY `fk_pengajuan_lampiran` (`id_surat`),
  ADD KEY `fk_syarat_lampiran` (`id_syarat`);

--
-- Indeks untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mhs`),
  ADD KEY `fk_prodi_mahasiswa` (`id_prodi`),
  ADD KEY `fk_id_dosen_mahasiswa` (`id_pa`),
  ADD KEY `fk_id_dosen_id_pb1` (`id_pb1`),
  ADD KEY `fk_id_dosen_id_pb2` (`id_pb2`);

--
-- Indeks untuk tabel `master_syarat`
--
ALTER TABLE `master_syarat`
  ADD PRIMARY KEY (`id_syarat`);

--
-- Indeks untuk tabel `ormawa`
--
ALTER TABLE `ormawa`
  ADD PRIMARY KEY (`id_ormawa`);

--
-- Indeks untuk tabel `prodi`
--
ALTER TABLE `prodi`
  ADD PRIMARY KEY (`id_prodi`);

--
-- Indeks untuk tabel `riwayat_disposisi`
--
ALTER TABLE `riwayat_disposisi`
  ADD PRIMARY KEY (`id_disposisi`);

--
-- Indeks untuk tabel `surat_pengajuan`
--
ALTER TABLE `surat_pengajuan`
  ADD PRIMARY KEY (`id_surat`),
  ADD KEY `fk_pengajuan_mhs` (`id_mhs`),
  ADD KEY `fk_pengajuan_jenis` (`id_jenis`);

--
-- Indeks untuk tabel `syarat_jenis_surat`
--
ALTER TABLE `syarat_jenis_surat`
  ADD PRIMARY KEY (`id_pivot`),
  ADD KEY `fk_jenis_syarat` (`id_jenis`),
  ADD KEY `fk_jenis_master_syarat` (`id_syarat`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `alur_surat`
--
ALTER TABLE `alur_surat`
  MODIFY `id_alur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id_dosen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `jenis_surat`
--
ALTER TABLE `jenis_surat`
  MODIFY `id_jenis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `lampiran_pengajuan`
--
ALTER TABLE `lampiran_pengajuan`
  MODIFY `id_lampiran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mhs` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `master_syarat`
--
ALTER TABLE `master_syarat`
  MODIFY `id_syarat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `ormawa`
--
ALTER TABLE `ormawa`
  MODIFY `id_ormawa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `prodi`
--
ALTER TABLE `prodi`
  MODIFY `id_prodi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `riwayat_disposisi`
--
ALTER TABLE `riwayat_disposisi`
  MODIFY `id_disposisi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `surat_pengajuan`
--
ALTER TABLE `surat_pengajuan`
  MODIFY `id_surat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT untuk tabel `syarat_jenis_surat`
--
ALTER TABLE `syarat_jenis_surat`
  MODIFY `id_pivot` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_aktif_kuliah`
--
ALTER TABLE `detail_aktif_kuliah`
  ADD CONSTRAINT `fk_aktif_kuliah_id_pa` FOREIGN KEY (`id_pa`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aktif_kuliah_id_surat` FOREIGN KEY (`id_surat`) REFERENCES `surat_pengajuan` (`id_surat`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `detail_surat_magang`
--
ALTER TABLE `detail_surat_magang`
  ADD CONSTRAINT `fk_magang_id_surat` FOREIGN KEY (`id_surat`) REFERENCES `surat_pengajuan` (`id_surat`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `detail_surat_riset`
--
ALTER TABLE `detail_surat_riset`
  ADD CONSTRAINT `fk_riset_id_pb1` FOREIGN KEY (`id_pb1`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riset_id_pb2` FOREIGN KEY (`id_pb2`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riset_id_surat` FOREIGN KEY (`id_surat`) REFERENCES `surat_pengajuan` (`id_surat`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `lampiran_pengajuan`
--
ALTER TABLE `lampiran_pengajuan`
  ADD CONSTRAINT `fk_pengajuan_lampiran` FOREIGN KEY (`id_surat`) REFERENCES `surat_pengajuan` (`id_surat`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_syarat_lampiran` FOREIGN KEY (`id_syarat`) REFERENCES `master_syarat` (`id_syarat`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_id_dosen_id_pb1` FOREIGN KEY (`id_pb1`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_id_dosen_id_pb2` FOREIGN KEY (`id_pb2`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_id_dosen_mahasiswa` FOREIGN KEY (`id_pa`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prodi_mahasiswa` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `surat_pengajuan`
--
ALTER TABLE `surat_pengajuan`
  ADD CONSTRAINT `fk_pengajuan_jenis` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_surat` (`id_jenis`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengajuan_mhs` FOREIGN KEY (`id_mhs`) REFERENCES `mahasiswa` (`id_mhs`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `syarat_jenis_surat`
--
ALTER TABLE `syarat_jenis_surat`
  ADD CONSTRAINT `fk_jenis_master_syarat` FOREIGN KEY (`id_syarat`) REFERENCES `master_syarat` (`id_syarat`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jenis_syarat` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_surat` (`id_jenis`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
