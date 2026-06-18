<?php
include "admin_header.php";

/** @var mysqli $koneksi */

$folder = "uploads/template_surat/";

if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$id_edit = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$dataEdit = null;

if ($id_edit > 0) {
    $qEdit = mysqli_query($koneksi, "
        SELECT * FROM jenis_surat 
        WHERE id_jenis = $id_edit
    ");

    $dataEdit = mysqli_fetch_assoc($qEdit);
}

/* TAMBAH TEMPLATE */
if (isset($_POST['tambah'])) {
    $nama_surat = mysqli_real_escape_string($koneksi, $_POST['nama_surat']);
    $kode_surat = mysqli_real_escape_string($koneksi, $_POST['kode_surat']);

    $file_template = "";

    if (!empty($_FILES['file_template']['name'])) {
        $nama_file = time() . "_" . basename($_FILES['file_template']['name']);
        $tmp_file = $_FILES['file_template']['tmp_name'];

        move_uploaded_file($tmp_file, $folder . $nama_file);
        $file_template = $nama_file;
    }

    mysqli_query($koneksi, "
        INSERT INTO jenis_surat (nama_surat, kode_surat, file_template)
        VALUES ('$nama_surat', '$kode_surat', '$file_template')
    ");

    echo "<script>alert('Template surat berhasil ditambahkan'); window.location='admin_template_surat.php';</script>";
}

/* UPDATE TEMPLATE */
if (isset($_POST['update'])) {
    $id_jenis = (int) $_POST['id_jenis'];
    $nama_surat = mysqli_real_escape_string($koneksi, $_POST['nama_surat']);
    $kode_surat = mysqli_real_escape_string($koneksi, $_POST['kode_surat']);

    $sqlFile = "";

    if (!empty($_FILES['file_template']['name'])) {
        $cekFile = mysqli_query($koneksi, "
            SELECT file_template 
            FROM jenis_surat 
            WHERE id_jenis = $id_jenis
        ");

        $fileLama = mysqli_fetch_assoc($cekFile);

        if (!empty($fileLama['file_template']) && file_exists($folder . $fileLama['file_template'])) {
            unlink($folder . $fileLama['file_template']);
        }

        $nama_file = time() . "_" . basename($_FILES['file_template']['name']);
        $tmp_file = $_FILES['file_template']['tmp_name'];

        move_uploaded_file($tmp_file, $folder . $nama_file);

        $sqlFile = ", file_template = '$nama_file'";
    }

    mysqli_query($koneksi, "
        UPDATE jenis_surat
        SET 
            nama_surat = '$nama_surat',
            kode_surat = '$kode_surat'
            $sqlFile
        WHERE id_jenis = $id_jenis
    ");

    echo "<script>alert('Template surat berhasil diperbarui'); window.location='admin_template_surat.php';</script>";
}

/* HAPUS TEMPLATE */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    $cek = mysqli_query($koneksi, "
        SELECT file_template 
        FROM jenis_surat 
        WHERE id_jenis = $id
    ");

    $data = mysqli_fetch_assoc($cek);

    if (!empty($data['file_template']) && file_exists($folder . $data['file_template'])) {
        unlink($folder . $data['file_template']);
    }

    mysqli_query($koneksi, "
        DELETE FROM jenis_surat
        WHERE id_jenis = $id
    ");

    echo "<script>alert('Template surat berhasil dihapus'); window.location='admin_template_surat.php';</script>";
}

$query = mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    ORDER BY id_jenis DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Template Surat</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">
        <div class="page-title">
            <h1>Kelola Template Surat</h1>
            <p>Template yang ditambahkan admin otomatis muncul di halaman pengajuan mahasiswa.</p>
        </div>

        <div class="table-card">

            <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
                <input type="hidden" name="id_jenis" value="<?= $dataEdit['id_jenis'] ?? ''; ?>">

                <input type="text" 
                       name="nama_surat" 
                       placeholder="Nama template surat" 
                       value="<?= htmlspecialchars($dataEdit['nama_surat'] ?? ''); ?>"
                       required
                       style="padding:10px; border:1px solid #ddd; border-radius:8px; width:260px;">

                <input type="text" 
                       name="kode_surat" 
                       placeholder="Kode surat" 
                       value="<?= htmlspecialchars($dataEdit['kode_surat'] ?? ''); ?>"
                       required
                       style="padding:10px; border:1px solid #ddd; border-radius:8px; width:180px;">

                <input type="file" 
                       name="file_template"
                       <?= $dataEdit ? '' : 'required'; ?>
                       style="padding:10px; border:1px solid #ddd; border-radius:8px;">

                <?php if ($dataEdit) { ?>
                    <button type="submit" name="update" class="btn btn-edit">
                        Update Template
                    </button>

                    <a href="admin_template_surat.php" class="btn btn-delete">
                        Batal
                    </a>
                <?php } else { ?>
                    <button type="submit" name="tambah" class="btn btn-add">
                        + Tambah Template
                    </button>
                <?php } ?>
            </form>

            <?php if ($dataEdit && !empty($dataEdit['file_template'])) { ?>
                <p style="margin-bottom:20px;">
                    File saat ini:
                    <a href="<?= $folder . $dataEdit['file_template']; ?>" target="_blank">
                        <?= htmlspecialchars($dataEdit['file_template']); ?>
                    </a>
                </p>
            <?php } ?>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Template</th>
                        <th>Kode Surat</th>
                        <th>File Template</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query)) { ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td><?= htmlspecialchars($row['kode_surat']); ?></td>
                                <td>
                                    <?php if (!empty($row['file_template'])) { ?>
                                        <a href="<?= $folder . $row['file_template']; ?>" target="_blank">
                                            Download
                                        </a>
                                    <?php } else { ?>
                                        Tidak ada file
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="admin_template_surat.php?edit=<?= $row['id_jenis']; ?>" class="btn btn-edit">
                                        Edit
                                    </a>

                                    <a href="admin_template_surat.php?hapus=<?= $row['id_jenis']; ?>"
                                       class="btn btn-delete"
                                       onclick="return confirm('Yakin ingin menghapus template ini?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">Belum ada template surat.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>