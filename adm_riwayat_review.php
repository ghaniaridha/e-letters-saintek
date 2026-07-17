<?php
session_start();
include "koneksi.php";

if (!isset($koneksi)) {
    include "koneksi.php";
}

// 1. Tentukan filter berdasarkan Session Role (Pastikan nama session-nya benar)
$role = $_SESSION['role_admin'] ?? ''; // Misal 'admin1' atau 'admin2'
$filter_admin = "";

if ($role == 'admin1') {
    $filter_admin = "AND sp.tujuan_admin = 'admin1'";
} elseif ($role == 'admin2') {
    $filter_admin = "AND sp.tujuan_admin = 'admin2'";
}

// 2. Query dengan filter
$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat, sp.id_ormawa, sp.file_surat_final,
        m.npm, m.nama_mhs, o.nama_ormawa, p.nama_prodi,
        js.nama_surat, sp.tanggal_pengajuan, sp.status_akhir
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    WHERE sp.status_akhir != 'Menunggu Admin'
    $filter_admin 
    AND (
        sp.status_akhir LIKE '%Wadek%' OR sp.status_akhir LIKE '%Dekan%' 
        OR sp.status_akhir LIKE '%Ditolak Admin%' OR sp.status_akhir LIKE '%Kasubbag%' 
        OR sp.status_akhir = 'Selesai' OR sp.status_akhir LIKE 'Disetujui%' 
        OR sp.status_akhir = 'Menunggu Surat Balasan'
    )
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Review</title>
    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* CSS MODAL (DITAMBAHKAN) */
        #modalPreview { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; }
        .modal-content-frame { background:#fff; width:85%; height:85%; border-radius:10px; position:relative; padding:20px; }
        .close-btn { position:absolute; right:20px; top:10px; font-size:30px; cursor:pointer; color:#333; }
    </style>
</head>
<body>
    <!-- KODE ASLI SESSION ALERT -->
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
        <?php unset($_SESSION['pesan']); unset($_SESSION['status']); ?>
    <?php endif; ?>

    <div class="adm-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">
            <div class="page-title">
                <h1>Riwayat Review Admin</h1>
                <p>Daftar surat yang sudah direview admin beserta arsip surat balasan fakultas.</p>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>No</th><th>NPM</th><th>Pengirim (Mhs/Ormawa)</th><th>Prodi</th><th>Jenis Surat</th><th>Tanggal</th><th>Status Akhir</th><th>Surat Balasan Fakultas</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($query)) {
                                $namaSurat = strtolower($row['nama_surat']);
                                // LOGIKA LINK ASLI
                                if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                                    $linkBalasan = "generate_balasan_magang.php?id=" . $row['id_surat'];
                                } else if (!empty($row['id_ormawa'])) {
                                    $linkBalasan = "preview_peminjaman_ruangan.php?id=" . $row['id_surat'] . "&mode=view";
                                } else {
                                    $linkBalasan = "generate_balasan_fakultas.php?id=" . $row['id_surat'];
                                }

                                $status = htmlspecialchars($row['status_akhir']);
                                $badge_class = (strpos($status, 'Ditolak') !== false) ? "badge-danger" : ((strpos($status, 'Selesai') !== false || strpos($status, 'Disetujui') !== false) ? "badge-success" : "badge-warning");
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= !empty($row['npm']) ? htmlspecialchars($row['npm']) : '-'; ?></td>
                                <td><?= !empty($row['nama_mhs']) ? htmlspecialchars($row['nama_mhs']) : '<b>(ORMAWA)</b> ' . htmlspecialchars($row['nama_ormawa']); ?></td>
                                <td><?= !empty($row['nama_prodi']) ? htmlspecialchars($row['nama_prodi']) : '-'; ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><span class="badge <?= $badge_class; ?>"><?= $status; ?></span></td>
                                <td>
                                    <?php if (!empty($row['id_ormawa']) || !empty($row['file_surat_final'])) { ?>
                                        <!-- TOMBOL MODAL -->
                                        <a href="javascript:void(0)" onclick="bukaSurat('<?= $linkBalasan; ?>')" class="btn btn-detail">Lihat Surat</a>
                                    <?php } else { ?>
                                        <span style="color:#94a3b8; font-style:italic;">Belum tersedia</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="javascript:void(0)"
                                    onclick="bukaDetail('adm_detail_surat_popup.php?id=<?= $row['id_surat']; ?>')"
                                    class="btn btn-detail">
                                    Detail
                                    </a>
                                </td>
                            </tr>
                        <?php } 
                        } else { ?>
                            <tr><td colspan="9" style="text-align:center;">Belum ada riwayat review admin.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- HTML MODAL (DITAMBAHKAN) -->
    <div id="modalPreview">
        <div class="modal-content-frame">
            <span class="close-btn" onclick="tutupModal()">&times;</span>
            <iframe id="frameSurat" src="" width="100%" height="90%" style="border:none;"></iframe>
        </div>
    </div>

    <div id="modalDetail" style="display:none;
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,.7);
        justify-content:center;
        align-items:center;
        z-index:9999;">

        <div style="
            width:80%;
            height:85%;
            background:white;
            border-radius:10px;
            position:relative;
            overflow:hidden;">

            <span onclick="tutupDetail()"
                style="
                    position:absolute;
                    right:20px;
                    top:10px;
                    font-size:30px;
                    cursor:pointer;">&times;</span>

            <iframe id="frameDetail"
                    width="100%"
                    height="100%"
                    style="border:none;">
            </iframe>

        </div>
    </div>

    <!-- SCRIPT MODAL (DITAMBAHKAN) -->
    <script>
        function bukaSurat(url) {
            document.getElementById('frameSurat').src = url;
            document.getElementById('modalPreview').style.display = 'flex';
        }
        function tutupModal() {
            document.getElementById('modalPreview').style.display = 'none';
            document.getElementById('frameSurat').src = '';
        }

       function bukaDetail(url){
            document.getElementById("frameDetail").src = url;
            document.getElementById("modalDetail").style.display = "flex";
        }

        function tutupDetail(){
            document.getElementById("modalDetail").style.display = "none";
            document.getElementById("frameDetail").src = "";
        }
    </script>

    
</body>
</html>