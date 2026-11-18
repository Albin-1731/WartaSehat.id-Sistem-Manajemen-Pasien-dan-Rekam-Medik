<?php
if (!isset($current_page)) {
    $current_page = '';
}
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Dokter Pemeriksa';
$user_poli = isset($_SESSION['nama_poli']) ? $_SESSION['nama_poli'] : 'Poliklinik';
$user_initials = function_exists('get_initials') ? get_initials($user_name) : 'DR';
?>
    <!-- Sidebar Navigation Dokter -->
    <aside class="ws-sidebar">
      <div class="ws-sidebar-brand">
        <div class="ws-brand-logo">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
          </svg>
        </div>
        <div class="ws-brand-text">
          <div class="ws-brand-title">WartaSehat<span>.id</span></div>
          <div class="ws-brand-badge">Portal Dokter Pemeriksa</div>
        </div>
      </div>

      <ul class="ws-sidebar-menu">
        <li class="ws-menu-header">Menu Dokter</li>
        
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
          <a href="antrean-poli.php" class="ws-menu-link <?= ($current_page === 'antrean_poli') ? 'active' : ''; ?>">
            <span class="ws-menu-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
            </span>
            <span>Antrean Poliklinik</span>
          </a>
        </li>
      </ul>

      <div class="ws-sidebar-footer">
        <div class="ws-user-card">
          <div class="ws-user-avatar" style="background-color: var(--ws-secondary-light); color: var(--ws-secondary);">
            <?= htmlspecialchars($user_initials); ?>
          </div>
          <div class="ws-user-details">
            <div class="ws-user-name"><?= htmlspecialchars($user_name); ?></div>
            <span class="ws-user-role"><?= htmlspecialchars($user_poli); ?></span>
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
