<?php
require_once '../../../backend/koneksi.php';
require_once '../../../backend/auth_check.php';

// Proteksi ketat: Hanya Petugas yang boleh mengakses
check_petugas();

$page_title = 'Dashboard Operasional Petugas - WartaSehat.id';
$current_page = 'dashboard';
$breadcrumb_parent = 'Petugas';
$breadcrumb_current = 'Dashboard';
$breadcrumb_parent_url = 'dashboard.php';

// -----------------------------------------------------------------------------
// 1. KPI Metrics Query Riil dari Database
// -----------------------------------------------------------------------------
// Total Seluruh Pasien Terdaftar
$q_total_pasien = mysqli_query($connection, "SELECT COUNT(*) AS total FROM pasien");
$total_pasien = mysqli_fetch_assoc($q_total_pasien)['total'] ?? 0;

// Total Antrean Hari Ini
$q_antrean_hari_ini = mysqli_query($connection, "SELECT COUNT(*) AS total FROM layanan WHERE DATE(waktu_masuk) = CURDATE()");
$total_antrean = mysqli_fetch_assoc($q_antrean_hari_ini)['total'] ?? 0;

// Pasien Menunggu Hari Ini
$q_menunggu = mysqli_query($connection, "SELECT COUNT(*) AS total FROM layanan WHERE DATE(waktu_masuk) = CURDATE() AND status_layanan = 'menunggu'");
$total_menunggu = mysqli_fetch_assoc($q_menunggu)['total'] ?? 0;

// Pasien Selesai Hari Ini
$q_selesai = mysqli_query($connection, "SELECT COUNT(*) AS total FROM layanan WHERE DATE(waktu_masuk) = CURDATE() AND status_layanan = 'selesai'");
$total_selesai = mysqli_fetch_assoc($q_selesai)['total'] ?? 0;

// -----------------------------------------------------------------------------
// 2. Filter & Pencarian Antrean Hari Ini
// -----------------------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$filter_poli = trim($_GET['poli'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$sql_antrean = "SELECT l.id_layanan, l.waktu_masuk, l.status_layanan, l.keluhan_awal, l.status_kondisi,
                       p.id_pasien, p.nama_pasien, p.nik, p.jenis_kelamin, p.tanggal_lahir,
                       rm.id_rekam_medik, rm.nomor_rm,
                       po.id_poli, po.nama_poli,
                       d.nama_dokter
                FROM layanan l
                JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik
                JOIN pasien p ON rm.id_pasien = p.id_pasien
                JOIN poli po ON l.id_poli = po.id_poli
                JOIN dokter d ON l.id_dokter = d.id_dokter
                WHERE DATE(l.waktu_masuk) = CURDATE()";

$params = [];
$types = "";

if (!empty($search)) {
    $sql_antrean .= " AND (p.nama_pasien LIKE ? OR p.nik LIKE ? OR rm.nomor_rm LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($filter_poli)) {
    $sql_antrean .= " AND po.nama_poli = ?";
    $params[] = $filter_poli;
    $types .= "s";
}

if (!empty($filter_status)) {
    $sql_antrean .= " AND l.status_layanan = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql_antrean .= " ORDER BY l.waktu_masuk ASC";

$stmt_antrean = mysqli_prepare($connection, $sql_antrean);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_antrean, $types, ...$params);
}
mysqli_stmt_execute($stmt_antrean);
$result_antrean = mysqli_stmt_get_result($stmt_antrean);

// Daftar Poli untuk dropdown filter
$daftar_poli = mysqli_query($connection, "SELECT * FROM poli ORDER BY nama_poli ASC");

// Include Header & Sidebar
include '../../includes/header.php';
include '../../includes/sidebar_petugas.php';
include '../../includes/topbar.php';
?>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'berhasil_daftar'): ?>
          <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-success">&check; Sukses</span>
              <div>
                <strong>Pendaftaran Berhasil!</strong> Pasien telah didaftarkan ke antrean poliklinik tujuan.
                <?= isset($_GET['nomor_rm']) ? 'No. RM: <code>' . htmlspecialchars($_GET['nomor_rm']) . '</code>' : ''; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'akses_ditolak'): ?>
          <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <strong>Akses Ditolak:</strong> Anda tidak memiliki wewenang untuk membuka portal dokter.
          </div>
        <?php endif; ?>

        <!-- Page Title & Greeting Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 ws-page-header">
          <div>
            <h1 class="h3 fw-bold mb-1">Dashboard Operasional Petugas</h1>
            <p class="text-muted mb-0">Pantau aktivitas pendaftaran pasien, alur antrean poli, dan status pelayanan hari ini.</p>
          </div>
          <div>
            <a href="pendaftaran-pasien.php" class="btn btn-primary">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
              </svg>
              <span>Daftarkan Pasien Baru</span>
            </a>
          </div>
        </div>

        <!-- 4 KPI Metrics Stats Grid -->
        <div class="row g-3 mb-4 ws-stats-grid">
          <!-- Stat 1: Total Pasien Terdaftar -->
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="ws-stat-card">
              <div class="ws-stat-icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                  <circle cx="9" cy="7" r="4"/>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
              </div>
              <div class="ws-stat-info">
                <div class="ws-stat-label">Total Pasien Terdaftar</div>
                <div class="ws-stat-value"><?= number_format($total_pasien); ?></div>
                <div class="ws-stat-desc">Di database master klinik</div>
              </div>
            </div>
          </div>

          <!-- Stat 2: Antrean Hari Ini -->
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="ws-stat-card variant-secondary">
              <div class="ws-stat-icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
              </div>
              <div class="ws-stat-info">
                <div class="ws-stat-label">Total Antrean Hari Ini</div>
                <div class="ws-stat-value"><?= number_format($total_antrean); ?></div>
                <div class="ws-stat-desc">Kunjungan terdaftar</div>
              </div>
            </div>
          </div>

          <!-- Stat 3: Pasien Menunggu -->
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="ws-stat-card variant-warning">
              <div class="ws-stat-icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <line x1="12" y1="8" x2="12" y2="12"/>
                  <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </div>
              <div class="ws-stat-info">
                <div class="ws-stat-label">Sedang Menunggu</div>
                <div class="ws-stat-value"><?= number_format($total_menunggu); ?></div>
                <div class="ws-stat-desc">Dalam ruang tunggu poli</div>
              </div>
            </div>
          </div>

          <!-- Stat 4: Kunjungan Selesai -->
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="ws-stat-card variant-success">
              <div class="ws-stat-icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                  <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
              </div>
              <div class="ws-stat-info">
                <div class="ws-stat-label">Pemeriksaan Selesai</div>
                <div class="ws-stat-value"><?= number_format($total_selesai); ?></div>
                <div class="ws-stat-desc">Rekam medik tersimpan</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="ws-card mb-4">
          <div class="ws-card-body p-3">
            <form action="dashboard.php" method="GET" class="d-flex gap-3 align-items-center flex-wrap ws-filter-bar">
              <div class="input-group" style="flex: 2; min-width: 240px;">
                <span class="input-group-text bg-white">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                  </svg>
                </span>
                <input 
                  type="text" 
                  class="form-control" 
                  name="search" 
                  placeholder="Cari No. RM, NIK, atau Nama Pasien..." 
                  value="<?= htmlspecialchars($search); ?>"
                >
              </div>

              <div style="flex: 1; min-width: 170px;">
                <select name="poli" class="form-select">
                  <option value="">Semua Poliklinik</option>
                  <?php while ($p = mysqli_fetch_assoc($daftar_poli)): ?>
                    <option value="<?= htmlspecialchars($p['nama_poli']); ?>" <?= ($filter_poli === $p['nama_poli']) ? 'selected' : ''; ?>>
                      <?= htmlspecialchars($p['nama_poli']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div style="flex: 1; min-width: 170px;">
                <select name="status" class="form-select">
                  <option value="">Semua Status Antrean</option>
                  <option value="menunggu" <?= ($filter_status === 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                  <option value="diperiksa" <?= ($filter_status === 'diperiksa') ? 'selected' : ''; ?>>Sedang Diperiksa</option>
                  <option value="selesai" <?= ($filter_status === 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                </select>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  Filter
                </button>
                <a href="dashboard.php" class="btn btn-secondary" title="Reset Filter">
                  Reset
                </a>
              </div>
            </form>
          </div>
        </div>

        <!-- Table: Antrean Hari Ini -->
        <div class="ws-card mb-4">
          <div class="ws-card-header">
            <div>
              <h2 class="ws-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
                Daftar Antrean & Kunjungan Pasien Hari Ini
              </h2>
              <div class="ws-card-subtitle">Menampilkan seluruh pasien terdaftar pada <?= format_tanggal_indo(); ?></div>
            </div>
            <div class="ws-card-actions">
              <span class="badge badge-primary"><?= mysqli_num_rows($result_antrean); ?> Kunjungan</span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table ws-table align-middle">
              <thead>
                <tr>
                  <th style="width: 70px;">No.</th>
                  <th style="width: 100px;">Waktu</th>
                  <th>No. Rekam Medis</th>
                  <th>Nama Lengkap Pasien</th>
                  <th>Poliklinik Tujuan</th>
                  <th>Dokter Pemeriksa</th>
                  <th>Keluhan Awal</th>
                  <th>Status</th>
                  <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($result_antrean) > 0): ?>
                  <?php $no = 1; while ($row = mysqli_fetch_assoc($result_antrean)): ?>
                    <tr>
                      <td class="fw-bold text-muted">#<?= $no++; ?></td>
                      <td class="text-muted small font-monospace"><?= date('H:i', strtotime($row['waktu_masuk'])); ?> WIB</td>
                      <td>
                        <a href="detail-pasien.php?id=<?= $row['id_pasien']; ?>" class="cell-code fw-bold">
                          <?= htmlspecialchars($row['nomor_rm'] ?? 'RM-' . $row['id_rekam_medik']); ?>
                        </a>
                      </td>
                      <td>
                        <div class="fw-bold"><?= htmlspecialchars($row['nama_pasien']); ?></div>
                        <div class="text-muted small font-monospace">NIK: <?= htmlspecialchars($row['nik']); ?></div>
                      </td>
                      <td>
                        <span class="badge badge-secondary"><?= htmlspecialchars($row['nama_poli']); ?></span>
                      </td>
                      <td><?= htmlspecialchars($row['nama_dokter']); ?></td>
                      <td class="small text-muted" style="max-width: 200px;">
                        <?= htmlspecialchars(mb_strimwidth($row['keluhan_awal'], 0, 45, '...')); ?>
                      </td>
                      <td>
                        <?php if ($row['status_layanan'] === 'menunggu'): ?>
                          <span class="badge badge-warning">
                            <span class="status-dot"></span> Menunggu
                          </span>
                        <?php elseif ($row['status_layanan'] === 'diperiksa'): ?>
                          <span class="badge badge-primary">
                            <span class="status-dot"></span> Sedang Diperiksa
                          </span>
                        <?php else: ?>
                          <span class="badge badge-success">
                            &check; Selesai
                          </span>
                          <?php if (!empty($row['status_kondisi'])): ?>
                            <div class="small text-muted mt-1"><?= htmlspecialchars($row['status_kondisi']); ?></div>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                          <a href="detail-pasien.php?id=<?= $row['id_pasien']; ?>" class="btn btn-sm btn-secondary" title="Profil Pasien">
                            Profil
                          </a>
                          <a href="riwayat-rekam-medik.php?id=<?= $row['id_pasien']; ?>" class="btn btn-sm btn-outline-primary" title="Riwayat Rekam Medik">
                            RM
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-2 text-muted">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                      </svg>
                      <div class="fw-semibold">Tidak Ada Antrean Hari Ini</div>
                      <div class="small">Belum ada pasien yang terdaftar di antrean pada tanggal ini.</div>
                      <a href="pendaftaran-pasien.php" class="btn btn-sm btn-primary mt-3">Daftarkan Pasien Pertama</a>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>

<?php include '../../includes/footer.php'; ?>
