<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$filter_admin = "";

if (isset($_SESSION['role_admin'])) {
    if ($_SESSION['role_admin'] == 'admin1') {
        $filter_admin = "AND sp.tujuan_admin = 'admin1'";
    } elseif ($_SESSION['role_admin'] == 'admin2') {
        $filter_admin = "AND sp.tujuan_admin = 'admin2'";
    }
}

$prodi           = $_GET['prodi'] ?? '';
$id_jenis_filter = $_GET['id_jenis'] ?? '';
$detail_id       = $_GET['detail'] ?? '';

/*Proses Lanjutkan/Tolak Permohonan*/
if (isset($_POST['aksi_admin'])) {
    $id_surat = (int) $_POST['id_surat'];
    $aksi     = $_POST['aksi_admin'];

    $dataSurat = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT sp.*, js.nama_surat
        FROM surat_pengajuan sp
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_surat = '$id_surat'
    "));

    if ($dataSurat) 
       if($aksi == "jadwal"){

    $tanggal = $_POST['tanggal_kegiatan'];
    $mulai   = $_POST['jam_mulai'];
    $selesai = $_POST['jam_selesai'];
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);

    mysqli_query($koneksi,"
        UPDATE detail_peminjaman_ruangan
        SET
            tanggal_mulai='$tanggal',
            tanggal_selesai='$tanggal',
            jam_mulai='$mulai',
            jam_selesai='$selesai',
            catatan='$catatan'
        WHERE id_surat='$id_surat'
    ");

    mysqli_query($koneksi,"
        UPDATE surat_pengajuan
        SET status_akhir='Selesai',
            status_keputusan='Disetujui',
            posisi_sekarang='Selesai'
        WHERE id_surat='$id_surat'
    ");

    header("Location: adm_permohonan.php");
    exit;
}

if($aksi == "tolak_ormawa"){

    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);

    $cekJenis = mysqli_fetch_assoc(mysqli_query($koneksi,"
        SELECT js.nama_surat
        FROM surat_pengajuan sp
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_surat='$id_surat'
    "));

    if(stripos($cekJenis['nama_surat'], 'dana') !== false){
        mysqli_query($koneksi,"
            UPDATE detail_pengajuan_dana
            SET catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");
    }else{
        mysqli_query($koneksi,"
            UPDATE detail_peminjaman_ruangan
            SET catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");
    }

    mysqli_query($koneksi,"
        UPDATE surat_pengajuan
        SET status_akhir='Ditolak Admin',
            status_keputusan='Ditolak',
            posisi_sekarang='Selesai'
        WHERE id_surat='$id_surat'
    ");
}


if ($aksi == 'tolak') {

    $alasan = mysqli_real_escape_string(
        $koneksi,
        $_POST['alasan_penolakan']
    );

    mysqli_query($koneksi,"
        UPDATE surat_pengajuan
        SET status_akhir='Ditolak Admin',
            posisi_sekarang='Selesai',
            alasan_penolakan='$alasan'
        WHERE id_surat='$id_surat'
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan'] = 'Permohonan berhasil ditolak';

    header("Location: adm_permohonan.php");
    exit;
}

if ($aksi == 'lanjut') {

    $namaSurat = strtolower($dataSurat['nama_surat']);

    if (!empty($dataSurat['id_ormawa'])) {

        $queryUpdate = "
            UPDATE surat_pengajuan
            SET status_akhir='Selesai',
                posisi_sekarang='Selesai'
            WHERE id_surat='$id_surat'
        ";

    } elseif (strpos($namaSurat, 'riset') !== false || strpos($namaSurat, 'aktif') !== false) {

        $queryUpdate = "
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Wadek 1',
                posisi_sekarang='Wadek 1',
                status_pimpinan='Menunggu'
            WHERE id_surat='$id_surat'
        ";

    } elseif (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {

        $queryUpdate = "
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Dekan',
                posisi_sekarang='Dekan',
                tujuan_admin='admin2',
                status_pimpinan='Menunggu'
            WHERE id_surat='$id_surat'
        ";

    } else {

        $queryUpdate = "
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Dekan',
                posisi_sekarang='Dekan',
                status_pimpinan='Menunggu'
            WHERE id_surat='$id_surat'
        ";
    }

    mysqli_query($koneksi, $queryUpdate);

    $_SESSION['status'] = 'success';
    $_SESSION['pesan']  = 'Surat berhasil diteruskan ke pimpinan';

    header("Location: adm_riwayat_review.php");
    exit;
}
}

/*Proses Hapus Data*/
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM surat_pengajuan WHERE id_surat = $id");
    echo "<script>alert('Permohonan berhasil dihapus'); window.location='adm_permohonan.php';</script>";
    exit;
}

$filter_admin = "";

if ($_SESSION['role_admin'] == 'admin1') {
    $filter_admin = "AND sp.tujuan_admin = 'admin1'";
} elseif ($_SESSION['role_admin'] == 'admin2') {
    $filter_admin = "AND sp.tujuan_admin = 'admin2'";
}

$where = "WHERE sp.status_akhir = 'Menunggu Admin' $filter_admin";

if ($prodi != "") {
    $prodiAman = mysqli_real_escape_string($koneksi, $prodi);
    // Tambahkan pelindung jika m.id_prodi NULL (pada data Ormawa)
    $where .= " AND m.id_prodi = '$prodiAman'";
}

if ($id_jenis_filter != "") {
    $idJenisAman = (int) $id_jenis_filter;
    $where .= " AND sp.id_jenis = '$idJenisAman'";
}

// Data untuk dropdown filter
$jenisSurat = mysqli_query($koneksi, "SELECT * FROM jenis_surat ORDER BY nama_surat ASC");

/*Query Utama (UBAH KE LEFT JOIN MAHASISWA & ORMAWA)*/
$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat,
        sp.id_jenis,
        sp.id_ormawa,
        m.npm,
        m.nama_mhs,
        o.nama_ormawa,
        p.nama_prodi,
        js.nama_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where
    ORDER BY sp.tanggal_pengajuan DESC
");

/*Query Detail Review (UBAH KE LEFT JOIN MAHASISWA & ORMAWA)*/
$detail = null;
$lampiran = [];

if ($detail_id != "") {
    $detail_id = (int) $detail_id;

    $query_detail = mysqli_query($koneksi, "
        SELECT 
            sp.*, 
            m.nama_mhs, 
            m.npm, 
            o.nama_ormawa,
            p.nama_prodi, 
            js.nama_surat,
            
            -- Detail Riset
            dsr.judul_skripsi, 
            dsr.lokasi_penelitian, 
            
            -- Detail Magang
            dsm.lokasi_magang,
            dsm.tanggal_mulai_magang,
            dsm.tanggal_selesai_magang,
            
            -- Detail Aktif Kuliah
            dak.lama_cuti,
            dak.ta_mulai_cuti,
            dak.ta_selesai_cuti,
            dak.tahun_akademik,
            
            -- Detail Peminjaman Ruangan (Ormawa)
            dpr.nama_kegiatan,
            dpr.ruangan_yang_diajukan,
            dpr.tanggal_mulai,
            dpr.proposal,
            dpr.surat_permohonan,
            
            -- Menggabungkan kolom sejenis
            COALESCE(dsm.surat_ditujukan, dsr.surat_ditujukan) AS surat_ditujukan,
            COALESCE(dsr.semester, dsm.semester, dak.semester) AS semester
        FROM surat_pengajuan sp
        LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
        LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
        LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
        LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
        LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
        WHERE sp.id_surat = '$detail_id'
    ");

    $detail = mysqli_fetch_assoc($query_detail);

    if ($detail && empty($detail['id_ormawa'])) {
        // Hanya cari di lampiran_pengajuan jika ini surat Mahasiswa
        $q_lampiran = mysqli_query($koneksi, "
            SELECT ms.nama_syarat, lp.file_upload 
            FROM lampiran_pengajuan lp
            JOIN master_syarat ms ON lp.id_syarat = ms.id_syarat
            WHERE lp.id_surat = '$detail_id'
        ");

        while ($row_lamp = mysqli_fetch_assoc($q_lampiran)) {
            $nama_syarat = strtolower($row_lamp['nama_syarat']);

            if (strpos($nama_syarat, 'proposal') !== false) {
                $lampiran['proposal'] = $row_lamp['file_upload'];
            } elseif (strpos($nama_syarat, 'khs') !== false) {
                $lampiran['khs'] = $row_lamp['file_upload'];
            } elseif (strpos($nama_syarat, 'ukt') !== false) {
                $lampiran['ukt'] = $row_lamp['file_upload'];
            } elseif (strpos($nama_syarat, 'ktm') !== false) {
                $lampiran['ktm'] = $row_lamp['file_upload'];
            } elseif (strpos($nama_syarat, 'cuti') !== false) {
                $lampiran['sk_cuti'] = $row_lamp['file_upload'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Permohonan Surat</title>
    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .review-box {
            background: #fff;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .08);
            margin-bottom: 25px;
        }

        .review-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .modal-preview {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .65);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .modal-content-preview {
            width: 88%;
            max-width: 1050px;
            background: #fff;
            padding: 20px;
            border-radius: 14px;
            position: relative;
        }

        .close-preview {
            position: absolute;
            right: 18px;
            top: 8px;
            font-size: 30px;
            cursor: pointer;
        }


        .modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    width: 500px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-sizing: border-box; /* Agar width 100% tidak keluar batas */
}

.modal-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn-batal {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 6px;
    background: #e0e0e0;
    cursor: pointer;
    font-weight: bold;
}

.btn-simpan {
    flex: 2;
    padding: 10px;
    border: none;
    border-radius: 6px;
    background: #f1c40f;
    cursor: pointer;
    font-weight: bold;
}

textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box; /* Sangat penting agar tidak melebar keluar modal */
}
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">

            <div class="page-title">
                <h1>Kelola Permohonan Surat</h1>
                <p>Permohonan yang menunggu verifikasi admin.</p>
            </div>

            <?php if ($detail) {
                $namaSurat = strtolower($detail['nama_surat']);
            ?>
                <div class="review-box">
                    <h2>Review Permohonan Surat <?= !empty($detail['id_ormawa']) ? 'Ormawa' : 'Mahasiswa'; ?></h2>
                    <br>

                    <table>
                        <?php if (!empty($detail['id_ormawa'])) { ?>
                            <!-- LAYOUT DETAIL JIKA PENGIRIM ADALAH ORMAWA -->
                            <tr>
                                <th>Nama Organisasi</th>
                                <td><?= htmlspecialchars($detail['nama_ormawa']); ?></td>
                            </tr>
                            <tr>
                                <th>Nama Kegiatan</th>
                                <td><?= htmlspecialchars($detail['nama_kegiatan'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Ruangan Diajukan</th>
                                <td><?= htmlspecialchars($detail['ruangan_yang_diajukan'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Kegiatan</th>
                                <td><?= date('d-m-Y', strtotime($detail['tanggal_mulai'] ?? 'now')); ?></td>
                            </tr>
                        <?php } else { ?>
                            <!-- LAYOUT DETAIL JIKA PENGIRIM ADALAH MAHASISWA -->
                            <tr>
                                <th>NPM</th>
                                <td><?= htmlspecialchars($detail['npm']); ?></td>
                            </tr>
                            <tr>
                                <th>Nama Mahasiswa</th>
                                <td><?= htmlspecialchars($detail['nama_mhs']); ?></td>
                            </tr>
                            <tr>
                                <th>Program Studi</th>
                                <td><?= htmlspecialchars($detail['nama_prodi']); ?></td>
                            </tr>
                            <tr>
                                <th>Semester</th>
                                <td><?= htmlspecialchars($detail['semester'] ?? '-'); ?></td>
                            </tr>
                        <?php } ?>

                        <tr>
                            <th>Jenis Surat</th>
                            <td><?= htmlspecialchars($detail['nama_surat']); ?></td>
                        </tr>

                        <?php if (strpos($namaSurat, 'riset') !== false) { ?>
                            <tr>
                                <th>Judul Skripsi</th>
                                <td><?= htmlspecialchars($detail['judul_skripsi'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Lokasi Penelitian</th>
                                <td><?= htmlspecialchars($detail['lokasi_penelitian'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Ditujukan Kepada</th>
                                <td><?= htmlspecialchars($detail['surat_ditujukan'] ?? '-'); ?></td>
                            </tr>

                        <?php } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) { ?>
                            <tr>
                                <th>Lokasi Magang</th>
                                <td><?= htmlspecialchars($detail['lokasi_magang'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Magang</th>
                                <td>
                                    <?= htmlspecialchars($detail['tanggal_mulai_magang'] ?? '-'); ?> s/d <?= htmlspecialchars($detail['tanggal_selesai_magang'] ?? '-'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Ditujukan Kepada</th>
                                <td><?= htmlspecialchars($detail['surat_ditujukan'] ?? '-'); ?></td>
                            </tr>

                        <?php } else if (strpos($namaSurat, 'aktif') !== false) { ?>
                            <tr>
                                <th>Lama Cuti</th>
                                <td><?= htmlspecialchars($detail['lama_cuti'] ?? '-'); ?> Semester</td>
                            </tr>
                            <tr>
                                <th>Periode Masa Cuti</th>
                                <td>
                                    Gasal: <?= htmlspecialchars($detail['ta_mulai_cuti'] ?? '-'); ?> <br>
                                    Genap: <?= htmlspecialchars($detail['ta_selesai_cuti'] ?? '-'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Tahun Akademik Aktif</th>
                                <td><?= htmlspecialchars($detail['tahun_akademik'] ?? '-'); ?></td>
                            </tr>
                        <?php } ?>

                        <tr>
                            <th>Status Saat Ini</th>
                            <td><?= htmlspecialchars($detail['status_akhir']); ?></td>
                        </tr>
                    </table>

                    <div class="review-actions" style="margin-top: 20px;">
                       <?php
                        // Penentuan link review dokumen utama
                        if (!empty($detail['id_ormawa'])) {

                            if (stripos($detail['nama_surat'], 'dana') !== false) {
                                $filePreview = "preview_pengajuan_dana.php?id=" . $detail['id_surat'] . "&mode=view";
                            } else {
                                $filePreview = "preview_peminjaman_ruangan.php?id=" . $detail['id_surat'] . "&mode=view";
                            }

                        } else {

                            $filePreview = "preview_surat.php?id=" . $detail['id_surat'];

                            if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                                $filePreview = "preview_magang.php?id=" . $detail['id_surat'];
                            } elseif (strpos($namaSurat, 'aktif') !== false) {
                                $filePreview = "mhs_preview_sk_aktif.php?id=" . $detail['id_surat'];
                            }
                        }
                        ?>

                        <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $filePreview; ?>')">
                            Review Surat Hasil Sistem
                        </a>

                        <?php if (!empty($detail['id_ormawa'])) { ?>
                            <!-- DOKUMEN UPLOAD KHUSUS ORMAWA -->
                            <?php if (!empty($detail['surat_permohonan'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/surat_permohonan/<?= htmlspecialchars($detail['surat_permohonan']); ?>')">Dokumen Pengajuan</a>
                            <?php } ?>
                            <?php if (!empty($detail['proposal'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($detail['proposal']); ?>')">Proposal Kegiatan</a>
                            <?php } ?>
                        <?php } else { ?>
                            <!-- DOKUMEN UPLOAD KHUSUS MAHASISWA -->
                            <?php if (!empty($lampiran['proposal'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['proposal']); ?>')">Proposal</a>
                            <?php } ?>
                            <?php if (!empty($lampiran['khs'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['khs']); ?>')">KHS</a>
                            <?php } ?>
                            <?php if (!empty($lampiran['ukt'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['ukt']); ?>')">Bukti UKT</a>
                            <?php } ?>
                            <?php if (!empty($lampiran['ktm'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['ktm']); ?>')">KTM</a>
                            <?php } ?>
                            <?php if (!empty($lampiran['sk_cuti'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['sk_cuti']); ?>')">SK Cuti</a>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
                

                <form method="POST" class="review-actions">
                    <input type="hidden" name="id_surat" value="<?= $detail['id_surat']; ?>">
                    <?php if(!empty($detail['id_ormawa'])){ ?>
                        <button type="button"
                                class="btn btn-edit"
                                onclick="bukaModalJadwal()">
                            Atur Jadwal
                        </button>
                        <button type="button"
                                class="btn btn-delete"
                                onclick="bukaModalTolak()">
                            Tolak
                        </button>
                    <a href="adm_permohonan.php" class="btn btn-detail">
                        Kembali
                    </a>
                    <?php }else{ ?>
                    <button type="submit"
                            name="aksi_admin"
                            value="lanjut"
                            class="btn btn-edit">
                        Lanjutkan ke Pimpinan
                    </button>

                    <button type="button"
                            class="btn btn-delete"
                            onclick="bukaModalTolakMhs()">
                        Tolak
                    </button>

                    <?php } ?>
                </form>
            <?php } ?>

            <div class="filter-container">
                <form method="GET">
                    <select name="prodi">
                        <option value="">Semua Prodi</option>

                        <?php
                        $queryProdi = mysqli_query($koneksi,"
                            SELECT * FROM prodi
                            ORDER BY nama_prodi ASC
                        ");

                        while($prd = mysqli_fetch_assoc($queryProdi)){
                        ?>
                            <option value="<?= $prd['id_prodi']; ?>"
                                <?= $prodi == $prd['id_prodi'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($prd['nama_prodi']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="id_jenis">
                        <option value="">Semua Jenis Surat</option>
                        <?php mysqli_data_seek($jenisSurat, 0);
                        while ($js = mysqli_fetch_assoc($jenisSurat)) { ?>
                            <option value="<?= $js['id_jenis']; ?>" <?= $id_jenis_filter == $js['id_jenis'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($js['nama_surat']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="btn btn-detail">Filter</button>
                    <a href="adm_permohonan.php" class="btn btn-delete">Reset</a>
                </form>
            </div>

            <div class="table-card-table">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NPM</th>
                            <th>Pengirim (Mhs/Ormawa)</th>
                            <th>Program Studi</th>
                            <th>Jenis Surat</th>
                            <th>Tanggal Diajukan</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                            <?php $no = 1;
                            while ($row = mysqli_fetch_assoc($query)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= !empty($row['npm']) ? htmlspecialchars($row['npm']) : '-'; ?></td>
                                    <td><?= !empty($row['nama_mhs']) ? htmlspecialchars($row['nama_mhs']) : '<b>(ORMAWA)</b> ' . htmlspecialchars($row['nama_ormawa']); ?></td>
                                    <td><?= !empty($row['nama_prodi']) ? htmlspecialchars($row['nama_prodi']) : '-'; ?></td>
                                    <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td>
                                        <span class="badge-warning">
                                            <?= htmlspecialchars($row['status_akhir']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="adm_permohonan.php?detail=<?= $row['id_surat']; ?>" class="btn btn-detail">
                                            Review
                                        </a>
                                        <a href="#" class="btn btn-delete" onclick="hapusData(<?= $row['id_surat']; ?>)">
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="8" style="text-align:center;">
                                    Data permohonan yang menunggu admin tidak ditemukan.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <div id="modalPreview" class="modal-preview">
        <div class="modal-content-preview">
            <span class="close-preview" onclick="tutupPreview()">&times;</span>
            <iframe id="previewFrame" width="100%" height="620px" style="border:none;"></iframe>
        </div>
    </div>

    <div id="modalJadwal" class="modal-preview">

    <div class="modal-content-preview">

        <h3>Atur Jadwal Ruangan</h3>
    <form method="POST" action="adm_permohonan.php">
    <input type="hidden" name="id_surat" value="<?= $detail['id_surat']; ?>">
    <!-- PENTING: Aksi harus 'jadwal' agar ditangkap oleh if($aksi == "jadwal") -->
    <input type="hidden" name="aksi_admin" value="jadwal"> 
    
    <div class="form-group">
        <label>Tanggal Kegiatan</label>
        <input type="date" name="tanggal_kegiatan" class="form-control" required>
    </div>

    <div style="display: flex; gap: 15px;">
        <div class="form-group" style="flex:1;">
            <label>Jam Mulai</label>
            <input type="time" name="jam_mulai" class="form-control" required>
        </div>
        <div class="form-group" style="flex:1;">
            <label>Jam Selesai</label>
            <input type="time" name="jam_selesai" class="form-control" required>
        </div>
    </div>

    <div class="form-group">
        <label>Catatan Admin</label>
        <textarea name="catatan" class="form-control" rows="3"></textarea>
    </div>

    <!-- Di dalam modalJadwal -->
<form>
    <div class="modal-actions">
        <button type="button" class="btn-batal" onclick="tutupModal()">Batal</button>
        <button type="submit" class="btn-simpan">Simpan Jadwal</button>
    </div>
</form>

    </div>

</div>


<div id="modalTolak" class="modal-preview">

    <div class="modal-content-preview">

        <h3>Alasan Penolakan</h3>

        <form method="POST">

            <input type="hidden"
                   name="id_surat"
                   value="<?= $detail['id_surat'] ?>">

            <input type="hidden"
                   name="aksi_admin"
                   value="tolak_ormawa">

            <textarea name="catatan"
                      rows="5"
                      required
                      placeholder="Masukkan alasan penolakan"></textarea>

            <br><br>

            <!-- Di dalam modalTolak -->
            <div class="modal-actions">
                <button type="button" class="btn-batal" onclick="tutupModal()">Batal</button>
                <button type="submit" class="btn-simpan" onclick="return confirm('Yakin ingin menyimpan penolakan ini?')">Simpan Penolakan</button>
            </div>

        </form>

    </div>

</div>



<div id="modalTolakMhs" class="modal-preview">
    <div class="modal-content-preview">

        <h3>Alasan Penolakan</h3>

        <form method="POST">
            <input type="hidden"
                   name="id_surat"
                   value="<?= $detail['id_surat'] ?>">

            <input type="hidden"
                   name="aksi_admin"
                   value="tolak">

            <textarea name="alasan_penolakan"
                      rows="5"
                      required
                      placeholder="Masukkan alasan penolakan"></textarea>

            <br><br>

            <div class="modal-actions">
                <button type="button"
                        class="btn-batal"
                        onclick="tutupModal()">
                    Batal
                </button>

                <button type="submit"
                        class="btn-simpan">
                    Simpan Penolakan
                </button>
            </div>

        </form>

    </div>
</div>

    <script>
        function bukaPreview(file) {
            document.getElementById('previewFrame').src = file;
            document.getElementById('modalPreview').style.display = 'flex';
        }

        function tutupPreview() {
            document.getElementById('modalPreview').style.display = 'none';
            document.getElementById('previewFrame').src = '';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalPreview');
            if (event.target == modal) {
                tutupPreview();
            }
        }

        function hapusData(id) {
            if (confirm('Yakin ingin menghapus permohonan ini?')) {
                window.location.href = 'adm_permohonan.php?hapus=' + id;
            }
        }
    </script>

<script>
    function bukaModalJadwal(){
        document.getElementById("modalJadwal").style.display = "flex";
    }

    function bukaModalTolak(){
        document.getElementById("modalTolak").style.display = "flex";
    }

    function bukaModalTolakMhs(){
    document.getElementById("modalTolakMhs").style.display = "flex";
}

    // Fungsi tutupModal sekarang menangani kedua ID sekaligus
   function tutupModal(){
    document.getElementById("modalJadwal").style.display = "none";
    document.getElementById("modalTolak").style.display = "none";

    const modalMhs = document.getElementById("modalTolakMhs");
    if(modalMhs){
        modalMhs.style.display = "none";
    }
}
</script>


</body>
</html>