<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location:index.php");
    exit;
}

$query_prodi = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");
$list_prodi = [];
while ($p = mysqli_fetch_assoc($query_prodi)) {
    $list_prodi[] = $p;
}

/* ===========================
   PENCARIAN, FILTER & PAGINATION
=========================== */
$keyword = $_GET['keyword'] ?? '';
$filter_prodi = $_GET['prodi'] ?? '';

$where_clauses = [];

if ($keyword != '') {
    $keyword_safe = mysqli_real_escape_string($koneksi, $keyword);
    $where_clauses[] = "(dosen.nip LIKE '%$keyword_safe%' OR dosen.nama_dosen LIKE '%$keyword_safe%' OR dosen.jabatan LIKE '%$keyword_safe%')";
}

if ($filter_prodi != '') {
    $prodi_safe = mysqli_real_escape_string($koneksi, $filter_prodi);
    $where_clauses[] = "dosen.id_prodi = '$prodi_safe'";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$limit = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($halaman - 1) * $limit;

$query_count = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM dosen $where_sql");
$row_count = mysqli_fetch_assoc($query_count);
$total_data = $row_count['total'];
$total_halaman = ceil($total_data / $limit);

$params = [];
if ($keyword != '') $params['keyword'] = $keyword;
if ($filter_prodi != '') $params['prodi'] = $filter_prodi;
$query_string = !empty($params) ? '&' . http_build_query($params) : '';

/* ===========================
   TAMBAH DOSEN
=========================== */
if (isset($_POST['tambah'])) {
    $nip         = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama_dosen  = mysqli_real_escape_string($koneksi, $_POST['nama_dosen']);
    $password    = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $jabatan     = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $role_akses  = mysqli_real_escape_string($koneksi, $_POST['role_akses']);

    $id_prodi    = !empty($_POST['id_prodi']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['id_prodi']) . "'" : "NULL";

    $kode_ttd_qr = hash('sha256', $nip . time());

    mysqli_query($koneksi, "
        INSERT INTO dosen (nip, nama_dosen, password, jabatan, role_akses, id_prodi, kode_ttd_qr)
        VALUES ('$nip', '$nama_dosen', '$password', '$jabatan', '$role_akses', $id_prodi, '$kode_ttd_qr')
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan'] = 'Data dosen berhasil ditambahkan';

    header("Location: adm_kelola_dosen.php");
    exit;
}

/* ===========================
   HAPUS DOSEN
=========================== */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    mysqli_query($koneksi, "DELETE FROM dosen WHERE id_dosen = '$id'");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan'] = 'Data dosen berhasil dihapus';

    header("Location: adm_kelola_dosen.php");
    exit;
}

/* ===========================
   UPDATE DOSEN
=========================== */
if (isset($_POST['update'])) {
    $id         = (int)$_POST['id_dosen'];
    $nip        = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama       = mysqli_real_escape_string($koneksi, $_POST['nama_dosen']);
    $jabatan    = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $role       = mysqli_real_escape_string($koneksi, $_POST['role_akses']);

    $id_prodi   = !empty($_POST['id_prodi']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['id_prodi']) . "'" : "NULL";

    mysqli_query($koneksi, "
        UPDATE dosen
        SET nip='$nip',
            nama_dosen='$nama',
            jabatan='$jabatan',
            role_akses='$role',
            id_prodi=$id_prodi
        WHERE id_dosen='$id'
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan'] = 'Data dosen berhasil diperbarui';

    header("Location: adm_kelola_dosen.php");
    exit;
}

/* ===========================
   DATA DOSEN DENGAN LIMIT & JOIN PRODI
=========================== */
$query = mysqli_query($koneksi, "
    SELECT dosen.*, prodi.nama_prodi 
    FROM dosen 
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    $where_sql
    ORDER BY 
        CASE 
            WHEN dosen.jabatan LIKE '%Dekan%' AND dosen.jabatan NOT LIKE '%Wakil%' AND dosen.jabatan NOT LIKE '%Wadek%' THEN 1
            WHEN dosen.jabatan LIKE '%Wakil Dekan 1%' OR dosen.jabatan LIKE '%Wakil Dekan I%' OR dosen.jabatan LIKE '%Wadek 1%' THEN 2
            WHEN dosen.jabatan LIKE '%Wakil Dekan 2%' OR dosen.jabatan LIKE '%Wakil Dekan II%' OR dosen.jabatan LIKE '%Wadek 2%' THEN 3
            WHEN dosen.jabatan LIKE '%Kasubbag%' OR dosen.jabatan LIKE '%Kasubag%' OR dosen.jabatan LIKE '%Tata Usaha%' THEN 4
            WHEN dosen.jabatan LIKE '%Ketua Program Studi%' OR dosen.jabatan LIKE '%Kaprodi%' OR dosen.jabatan LIKE '%Ketua Jurusan%' OR dosen.jabatan LIKE '%Kajur%' THEN 5
            WHEN dosen.jabatan LIKE '%Sekretaris Program Studi%' OR dosen.jabatan LIKE '%Sekprodi%' OR dosen.jabatan LIKE '%Sekretaris Jurusan%' OR dosen.jabatan LIKE '%Sekjur%' THEN 6
            ELSE 7
        END ASC,
        dosen.nama_dosen ASC
    LIMIT $limit OFFSET $offset
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Dosen</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title">
                <h1>Kelola Data Dosen & Tenaga Kerja FST</h1>
            </div>

            <?php if (isset($_SESSION['pesan'])) { ?>
                <script>
                    Swal.fire({
                        icon: '<?= $_SESSION['status']; ?>',
                        title: '<?= $_SESSION['status'] == "success" ? "Berhasil!" : "Gagal!" ?>',
                        text: <?= json_encode($_SESSION['pesan']); ?>,
                        timer: 2500,
                        showConfirmButton: false
                    });
                </script>

                <?php
                unset($_SESSION['status']);
                unset($_SESSION['pesan']);
                ?>
            <?php } ?>

            <div class="table-card-table">
                <form method="GET" action="" class="filter-section filter-section-split">

                    <div class="filter-left-group">
                        <input type="text" name="keyword" placeholder="Cari NIP, Nama, Jabatan..." value="<?= htmlspecialchars($keyword); ?>">

                        <select name="prodi" class="select-role">
                            <option value="">Semua Prodi</option>
                            <?php
                            $res_prodi = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");
                            while ($p = mysqli_fetch_assoc($res_prodi)) {
                                $selected = ($filter_prodi == $p['id_prodi']) ? 'selected' : '';
                                echo "<option value='" . $p['id_prodi'] . "' $selected>" . htmlspecialchars($p['nama_prodi']) . "</option>";
                            }
                            ?>
                        </select>

                        <button type="submit" class="btn-filter">
                            <i class="fa-solid fa-search"></i> Cari
                        </button>
                        <a href="adm_kelola_dosen.php" class="btn-reset-filter">
                            <i class="fa-solid fa-rotate-left"></i>Reset
                        </a>
                    </div>

                    <div>
                        <button type="button" class="btn-tambah-dosen" onclick="bukaTambah()">
                            <i class="fa-solid fa-plus"></i> Tambah Data
                        </button>
                    </div>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIP</th>
                            <th>Nama</th>
                            <th>Program Studi</th>
                            <th>Jabatan</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($query && mysqli_num_rows($query) > 0) {
                            $no = $offset + 1;
                            while ($row = mysqli_fetch_assoc($query)) {
                        ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nip']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_dosen']); ?></td>
                                    <td><?= !empty($row['nama_prodi']) ? htmlspecialchars($row['nama_prodi']) : '-'; ?></td>
                                    <td><?= htmlspecialchars($row['jabatan']); ?></td>
                                    <td><?= htmlspecialchars($row['role_akses']); ?></td>
                                    <td>
                                        <div class="action-group-table">
                                            <a href="#" class="btn btn-detail" onclick="editDosen(
                                            '<?= $row['id_dosen']; ?>',
                                            '<?= htmlspecialchars($row['nip'], ENT_QUOTES); ?>',
                                            '<?= htmlspecialchars($row['nama_dosen'], ENT_QUOTES); ?>',
                                            '<?= htmlspecialchars($row['jabatan'], ENT_QUOTES); ?>',
                                            '<?= htmlspecialchars($row['role_akses'], ENT_QUOTES); ?>',
                                            '<?= $row['id_prodi']; ?>' 
                                        )">
                                                Edit
                                            </a>

                                            <a href="#" class="btn btn-delete" onclick="hapusDosen(<?= $row['id_dosen']; ?>)">
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="7" class="empty-table">Data dosen tidak ditemukan.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <?php if (isset($total_halaman) && $total_halaman > 0): ?>
                    <div class="pagination-container">
                        <ul class="pagination">
                            <?php if ($halaman > 1): ?>
                                <li><a href="?page=<?= $halaman - 1 ?><?= $query_string ?>">Sebelumnya</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>Sebelumnya</span></li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                                <?php if ($i == $halaman): ?>
                                    <li class="active"><span><?= $i ?></span></li>
                                <?php else: ?>
                                    <li><a href="?page=<?= $i ?><?= $query_string ?>"><?= $i ?></a></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($halaman < $total_halaman): ?>
                                <li><a href="?page=<?= $halaman + 1 ?><?= $query_string ?>">Selanjutnya</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>Selanjutnya</span></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="modalTambah" class="modal-preview">
        <div class="modal-content-preview">
            <h3>Tambah Data Baru</h3>
            <form method="POST" onsubmit="konfirmasiSimpan(event, this, 'Yakin ingin menyimpan data dosen baru ini?'); return false;">
                <input type="hidden" name="tambah" value="1">

                <div class="form-group">
                    <label>NIP</label>
                    <input type="text"
                        name="nip"
                        class="form-control"
                        placeholder="Masukkan NIP Dosen"
                        pattern="[0-9]+"
                        title="NIP hanya boleh berisi angka."
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                        required>
                </div>
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="nama_dosen" required class="form-control" placeholder="Masukkan nama lengkap beserta gelar">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required class="form-control" placeholder="Masukkan password akun">
                </div>

                <div class="form-group">
                    <label>Program Studi <small class="text-muted-small">(Kosongkan jika Pimpinan)</small></label>
                    <select name="id_prodi" class="form-control">
                        <option value="">-- Tidak terikat prodi spesifik --</option>
                        <?php foreach ($list_prodi as $pr): ?>
                            <option value="<?= $pr['id_prodi'] ?>"><?= htmlspecialchars($pr['nama_prodi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan" required class="form-control" placeholder="Contoh: Ketua Prodi / Dosen Tetap">
                </div>
                <div class="form-group">
                    <label>Role Akses</label>
                    <select name="role_akses" class="form-control" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="dosen">Dosen</option>
                        <option value="pimpinan">Pimpinan</option>
                    </select>
                </div>

                <div class="modal-footer-actions">
                    <button type="button" class="btn-modal-cancel" onclick="konfirmasiBatal('modalTambah')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT DOSEN -->
    <div id="modalEdit" class="modal-preview">
        <div class="modal-content-preview">
            <h3>Edit Data</h3>
            <form method="POST" onsubmit="konfirmasiSimpan(event, this, 'Yakin ingin menyimpan perubahan data dosen ini?'); return false;">
                <input type="hidden" name="update" value="1">
                <input type="hidden" name="id_dosen" id="edit_id">

                <div class="form-group">
                    <label>NIP</label>
                    <input type="text" name="nip" id="edit_nip" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="nama_dosen" id="edit_nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Program Studi <small class="text-muted-small">(Kosongkan jika Pimpinan)</small></label>
                    <select name="id_prodi" id="edit_prodi" class="form-control">
                        <option value="">-- Tidak terikat prodi spesifik --</option>
                        <?php foreach ($list_prodi as $pr): ?>
                            <option value="<?= $pr['id_prodi'] ?>"><?= htmlspecialchars($pr['nama_prodi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan" id="edit_jabatan" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_akses" id="edit_role" class="form-control">
                        <option value="dosen">Dosen</option>
                        <option value="pimpinan">Pimpinan</option>
                    </select>
                </div>

                <div class="modal-footer-actions">
                    <button type="button" class="btn-modal-cancel" onclick="konfirmasiBatal('modalEdit')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <?php include "adm_footer.php"; ?>

    <script>
        function bukaTambah() {
            document.getElementById('modalTambah').style.display = 'flex';
        }

        function tutupTambah() {
            document.getElementById('modalTambah').style.display = 'none';
        }

        function editDosen(id, nip, nama, jabatan, role, prodi) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nip').value = nip;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_jabatan').value = jabatan;
            document.getElementById('edit_role').value = role;

            document.getElementById('edit_prodi').value = prodi ? prodi : "";

            document.getElementById('modalEdit').style.display = 'flex';
        }

        function tutupEdit() {
            document.getElementById('modalEdit').style.display = 'none';
        }

        // Fungsi Hapus dengan SweetAlert
        function hapusDosen(id) {
            Swal.fire({
                title: 'Hapus dosen?',
                text: 'Data dosen yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location = 'adm_kelola_dosen.php?hapus=' + id;
                }
            });
        }

        // Konfirmasi untuk menyimpan data (Tambah / Edit)
        function konfirmasiSimpan(event, formElement, pesan) {
            event.preventDefault();

            Swal.fire({
                title: 'Konfirmasi',
                text: pesan,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    formElement.submit();
                }
            });
        }

        // Konfirmasi membatalkan pengisian form
        function konfirmasiBatal(modalId) {
            Swal.fire({
                title: 'Batalkan?',
                text: 'Data yang sudah diisi tidak akan disimpan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Lanjutkan Mengisi'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(modalId).style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>