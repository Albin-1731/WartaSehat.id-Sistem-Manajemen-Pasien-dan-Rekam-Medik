<?php
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'petugas') {
        header("Location: frontend/pages/petugas/dashboard.php");
        exit();
    } else if ($_SESSION['role'] === 'dokter') {
        header("Location: frontend/pages/dokter/dashboard.php");
        exit();
    }
}

// Pengguna belum login diarahkan ke halaman login
header("Location: frontend/pages/auth/login.php");
exit();
?>
