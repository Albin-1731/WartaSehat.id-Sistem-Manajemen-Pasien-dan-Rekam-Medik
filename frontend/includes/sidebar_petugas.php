<?php
if (!isset($current_page)) {
    $current_page = '';
}
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Petugas Administrasi';
$user_initials = function_exists('get_initials') ? get_initials($user_name) : 'PA';
?>
    <!-- Sidebar Navigation Petugas -->
    <aside class="ws-sidebar">
      <div class="ws-sidebar-brand">
        <div class="ws-brand-logo">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 6v12M6 12h12"/>
            <circle cx="12" cy="12" r="10" stroke-width="1.8"/>
          </svg>
        </div>
        <div class="ws-brand-text">
          <div class="ws-brand-title">WartaSehat<span>.id</span></div>
          <div class="ws-brand-badge">Petugas Administrasi</div>
        </div>
      </div>

      <ul class="ws-sidebar-menu">
        <li class="ws-menu-header">Menu Utama</li>
        
        <li class="ws-menu-item">
          <a href="dashboard.php" class="ws-menu-link <?= ($current_page === 'dashboard') ? 'active' : ''; ?>">
            <span class="ws-menu-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
              </svg>
            </span>
            <span>Dashboard</span>
          </a>
        </li>

        <li class="ws-menu-item">
          <a href="pendaftaran-pasien.php" class="ws-menu-link <?= ($current_page === 'pendaftaran') ? 'active' : ''; ?>">
            <span class="ws-menu-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
              </svg>
            </span>
            <span>Pendaftaran Pasien</span>
          </a>
        </li>

        <li class="ws-menu-item">
          <a href="daftar-pasien.php" class="ws-menu-link <?= ($current_page === 'daftar_pasien') ? 'active' : ''; ?>">
            <span class="ws-menu-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </span>
            <span>Data Pasien</span>
          </a>
        </li>
      </ul>

      <div class="ws-sidebar-footer">
        <div class="ws-user-card">
          <div class="ws-user-avatar"><?= htmlspecialchars($user_initials); ?></div>
          <div class="ws-user-details">
            <div class="ws-user-name"><?= htmlspecialchars($user_name); ?></div>
            <span class="ws-user-role">Petugas Pendaftaran</span>
          </div>
        </div>
        <a href="../auth/logout.php" class="ws-logout-btn" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
          Keluar Sistem
        </a>
      </div>
    </aside>
