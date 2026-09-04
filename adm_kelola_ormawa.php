<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$sql = "
    SELECT
        o.*,
        p.nama_prodi,
        p.id_kaprodi,
        COALESCE(d_ukm.nama_dosen, d_kaprodi.nama_dosen) AS nama_pembina
    FROM ormawa o
    LEFT JOIN prodi p ON o.id_prodi = p.id_prodi
    LEFT JOIN dosen d_ukm ON o.id_pembina = d_ukm.id_dosen
    LEFT JOIN dosen d_kaprodi ON p.id_kaprodi = d_kaprodi.id_dosen
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
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
    ORDER BY
    o.nama_ormawa ASC
";

/* pagination */
$batas = 5;
$halaman = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

$halaman_awal = ($halaman > 1)
    ? ($halaman * $batas) - $batas
    : 0;

$query_total = mysqli_query($koneksi, $sql);
$jumlah_data = mysqli_num_rows($query_total);
$total_halaman = ceil($jumlah_data / $batas);
$sql_limit = $sql . " LIMIT $halaman_awal,$batas";
$query = mysqli_query($koneksi, $sql_limit);

$opt_dosen = "";
$res_dosen = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
while ($d = mysqli_fetch_assoc($res_dosen)) {
    $opt_dosen .= '<option value="' . $d['id_dosen'] . '">' . htmlspecialchars($d['nama_dosen'], ENT_QUOTES) . ' (NIP. ' . htmlspecialchars($d['nip'], ENT_QUOTES) . ')</option>';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ormawa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=1.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title">
                <h1>Kelola Ormawa & UKM</h1>
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
                <form method="GET" class="filter-section filter-section-split">

                    <div class="filter-left-group">
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
                            while ($p = mysqli_fetch_assoc($prodiQuery)) {
                            ?>
                                <option value="<?= $p['id_prodi'] ?>"
                                    <?= (isset($_GET['prodi']) && $_GET['prodi'] == $p['id_prodi']) ? 'selected' : '' ?>>
                                    <?= $p['nama_prodi'] ?>
                                </option>
                            <?php } ?>
                        </select>

                        <button type="submit" class="btn-filter">
                            <i class="fa-solid fa-search"></i> Cari
                        </button>
                        <a href="adm_kelola_ormawa.php" class="btn-reset-filter">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    </div>

                    <div class="filter-right-group">
                        <button type="button" class="btn-tambah-dosen" onclick="window.location='adm_tambah_ormawa.php'">
                            <i class="fa-solid fa-plus"></i> Tambah Ormawa
                        </button>
                    </div>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Organisasi</th>
                            <th>Singkatan Organisasi</th>
                            <th>Penanggung Jawab</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (mysqli_num_rows($query) > 0) { ?>
                            <?php
                            $no = $halaman_awal + 1;
                            while ($row = mysqli_fetch_assoc($query)) {
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama_ormawa']) ?></td>
                                    <td><?= htmlspecialchars($row['singkatan_ormawa']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_pembina']) ?></td>
                                    <td>
                                        <div class="action-group-sec">
                                            <button type="button" class="btn btn-detail"
                                                data-nama="<?= htmlspecialchars($row['nama_ormawa'] ?? '', ENT_QUOTES) ?>"
                                                data-singkatan="<?= htmlspecialchars($row['singkatan_ormawa'] ?? '', ENT_QUOTES) ?>"
                                                data-username="<?= htmlspecialchars($row['username'] ?? '', ENT_QUOTES) ?>"
                                                data-prodi="<?= htmlspecialchars($row['nama_prodi'] ?? 'Fakultas Sains dan Teknologi UIN Raden Intan Lampung', ENT_QUOTES) ?>"
                                                data-pembina="<?= htmlspecialchars($row['nama_pembina'] ?? 'Belum Diatur', ENT_QUOTES) ?>"
                                                data-ketua="<?= htmlspecialchars($row['nama_ketua'] ?? 'Belum Diisi', ENT_QUOTES) ?>"
                                                data-npmketua="<?= htmlspecialchars($row['npm_ketua'] ?? '-', ENT_QUOTES) ?>"
                                                data-sekretaris="<?= htmlspecialchars($row['nama_sekretaris'] ?? 'Belum Diisi', ENT_QUOTES) ?>"
                                                data-npmsekretaris="<?= htmlspecialchars($row['npm_sekretaris'] ?? '-', ENT_QUOTES) ?>"
                                                data-email="<?= htmlspecialchars($row['email_ormawa'] ?? '-', ENT_QUOTES) ?>"
                                                data-kontak="<?= htmlspecialchars($row['kontak_ormawa'] ?? '-', ENT_QUOTES) ?>"
                                                data-alamat="<?= htmlspecialchars($row['alamat_sekretariat'] ?? '-', ENT_QUOTES) ?>"
                                                onclick="bukaModalDetail(this)">
                                                Detail
                                            </button>

                                            <button type="button" class="btn btn-edit"
                                                data-id="<?= $row['id_ormawa'] ?>"
                                                data-nama="<?= htmlspecialchars($row['nama_ormawa'], ENT_QUOTES) ?>"
                                                data-singkatan="<?= htmlspecialchars($row['singkatan_ormawa'] ?? '', ENT_QUOTES) ?>"
                                                data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>"
                                                data-prodi="<?= htmlspecialchars($row['nama_prodi'] ?? 'Tidak Ada', ENT_QUOTES) ?>"
                                                data-jenis="<?= htmlspecialchars($row['jenis_organisasi'], ENT_QUOTES) ?>"
                                                data-idpembina="<?= $row['id_pembina'] ?? '' ?>"
                                                data-namapembina="<?= htmlspecialchars($row['nama_pembina'] ?? 'Belum diatur', ENT_QUOTES) ?>"
                                                onclick="bukaModalEdit(this)">
                                                Edit
                                            </button>
                                            <button type="button" class="btn btn-reset" onclick="konfirmasiReset('<?= $row['id_ormawa'] ?>')">
                                                Atur Sandi
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
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
        function bukaModalDetail(btn) {
            const nama_ormawa = btn.getAttribute('data-nama');
            const singkatan = btn.getAttribute('data-singkatan');
            const username = btn.getAttribute('data-username');
            const prodi = btn.getAttribute('data-prodi');
            const kaprodi = btn.getAttribute('data-pembina');
            const nama_ketua = btn.getAttribute('data-ketua');
            const npm_ketua = btn.getAttribute('data-npmketua');
            const nama_sekretaris = btn.getAttribute('data-sekretaris');
            const npm_sekretaris = btn.getAttribute('data-npmsekretaris');
            const email = btn.getAttribute('data-email');
            const kontak = btn.getAttribute('data-kontak');
            const alamat = btn.getAttribute('data-alamat');

            lihatDetail(nama_ormawa, singkatan, username, prodi, kaprodi, nama_ketua, npm_ketua, nama_sekretaris, npm_sekretaris, email, kontak, alamat);
        }

        function lihatDetail(nama_ormawa, singkatan, username, prodi, kaprodi, nama_ketua, npm_ketua, nama_sekretaris, npm_sekretaris, email, kontak, alamat) {
            let html = `
    <div class="swal-scroll-container">
        <div class="section-title">
            Informasi Akademik
        </div>
        <div class="detail-row">
            <div class="detail-label">Nama Organisasi</div>
            <div class="detail-value">${nama_ormawa}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Singkatan Organisasi</div>
            <div class="detail-value">${singkatan}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Username</div>
            <div class="detail-value">${username}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Naungan Prodi / Fakultas</div>
            <div class="detail-value">${prodi}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Penanggung Jawab</div>
            <div class="detail-value">${kaprodi}</div>
        </div>

        <div class="section-title mt-20">
            Kepengurusan & Kontak
        </div>
        <div class="detail-row">
            <div class="detail-label">Ketua</div>
            <div class="detail-value">${nama_ketua} <br><small class="text-muted-small">(NPM: ${npm_ketua})</small></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Sekretaris</div>
            <div class="detail-value">${nama_sekretaris} <br><small class="text-muted-small">(NPM: ${npm_sekretaris})</small></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Email</div>
            <div class="detail-value">${email}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Kontak / HP</div>
            <div class="detail-value">${kontak}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Sekretariat</div>
            <div class="detail-value">${alamat}</div>
        </div>
    </div>
    `;

            Swal.fire({
                title: 'Detail Ormawa',
                html: html,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false
            });
        }

        function konfirmasiReset(id) {
            Swal.fire({
                title: 'Atur ulang kata sandi?',
                text: 'Password akan direset menjadi username ormawa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location =
                        'adm_reset_pw_ormawa.php?id=' + id;
                }
            });

        }

        function bukaModalEdit(btn) {
            const id = btn.getAttribute('data-id');
            const nama = btn.getAttribute('data-nama');
            const singkatan = btn.getAttribute('data-singkatan');
            const username = btn.getAttribute('data-username');
            const prodi = btn.getAttribute('data-prodi');
            const jenis = btn.getAttribute('data-jenis');
            const idPembina = btn.getAttribute('data-idpembina');
            const namaPembina = btn.getAttribute('data-namapembina');

            editOrmawa(id, nama, singkatan, username, prodi, jenis, idPembina, namaPembina);
        }

        const dosenOptionsHtml = <?= json_encode($opt_dosen); ?>;

        function editOrmawa(id, nama, singkatan, username, prodi, jenis, idPembina, namaPembina) {
            let penanggungJawabHtml = '';

            if (jenis === 'Ormawa') {
                penanggungJawabHtml = `
            <label>Kaprodi (Penanggung Jawab)</label>
            <input class="swal2-input swal-input-readonly" value="${namaPembina}" readonly>
            <small class="swal-hint-text">*Kaprodi hanya dapat diubah melalui menu Kelola Program Studi.</small>
        `;
            } else {
                penanggungJawabHtml = `
            <label>Dosen Pembina</label>
            <select id="swal_pembina" class="swal2-input select-cari-dosen">
                <option value=""></option>
                ${dosenOptionsHtml}
            </select>
        `;
            }

            Swal.fire({
                title: 'Edit Data ' + jenis,
                width: 650,
                html: `
        <div class="swal-form-wrapper swal-scroll-container">
            
            <label>Nama Lengkap Organisasi</label>
            <input id="swal_nama" class="swal2-input" value="${nama}">

            <label>Singkatan / Akronim</label>
            <input id="swal_singkatan" class="swal2-input" value="${singkatan}">

            <label>Username Login</label>
            <input id="swal_username" class="swal2-input" value="${username}">

            ${jenis === 'Ormawa' ? `
            <label>Program Studi Naungan</label>
            <input class="swal2-input swal-input-readonly" value="${prodi}" readonly>
            ` : ''}

            ${penanggungJawabHtml}

        </div>
        `,
                didOpen: () => {
                    if (jenis === 'UKM') {
                        $('#swal_pembina').select2({
                            placeholder: "-- Pilih Dosen Pembina --",
                            allowClear: true,
                            width: '100%',
                            dropdownParent: Swal.getPopup()
                        });

                        if (idPembina) {
                            $('#swal_pembina').val(idPembina).trigger('change');
                        }
                    }
                },
                showCancelButton: true,
                confirmButtonText: 'Simpan Perubahan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                preConfirm: () => {
                    const inputNama = document.getElementById('swal_nama').value.trim();
                    const inputSingkatan = document.getElementById('swal_singkatan').value.trim();
                    const inputUsername = document.getElementById('swal_username').value.trim();

                    let inputPembina = '';
                    if (jenis === 'UKM') {
                        inputPembina = $('#swal_pembina').val();
                        if (!inputPembina) {
                            Swal.showValidationMessage('Dosen pembina wajib dipilih!');
                            return false;
                        }
                    }

                    if (!inputNama || !inputSingkatan || !inputUsername) {
                        Swal.showValidationMessage('Pastikan Nama, Singkatan, dan Username tidak kosong!');
                        return false;
                    }

                    return {
                        nama: inputNama,
                        singkatan: inputSingkatan,
                        username: inputUsername,
                        pembina: inputPembina
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const data = result.value;

                    // Popup Konfirmasi Simpan/Batal
                    Swal.fire({
                        title: 'Simpan Perubahan?',
                        text: 'Pastikan data yang diubah sudah sesuai.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan!',
                        cancelButtonText: 'Kembali',
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d'
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            window.location.href = `adm_update_ormawa.php?id=${id}&nama=${encodeURIComponent(data.nama)}&singkatan=${encodeURIComponent(data.singkatan)}&username=${encodeURIComponent(data.username)}&id_pembina=${data.pembina}`;
                        } else if (confirmResult.dismiss === Swal.DismissReason.cancel) {
                            editOrmawa(id, data.nama, data.singkatan, data.username, prodi, jenis, data.pembina, namaPembina);
                        }
                    });

                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        title: 'Batalkan Pengeditan?',
                        text: 'Perubahan yang Anda buat tidak akan disimpan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Batalkan',
                        cancelButtonText: 'Kembali Edit',
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d'
                    }).then((cancelResult) => {
                        if (cancelResult.dismiss === Swal.DismissReason.cancel) {
                            editOrmawa(id, nama, singkatan, username, prodi, jenis, idPembina, namaPembina);
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>