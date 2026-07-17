<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

/* ==========================
   FILTER DAN PENCARIAN
========================== */

$sql = "
    SELECT
        o.*,
        p.nama_prodi,
        d.nama_dosen AS nama_pembina
    FROM ormawa o
    LEFT JOIN prodi p ON o.id_prodi = p.id_prodi
    LEFT JOIN dosen d ON o.id_pembina = d.id_dosen
";

$where = [];

/* pencarian */
if (!empty($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['keyword']);

    $where[] = "
        (
            o.nama_ormawa LIKE '%$keyword%'
            OR o.username LIKE '%$keyword%'
        )
    ";
}

/* filter prodi */
if (!empty($_GET['prodi'])) {
    $prodi = mysqli_real_escape_string($koneksi, $_GET['prodi']);

    $where[] = "o.id_prodi = '$prodi'";
}
if(count($where) > 0){
    $sql .= " WHERE ".implode(" AND ", $where);
}

$sql .= "
    ORDER BY
    o.nama_ormawa ASC
";

/* ==========================
   PAGINATION
========================== */

$batas = 5;

$halaman = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

$halaman_awal = ($halaman > 1)
    ? ($halaman * $batas) - $batas
    : 0;

$query_total = mysqli_query($koneksi,$sql);

$jumlah_data = mysqli_num_rows($query_total);

$total_halaman = ceil($jumlah_data / $batas);

$sql_limit = $sql." LIMIT $halaman_awal,$batas";

$query = mysqli_query($koneksi,$sql_limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ormawa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<div class="admin-wrapper">

    <?php include "adm_sidebar.php"; ?>

    <main class="main-content">

        <div class="page-title">
            <h1>Verifikasi & Kelola Ormawa</h1>
            <p>Kelola akun organisasi mahasiswa.</p>
        </div>

        <div class="table-card">

            <form method="GET" class="filter-section">

                <input type="text"
                       name="keyword"
                       placeholder="Cari nama ormawa..."
                       value="<?= $_GET['keyword'] ?? '' ?>">

                <select name="prodi">
                    <option value="">Semua Prodi</option>

                    <?php
                    $prodiQuery = mysqli_query(
                        $koneksi,
                        "SELECT * FROM prodi ORDER BY nama_prodi ASC"
                    );

                    while($p = mysqli_fetch_assoc($prodiQuery)){
                    ?>
                        <option value="<?= $p['id_prodi'] ?>"
                            <?= (isset($_GET['prodi']) && $_GET['prodi']==$p['id_prodi']) ? 'selected' : '' ?>>
                            <?= $p['nama_prodi'] ?>
                        </option>
                    <?php } ?>
                </select>

                <button type="submit" class="btn-filter">
                    <i class="fa-solid fa-search"></i>
                    Cari
                </button>

            </form>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Ormawa</th>
                        <th>Username</th>
                        <th>Program Studi</th>
                        <th>Pembina</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if(mysqli_num_rows($query) > 0){ ?>

                        <?php
                        $no = $halaman_awal + 1;

                        while($row = mysqli_fetch_assoc($query)){
                        ?>

                        <tr>

                            <td><?= $no++ ?></td>

                            <td><?= htmlspecialchars($row['nama_ormawa']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nama_prodi']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pembina']) ?></td>

                            <td>

                                <button
                                    type="button"
                                    class="btn btn-detail"
                                    onclick="lihatDetail(
                                        '<?= htmlspecialchars($row['nama_ormawa'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['nama_prodi'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['nama_pembina'], ENT_QUOTES) ?>'
                                    )"
                                >
                                    Detail
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-edit"
                                    onclick="editOrmawa(
                                        '<?= $row['id_ormawa'] ?>',
                                        '<?= htmlspecialchars($row['nama_ormawa'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['nama_prodi'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['nama_pembina'], ENT_QUOTES) ?>'
                                    )">
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-reset"
                                    onclick="konfirmasiReset('<?= $row['id_ormawa'] ?>')">
                                    Atur Sandi
                                </button>

                            </td>

                        </tr>

                        <?php } ?>

                    <?php }else{ ?>

                        <tr>
                            <td colspan="7" class="empty-table">
                                Belum ada data Ormawa.
                            </td>
                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    </main>

</div>
<script>

function lihatDetail(
    nama_ormawa,
    username,
    prodi,
    pembina
){

    let html = `
        <div class="swal-scroll-container">

            <div class="section-title">
                Informasi Ormawa
            </div>

            <div class="detail-row">
                <div class="detail-label">Nama Ormawa</div>
                <div class="detail-value">${nama_ormawa}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Username</div>
                <div class="detail-value">${username}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Program Studi</div>
                <div class="detail-value">${prodi}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Dosen Pembina</div>
                <div class="detail-value">${pembina}</div>
            </div>
        </div>
    `;

    Swal.fire({
        title: 'Detail Ormawa',
        html: html,
        width: '550px',
        showCloseButton: true,
        showConfirmButton: false
    });

}

</script>
<script>
function konfirmasiReset(id){

    Swal.fire({
        title: 'Atur ulang kata sandi?',
        text: 'Password akan direset menjadi username ormawa.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reset',
        cancelButtonText: 'Batal'
    }).then((result)=>{
        if(result.isConfirmed){
            window.location =
                'adm_reset_pw_ormawa.php?id=' + id;
        }
    });

}
</script>

<script>
function editOrmawa(
    id,
    nama,
    username,
    prodi,
    pembina
){
    Swal.fire({
        title: 'Edit Data Ormawa',
        width: 650,
        html: `
            <div style="text-align:left;margin-top:20px">

                <label style="font-weight:600">Nama Ormawa</label>
                <input id="swal_nama"
                    class="swal2-input"
                    style="width:100%;margin:8px 0 18px 0"
                    value="${nama}">

                <label style="font-weight:600">Username</label>
                <input id="swal_username"
                    class="swal2-input"
                    style="width:100%;margin:8px 0 18px 0"
                    value="${username}">

                <label style="font-weight:600">Program Studi</label>
                <input
                    class="swal2-input"
                    style="width:100%;margin:8px 0 18px 0;background:#f5f5f5"
                    value="${prodi}"
                    readonly>

                <label style="font-weight:600; display:block; margin-bottom:8px;">
                    Dosen Pembina
                </label>

                <input
                    type="text"
                    class="swal2-input"
                    value="${pembina}"
                    readonly
                    style="
                        width:100%;
                        margin:0;
                        background:#f5f5f5;
                        box-sizing:border-box;
                    ">

            </div>
        `,
        showCancelButton:true,
        confirmButtonText:'Simpan Perubahan',
        cancelButtonText:'Batal',

        confirmButtonColor:'#0d6efd',
        cancelButtonColor:'#6c757d',

        preConfirm:()=>{

            const nama =
                document.getElementById('swal_nama').value;

            const username =
                document.getElementById('swal_username').value;

            window.location =
                'adm_update_ormawa.php?id=' + id +
                '&nama=' + encodeURIComponent(nama) +
                '&username=' + encodeURIComponent(username);
        }
    });
}
</script>
</body>
</html>