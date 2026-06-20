<?php
session_start();
include "koneksi.php";

$id_mhs = $_SESSION['id_mhs'];

$query_riwayat = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat,
        sp.nomor_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir,
        sp.status_dospem1,
        sp.status_dospem2,
        sp.status_pimpinan,
        sp.file_surat_final,
        sp.dokumen_hash,
        js.nama_surat
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    WHERE sp.id_mhs = '$id_mhs'
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php
    if (isset($_SESSION['pesan'])) {
        $alertType = ($_SESSION['status'] == "success") ? "success" : "error";
        echo "<script>
        Swal.fire({
            icon: '$alertType',
            title: '" . ($_SESSION['status'] == "success" ? "Berhasil!" : "Gagal!") . "',
            text: '" . $_SESSION['pesan'] . "',
            timer: 2000,
            showConfirmButton: false
        });
    </script>";
        unset($_SESSION['pesan']);
        unset($_SESSION['status']);
    }
    ?>

    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/AKADEMIK FST2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="mhs_dashboard.php">Beranda</a>
            <a href="mhs_dashboard.php#services">Layanan Akademik</a>
            <a href="mhs_dashboard.php#riwayat">Informasi</a>
            <a href="mhs_riwayat.php">Riwayat Permohonan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <?php
                $namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
                $idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
                $role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

                $inisial = '';
                $namaParts = explode(' ', $namaLengkap);
                if (!empty($namaParts)) {
                    $inisial = strtoupper(substr($namaParts[0], 0, 1));
                }
                ?>
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>

                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap) ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section id="riwayat-permohonan" class="riwayat-permohonan">
        <div class="riwayat-permohonan-header">
            <h2>Riwayat Permohonan</h2>
        </div>

        <div class="table-responsive">
            <table class="table-riwayat">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Jenis Surat</th>
                        <th>Progres</th>
                        <th>Status Pengajuan</th>
                        <th>Verifikasi</th>
                        <th>File Final</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) { ?>
                        <?php $no = 1;
                        while ($row = mysqli_fetch_assoc($query_riwayat)) { ?>
                            <?php
                            $tanggal = date('d-m-Y H:i', strtotime($row['tanggal_pengajuan']));
                            $status = $row['status_akhir'];

                            if ($status == 'Selesai') {
                                $badge_class = 'status-selesai';
                            } elseif (strpos($status, 'Ditolak') !== false) {
                                $badge_class = 'status-ditolak';
                            } else {
                                $badge_class = 'status-proses';
                            }
                            ?>

                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $tanggal; ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>

                                <td>
                                    <div style="font-size:0.85rem; line-height:1.8;">
                                        <div>Dospem 1: <?= htmlspecialchars($row['status_dospem1']); ?></div>
                                        <div>Dospem 2: <?= htmlspecialchars($row['status_dospem2']); ?></div>
                                        <div>Pimpinan: <?= htmlspecialchars($row['status_pimpinan']); ?></div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge-status <?= $badge_class; ?>">
                                        <?= htmlspecialchars($status); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($row['dokumen_hash'])) { ?>
                                        <a href="verifikasi_surat.php?hash=<?= htmlspecialchars($row['dokumen_hash']); ?>"
                                            target="_blank"
                                            class="btn-aksi">
                                            Verifikasi
                                        </a>
                                    <?php } else { ?>
                                        <span style="color:#94a3b8;">-</span>
                                    <?php } ?>
                                </td>

                                <td>
                                    <?php if (!empty($row['file_surat_final'])) { ?>
                                        <a href="uploads/surat_final/<?= htmlspecialchars($row['file_surat_final']); ?>"
                                            target="_blank"
                                            class="btn-aksi"
                                            style="background-color:#10b981; color:white; border-color:#10b981;">
                                            Unduh
                                        </a>
                                    <?php } else { ?>
                                        <span style="color:#94a3b8; font-style:italic; font-size:0.85rem;">
                                            Belum Tersedia
                                        </span>
                                    <?php } ?>
                                </td>

                                <td>
                                    <a href="detail_pengajuan.php?id=<?= $row['id_surat']; ?>" class="btn-aksi">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                Belum ada riwayat permohonan surat.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userBtn = document.getElementById('user-btn');
            const dropdown = document.getElementById('user-dropdown');

            userBtn.addEventListener('click', function(event) {
                dropdown.classList.toggle('show');
                event.stopPropagation();
            });

            window.addEventListener('click', function(event) {
                if (!event.target.matches('#user-btn') && !event.target.closest('#user-btn')) {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                }
            });
        });
    </script>

</body>

</html>