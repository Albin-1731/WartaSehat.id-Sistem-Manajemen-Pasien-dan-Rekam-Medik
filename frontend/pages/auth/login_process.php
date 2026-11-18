<?php
session_start();
require_once '../../../backend/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header("Location: login.php?pesan=gagal");
    exit();
}

// -----------------------------------------------------------------------------
// 1. Cek Akun di Tabel `petugas`
// -----------------------------------------------------------------------------
$stmt_petugas = mysqli_prepare($connection, "SELECT id_petugas, nama_petugas, username, password FROM petugas WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_petugas, "s", $username);
mysqli_stmt_execute($stmt_petugas);
$result_petugas = mysqli_stmt_get_result($stmt_petugas);

if ($row_petugas = mysqli_fetch_assoc($result_petugas)) {
    // Verifikasi password (dukung password_hash maupun plaintext bawaan)
    if (password_verify($password, $row_petugas['password']) || $password === $row_petugas['password']) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $row_petugas['id_petugas'];
        $_SESSION['nama']     = $row_petugas['nama_petugas'];
        $_SESSION['username'] = $row_petugas['username'];
        $_SESSION['role']     = 'petugas';

        header("Location: ../petugas/dashboard.php");
        exit();
    }
}

// -----------------------------------------------------------------------------
// 2. Cek Akun di Tabel `dokter` (JOIN `poli`)
// -----------------------------------------------------------------------------
$stmt_dokter = mysqli_prepare($connection, "SELECT d.id_dokter, d.id_poli, p.nama_poli, d.nama_dokter, d.username, d.password 
                                            FROM dokter d 
                                            JOIN poli p ON d.id_poli = p.id_poli 
                                            WHERE d.username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_dokter, "s", $username);
mysqli_stmt_execute($stmt_dokter);
$result_dokter = mysqli_stmt_get_result($stmt_dokter);

if ($row_dokter = mysqli_fetch_assoc($result_dokter)) {
    // Verifikasi password (dukung password_hash maupun plaintext bawaan)
    if (password_verify($password, $row_dokter['password']) || $password === $row_dokter['password']) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $row_dokter['id_dokter'];
        $_SESSION['id_poli']   = $row_dokter['id_poli'];
        $_SESSION['nama_poli'] = $row_dokter['nama_poli'];
        $_SESSION['nama']      = $row_dokter['nama_dokter'];
        $_SESSION['username']  = $row_dokter['username'];
        $_SESSION['role']      = 'dokter';

        header("Location: ../dokter/dashboard.php");
        exit();
    }
}

// Jika username dan password tidak cocok di kedua tabel
header("Location: login.php?pesan=gagal");
exit();
?>
