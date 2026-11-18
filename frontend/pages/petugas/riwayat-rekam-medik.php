<?php
require_once '../../../backend/koneksi.php';
require_once '../../../backend/auth_check.php';

check_petugas();

$id_pasien = (int)($_GET['id'] ?? 0);

if ($id_pasien <= 0) {
    header("Location: daftar-pasien.php");
    exit();
}

$page_title = 'Arsip Rekam Medik Pasien - WartaSehat.id';
$current_page = 'daftar_pasien';
$breadcrumb_parent = 'Data Pasien';
$breadcrumb_current = 'Arsip Rekam Medik';
$breadcrumb_parent_url = 'detail-pasien.php?id=' . $id_pasien;

// -----------------------------------------------------------------------------
// Ambil Identitas Pasien & RM
// -----------------------------------------------------------------------------
$stmt = mysqli_prepare($connection, "SELECT p.*, rm.id_rekam_medik, rm.nomor_rm 
                                     FROM pasien p 
                                     LEFT JOIN rekam_medik rm ON p.id_pasien = rm.id_pasien 
                                     WHERE p.id_pasien = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_pasien);
mysqli_stmt_execute($stmt);
$pasien = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pasien) {
    header("Location: daftar-pasien.php");
    exit();
}

// Hitung usia
$tgl_lahir = new DateTime($pasien['tanggal_lahir']);
$usia = $tgl_lahir->diff(new DateTime())->y;

// -----------------------------------------------------------------------------
// Filter Tanggal & Poli
// -----------------------------------------------------------------------------
$dari_tanggal = trim($_GET['dari_tanggal'] ?? '');
$sampai_tanggal = trim($_GET['sampai_tanggal'] ?? '');
$filter_poli = (int)($_GET['poli'] ?? 0);

$sql_riwayat = "SELECT l.*, po.nama_poli, d.nama_dokter 
                FROM layanan l 
                JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik 
                JOIN poli po ON l.id_poli = po.id_poli 
                JOIN dokter d ON l.id_dokter = d.id_dokter 
                WHERE rm.id_pasien = ?";

$params = [$id_pasien];
$types = "i";

if (!empty($dari_tanggal)) {
    $sql_riwayat .= " AND DATE(l.waktu_masuk) >= ?";
    $params[] = $dari_tanggal;
    $types .= "s";
}

if (!empty($sampai_tanggal)) {
    $sql_riwayat .= " AND DATE(l.waktu_masuk) <= ?";
    $params[] = $sampai_tanggal;
    $types .= "s";
}

if (!empty($filter_poli)) {
    $sql_riwayat .= " AND l.id_poli = ?";
    $params[] = $filter_poli;
    $types .= "i";
}

$sql_riwayat .= " ORDER BY l.waktu_masuk DESC";

$stmt_riwayat = mysqli_prepare($connection, $sql_riwayat);
mysqli_stmt_bind_param($stmt_riwayat, $types, ...$params);
mysqli_stmt_execute($stmt_riwayat);
$res_riwayat = mysqli_stmt_get_result($stmt_riwayat);
$total_kunjungan = mysqli_num_rows($res_riwayat);

$q_poli_list = mysqli_query($connection, "SELECT * FROM poli ORDER BY nama_poli ASC");

include '../../includes/header.php';
include '../../includes/sidebar_petugas.php';
include '../../includes/topbar.php';
?>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        
        <!-- Patient Header Banner -->
        <div class="ws-card mb-4 bg-white">
          <div class="ws-card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div class="d-flex align-items-center gap-3">
                <div class="ws-user-avatar" style="width: 56px; height: 56px; font-size: 1.35rem; background-color: var(--ws-primary-light); color: var(--ws-primary);">
                  <?= get_initials($pasien['nama_pasien']); ?>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="h4 fw-bold mb-0">Arsip Rekam Medik: <?= htmlspecialchars($pasien['nama_pasien']); ?></h1>
                    <span class="cell-code"><?= htmlspecialchars($pasien['nomor_rm'] ?? 'RM-' . $pasien['id_rekam_medik']); ?></span>
                  </div>
                  <div class="ws-identity-list">
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">NIK:</span>
                      <span class="ws-identity-val font-monospace"><?= htmlspecialchars($pasien['nik']); ?></span>
                    </div>
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">JK:</span>
                      <span class="ws-identity-val"><?= htmlspecialchars($pasien['jenis_kelamin']); ?></span>
                    </div>
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">Usia:</span>
                      <span class="ws-identity-val"><?= $usia; ?> Thn</span>
                    </div>
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">Gol Darah:</span>
                      <span class="ws-identity-val badge badge-secondary"><?= htmlspecialchars($pasien['golongan_darah'] ?? '-'); ?></span>
                    </div>
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">Total Riwayat:</span>
                      <span class="ws-identity-val fw-bold text-primary"><?= $total_kunjungan; ?> Kunjungan</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2">
                <a href="detail-pasien.php?id=<?= $id_pasien; ?>" class="btn btn-secondary">
                  &larr; Profil Pasien
                </a>
                <a href="pendaftaran-pasien.php?cek_nik=<?= $pasien['nik']; ?>" class="btn btn-primary">
                  &plus; Daftarkan Kunjungan Baru
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Riwayat Form -->
        <div class="ws-card mb-4">
          <div class="ws-card-body p-3">
            <form action="riwayat-rekam-medik.php" method="GET" class="d-flex gap-3 align-items-center flex-wrap ws-filter-bar">
              <input type="hidden" name="id" value="<?= $id_pasien; ?>">
              <div class="d-flex align-items-center gap-2" style="flex: 1; min-width: 200px;">
                <label for="dari_tanggal" class="form-label mb-0 small text-nowrap fw-bold">Dari:</label>
                <input type="date" class="form-control form-control-sm" id="dari_tanggal" name="dari_tanggal" value="<?= htmlspecialchars($dari_tanggal); ?>">
              </div>
              <div class="d-flex align-items-center gap-2" style="flex: 1; min-width: 200px;">
                <label for="sampai_tanggal" class="form-label mb-0 small text-nowrap fw-bold">Sampai:</label>
                <input type="date" class="form-control form-control-sm" id="sampai_tanggal" name="sampai_tanggal" value="<?= htmlspecialchars($sampai_tanggal); ?>">
              </div>
              <div style="flex: 1; min-width: 180px;">
                <select name="poli" class="form-select form-select-sm">
                  <option value="">Semua Poliklinik</option>
                  <?php while ($po = mysqli_fetch_assoc($q_poli_list)): ?>
                    <option value="<?= $po['id_poli']; ?>" <?= ($filter_poli == $po['id_poli']) ? 'selected' : ''; ?>>
                      <?= htmlspecialchars($po['nama_poli']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Filter Riwayat</button>
                <a href="riwayat-rekam-medik.php?id=<?= $id_pasien; ?>" class="btn btn-sm btn-secondary">Reset</a>
              </div>
            </form>
          </div>
        </div>

        <!-- Timeline of Medical Records (Accordion Style) -->
        <div class="d-flex flex-column gap-3">
          <?php if ($total_kunjungan > 0): ?>
            <?php $counter = $total_kunjungan; while ($rm = mysqli_fetch_assoc($res_riwayat)): ?>
              <details class="ws-encounter-accordion <?= ($counter === $total_kunjungan) ? 'is-active' : ''; ?>" <?= ($counter === $total_kunjungan) ? 'open' : ''; ?>>
                <summary class="ws-encounter-summary">
                  <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="badge badge-primary fs-6 fw-bold">Kunjungan #<?= $counter--; ?></span>
                    <div>
                      <h2 class="h5 fw-bold mb-0"><?= format_tanggal_indo($rm['waktu_masuk']); ?> &bull; <?= date('H:i', strtotime($rm['waktu_masuk'])); ?> WIB</h2>
                      <div class="text-muted small"><?= htmlspecialchars($rm['nama_poli']); ?> &bull; <?= htmlspecialchars($rm['nama_dokter']); ?></div>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <?php if ($rm['status_layanan'] === 'selesai'): ?>
                      <span class="badge badge-success">&check; Selesai <?= !empty($rm['status_kondisi']) ? '('.htmlspecialchars($rm['status_kondisi']).')' : ''; ?></span>
                    <?php elseif ($rm['status_layanan'] === 'diperiksa'): ?>
                      <span class="badge badge-primary"><span class="status-dot"></span> Sedang Diperiksa</span>
                    <?php else: ?>
                      <span class="badge badge-warning"><span class="status-dot"></span> Menunggu</span>
                    <?php endif; ?>
                    <span class="ws-accordion-chevron">&#9662;</span>
                  </div>
                </summary>

                <div class="ws-encounter-body border-top p-4 bg-white">
                  <!-- Keluhan Awal Pasien -->
                  <div class="mb-3 p-3 rounded bg-light border">
                    <strong class="text-primary d-block mb-1">Keluhan Awal Saat Registrasi:</strong>
                    <div><?= nl2br(htmlspecialchars($rm['keluhan_awal'])); ?></div>
                  </div>

                  <!-- 1. Pemeriksaan Awal -->
                  <?php if (!empty($rm['pemeriksaan_awal'])): ?>
                    <div class="mb-3">
                      <div class="text-uppercase small fw-bold text-primary mb-1">1. Pemeriksaan Awal (Tanda Vital & Temuan Fisik)</div>
                      <div class="p-3 rounded border font-monospace small bg-light">
                        <?= nl2br(htmlspecialchars($rm['pemeriksaan_awal'])); ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- 2. Diagnosis Primer & Sekunder -->
                  <?php if (!empty($rm['diagnosis_primer'])): ?>
                    <div class="mb-3">
                      <div class="text-uppercase small fw-bold text-primary mb-1">2. Diagnosis Dokter</div>
                      <div class="p-3 rounded border bg-light">
                        <div><strong>Diagnosis Primer:</strong> <span class="badge badge-danger font-monospace fs-6"><?= htmlspecialchars($rm['diagnosis_primer']); ?></span></div>
                        <?php if (!empty($rm['diagnosis_sekunder'])): ?>
                          <div class="mt-2"><strong>Diagnosis Sekunder:</strong> <span class="badge badge-secondary font-monospace"><?= htmlspecialchars($rm['diagnosis_sekunder']); ?></span></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- 3. Tindakan Medis -->
                  <?php if (!empty($rm['tindakan'])): ?>
                    <div class="mb-3">
                      <div class="text-uppercase small fw-bold text-primary mb-1">3. Tindakan / Prosedur Medis</div>
                      <div class="p-3 rounded border bg-light">
                        <?= nl2br(htmlspecialchars($rm['tindakan'])); ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- 4. Pemeriksaan Akhir -->
                  <?php if (!empty($rm['pemeriksaan_akhir'])): ?>
                    <div class="mb-3">
                      <div class="text-uppercase small fw-bold text-primary mb-1">4. Evaluasi Pemeriksaan Akhir</div>
                      <div class="p-3 rounded border bg-light">
                        <?= nl2br(htmlspecialchars($rm['pemeriksaan_akhir'])); ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- 5. Resep & Catatan Dokter -->
                  <?php if (!empty($rm['catatan_dokter'])): ?>
                    <div class="mb-0">
                      <div class="text-uppercase small fw-bold text-primary mb-1">5. Terapi Farmasi / Catatan Dokter</div>
                      <div class="p-3 rounded border bg-light font-monospace small">
                        <?= nl2br(htmlspecialchars($rm['catatan_dokter'])); ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($rm['status_layanan'] !== 'selesai'): ?>
                    <div class="alert alert-info mt-3 mb-0 py-2 small">
                      Pemeriksaan untuk kunjungan ini sedang berlangsung atau menunggu giliran dokter.
                    </div>
                  <?php endif; ?>
                </div>
              </details>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="ws-card p-5 text-center text-muted">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-3 text-muted">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
              <h3 class="h5 fw-bold text-dark">Belum Ada Riwayat Kunjungan</h3>
              <p class="small text-muted mb-3">Pasien ini belum pernah melakukan pemeriksaan di poliklinik atau filter tanggal tidak menemukan data.</p>
              <a href="pendaftaran-pasien.php?cek_nik=<?= $pasien['nik']; ?>" class="btn btn-primary btn-sm">Daftarkan Kunjungan Pertama</a>
            </div>
          <?php endif; ?>
        </div>

      </main>

<?php include '../../includes/footer.php'; ?>
