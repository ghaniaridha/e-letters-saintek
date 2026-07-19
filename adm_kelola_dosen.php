<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location:index.php");
    exit;
}

/* ===========================
   TAMBAH DOSEN
=========================== */
if (isset($_POST['tambah'])) {

    $nip         = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama_dosen  = mysqli_real_escape_string($koneksi, $_POST['nama_dosen']);
    $password    = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $jabatan     = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $role_akses  = mysqli_real_escape_string($koneksi, $_POST['role_akses']);

    $kode_ttd_qr = hash('sha256', $nip . time());

    mysqli_query($koneksi, "
        INSERT INTO dosen
        (
            nip,
            nama_dosen,
            password,
            jabatan,
            role_akses,
            kode_ttd_qr
        )
        VALUES
        (
            '$nip',
            '$nama_dosen',
            '$password',
            '$jabatan',
            '$role_akses',
            '$kode_ttd_qr'
        )
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

    mysqli_query($koneksi, "
        DELETE FROM dosen
        WHERE id_dosen = '$id'
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan'] = 'Data dosen berhasil dihapus';

    header("Location: adm_kelola_dosen.php");
    exit;
}
    if(isset($_POST['update'])){

    $id         = (int)$_POST['id_dosen'];
    $nip        = mysqli_real_escape_string($koneksi,$_POST['nip']);
    $nama       = mysqli_real_escape_string($koneksi,$_POST['nama_dosen']);
    $jabatan    = mysqli_real_escape_string($koneksi,$_POST['jabatan']);
    $role       = mysqli_real_escape_string($koneksi,$_POST['role_akses']);

    mysqli_query($koneksi,"
        UPDATE dosen
        SET nip='$nip',
            nama_dosen='$nama',
            jabatan='$jabatan',
            role_akses='$role'
        WHERE id_dosen='$id'
    ");

    $_SESSION['status']='success';
    $_SESSION['pesan']='Data dosen berhasil diperbarui';

    header("Location: adm_kelola_dosen.php");
    exit;
}
/* ===========================
   DATA DOSEN
=========================== */
$query = mysqli_query($koneksi, "
    SELECT *
    FROM dosen
    ORDER BY nama_dosen ASC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Dosen</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



    <style>
.modal-preview{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    z-index:9999;
    justify-content:center;
    align-items:center;
}

.modal-content-preview{
    width:500px;
    background:#fff;
    padding:25px;
    border-radius:15px;
    box-shadow:0 10px 25px rgba(0,0,0,.2);
}

.form-group{
    margin-bottom:15px;
}

.form-control{
    width:100%;
    padding:10px;
    border:1px solid #ddd;
    border-radius:8px;
    box-sizing:border-box;
}

.modal-content-preview h3{
    margin-top: 0;
    margin-bottom: 25px;
}

.form-group{
    margin-bottom: 18px;
}

.form-group label{
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

</style>


</head>

<body>

<div class="admin-wrapper">

    <?php include "adm_sidebar.php"; ?>

    <main class="main-content">

        <div class="page-title">
            <h1>Kelola Data Dosen</h1>
            <p>Tambah, ubah, dan hapus data dosen.</p>
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

        <!-- FORM TAMBAH DOSEN -->
        <div class="table-card" style="padding:20px; margin-bottom:20px;">

            <form method="POST">

                <div class="form-group">
                    <label>NIP</label>
                    <input type="text" name="nip" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Nama Dosen</label>
                    <input type="text" name="nama_dosen" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Role Akses</label>

                    <select name="role_akses" class="form-control" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="dosen">Dosen</option>
                        <option value="pimpinan">Pimpinan</option>
                     </select>
                </div>

                <button type="submit" name="tambah" class="btn btn-edit">
                    Tambah Dosen
                </button>

            </form>

        </div>

        <!-- TABEL DOSEN -->
        <div class="table-card-table">

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Dosen</th>
                        <th>Jabatan</th>
                        <th>Role</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    $no = 1;

                    while ($row = mysqli_fetch_assoc($query)) {
                    ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td><?= htmlspecialchars($row['nip']); ?></td>

                            <td><?= htmlspecialchars($row['nama_dosen']); ?></td>

                            <td><?= htmlspecialchars($row['jabatan']); ?></td>

                            <td><?= htmlspecialchars($row['role_akses']); ?></td>

                            <td>
                                <a href="#"class="btn btn-detail"onclick="editDosen(
                                        '<?= $row['id_dosen']; ?>',
                                        '<?= htmlspecialchars($row['nip'], ENT_QUOTES); ?>',
                                        '<?= htmlspecialchars($row['nama_dosen'], ENT_QUOTES); ?>',
                                        '<?= htmlspecialchars($row['jabatan'], ENT_QUOTES); ?>',
                                        '<?= htmlspecialchars($row['role_akses'], ENT_QUOTES); ?>'
                                    )">
                                        Edit
                                </a>

                                <a href="#"class="btn btn-delete"onclick="hapusDosen(<?= $row['id_dosen']; ?>)">
                                    Hapus
                                </a>
                            </td>
                        </tr>

                    <?php } ?>

                </tbody>
            </table>

        </div>

    </main>

</div>


<div id="modalEdit" class="modal-preview" style="display:none;">
    <div class="modal-content-preview">

        <h3>Edit Dosen</h3>

        <form method="POST">

            <input type="hidden" name="id_dosen" id="edit_id">

            <div class="form-group">
                <label>NIP</label>
                <input type="text" name="nip" id="edit_nip" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Nama Dosen</label>
                <input type="text" name="nama_dosen" id="edit_nama" class="form-control" required>
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

            <br>

            <button type="button"
                    class="btn btn-delete"
                    onclick="tutupEdit()">
                Batal
            </button>

            <button type="submit"
                    name="update"
                    class="btn btn-edit">
                Simpan Perubahan
            </button>

        </form>

    </div>
</div>

<script>
function editDosen(id,nip,nama,jabatan,role){

    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nip').value = nip;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_jabatan').value = jabatan;
    document.getElementById('edit_role').value = role;

    document.getElementById('modalEdit').style.display='flex';
}

function tutupEdit(){
    document.getElementById('modalEdit').style.display='none';
}

function hapusDosen(id){

    Swal.fire({
        title:'Hapus dosen?',
        text:'Data dosen yang dihapus tidak dapat dikembalikan.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Ya, hapus',
        cancelButtonText:'Batal'
    }).then((result)=>{

        if(result.isConfirmed){
            window.location='adm_kelola_dosen.php?hapus='+id;
        }

    });
}
</script>
</body>
</html>