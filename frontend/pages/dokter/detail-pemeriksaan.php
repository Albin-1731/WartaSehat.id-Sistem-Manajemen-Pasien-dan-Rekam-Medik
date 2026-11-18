<?php
require_once '../../../backend/koneksi.php';
require_once '../../../backend/auth_check.php';

check_dokter();

$id_layanan = (int)($_GET['id_layanan'] ?? 0);

if ($id_layanan <= 0) {
    header("Location: antrean-poli.php");
    exit();
}

$id_dokter = $_SESSION['user_id'];
$id_poli = $_SESSION['id_poli'] ?? 1;

$error_msg = '';
$pesan = $_GET['pesan'] ?? '';

// -----------------------------------------------------------------------------
// 1. Ambil Data Kunjungan Layanan & Pasien
// -----------------------------------------------------------------------------
$stmt = mysqli_prepare($connection, "SELECT l.*, rm.id_rekam_medik, rm.nomor_rm,
                                           p.id_pasien, p.nama_pasien, p.nik, p.tanggal_lahir, p.jenis_kelamin, p.golongan_darah, p.no_telp,
                                           po.nama_poli, d.nama_dokter
                                    FROM layanan l
                                    JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik
                                    JOIN pasien p ON rm.id_pasien = p.id_pasien
                                    JOIN poli po ON l.id_poli = po.id_poli
                                    JOIN dokter d ON l.id_dokter = d.id_dokter
                                    WHERE l.id_layanan = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_layanan);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$kunjungan = mysqli_fetch_assoc($res);

if (!$kunjungan) {
    header("Location: antrean-poli.php");
    exit();
}

// Proteksi hak akses per dokter: pastikan antrean ini ditugaskan ke dokter yang sedang login
if ((int)$kunjungan['id_dokter'] !== (int)$id_dokter) {
    header("Location: antrean-poli.php?pesan=bukan_pasien_anda");
    exit();
}

// Jika status masih 'menunggu', otomatis ubah jadi 'diperiksa' saat dokter membuka halaman ini
if ($kunjungan['status_layanan'] === 'menunggu') {
    mysqli_query($connection, "UPDATE layanan SET status_layanan = 'diperiksa' WHERE id_layanan = $id_layanan");
    $kunjungan['status_layanan'] = 'diperiksa';
}

$tgl_lahir = new DateTime($kunjungan['tanggal_lahir']);
$usia = $tgl_lahir->diff(new DateTime())->y;

// -----------------------------------------------------------------------------
// 2. Pemrosesan Penyimpanan Rekam Medik Dokter (POST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submit_action = $_POST['submit_action'] ?? 'selesai';
    
    // Gabungkan tanda vital & fisik awal ke kolom `pemeriksaan_awal`
    $td = trim($_POST['tekanan_darah'] ?? '');
    $suhu = trim($_POST['suhu_tubuh'] ?? '');
    $bb = trim($_POST['berat_badan'] ?? '');
    $tb = trim($_POST['tinggi_badan'] ?? '');
    $anamnesis = trim($_POST['anamnesis'] ?? '');
    $alergi = trim($_POST['riwayat_alergi'] ?? '');
    $fisik_awal = trim($_POST['pemeriksaan_fisik'] ?? '');

    $pemeriksaan_awal = "Vital: TD $td mmHg | Suhu $suhu C | BB $bb kg | TB $tb cm\n";
    $pemeriksaan_awal .= "Anamnesis: $anamnesis\n";
    if (!empty($alergi)) $pemeriksaan_awal .= "Alergi: $alergi\n";
    if (!empty($fisik_awal)) $pemeriksaan_awal .= "Temuan Fisik: $fisik_awal";

    // Diagnosis primer & sekunder
    $diagnosis_primer = trim($_POST['diagnosis_primer'] ?? '');
    $diagnosis_sekunder = trim($_POST['diagnosis_sekunder'] ?? '');
    
    // Tindakan medis
    $tindakan = trim($_POST['tindakan'] ?? '');

    // Pemeriksaan akhir
    $td_akhir = trim($_POST['tekanan_darah_akhir'] ?? '');
    $suhu_akhir = trim($_POST['suhu_tubuh_akhir'] ?? '');
    $nadi_akhir = trim($_POST['nadi_akhir'] ?? '');
    $evaluasi_akhir = trim($_POST['evaluasi_akhir'] ?? '');
    $pemeriksaan_akhir = "Vital Akhir: TD $td_akhir mmHg | Suhu $suhu_akhir C | Nadi $nadi_akhir x/mnt\nEvaluasi: $evaluasi_akhir";

    // Resep obat & catatan dokter
    $resep_obat = trim($_POST['resep_obat'] ?? '');
    $edukasi_dokter = trim($_POST['catatan_dokter'] ?? '');
    $catatan_dokter = "Resep Obat:\n$resep_obat\n\nCatatan & Edukasi:\n$edukasi_dokter";

    // Status kondisi akhir
    $status_kondisi = $_POST['status_kondisi'] ?? 'Rawat Jalan';

    if ($submit_action === 'selesai') {
        $status_layanan = 'selesai';
        $waktu_keluar = date('Y-m-d H:i:s');
    } else {
        $status_layanan = 'diperiksa';
        $waktu_keluar = null;
    }

    $stmt_update = mysqli_prepare($connection, "UPDATE layanan SET 
        pemeriksaan_awal = ?,
        diagnosis_primer = ?,
        diagnosis_sekunder = ?,
        tindakan = ?,
        pemeriksaan_akhir = ?,
        catatan_dokter = ?,
        status_kondisi = ?,
        status_layanan = ?,
        waktu_keluar = ?
        WHERE id_layanan = ?");

    mysqli_stmt_bind_param($stmt_update, "sssssssssi", 
        $pemeriksaan_awal, 
        $diagnosis_primer, 
        $diagnosis_sekunder, 
        $tindakan, 
        $pemeriksaan_akhir, 
        $catatan_dokter, 
        $status_kondisi, 
        $status_layanan, 
        $waktu_keluar, 
        $id_layanan
    );

    if (mysqli_stmt_execute($stmt_update)) {
        if ($status_layanan === 'selesai') {
            header("Location: dashboard.php?pesan=pemeriksaan_selesai");
            exit();
        } else {
            header("Location: detail-pemeriksaan.php?id_layanan=$id_layanan&pesan=draf_disimpan");
            exit();
        }
    } else {
        $error_msg = "Gagal menyimpan rekam medis: " . mysqli_error($connection);
    }
}

// -----------------------------------------------------------------------------
// 3. Ekstraksi Data Pemeriksaan Riil (Form Kosong Murni Jika Pemeriksaan Baru)
// -----------------------------------------------------------------------------
$val_td = '';
$val_suhu = '';
$val_bb = '';
$val_tb = '';
$val_anamnesis = '';
$val_alergi = '';
$val_fisik = '';
$val_diag_primer = $kunjungan['diagnosis_primer'] ?? '';
$val_diag_sekunder = $kunjungan['diagnosis_sekunder'] ?? '';
$val_tindakan = $kunjungan['tindakan'] ?? '';
$val_td_akhir = '';
$val_suhu_akhir = '';
$val_nadi_akhir = '';
$val_evaluasi_akhir = '';
$val_resep = '';
$val_catatan = '';

if (!empty($kunjungan['pemeriksaan_awal'])) {
    if (preg_match('/Vital: TD (.*?) mmHg/', $kunjungan['pemeriksaan_awal'], $m)) $val_td = $m[1];
    if (preg_match('/Suhu (.*?) C/', $kunjungan['pemeriksaan_awal'], $m)) $val_suhu = $m[1];
    if (preg_match('/BB (.*?) kg/', $kunjungan['pemeriksaan_awal'], $m)) $val_bb = $m[1];
    if (preg_match('/TB (.*?) cm/', $kunjungan['pemeriksaan_awal'], $m)) $val_tb = $m[1];
    if (preg_match('/Anamnesis: (.*?)(?=(Alergi:|Temuan Fisik:|$))/s', $kunjungan['pemeriksaan_awal'], $m)) $val_anamnesis = trim($m[1]);
    if (preg_match('/Alergi: (.*?)(?=(Temuan Fisik:|$))/s', $kunjungan['pemeriksaan_awal'], $m)) $val_alergi = trim($m[1]);
    if (preg_match('/Temuan Fisik: (.*)/s', $kunjungan['pemeriksaan_awal'], $m)) $val_fisik = trim($m[1]);
}

if (!empty($kunjungan['pemeriksaan_akhir'])) {
    if (preg_match('/Vital Akhir: TD (.*?) mmHg/', $kunjungan['pemeriksaan_akhir'], $m)) $val_td_akhir = $m[1];
    if (preg_match('/Suhu (.*?) C/', $kunjungan['pemeriksaan_akhir'], $m)) $val_suhu_akhir = $m[1];
    if (preg_match('/Nadi (.*?) x\/mnt/', $kunjungan['pemeriksaan_akhir'], $m)) $val_nadi_akhir = $m[1];
    if (preg_match('/Evaluasi: (.*)/s', $kunjungan['pemeriksaan_akhir'], $m)) $val_evaluasi_akhir = trim($m[1]);
}

if (!empty($kunjungan['catatan_dokter'])) {
    if (preg_match('/Resep Obat:\n(.*?)(?=\n\nCatatan & Edukasi:|$)/s', $kunjungan['catatan_dokter'], $m)) {
        $val_resep = trim($m[1]);
        if (preg_match('/Catatan & Edukasi:\n(.*)/s', $kunjungan['catatan_dokter'], $m2)) {
            $val_catatan = trim($m2[1]);
        }
    } else {
        $val_resep = $kunjungan['catatan_dokter'];
    }
}

$page_title = 'Pemeriksaan Pasien EMR - WartaSehat.id';
$current_page = 'antrean_poli';
$breadcrumb_parent = 'Antrean Poliklinik';
$breadcrumb_current = 'Lembar Rekam Medik';
$breadcrumb_parent_url = 'antrean-poli.php';

include '../../includes/header.php';
include '../../includes/sidebar_dokter.php';
include '../../includes/topbar.php';
?>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        
        <?php if ($pesan === 'draf_disimpan'): ?>
          <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <strong>Draf Disimpan:</strong> Catatan pemeriksaan berhasil disimpan. Pasien tetap berstatus sedang diperiksa.
          </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
          <div class="alert alert-danger mb-4" role="alert">
            <strong>Terjadi Kesalahan:</strong> <?= htmlspecialchars($error_msg); ?>
          </div>
        <?php endif; ?>

        <!-- Clinical Patient Header Card -->
        <div class="ws-card mb-4 border-2 border-primary">
          <div class="ws-card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div class="d-flex align-items-center gap-3">
                <div class="ws-user-avatar" style="width: 60px; height: 60px; font-size: 1.4rem; background-color: var(--ws-primary-light); color: var(--ws-primary);">
                  <?= get_initials($kunjungan['nama_pasien']); ?>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="h3 fw-bold mb-0"><?= htmlspecialchars($kunjungan['nama_pasien']); ?></h1>
                    <span class="cell-code fs-6"><?= htmlspecialchars($kunjungan['nomor_rm'] ?? 'RM-' . $kunjungan['id_rekam_medik']); ?></span>
                    <?php if ($kunjungan['status_layanan'] === 'selesai'): ?>
                      <span class="badge badge-success">&check; Selesai</span>
                    <?php else: ?>
                      <span class="badge badge-warning"><span class="status-dot"></span> Sedang Diperiksa</span>
                    <?php endif; ?>
                  </div>
                  <div class="ws-identity-list mt-1">
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">NIK:</span>
                      <span class="ws-identity-val font-monospace"><?= htmlspecialchars($kunjungan['nik']); ?></span>
                    </div>
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">JK:</span>
                      <span class="ws-identity-val"><?= htmlspecialchars($kunjungan['jenis_kelamin']); ?></span>
                    </div>
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">Usia:</span>
                      <span class="ws-identity-val"><?= $usia; ?> Tahun</span>
                    </div>
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">Gol Darah:</span>
                      <span class="ws-identity-val badge badge-secondary"><?= htmlspecialchars($kunjungan['golongan_darah'] ?? '-'); ?></span>
                    </div>
                    <div class="ws-identity-item">
                      <span class="ws-identity-label">No. HP:</span>
                      <span class="ws-identity-val font-monospace"><?= htmlspecialchars($kunjungan['no_telp']); ?></span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2">
                <a href="riwayat-rekam-medik.php?id=<?= $kunjungan['id_pasien']; ?>" class="btn btn-outline-primary" target="_blank">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                  </svg>
                  Buka Arsip Rekam Medik
                </a>
                <a href="antrean-poli.php" class="btn btn-secondary">
                  &larr; Antrean Poli
                </a>
              </div>
            </div>

            <!-- Banner Keluhan Awal dari Registrasi Loket -->
            <div class="mt-3 p-3 bg-light rounded border">
              <strong class="text-primary d-block mb-1">Keluhan Awal Pasien Saat Registrasi:</strong>
              <div class="text-secondary-emphasis">"<?= htmlspecialchars($kunjungan['keluhan_awal']); ?>"</div>
            </div>
          </div>
        </div>

        <!-- FORMULIR REKAM MEDIK DOKTER (POST) -->
        <form action="detail-pemeriksaan.php?id_layanan=<?= $id_layanan; ?>" method="POST">
          <div class="row g-4">
            
            <!-- Left Column: Rekam Medis Klinis (Sesuai DATABASE.md) -->
            <div class="col-12 col-lg-8">
              
              <!-- CARD 1: Pemeriksaan Awal -->
              <div class="ws-card mb-4">
                <div class="ws-card-header">
                  <h2 class="ws-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                    1. Pemeriksaan Awal (Tanda Vital & Anamnesis)
                  </h2>
                  <span class="badge badge-secondary">Tabel: <code>pemeriksaan_awal</code></span>
                </div>
                <div class="ws-card-body">
                  <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6 col-md-3">
                      <label for="tekanan_darah" class="form-label small text-muted">Tekanan Darah (TD)</label>
                      <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" id="tekanan_darah" name="tekanan_darah" value="<?= htmlspecialchars($val_td); ?>" required>
                        <span class="input-group-text">mmHg</span>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                      <label for="suhu_tubuh" class="form-label small text-muted">Suhu Tubuh</label>
                      <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" id="suhu_tubuh" name="suhu_tubuh" value="<?= htmlspecialchars($val_suhu); ?>" required>
                        <span class="input-group-text">&deg;C</span>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                      <label for="berat_badan" class="form-label small text-muted">Berat Badan (BB)</label>
                      <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" id="berat_badan" name="berat_badan" value="<?= htmlspecialchars($val_bb); ?>">
                        <span class="input-group-text">kg</span>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                      <label for="tinggi_badan" class="form-label small text-muted">Tinggi Badan (TB)</label>
                      <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" id="tinggi_badan" name="tinggi_badan" value="<?= htmlspecialchars($val_tb); ?>">
                        <span class="input-group-text">cm</span>
                      </div>
                    </div>
                  </div>

                  <hr class="my-3 text-muted">

                  <div class="mb-3">
                    <label for="anamnesis" class="form-label fw-bold">Anamnesis / Riwayat Penyakit Sekarang <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="anamnesis" name="anamnesis" rows="3" required><?= htmlspecialchars($val_anamnesis); ?></textarea>
                  </div>

                  <div class="mb-3">
                    <label for="riwayat_alergi" class="form-label fw-bold">Riwayat Alergi Obat / Makanan</label>
                    <input type="text" class="form-control" id="riwayat_alergi" name="riwayat_alergi" value="<?= htmlspecialchars($val_alergi); ?>">
                  </div>

                  <div class="mb-0">
                    <label for="pemeriksaan_fisik" class="form-label fw-bold">Temuan Fisik Awal</label>
                    <textarea class="form-control" id="pemeriksaan_fisik" name="pemeriksaan_fisik" rows="2"><?= htmlspecialchars($val_fisik); ?></textarea>
                  </div>
                </div>
              </div>

              <!-- CARD 2: Diagnosis Primer & Sekunder -->
              <div class="ws-card mb-4">
                <div class="ws-card-header">
                  <h2 class="ws-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"/>
                      <polyline points="12 6 12 12 14 14"/>
                    </svg>
                    2. Diagnosis Medis
                  </h2>
                  <span class="badge badge-danger">Tabel: <code>diagnosis_primer</code></span>
                </div>
                <div class="ws-card-body">
                  <div class="mb-3">
                    <label for="diagnosis_primer" class="form-label fw-bold">Diagnosis Primer (Utama / ICD-10) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace fw-bold text-danger" id="diagnosis_primer" name="diagnosis_primer" value="<?= htmlspecialchars($val_diag_primer); ?>" required>
                    <div class="form-text">Diagnosis utama yang menjadi fokus terapi pada kunjungan ini.</div>
                  </div>

                  <div class="mb-0">
                    <label for="diagnosis_sekunder" class="form-label fw-bold">Diagnosis Sekunder (Penyerta / Komplikasi)</label>
                    <input type="text" class="form-control font-monospace" id="diagnosis_sekunder" name="diagnosis_sekunder" value="<?= htmlspecialchars($val_diag_sekunder); ?>">
                  </div>
                </div>
              </div>

              <!-- CARD 3: Tindakan Medis / Prosedur -->
              <div class="ws-card mb-4">
                <div class="ws-card-header">
                  <h2 class="ws-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                      <path d="M12 5v14"/>
                    </svg>
                    3. Tindakan / Prosedur Poliklinik
                  </h2>
                  <span class="badge badge-secondary">Tabel: <code>tindakan</code></span>
                </div>
                <div class="ws-card-body">
                  <textarea class="form-control" id="tindakan" name="tindakan" rows="3"><?= htmlspecialchars($val_tindakan); ?></textarea>
                </div>
              </div>

              <!-- CARD 4: Pemeriksaan Akhir -->
              <div class="ws-card mb-4">
                <div class="ws-card-header">
                  <h2 class="ws-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    4. Pemeriksaan Akhir & Evaluasi Pasca Tindakan
                  </h2>
                  <span class="badge badge-secondary">Tabel: <code>pemeriksaan_akhir</code></span>
                </div>
                <div class="ws-card-body">
                  <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-4">
                      <label class="form-label small text-muted">Tekanan Darah Akhir</label>
                      <input type="text" class="form-control form-control-sm font-monospace" name="tekanan_darah_akhir" value="<?= htmlspecialchars($val_td_akhir); ?>">
                    </div>
                    <div class="col-12 col-sm-4">
                      <label class="form-label small text-muted">Suhu Tubuh Akhir</label>
                      <input type="text" class="form-control form-control-sm font-monospace" name="suhu_tubuh_akhir" value="<?= htmlspecialchars($val_suhu_akhir); ?>">
                    </div>
                    <div class="col-12 col-sm-4">
                      <label class="form-label small text-muted">Denyut Nadi</label>
                      <input type="text" class="form-control form-control-sm font-monospace" name="nadi_akhir" value="<?= htmlspecialchars($val_nadi_akhir); ?>">
                    </div>
                  </div>
                  <div>
                    <label class="form-label fw-bold">Evaluasi Respon Pasca Tindakan</label>
                    <textarea class="form-control" name="evaluasi_akhir" rows="2"><?= htmlspecialchars($val_evaluasi_akhir); ?></textarea>
                  </div>
                </div>
              </div>

              <!-- CARD 5: Resep Obat & Catatan Dokter -->
              <div class="ws-card mb-4">
                <div class="ws-card-header">
                  <h2 class="ws-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                    5. Resep Obat & Catatan Dokter
                  </h2>
                  <span class="badge badge-primary">Tabel: <code>catatan_dokter</code></span>
                </div>
                <div class="ws-card-body">
                  <div class="mb-3">
                    <label for="resep_obat" class="form-label fw-bold">Resep Obat Elektronik <span class="text-danger">*</span></label>
                    <textarea class="form-control font-monospace" id="resep_obat" name="resep_obat" rows="4" required><?= htmlspecialchars($val_resep); ?></textarea>
                  </div>

                  <div class="mb-0">
                    <label for="catatan_dokter" class="form-label fw-bold">Edukasi & Catatan Pasien</label>
                    <textarea class="form-control" id="catatan_dokter" name="catatan_dokter" rows="2"><?= htmlspecialchars($val_catatan); ?></textarea>
                  </div>
                </div>
              </div>

            </div>

            <!-- Right Column: Status Kondisi & Tombol Selesai -->
            <div class="col-12 col-lg-4">
              <div class="ws-card mb-4 border-2 border-primary sticky-top" style="top: 80px;">
                <div class="ws-card-header bg-light">
                  <h2 class="ws-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Disposisi & Selesaikan
                  </h2>
                </div>
                <div class="ws-card-body p-4">
                  <div class="mb-4">
                    <label for="status_kondisi" class="form-label fw-bold">
                      Status Kondisi Pasien <span class="text-danger">*</span>
                    </label>
                    <select class="form-select form-select-lg fw-bold text-primary" id="status_kondisi" name="status_kondisi" required>
                      <option value="Rawat Jalan" <?= ($kunjungan['status_kondisi'] === 'Rawat Jalan' || empty($kunjungan['status_kondisi'])) ? 'selected' : ''; ?>>Rawat Jalan</option>
                      <option value="Rawat Inap" <?= ($kunjungan['status_kondisi'] === 'Rawat Inap') ? 'selected' : ''; ?>>Rawat Inap</option>
                      <option value="Sembuh" <?= ($kunjungan['status_kondisi'] === 'Sembuh') ? 'selected' : ''; ?>>Sembuh</option>
                      <option value="Meninggal" <?= ($kunjungan['status_kondisi'] === 'Meninggal') ? 'selected' : ''; ?>>Meninggal</option>
                    </select>
                    <div class="form-text small">Status akhir pasien sesuai spesifikasi <code>DATABASE.md</code>.</div>
                  </div>

                  <div class="d-grid gap-2">
                    <button type="submit" name="submit_action" value="selesai" class="btn btn-primary btn-lg py-3 fw-bold">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                      </svg>
                      Simpan & Selesaikan Kunjungan
                    </button>

                    <button type="submit" name="submit_action" value="draf" class="btn btn-outline-primary py-2">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                      </svg>
                      Simpan Draf Pemeriksaan
                    </button>

                    <a href="antrean-poli.php" class="btn btn-secondary">
                      Batal & Kembali
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </form>

      </main>

<?php include '../../includes/footer.php'; ?>
