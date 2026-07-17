<?php
session_start();
include "koneksi.php";

// 1. Cek Login
if (!isset($_SESSION['npm'])) {
    exit("Akses ditolak");
}

// 2. Ambil ID dari URL
$id_surat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 3. Ambil data
$query = mysqli_query($koneksi, "
    SELECT sp.*, js.nama_surat, m.nama_mhs, m.npm
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    WHERE sp.id_surat = '$id_surat' AND sp.id_mhs = '{$_SESSION['id_mhs']}'
");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "Data tidak ditemukan.";
    exit;
}
?>

<style>
    .table-detail { width: 100%; border-collapse: collapse; margin-top: 5px; }
    .table-detail td { padding: 8px 5px; border-bottom: 1px solid #eee; vertical-align: top; }
    .label-td { width: 40%; font-weight: bold; color: #333; }
    .status-badge { 
        padding: 4px 8px; 
        border-radius: 4px; 
        color: #fff; 
        display: inline-block;
        white-space: nowrap; 
        font-size: 13px;
    }
</style>

<table class="table-detail">
    <tr>
        <td class="label-td">Nama Mahasiswa</td>
        <td>: <?= htmlspecialchars($data['nama_mhs']); ?></td>
    </tr>
    <tr>
        <td class="label-td">NPM</td>
        <td>: <?= htmlspecialchars($data['npm']); ?></td>
    </tr>
    <tr>
        <td class="label-td">Jenis Surat</td>
        <td>: <?= htmlspecialchars($data['nama_surat']); ?></td>
    </tr>
    <tr>
        <td class="label-td">Status Akhir</td>
        <td>: 
            <?php
                $warnaStatus = (stripos($data['status_akhir'], 'Ditolak') !== false)
                ? '#dc3545'
                : '#28a745';
            ?>

                <span class="status-badge" style="background: <?= $warnaStatus; ?>">
                    <?= htmlspecialchars($data['status_akhir']); ?>
                </span>
        </td>
    </tr>
    <tr>
        <td class="label-td">Catatan</td>
        <td>: <?= !empty($data['catatan']) ? htmlspecialchars($data['catatan']) : '<em>Tidak ada catatan</em>'; ?></td>
    </tr>
</table>

</body>
</html>