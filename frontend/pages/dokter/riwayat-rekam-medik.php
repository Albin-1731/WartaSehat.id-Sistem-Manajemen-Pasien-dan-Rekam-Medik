<?php
require_once '../../backend/koneksi.php';
require_once '../../backend/auth_check.php';

check_dokter();

$id_pasien = (int)($_GET['id'] ?? 0);

if ($id_pasien <= 0) {
    header("Location: antrean-poli.php");
    exit();
}

$page_title = 'Arsip Rekam Medik Pasien - WartaSehat.id';
$current_page = 'antrean_poli';
$breadcrumb_parent = 'Portal Dokter';
$breadcrumb_current = 'Arsip Rekam Medik Pasien';
$breadcrumb_parent_url = 'antrean-poli.php';

// Ambil Identitas Pasien
$stmt = mysqli_prepare($connection, "SELECT p.*, rm.id_rekam_medik, rm.nomor_rm 
                                     FROM pasien p 
                                     LEFT JOIN rekam_medik rm ON p.id_pasien = rm.id_pasien 
                                     WHERE p.id_pasien = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_pasien);
mysqli_stmt_execute($stmt);
$pasien = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pasien) {
    header("Location: antrean-poli.php");
    exit();
}

$tgl_lahir = new DateTime($pasien['tanggal_lahir']);
$usia = $tgl_lahir->diff(new DateTime())->y;

// Ambil Seluruh Riwayat Kunjungan Pasien
$stmt_riwayat = mysqli_prepare($connection, "SELECT l.*, po.nama_poli, d.nama_dokter 
                                            FROM layanan l 
                                            JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik 
                                            JOIN poli po ON l.id_poli = po.id_poli 
                                            JOIN dokter d ON l.id_dokter = d.id_dokter 
                                            WHERE rm.id_pasien = ? 
                                            ORDER BY l.waktu_masuk DESC");
mysqli_stmt_bind_param($stmt_riwayat, "i", $id_pasien);
mysqli_stmt_execute($stmt_riwayat);
$res_riwayat = mysqli_stmt_get_result($stmt_riwayat);
$total_kunjungan = mysqli_num_rows($res_riwayat);

include '../../includes/header.php';
include '../../includes/sidebar_dokter.php';
include '../../includes/topbar.php';
?>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        
        <!-- Patient Info Header Banner -->
        <div class="ws-card mb-4 bg-white border-2 border-primary-subtle">
          <div class="ws-card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div class="d-flex align-items-center gap-3">
                <div class="ws-user-avatar" style="width: 56px; height: 56px; font-size: 1.35rem; background-color: var(--ws-primary-light); color: var(--ws-primary);">
                  <?= get_initials($pasien['nama_pasien']); ?>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="h4 fw-bold mb-0">Arsip Riwayat Rekam Medis Pasien</h1>
                    <span class="cell-code"><?= htmlspecialchars($pasien['nomor_rm'] ?? 'RM-' . $pasien['id_rekam_medik']); ?></span>
                  </div>
                  <div class="ws-identity-list">
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">Nama:</span>
                      <span class="ws-identity-val fw-bold"><?= htmlspecialchars($pasien['nama_pasien']); ?></span>
                    </div>
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
                      <span class="ws-identity-val"><?= $usia; ?> Tahun</span>
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
                <a href="antrean-poli.php" class="btn btn-secondary">
                  &larr; Kembali ke Antrean
                </a>
              </div>
            </div>
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
                      <div class="text-muted small"><?= htmlspecialchars($rm['nama_poli']); ?> &bull; Dokter: <?= htmlspecialchars($rm['nama_dokter']); ?></div>
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
                  <!-- Keluhan Awal -->
                  <div class="mb-3 p-3 rounded bg-light border">
                    <strong class="text-primary d-block mb-1">Keluhan Awal:</strong>
                    <div><?= nl2br(htmlspecialchars($rm['keluhan_awal'])); ?></div>
                  </div>

                  <!-- 1. Pemeriksaan Awal -->
                  <?php if (!empty($rm['pemeriksaan_awal'])): ?>
                    <div class="mb-3">
                      <div class="text-uppercase small fw-bold text-primary mb-1">1. Pemeriksaan Awal</div>
                      <div class="p-3 rounded border font-monospace small bg-light">
                        <?= nl2br(htmlspecialchars($rm['pemeriksaan_awal'])); ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- 2. Diagnosis Primer & Sekunder -->
                  <?php if (!empty($rm['diagnosis_primer'])): ?>
                    <div class="mb-3">
                      <div class="text-uppercase small fw-bold text-primary mb-1">2. Diagnosis Medis</div>
                      <div class="p-3 rounded border bg-light">
                        <div><strong>Diagnosis Primer:</strong> <span class="badge badge-danger font-monospace fs-6"><?= htmlspecialchars($rm['diagnosis_primer']); ?></span></div>
                        <?php if (!empty($rm['diagnosis_sekunder'])): ?>
                          <div class="mt-2"><strong>Diagnosis Sekunder:</strong> <span class="badge badge-secondary font-monospace"><?= htmlspecialchars($rm['diagnosis_sekunder']); ?></span></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- 3. Tindakan -->
                  <?php if (!empty($rm['tindakan'])): ?>
                    <div class="mb-3">
                      <div class="text-uppercase small fw-bold text-primary mb-1">3. Tindakan / Prosedur Poliklinik</div>
                      <div class="p-3 rounded border bg-light">
                        <?= nl2br(htmlspecialchars($rm['tindakan'])); ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- 4. Pemeriksaan Akhir -->
                  <?php if (!empty($rm['pemeriksaan_akhir'])): ?>
                    <div class="mb-3">
                      <div class="text-uppercase small fw-bold text-primary mb-1">4. Pemeriksaan Akhir & Evaluasi</div>
                      <div class="p-3 rounded border bg-light">
                        <?= nl2br(htmlspecialchars($rm['pemeriksaan_akhir'])); ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- 5. Resep Obat & Catatan Dokter -->
                  <?php if (!empty($rm['catatan_dokter'])): ?>
                    <div class="mb-0">
                      <div class="text-uppercase small fw-bold text-primary mb-1">5. Resep Obat & Catatan Dokter</div>
                      <div class="p-3 rounded border bg-light font-monospace small">
                        <?= nl2br(htmlspecialchars($rm['catatan_dokter'])); ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </details>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="ws-card p-5 text-center text-muted">
              <h3 class="h5 fw-bold text-dark">Belum Ada Rekam Medik Terdahulu</h3>
              <p class="small text-muted mb-0">Pasien ini belum memiliki arsip kunjungan medis sebelumnya.</p>
            </div>
          <?php endif; ?>
        </div>

      </main>

<?php include '../../includes/footer.php'; ?>
