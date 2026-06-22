<?php
session_start();
session_destroy();
?>

<link href="style.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil Berhasil!',
            text: 'Anda telah berhasil Keluar dari sistem.',
            timer: 2000, 
            showConfirmButton: false,
            customClass: {
            title: 'swal-title'
        }
        }).then(() => {
            window.location.href = "index.php"; 
        });
    });
</script>