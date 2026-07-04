<?php
session_start();
include "koneksi.php";

$folder = "uploads/templat_surat/";

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

/*tambah templat*/
if (isset($_POST['tambah'])) {
    $nama_surat = mysqli_real_escape_string($koneksi, $_POST['nama_surat']);
    $kode_surat = mysqli_real_escape_string($koneksi, $_POST['kode_surat']);
    $status     = (int) $_POST['status'];

    $file_template = "";

    if (!empty($_FILES['file_template']['name'])) {
        $nama_file = time() . "_" . basename($_FILES['file_template']['name']);
        $tmp_file = $_FILES['file_template']['tmp_name'];

        move_uploaded_file($tmp_file, $folder . $nama_file);
        $file_template = $nama_file;
    }

    mysqli_query($koneksi, "
        INSERT INTO jenis_surat (nama_surat, kode_surat, file_template, status)
        VALUES ('$nama_surat', '$kode_surat', '$file_template', $status)
    ");

    $_SESSION['pesan'] = 'Templat surat berhasil ditambahkan.';
    $_SESSION['status'] = 'success';
    header("Location: adm_template_surat.php");
    exit();
}

/*edit templat*/
if (isset($_POST['update'])) {
    $id_jenis = (int) $_POST['id_jenis'];
    $nama_surat = mysqli_real_escape_string($koneksi, $_POST['nama_surat']);
    $kode_surat = mysqli_real_escape_string($koneksi, $_POST['kode_surat']);
    $status     = (int) $_POST['status'];

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
            kode_surat = '$kode_surat',
            status = $status
            $sqlFile
        WHERE id_jenis = $id_jenis
    ");

    $_SESSION['pesan'] = 'Templat surat berhasil diperbarui.';
    $_SESSION['status'] = 'success';
    header("Location: adm_template_surat.php");
    exit();
}

/*hapus templat*/
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

    $_SESSION['pesan'] = 'Templat surat berhasil dihapus.';
    $_SESSION['status'] = 'success';
    header("Location: adm_template_surat.php");
    exit();
}

$query = mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    ORDER BY status DESC, id_jenis DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Template Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
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

    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">
            <div class="page-title">
                <h1>Kelola Templat Surat</h1>
                <p>Templat yang ditambahkan akan muncul di halaman daftar templat surat mahasiswa.</p>
            </div>

            <div class="table-card" id="kelola-templat-surat">
                <div class="table-toolbar">
                    <div class="toolbar-actions">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="cariSuratAdmin" placeholder="Cari surat...">
                        </div>
                        <button class="btn btn-add" onclick="openModal()">+ Tambah Templat</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Jenis Surat</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($query && mysqli_num_rows($query) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($query)) { ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_surat']); ?></strong><br><small><?= htmlspecialchars($row['kode_surat']); ?></small></td>
                                        <td>
                                            <a href="?id=<?= $row['id_jenis']; ?>&status=<?= $row['status'] == 1 ? 0 : 1; ?>"
                                                class="status-badge <?= $row['status'] == 1 ? 'aktif' : 'nonaktif'; ?>">
                                                <?= $row['status'] == 1 ? 'Aktif' : 'Nonaktif'; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['file_template'])) { ?>
                                                <a href="<?= $folder . $row['file_template']; ?>" target="_blank" class="btn-action-download" title="Unduh Template">
                                                    <i class="fa-solid fa-file-arrow-down"></i>
                                                </a>
                                            <?php } else { ?>
                                                <span class="text-muted">-</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <div class="action-container">
                                                <a href="?edit=<?= $row['id_jenis']; ?>" class="btn-action edit" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="?hapus=<?= $row['id_jenis']; ?>" class="btn-action delete" title="Hapus" onclick="konfirmasiHapus(event, this.href)">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="5" class="empty-table-row">Belum ada template surat.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="modalTemplate" class="modal <?= $dataEdit ? 'show' : '' ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?= $dataEdit ? 'Edit Template Surat' : 'Tambah Template Baru' ?></h3>
                        <span class="close-modal" onclick="closeModal()">&times;</span>
                    </div>

                    <div class="modal-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_jenis" value="<?= $dataEdit['id_jenis'] ?? ''; ?>">

                            <div class="form-group">
                                <label>Jenis Surat</label>
                                <input type="text" name="nama_surat" placeholder="Contoh: Izin Magang" value="<?= htmlspecialchars($dataEdit['nama_surat'] ?? ''); ?>" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Kode Surat</label>
                                <input type="text" name="kode_surat" placeholder="Contoh: B-/Un.16/" value="<?= htmlspecialchars($dataEdit['kode_surat'] ?? ''); ?>" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" required class="form-control">
                                    <option value="1" <?= (isset($dataEdit['status']) && $dataEdit['status'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?= (isset($dataEdit['status']) && $dataEdit['status'] == 0) ? 'selected' : ''; ?>>Nonaktif</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>File Template</label>
                                <input type="file" name="file_template" <?= $dataEdit ? '' : 'required'; ?> class="form-control">
                                <?php if ($dataEdit && !empty($dataEdit['file_template'])) { ?>
                                    <small class="file-info-text">
                                        File saat ini: <a href="<?= $folder . $dataEdit['file_template']; ?>" target="_blank"><?= htmlspecialchars($dataEdit['file_template']); ?></a>
                                    </small>
                                <?php } ?>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                                <?php if ($dataEdit) { ?>
                                    <button type="submit" name="update" class="btn btn-primary" onclick="konfirmasiSimpan(event, 'update')">Update Template</button>
                                <?php } else { ?>
                                    <button type="submit" name="tambah" class="btn btn-success" onclick="konfirmasiSimpan(event, 'tambah')">Simpan Template</button>
                                <?php } ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        //fungsi search
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('cariSuratAdmin');
            const tableRows = document.querySelectorAll('#kelola-templat-surat table tbody tr');

            if (searchInput) {
                searchInput.addEventListener('keyup', function(e) {
                    const term = e.target.value.toLowerCase();

                    tableRows.forEach(row => {
                        if (row.cells.length === 1) return;
                        const jenisSurat = row.cells[1].textContent.toLowerCase();

                        if (jenisSurat.includes(term)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });

        //fungsi untuk membuka popup tambah dan edit template
        function openModal() {
            <?php if ($dataEdit) { ?>
                window.location.href = 'adm_template_surat.php';
            <?php } else { ?>
                document.getElementById('modalTemplate').classList.add('show');
            <?php } ?>
        }

        //fungsi untuk menutup popup tambah dan edit template
        function closeModal() {
            <?php if ($dataEdit) { ?>
                window.location.href = 'adm_template_surat.php';
            <?php } else { ?>
                document.getElementById('modalTemplate').classList.remove('show');
            <?php } ?>
        }

        //fungsi konfirmasi hapus
        function konfirmasiHapus(event, url) {
            event.preventDefault();

            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Data templat yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }

        //fungsi konfirmasi simpan/perbarui data templat
        function konfirmasiSimpan(event, aksi) {
            event.preventDefault();

            let judul = aksi === 'Perbarui' ? 'Perbarui Templat?' : 'Simpan Templat?';
            let teks = aksi === 'Perbarui' ? 'Perubahan akan disimpan.' : 'Templat surat baru akan ditambahkan.';
            let warnaTombol = aksi === 'Perbarui' ? '#3b82f6' : '#10b981';
            let teksTombol = aksi === 'Perbarui' ? 'Ya, Update!' : 'Ya, Simpan!';

            Swal.fire({
                title: judul,
                text: teks,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: warnaTombol,
                cancelButtonColor: '#64748b',
                confirmButtonText: teksTombol,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = event.target.closest('form');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = aksi;
                    form.appendChild(hiddenInput);

                    form.submit();
                }
            });
        }
    </script>

</body>

</html>