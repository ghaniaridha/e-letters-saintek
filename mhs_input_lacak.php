<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="index.css?v=1.2" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <form class="login-form" method="GET" action="mhs_lacak.php">
        <div class="form-container">
            <div class="form-image">
                <a class="navbar-brand"></a>
            </div>
            <div class="form-content">
                <img src="images/logo1.png" alt="Logo" class="logo-login">

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?= $_SESSION['error']; ?>
                    </div>
                <?php unset($_SESSION['error']);
                endif; ?>

                <div class="box-input-data">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="kode" class="input-uppercase" placeholder="Masukkan Kode Lacak (Cth: TRK-A1B2C)" required autocomplete="off">
                </div>

                <button type="submit" class="btn-input">Lacak Surat</button>

                <div class="register-link">
                    Kembali ke Halaman <a href="index.php">Masuk</a>
                </div>
            </div>
        </div>
    </form>
</body>

</html>