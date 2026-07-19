<?php
session_start();
include "koneksi.php";

$id_dosen = $_SESSION['id_dosen'];
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='login.php';</script>";
    exit;
}

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Dosen';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Dosen';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

// PERBAIKAN QUERY RIWAYAT VERIFIKASI (MENDUKUNG MAHASISWA & ORMAWA)
$query = mysqli_query($koneksi, "
    SELECT 
        sp.*,
        m.nama_mhs,
        m.npm,
        o.nama_ormawa,      -- Tambahan: Ambil nama ormawa
        p.nama_prodi,
        js.nama_surat,
        dsr.id_pb1,
        dsr.id_pb2,
        dsr.status_pb1,
        dsr.status_pb2,
        dak.id_pa,          
        dak.status_pa       
    FROM surat_pengajuan sp
    -- DIUBAH MENJADI LEFT JOIN agar data surat Ormawa tidak terbuang/hilang
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa 
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat 
    WHERE
    (
        -- Skenario A: Dosen adalah PB2 dan sudah tidak 'Menunggu'
        dsr.id_pb2 = '$id_dosen'
        AND dsr.status_pb2 != 'Menunggu'
    )
    OR
    (
        -- Skenario B: Dosen adalah PB1 dan sudah tidak 'Menunggu'
        dsr.id_pb1 = '$id_dosen'
        AND dsr.status_pb1 != 'Menunggu'
    )
    OR
    (
        -- Skenario C: Dosen adalah Pembimbing Academic dan sudah tidak 'Menunggu'
        dak.id_pa = '$id_dosen'
        AND dak.status_pa != 'Menunggu'
    )
    OR
    (
        -- BARU!! Skenario D: Dosen adalah Pembina Ormawa dan posisi surat sudah bergeser (Sudah diverifikasi)
        sp.id_pembina = '$id_dosen'
        AND sp.posisi_sekarang != 'Pembina'
    )
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Verifikasi</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="dosen-page">
    <?php if (isset($_SESSION['pesan'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['status']; ?>',
                title: '<?= ($_SESSION['status'] == "success") ? "Berhasil!" : "Gagal!"; ?>',
                text: <?= json_encode($_SESSION['pesan']); ?>,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>
        <?php
        unset($_SESSION['pesan']);
        unset($_SESSION['status']);
        ?>
    <?php endif; ?>

    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="dosen_beranda.php">Beranda</a>
            <a href="dosen_permohonan.php">Verifikasi Permohonan</a>
            <a href="dosen_beranda.php#riwayat">Informasi</a>
            <a href="dosen_riwayat.php">Riwayat Verifikasi</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial); ?></span>
                </button>

                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap); ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin); ?> - <?= htmlspecialchars($role); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section id="daftar-surat" class="daftar-surat">
        <div class="riwayat-permohonan-header">
            <h2>Riwayat Verifikasi</h2>
            <p>Daftar permohonan surat yang sudah diverifikasi.</p>
        </div>

        <div class="table-wrapper" id="template-surat">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchSurat" class="search-input" placeholder="Cari Permohonan surat...">
            </div>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Pengirim (Mhs/Ormawa)</th>
                        <th>NPM</th>
                        <th>Jenis Surat</th>
                        <th>Status Anda</th>
                        <th>Status Akhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                        <?php $no = 1;
                        while ($row = mysqli_fetch_assoc($query)) { ?>

                            <?php
                            // Penentuan Status Verifikasi Sisi Dosen secara dinamis
                            if (isset($row['id_pb1']) && $row['id_pb1'] == $id_dosen) {
                                $statusAnda = $row['status_pb1'];
                            } elseif (isset($row['id_pb2']) && $row['id_pb2'] == $id_dosen) {
                                $statusAnda = $row['status_pb2'];
                            } elseif (isset($row['id_pa']) && $row['id_pa'] == $id_dosen) {
                                $statusAnda = $row['status_pa'];
                            } elseif (isset($row['id_pembina']) && $row['id_pembina'] == $id_dosen) {
                                // Mengambil potongan kata dari status akhir (misal: Disetujui/Ditolak)
                                $statusAnda = (strpos($row['status_akhir'], 'Ditolak') !== false) ? 'Ditolak' : 'Disetujui';
                            } else {
                                $statusAnda = '-';
                            }
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <!-- Mengubah penampilan Kolom Pengirim jika data adalah Ormawa -->
                                <td><?= !empty($row['nama_mhs']) ? htmlspecialchars($row['nama_mhs']) : '<b>(ORMAWA)</b> ' . htmlspecialchars($row['nama_ormawa']); ?></td>
                                <td><?= !empty($row['npm']) ? htmlspecialchars($row['npm']) : '-'; ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td><?= htmlspecialchars($statusAnda); ?></td>
                                <td><?= htmlspecialchars($row['status_akhir']); ?></td>
                                <td>
                                    <a href="dosen_detail_permohonan.php?id=<?= $row['id_surat']; ?>&asal=riwayat" class="btn btn-detail">Detail</a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" class="empty-table-cell">
                                <i class="fa-solid fa-folder-open"></i>
                                <p>Belum ada riwayat verifikasi.</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const userBtn = document.getElementById("user-btn");
            const dropdown = document.getElementById("user-dropdown");

            userBtn.addEventListener("click", function(e) {
                dropdown.classList.toggle("show");
                e.stopPropagation();
            });

            window.addEventListener("click", function(e) {
                if (!e.target.closest(".user-menu-container")) {
                    dropdown.classList.remove("show");
                }
            });
        });
    </script>
</body>

</html>