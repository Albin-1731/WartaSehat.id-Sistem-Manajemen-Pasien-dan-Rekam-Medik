<?php
require_once '../../backend/koneksi.php';
require_once '../../backend/auth_check.php';

check_dokter();

$page_title = 'Manajemen Antrean Poliklinik - WartaSehat.id';
$current_page = 'antrean_poli';
$breadcrumb_parent = 'Portal Dokter';
$breadcrumb_current = 'Antrean Poliklinik';
$breadcrumb_parent_url = 'dashboard.php';

$id_dokter = $_SESSION['user_id'];
$id_poli = $_SESSION['id_poli'] ?? 1;
$nama_poli = $_SESSION['nama_poli'] ?? 'Poliklinik';

// -----------------------------------------------------------------------------
// Hitung Tab Kategori Khusus Dokter yang Sedang Login
// -----------------------------------------------------------------------------
$q_count = mysqli_query($connection, "SELECT 
    COUNT(*) AS total_all,
    SUM(CASE WHEN status_layanan = 'diperiksa' THEN 1 ELSE 0 END) AS total_diperiksa,
    SUM(CASE WHEN status_layanan = 'menunggu' THEN 1 ELSE 0 END) AS total_menunggu,
    SUM(CASE WHEN status_layanan = 'selesai' THEN 1 ELSE 0 END) AS total_selesai
  FROM layanan 
  WHERE id_dokter = $id_dokter AND DATE(waktu_masuk) = CURDATE()");
$counts = mysqli_fetch_assoc($q_count);

$count_all = $counts['total_all'] ?? 0;
$count_diperiksa = $counts['total_diperiksa'] ?? 0;
$count_menunggu = $counts['total_menunggu'] ?? 0;
$count_selesai = $counts['total_selesai'] ?? 0;

// -----------------------------------------------------------------------------
// Filter & Pencarian
// -----------------------------------------------------------------------------
$tab = trim($_GET['tab'] ?? 'semua');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT l.id_layanan, l.waktu_masuk, l.status_layanan, l.keluhan_awal, l.diagnosis_primer, l.status_kondisi,
               p.id_pasien, p.nama_pasien, p.nik, p.jenis_kelamin, p.tanggal_lahir,
               rm.nomor_rm, rm.id_rekam_medik
        FROM layanan l
        JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik
        JOIN pasien p ON rm.id_pasien = p.id_pasien
        WHERE l.id_dokter = ? AND DATE(l.waktu_masuk) = CURDATE()";

$params = [$id_dokter];
$types = "i";

if ($tab === 'diperiksa') {
    $sql .= " AND l.status_layanan = 'diperiksa'";
} elseif ($tab === 'menunggu') {
    $sql .= " AND l.status_layanan = 'menunggu'";
} elseif ($tab === 'selesai') {
    $sql .= " AND l.status_layanan = 'selesai'";
}

if (!empty($search)) {
    $sql .= " AND (p.nama_pasien LIKE ? OR p.nik LIKE ? OR rm.nomor_rm LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY FIELD(l.status_layanan, 'diperiksa', 'menunggu', 'selesai'), l.waktu_masuk ASC";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include '../../includes/header.php';
include '../../includes/sidebar_dokter.php';
include '../../includes/topbar.php';
?>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'bukan_pasien_anda'): ?>
          <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <strong>Akses Dibatasi:</strong> Pasien tersebut ditujukan untuk dokter pemeriksa yang berbeda di poliklinik ini.
          </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4 ws-page-header">
          <div>
            <h1 class="h3 fw-bold mb-1">Manajemen Antrean Poliklinik</h1>
            <p class="text-muted mb-0">Kelola dan panggil pasien yang sedang mengantre di <strong><?= htmlspecialchars($nama_poli); ?></strong>.</p>
          </div>
          <div>
            <a href="dashboard.php" class="btn btn-secondary">
              &larr; Kembali ke Dashboard
            </a>
          </div>
        </div>

        <!-- Filter Tab Kategori Antrean -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
          <a href="antrean-poli.php?tab=semua&search=<?= urlencode($search); ?>" 
             class="btn btn-sm <?= ($tab === 'semua') ? 'btn-primary' : 'btn-secondary'; ?>">
            Semua Antrean (<?= $count_all; ?>)
          </a>
          <a href="antrean-poli.php?tab=diperiksa&search=<?= urlencode($search); ?>" 
             class="btn btn-sm <?= ($tab === 'diperiksa') ? 'btn-primary' : 'btn-secondary'; ?>">
            Sedang Diperiksa (<?= $count_diperiksa; ?>)
          </a>
          <a href="antrean-poli.php?tab=menunggu&search=<?= urlencode($search); ?>" 
             class="btn btn-sm <?= ($tab === 'menunggu') ? 'btn-primary' : 'btn-secondary'; ?>">
            Menunggu (<?= $count_menunggu; ?>)
          </a>
          <a href="antrean-poli.php?tab=selesai&search=<?= urlencode($search); ?>" 
             class="btn btn-sm <?= ($tab === 'selesai') ? 'btn-primary' : 'btn-secondary'; ?>">
            Selesai (<?= $count_selesai; ?>)
          </a>
        </div>

        <!-- Toolbar Pencarian -->
        <div class="ws-card mb-4">
          <div class="ws-card-body p-3">
            <form action="antrean-poli.php" method="GET" class="d-flex gap-3 align-items-center flex-wrap ws-filter-bar">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($tab); ?>">
              <div class="input-group" style="flex: 2; min-width: 250px;">
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

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="antrean-poli.php?tab=<?= htmlspecialchars($tab); ?>" class="btn btn-secondary">Reset</a>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabel Antrean Poliklinik Lengkap -->
        <div class="ws-card">
          <div class="ws-card-header">
            <div>
              <h2 class="ws-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
                Daftar Antrean Pasien (<?= ucfirst($tab); ?>)
              </h2>
            </div>
            <span class="badge badge-primary"><?= mysqli_num_rows($result); ?> Pasien</span>
          </div>

          <div class="table-responsive">
            <table class="table ws-table align-middle">
              <thead>
                <tr>
                  <th style="width: 70px;">No.</th>
                  <th style="width: 100px;">Waktu</th>
                  <th>No. Rekam Medis</th>
                  <th>Nama Lengkap Pasien</th>
                  <th>Usia / JK</th>
                  <th>Keluhan Awal</th>
                  <th>Status</th>
                  <th class="text-center" style="width: 220px;">Aksi Pemeriksaan</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                  <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php 
                      $usia = (new DateTime($row['tanggal_lahir']))->diff(new DateTime())->y;
                    ?>
                    <tr>
                      <td class="fw-bold text-muted">#<?= $no++; ?></td>
                      <td class="font-monospace text-muted small"><?= date('H:i', strtotime($row['waktu_masuk'])); ?> WIB</td>
                      <td>
                        <span class="cell-code fw-bold"><?= htmlspecialchars($row['nomor_rm'] ?? 'RM-' . $row['id_rekam_medik']); ?></span>
                      </td>
                      <td>
                        <div class="fw-bold fs-6"><?= htmlspecialchars($row['nama_pasien']); ?></div>
                        <div class="text-muted small font-monospace">NIK: <?= htmlspecialchars($row['nik']); ?></div>
                      </td>
                      <td>
                        <div><?= htmlspecialchars($row['jenis_kelamin']); ?></div>
                        <div class="small text-muted"><?= $usia; ?> Tahun</div>
                      </td>
                      <td class="small text-muted" style="max-width: 220px;">
                        <?= htmlspecialchars(mb_strimwidth($row['keluhan_awal'], 0, 45, '...')); ?>
                      </td>
                      <td>
                        <?php if ($row['status_layanan'] === 'diperiksa'): ?>
                          <span class="badge badge-primary"><span class="status-dot"></span> Sedang Diperiksa</span>
                        <?php elseif ($row['status_layanan'] === 'menunggu'): ?>
                          <span class="badge badge-warning"><span class="status-dot"></span> Menunggu</span>
                        <?php else: ?>
                          <span class="badge badge-success">&check; Selesai</span>
                          <?php if (!empty($row['status_kondisi'])): ?>
                            <div class="small text-muted mt-1"><?= htmlspecialchars($row['status_kondisi']); ?></div>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                          <?php if ($row['status_layanan'] === 'menunggu'): ?>
                            <a href="detail-pemeriksaan.php?id_layanan=<?= $row['id_layanan']; ?>" class="btn btn-sm btn-primary">
                              Panggil & Periksa
                            </a>
                          <?php elseif ($row['status_layanan'] === 'diperiksa'): ?>
                            <a href="detail-pemeriksaan.php?id_layanan=<?= $row['id_layanan']; ?>" class="btn btn-sm btn-warning fw-bold">
                              Lanjut Periksa
                            </a>
                          <?php else: ?>
                            <a href="detail-pemeriksaan.php?id_layanan=<?= $row['id_layanan']; ?>" class="btn btn-sm btn-secondary">
                              Lihat EMR
                            </a>
                          <?php endif; ?>

                          <a href="riwayat-rekam-medik.php?id=<?= $row['id_pasien']; ?>" class="btn btn-sm btn-outline-primary" title="Riwayat RM Masa Lalu">
                            RM
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                      <div class="fw-semibold">Tidak Ada Antrean pada Kategori Ini</div>
                      <div class="small">Silakan pilih kategori tab lain atau reset filter pencarian.</div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>

<?php include '../../includes/footer.php'; ?>
