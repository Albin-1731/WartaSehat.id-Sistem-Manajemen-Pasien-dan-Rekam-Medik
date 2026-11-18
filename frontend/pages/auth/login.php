<?php
session_start();

// Jika pengguna sudah login, arahkan langsung ke dashboard sesuai role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'petugas') {
        header("Location: ../petugas/dashboard.php");
        exit();
    } else if ($_SESSION['role'] === 'dokter') {
        header("Location: ../dokter/dashboard.php");
        exit();
    }
}

// Cek parameter pesan umpan balik
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk ke Sistem - WartaSehat.id</title>
  <!-- Bootstrap 5 CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Local Custom CSS -->
  <link rel="stylesheet" href="../../css/variables.css">
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="../../css/responsive.css">
</head>
<body class="ws-auth-wrapper">

  <div class="ws-auth-card">
    <div class="ws-auth-header">
      <div class="ws-auth-logo">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 6v12M6 12h12"/>
          <circle cx="12" cy="12" r="10" stroke-width="1.8"/>
        </svg>
      </div>
      <h1 class="ws-auth-title">WartaSehat<span>.id</span></h1>
      <p class="ws-auth-subtitle">Sistem Informasi Manajemen Pasien & Rekam Medik</p>
    </div>

    <!-- Alert Umpan Balik Sesi & Autentikasi -->
    <?php if ($pesan === 'gagal'): ?>
      <div class="alert alert-danger mb-3" role="alert">
        <div class="alert-title fw-bold">Autentikasi Gagal</div>
        <div class="small">Username atau kata sandi tidak sesuai. Silakan periksa kembali.</div>
      </div>
    <?php elseif ($pesan === 'belum_login'): ?>
      <div class="alert alert-warning mb-3" role="alert">
        <div class="alert-title fw-bold">Akses Terbatas</div>
        <div class="small">Silakan masuk dengan akun Anda terlebih dahulu.</div>
      </div>
    <?php elseif ($pesan === 'akses_ditolak'): ?>
      <div class="alert alert-danger mb-3" role="alert">
        <div class="alert-title fw-bold">Hak Akses Ditolak</div>
        <div class="small">Anda tidak memiliki hak untuk mengakses halaman tersebut.</div>
      </div>
    <?php elseif ($pesan === 'logout'): ?>
      <div class="alert alert-success mb-3" role="alert">
        <div class="alert-title fw-bold">Berhasil Keluar</div>
        <div class="small">Sesi Anda telah berhasil diakhiri. Sampai jumpa kembali!</div>
      </div>
    <?php endif; ?>

    <!-- Formulir Login Utama (POST ke login_process.php) -->
    <form action="login_process.php" method="POST" autocomplete="on">
      <div class="form-group mb-3">
        <label for="username" class="form-label">
          Username Pengguna <span class="required text-danger">*</span>
        </label>
        <div class="input-group">
          <span class="input-group-text">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <input 
            type="text" 
            class="form-control" 
            id="username" 
            name="username" 
            placeholder="Masukkan username Anda" 
            required 
            autofocus
            maxlength="50"
          >
        </div>
        <div class="form-text small text-muted">Contoh akun: <code>petugas_budi</code> atau <code>dr_hendra</code></div>
      </div>

      <div class="form-group mb-4">
        <label for="password" class="form-label">
          Kata Sandi <span class="required text-danger">*</span>
        </label>
        <div class="input-group">
          <span class="input-group-text">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input 
            type="password" 
            class="form-control" 
            id="password" 
            name="password" 
            placeholder="Masukkan kata sandi" 
            required 
            minlength="4"
            maxlength="100"
          >
        </div>
        <div class="form-text small text-muted">Password bawaan: <code>password123</code></div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
          <polyline points="10 17 15 12 10 7"/>
          <line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        Masuk ke Sistem
      </button>
    </form>

    <div class="text-center mt-4 pt-3 border-top">
      <a href="../../index.php" class="text-muted small text-decoration-none">
        &larr; Kembali ke Beranda
      </a>
      <div class="text-muted small mt-2">
        &copy; 2026 WartaSehat.id &bull; Versi 1.0 (Klinik & RS Pratama)
      </div>
    </div>
  </div>

</body>
</html>
