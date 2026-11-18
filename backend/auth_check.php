<?php
/**
 * WartaSehat.id — Helper Autentikasi & Proteksi Hak Akses Sesi
 * Memastikan proteksi ketat:
 * - Pengunjung belum login diarahkan ke login.php
 * - Dokter TIDAK BISA mengakses halaman Petugas (diarahkan ke dashboard dokter)
 * - Petugas TIDAK BISA mengakses halaman Dokter (diarahkan ke dashboard petugas)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah pengguna sudah login
 */
function check_login($redirect_to_login = true) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        if ($redirect_to_login) {
            header("Location: ../auth/login.php?pesan=belum_login");
            exit();
        }
        return false;
    }
    return true;
}

/**
 * Proteksi Khusus Halaman Petugas
 * Jika Dokter mencoba masuk, tendang kembali ke dashboard dokter!
 */
function check_petugas() {
    check_login(true);
    if ($_SESSION['role'] !== 'petugas') {
        // Terlarang: Dokter dilarang akses halaman petugas!
        header("Location: ../dokter/dashboard.php?pesan=akses_ditolak");
        exit();
    }
}

/**
 * Proteksi Khusus Halaman Dokter
 * Jika Petugas mencoba masuk, tendang kembali ke dashboard petugas!
 */
function check_dokter() {
    check_login(true);
    if ($_SESSION['role'] !== 'dokter') {
        // Terlarang: Petugas dilarang akses halaman dokter!
        header("Location: ../petugas/dashboard.php?pesan=akses_ditolak");
        exit();
    }
}

/**
 * Helper untuk mengambil inisial nama (misal "Budi Santoso" -> "BS")
 */
function get_initials($name) {
    $words = explode(" ", trim($name));
    $initials = "";
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= strtoupper($w[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    return !empty($initials) ? $initials : "WS";
}

/**
 * Helper format tanggal Indonesia (misal: "Senin, 16 November 2026")
 */
function format_tanggal_indo($datetime = null) {
    $timestamp = $datetime ? strtotime($datetime) : time();
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $day_index = date('w', $timestamp);
    $d = date('j', $timestamp);
    $m = (int)date('n', $timestamp);
    $y = date('Y', $timestamp);
    return $hari[$day_index] . ', ' . $d . ' ' . $bulan[$m] . ' ' . $y;
}
?>
