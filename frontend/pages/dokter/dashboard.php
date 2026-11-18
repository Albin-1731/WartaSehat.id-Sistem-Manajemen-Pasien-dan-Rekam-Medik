<?php
require_once '../../../backend/koneksi.php';
require_once '../../../backend/auth_check.php';

// Proteksi ketat: Hanya Dokter yang boleh mengakses
check_dokter();

$page_title = 'Dashboard Dokter Pemeriksa - WartaSehat.id';
$current_page = 'dashboard';
$breadcrumb_parent = 'Portal Dokter';
$breadcrumb_current = 'Dashboard Pemeriksaan';
$breadcrumb_parent_url = 'dashboard.php';

$id_dokter = $_SESSION['user_id'];
$id_poli = $_SESSION['id_poli'] ?? 1;
$nama_dokter = $_SESSION['nama'] ?? 'Dokter';
$nama_poli = $_SESSION['nama_poli'] ?? 'Poliklinik';

// -----------------------------------------------------------------------------
// 1. KPI Metrics Hari Ini untuk Poliklinik Dokter Ini
// -----------------------------------------------------------------------------
// Pasien Menunggu untuk Dokter Ini Hari Ini
$q_menunggu = mysqli_query($connection, "SELECT COUNT(*) AS total FROM layanan WHERE id_dokter = $id_dokter AND DATE(waktu_masuk) = CURDATE() AND status_layanan = 'menunggu'");
$total_menunggu = mysqli_fetch_assoc($q_menunggu)['total'] ?? 0;

// Pasien Sedang Diperiksa oleh Dokter Ini Hari Ini
$q_diperiksa = mysqli_query($connection, "SELECT COUNT(*) AS total FROM layanan WHERE id_dokter = $id_dokter AND DATE(waktu_masuk) = CURDATE() AND status_layanan = 'diperiksa'");
$total_diperiksa = mysqli_fetch_assoc($q_diperiksa)['total'] ?? 0;

// Pasien Selesai oleh Dokter Ini Hari Ini
$q_selesai = mysqli_query($connection, "SELECT COUNT(*) AS total FROM layanan WHERE id_dokter = $id_dokter AND DATE(waktu_masuk) = CURDATE() AND status_layanan = 'selesai'");
$total_selesai = mysqli_fetch_assoc($q_selesai)['total'] ?? 0;

// Total Antrean Hari Ini untuk Dokter Ini
$total_antrean_poli = $total_menunggu + $total_diperiksa + $total_selesai;

// -----------------------------------------------------------------------------
// 2. Spotlight Pasien Aktif di Ruang Periksa (Status 'diperiksa')
// -----------------------------------------------------------------------------
$q_aktif = mysqli_query($connection, "SELECT l.id_layanan, l.waktu_masuk, l.keluhan_awal,
                                            p.id_pasien, p.nama_pasien, p.nik, p.jenis_kelamin, p.tanggal_lahir,
                                            rm.nomor_rm, rm.id_rekam_medik
                                     FROM layanan l
                                     JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik
                                     JOIN pasien p ON rm.id_pasien = p.id_pasien
                                     WHERE l.id_dokter = $id_dokter 
                                       AND DATE(l.waktu_masuk) = CURDATE() 
                                       AND l.status_layanan = 'diperiksa'
                                     ORDER BY l.waktu_masuk ASC LIMIT 1");
$pasien_aktif = mysqli_fetch_assoc($q_aktif);

// -----------------------------------------------------------------------------
// 3. Daftar Antrean Poliklinik Hari Ini
// -----------------------------------------------------------------------------
$sql_antrean = "SELECT l.id_layanan, l.waktu_masuk, l.status_layanan, l.keluhan_awal, l.diagnosis_primer, l.status_kondisi,
                       p.id_pasien, p.nama_pasien, p.nik, p.jenis_kelamin, p.tanggal_lahir,
                       rm.nomor_rm, rm.id_rekam_medik
                FROM layanan l
                JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik
                JOIN pasien p ON rm.id_pasien = p.id_pasien
                WHERE l.id_dokter = ? AND DATE(l.waktu_masuk) = CURDATE()
                ORDER BY FIELD(l.status_layanan, 'diperiksa', 'menunggu', 'selesai'), l.waktu_masuk ASC";

$stmt_antrean = mysqli_prepare($connection, $sql_antrean);
mysqli_stmt_bind_param($stmt_antrean, "i", $id_dokter);
mysqli_stmt_execute($stmt_antrean);
$result_antrean = mysqli_stmt_get_result($stmt_antrean);

include '../../includes/header.php';
include '../../includes/sidebar_dokter.php';
include '../../includes/topbar.php';
?>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'pemeriksaan_selesai'): ?>
          <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-success">&check; Selesai</span>
              <div>
                <strong>Pemeriksaan Pasien Berhasil Disimpan!</strong> Rekam medis elektronik telah diperbarui dan status kunjungan diselesaikan.
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'akses_ditolak'): ?>
          <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <strong>Akses Ditolak:</strong> Anda tidak memiliki wewenang untuk membuka portal petugas administrasi.
          </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 ws-page-header">
          <div>
            <h1 class="h3 fw-bold mb-1">Dashboard Dokter Pemeriksa</h1>
            <p class="text-muted mb-0">Selamat bertugas, <strong><?= htmlspecialchars($nama_dokter); ?></strong> di <strong><?= htmlspecialchars($nama_poli); ?></strong>.</p>
          </div>
          <div class="ws-header-actions">
            <a href="antrean-poli.php" class="btn btn-secondary">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
              Kelola Antrean Poli
            </a>
          </div>
        </div>

        <!-- 4 KPI Metrics Stats Grid Khusus Poli Dokter -->
        <div class="row g-3 mb-4 ws-stats-grid">
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="ws-stat-card variant-warning">
              <div class="ws-stat-icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
              </div>
              <div class="ws-stat-info">
                <div class="ws-stat-label">Pasien Menunggu</div>
                <div class="ws-stat-value"><?= number_format($total_menunggu); ?></div>
                <div class="ws-stat-desc">Di ruang tunggu <?= htmlspecialchars($nama_poli); ?></div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="ws-stat-card">
              <div class="ws-stat-icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
              </div>
              <div class="ws-stat-info">
                <div class="ws-stat-label">Sedang Diperiksa</div>
                <div class="ws-stat-value"><?= number_format($total_diperiksa); ?></div>
                <div class="ws-stat-desc">Di dalam ruang periksa</div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="ws-stat-card variant-success">
              <div class="ws-stat-icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                  <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
              </div>
              <div class="ws-stat-info">
                <div class="ws-stat-label">Selesai Diperiksa</div>
                <div class="ws-stat-value"><?= number_format($total_selesai); ?></div>
                <div class="ws-stat-desc">Hari ini telah tuntas</div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="ws-stat-card variant-secondary">
              <div class="ws-stat-icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                  <circle cx="9" cy="7" r="4"/>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
              </div>
              <div class="ws-stat-info">
                <div class="ws-stat-label">Total Antrean Poli</div>
                <div class="ws-stat-value"><?= number_format($total_antrean_poli); ?></div>
                <div class="ws-stat-desc">Kunjungan poliklinik</div>
              </div>
            </div>
          </div>
        </div>

        <!-- SPOTLIGHT PASIEN AKTIF DI RUANG PERIKSA -->
        <?php if ($pasien_aktif): ?>
          <?php 
            $usia_aktif = (new DateTime($pasien_aktif['tanggal_lahir']))->diff(new DateTime())->y;
          ?>
          <div class="ws-card mb-4 border-2 border-primary bg-primary-subtle p-1">
            <div class="ws-card-body p-4 bg-white rounded">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                  <div class="ws-user-avatar" style="width: 56px; height: 56px; font-size: 1.35rem; background-color: var(--ws-primary-light); color: var(--ws-primary);">
                    <?= get_initials($pasien_aktif['nama_pasien']); ?>
                  </div>
                  <div>
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge badge-primary"><span class="status-dot"></span> Sedang Di Dalam Ruang Periksa</span>
                      <span class="cell-code font-monospace fw-bold"><?= htmlspecialchars($pasien_aktif['nomor_rm'] ?? 'RM-' . $pasien_aktif['id_rekam_medik']); ?></span>
                    </div>
                    <h2 class="h4 fw-bold mb-1 mt-1"><?= htmlspecialchars($pasien_aktif['nama_pasien']); ?></h2>
                    <div class="small text-muted">
                      NIK: <span class="font-monospace"><?= htmlspecialchars($pasien_aktif['nik']); ?></span> &bull; 
                      <?= htmlspecialchars($pasien_aktif['jenis_kelamin']); ?> (<?= $usia_aktif; ?> Tahun) &bull; 
                      Masuk: <?= date('H:i', strtotime($pasien_aktif['waktu_masuk'])); ?> WIB
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <a href="detail-pemeriksaan.php?id_layanan=<?= $pasien_aktif['id_layanan']; ?>" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                    <span>Lanjutkan Pemeriksaan Medis</span>
                  </a>
                  <a href="riwayat-rekam-medik.php?id=<?= $pasien_aktif['id_pasien']; ?>" class="btn btn-outline-primary">
                    Buka Riwayat RM
                  </a>
                </div>
              </div>

              <div class="mt-3 p-3 rounded bg-light border">
                <strong class="text-primary d-block mb-1">Keluhan Awal:</strong>
                <div class="text-secondary-emphasis">"<?= htmlspecialchars($pasien_aktif['keluhan_awal']); ?>"</div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="ws-card mb-4 bg-light p-3 border-dashed text-center">
            <span class="text-muted small">
              Saat ini tidak ada pasien di ruang periksa. Silakan pilih pasien berstatus <strong>Menunggu</strong> pada tabel antrean di bawah untuk memulai pemeriksaan.
            </span>
          </div>
        <?php endif; ?>

        <!-- Tabel Antrean Poliklinik Hari Ini -->
        <div class="ws-card">
          <div class="ws-card-header">
            <div>
              <h2 class="ws-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
                Antrean Pasien <?= htmlspecialchars($nama_poli); ?> (Hari Ini)
              </h2>
              <div class="ws-card-subtitle">Menampilkan seluruh antrean poliklinik per <?= format_tanggal_indo(); ?></div>
            </div>
            <span class="badge badge-primary"><?= mysqli_num_rows($result_antrean); ?> Pasien</span>
          </div>

          <div class="table-responsive">
            <table class="table ws-table align-middle">
              <thead>
                <tr>
                  <th style="width: 70px;">No.</th>
                  <th style="width: 100px;">Waktu</th>
                  <th>No. RM</th>
                  <th>Nama Lengkap Pasien</th>
                  <th>Usia / JK</th>
                  <th>Keluhan Awal</th>
                  <th>Status Antrean</th>
                  <th class="text-center" style="width: 200px;">Aksi Medis</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($result_antrean) > 0): ?>
                  <?php $no = 1; while ($row = mysqli_fetch_assoc($result_antrean)): ?>
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
                              Mulai Periksa
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
                      <div class="fw-semibold">Belum Ada Antrean di <?= htmlspecialchars($nama_poli); ?> Hari Ini</div>
                      <div class="small">Pasien yang didaftarkan petugas loket akan otomatis muncul di sini.</div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>

<?php include '../../includes/footer.php'; ?>
