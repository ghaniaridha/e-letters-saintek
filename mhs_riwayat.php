<?php
session_start();
include "koneksi.php";

$id_mhs = $_SESSION['id_mhs'];
$query_riwayat = mysqli_query($koneksi, "SELECT * FROM pengajuan_surat WHERE id_mhs = '$id_mhs' ORDER BY tgl_pengajuan DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css" media="screen" title="no title">
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
                        <span class="user-name"><?= ($namaLengkap) ?></span>
                        <span class="user-role"><?= $idLogin ?> - <?= $role ?></span>
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
                        <th>File Surat Permohonan</th>
                        <th>Status Pengajuan</th>
                        <th>File Final</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    // Cek apakah ada data pengajuan
                    if (mysqli_num_rows($query_riwayat) > 0) {
                        // Looping data dari database
                        while ($row = mysqli_fetch_assoc($query_riwayat)) {
                            $tanggal = date('d-m-Y H:i', strtotime($row['tgl_pengajuan']));

                            echo "<tr>";

                            // 1. Kolom No
                            echo "<td>" . $no++ . "</td>";

                            // 2. Kolom Tanggal Pengajuan
                            echo "<td>" . $tanggal . "</td>";

                            // 3. Kolom Jenis Surat 
                            // Catatan: Agar muncul nama teks (seperti 'Surat Keterangan Riset'), pastikan query SQL Anda sudah me-JOIN tabel pengajuan dengan tabel jenis_surat
                            $jenis_surat = isset($row['nama_jenis']) ? $row['nama_jenis'] : "Surat Riset";
                            echo "<td>" . htmlspecialchars($jenis_surat) . "</td>";

                            // 4. Kolom File Surat Permohonan (Hasil generate otomatis dengan barcode)
                            // Diarahkan ke folder tempat Anda menyimpan PDF surat tersebut
                            $file_permohonan = "hasil_surat/surat_" . $row['id_pengajuan'] . ".pdf";
                            echo "<td>";
                            if (file_exists($file_permohonan)) {
                                echo "<a href='$file_permohonan' target='_blank' class='btn-aksi' style='padding: 4px 10px; font-size: 0.8rem;'>Unduh</a>";
                            } else {
                                echo "<span style='color: #94a3b8;'>-</span>";
                            }
                            echo "</td>";

                            // 5. Kolom Status Pengajuan
                            $status = $row['status_pengajuan'];
                            $badge_class = ($status == 'SELESAI' || $status == 'Selesai') ? 'status-selesai' : 'status-proses';
                            echo "<td><span class='badge-status $badge_class'>" . $status . "</span></td>";

                            // 6. Kolom File Final (Mengambil data dari kolom file_surat_hasil jika sudah diupload admin)
                            echo "<td>";
                            if (!empty($row['file_surat_hasil'])) {
                                echo "<a href='files_final/" . $row['file_surat_hasil'] . "' target='_blank' class='btn-aksi' style='padding: 4px 10px; font-size: 0.8rem; background-color: #10b981; color: white; border-color: #10b981;'>Unduh</a>";
                            } else {
                                echo "<span style='color: #94a3b8; font-style: italic; font-size: 0.85rem;'>Belum Tersedia</span>";
                            }
                            echo "</td>";

                            // 7. Kolom Aksi (Detail)
                            echo "<td>";
                            echo "<a href='detail_pengajuan.php?id=" . $row['id_pengajuan'] . "' class='btn-aksi'>Detail</a>";
                            echo "</td>";

                            echo "</tr>";
                        }
                    } else {
                        // PERUBAHAN KRUSIAL: colspan diubah dari 6 menjadi 7 agar penuh menutupi semua kolom
                        echo "<tr><td colspan='7' class='text-center'>Belum ada riwayat permohonan surat.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>
</body>

</html>