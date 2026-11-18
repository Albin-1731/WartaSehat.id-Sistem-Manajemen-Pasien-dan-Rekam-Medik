<?php
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$parent_title = isset($breadcrumb_parent) ? $breadcrumb_parent : ($role === 'dokter' ? 'Portal Dokter' : 'Petugas');
$current_title = isset($breadcrumb_current) ? $breadcrumb_current : 'Dashboard';
$parent_url = isset($breadcrumb_parent_url) ? $breadcrumb_parent_url : 'dashboard.php';
$tanggal_sekarang = function_exists('format_tanggal_indo') ? format_tanggal_indo() : date('l, d F Y');
?>
    <!-- Main Content Wrapper -->
    <div class="ws-main-wrapper">
      <!-- Topbar Header -->
      <header class="ws-topbar">
        <div class="ws-topbar-left">
          <label for="sidebarToggle" class="sidebar-toggle-btn" aria-label="Buka Menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="3" y1="12" x2="21" y2="12"/>
              <line x1="3" y1="6" x2="21" y2="6"/>
              <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
          </label>
          <nav class="ws-breadcrumb-nav" aria-label="Breadcrumb">
            <a href="<?= $parent_url; ?>" class="ws-parent"><?= htmlspecialchars($parent_title); ?></a>
            <span class="ws-separator">/</span>
            <span class="ws-current"><?= htmlspecialchars($current_title); ?></span>
          </nav>
        </div>

        <div class="ws-topbar-right">
          <?php if ($role === 'dokter' && isset($_SESSION['nama_poli'])): ?>
            <span class="badge badge-primary px-3 py-2 fw-semibold">
              <?= htmlspecialchars($_SESSION['nama_poli']); ?>
            </span>
          <?php endif; ?>

          <div class="ws-system-date">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span><?= $tanggal_sekarang; ?></span>
          </div>
        </div>
      </header>
