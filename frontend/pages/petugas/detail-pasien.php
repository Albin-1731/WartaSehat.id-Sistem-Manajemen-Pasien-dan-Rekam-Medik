<?php
require_once '../../../backend/koneksi.php';
require_once '../../../backend/auth_check.php';

check_petugas();

$id_pasien = (int)($_GET['id'] ?? 0);

if ($id_pasien <= 0) {
    header("Location: daftar-pasien.php");
    exit();
}

$page_title = 'Detail Profil Pasien - WartaSehat.id';
$current_page = 'daftar_pasien';
$breadcrumb_parent = 'Data Pasien';
$breadcrumb_current = 'Detail Profil';
$breadcrumb_parent_url = 'daftar-pasien.php';

$pesan = $_GET['pesan'] ?? '';
$error = '';

// -----------------------------------------------------------------------------
// Pemrosesan Pembaruan Data Pasien (POST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pasien') {
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'Laki-laki';
    $golongan_darah = $_POST['golongan_darah'] ?? '-';
    $agama = $_POST['agama'] ?? 'Islam';
    $status_pernikahan = $_POST['status_pernikahan'] ?? 'Belum Menikah';
    $pekerjaan = trim($_POST['pekerjaan'] ?? '-');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama_pasien) || empty($no_telp) || empty($alamat)) {
        $error = "Nama, Nomor Telepon, dan Alamat tidak boleh kosong.";
    } else {
        $stmt_up = mysqli_prepare($connection, "UPDATE pasien SET nama_pasien = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ?, golongan_darah = ?, agama = ?, status_pernikahan = ?, pekerjaan = ?, no_telp = ?, alamat = ? WHERE id_pasien = ?");
        mysqli_stmt_bind_param($stmt_up, "ssssssssssi", $nama_pasien, $tempat_lahir, $tanggal_lahir, $jenis_kelamin, $golongan_darah, $agama, $status_pernikahan, $pekerjaan, $no_telp, $alamat, $id_pasien);
        if (mysqli_stmt_execute($stmt_up)) {
            header("Location: detail-pasien.php?id=$id_pasien&pesan=sukses_update");
            exit();
        } else {
            $error = "Gagal memperbarui data pasien: " . mysqli_error($connection);
        }
    }
}

// -----------------------------------------------------------------------------
// Ambil Data Pasien & Rekam Medik Terbaru
// -----------------------------------------------------------------------------
$stmt = mysqli_prepare($connection, "SELECT p.*, rm.id_rekam_medik, rm.nomor_rm, rm.created_at AS tgl_terdaftar 
                                     FROM pasien p 
                                     LEFT JOIN rekam_medik rm ON p.id_pasien = rm.id_pasien 
                                     WHERE p.id_pasien = ? 
                                     ORDER BY rm.id_rekam_medik DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_pasien);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pasien = mysqli_fetch_assoc($res);

if (!$pasien) {
    header("Location: daftar-pasien.php");
    exit();
}

// Hitung usia
$tgl_lahir = new DateTime($pasien['tanggal_lahir']);
$usia = $tgl_lahir->diff(new DateTime())->y;

// Ambil riwayat kunjungan pasien (seluruh rekam medis milik pasien ini)
$stmt_kunjungan = mysqli_prepare($connection, "SELECT l.id_layanan, l.waktu_masuk, l.status_layanan, l.keluhan_awal, l.diagnosis_primer, l.status_kondisi, po.nama_poli, d.nama_dokter, rm.nomor_rm 
                                              FROM layanan l 
                                              JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik 
                                              JOIN poli po ON l.id_poli = po.id_poli 
                                              JOIN dokter d ON l.id_dokter = d.id_dokter 
                                              WHERE rm.id_pasien = ? 
                                              ORDER BY l.waktu_masuk DESC");
mysqli_stmt_bind_param($stmt_kunjungan, "i", $id_pasien);
mysqli_stmt_execute($stmt_kunjungan);
$res_kunjungan = mysqli_stmt_get_result($stmt_kunjungan);

include '../../includes/header.php';
include '../../includes/sidebar_petugas.php';
include '../../includes/topbar.php';
?>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        
        <?php if ($pesan === 'sukses_update'): ?>
          <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <strong>Berhasil Diperbarui:</strong> Data profil pasien telah berhasil disimpan ke database.
          </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger mb-4" role="alert">
            <strong>Gagal Memperbarui:</strong> <?= htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <!-- Patient Hero Card -->
        <div class="ws-card mb-4">
          <div class="ws-card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div class="d-flex align-items-center gap-3">
                <div class="ws-user-avatar" style="width: 64px; height: 64px; font-size: 1.5rem; background-color: var(--ws-primary-light); color: var(--ws-primary);">
                  <?= get_initials($pasien['nama_pasien']); ?>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="h3 fw-bold mb-0"><?= htmlspecialchars($pasien['nama_pasien']); ?></h1>
                    <span class="cell-code fs-6"><?= htmlspecialchars($pasien['nomor_rm'] ?? 'RM-' . $pasien['id_rekam_medik']); ?></span>
                  </div>
                  <!-- Identity Meta Badges -->
                  <div class="ws-identity-list mt-1">
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
                  </div>
                </div>
              </div>

              <!-- Header Action Buttons -->
              <div class="d-flex gap-2 flex-wrap">
                <a href="riwayat-rekam-medik.php?id=<?= $pasien['id_pasien']; ?>" class="btn btn-outline-primary">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                  </svg>
                  Buka Rekam Medik
                </a>
                <a href="pendaftaran-pasien.php?cek_nik=<?= $pasien['nik']; ?>" class="btn btn-primary">
                  &plus; Daftarkan ke Antrean Poli
                </a>
                <a href="#modal-edit-pasien" class="btn btn-secondary">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  Ubah Data
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- Biodata Lengkap -->
          <div class="col-12 col-lg-7">
            <div class="ws-card">
              <div class="ws-card-header">
                <h2 class="ws-card-title">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                  Biodata Demografis Pasien
                </h2>
                <span class="badge badge-secondary">Tabel: <code>pasien</code></span>
              </div>
              <div class="ws-card-body p-0">
                <table class="table ws-table mb-0">
                  <tbody>
                    <tr>
                      <td class="text-muted fw-semibold" style="width: 220px;">Nomor Rekam Medis (RM)</td>
                      <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($pasien['nomor_rm'] ?? 'RM-' . $pasien['id_rekam_medik']); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">NIK (KTP)</td>
                      <td class="font-monospace fw-bold"><?= htmlspecialchars($pasien['nik']); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Nama Lengkap</td>
                      <td class="fw-bold"><?= htmlspecialchars($pasien['nama_pasien']); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Tempat, Tanggal Lahir</td>
                      <td><?= htmlspecialchars($pasien['tempat_lahir'] ?? '-'); ?>, <?= date('d F Y', strtotime($pasien['tanggal_lahir'])); ?> (Usia: <?= $usia; ?> Tahun)</td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Jenis Kelamin</td>
                      <td><?= htmlspecialchars($pasien['jenis_kelamin']); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Golongan Darah</td>
                      <td><span class="badge badge-secondary"><?= htmlspecialchars($pasien['golongan_darah'] ?? '-'); ?></span></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Agama</td>
                      <td><?= htmlspecialchars($pasien['agama'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Status Pernikahan</td>
                      <td><?= htmlspecialchars($pasien['status_pernikahan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Pekerjaan</td>
                      <td><?= htmlspecialchars($pasien['pekerjaan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Nomor Telepon / WA</td>
                      <td class="font-monospace"><?= htmlspecialchars($pasien['no_telp']); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Alamat Domisili</td>
                      <td><?= nl2br(htmlspecialchars($pasien['alamat'])); ?></td>
                    </tr>
                    <tr>
                      <td class="text-muted fw-semibold">Tanggal Terdaftar</td>
                      <td><?= date('d F Y, H:i', strtotime($pasien['created_at'])); ?> WIB</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Riwayat Kunjungan Singkat -->
          <div class="col-12 col-lg-5">
            <div class="ws-card">
              <div class="ws-card-header">
                <h2 class="ws-card-title">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                  </svg>
                  Riwayat Kunjungan Poliklinik
                </h2>
                <span class="badge badge-primary"><?= mysqli_num_rows($res_kunjungan); ?> Kunjungan</span>
              </div>
              <div class="ws-card-body p-3">
                <?php if (mysqli_num_rows($res_kunjungan) > 0): ?>
                  <div class="d-flex flex-column gap-3">
                    <?php while ($k = mysqli_fetch_assoc($res_kunjungan)): ?>
                      <div class="p-3 rounded border bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <strong class="text-primary"><?= htmlspecialchars($k['nama_poli']); ?></strong>
                          <span class="small text-muted"><?= date('d/m/Y H:i', strtotime($k['waktu_masuk'])); ?></span>
                        </div>
                        <div class="small text-dark mb-1">Dokter: <?= htmlspecialchars($k['nama_dokter']); ?></div>
                        <div class="small text-muted mb-2">Keluhan: <?= htmlspecialchars($k['keluhan_awal']); ?></div>
                        
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                          <?php if ($k['status_layanan'] === 'selesai'): ?>
                            <span class="badge badge-success">&check; Selesai <?= !empty($k['status_kondisi']) ? '('.htmlspecialchars($k['status_kondisi']).')' : ''; ?></span>
                          <?php elseif ($k['status_layanan'] === 'diperiksa'): ?>
                            <span class="badge badge-primary">Sedang Diperiksa</span>
                          <?php else: ?>
                            <span class="badge badge-warning">Menunggu</span>
                          <?php endif; ?>

                          <?php if (!empty($k['diagnosis_primer'])): ?>
                            <span class="small font-monospace text-danger fw-semibold"><?= htmlspecialchars($k['diagnosis_primer']); ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endwhile; ?>
                  </div>
                <?php else: ?>
                  <div class="text-center py-4 text-muted">
                    <p class="mb-0">Belum ada riwayat kunjungan poliklinik untuk pasien ini.</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- MODAL EDIT DATA PASIEN (Pure CSS Target Modal) -->
        <div id="modal-edit-pasien" class="ws-modal-overlay">
          <div class="ws-modal-dialog" style="max-width: 600px;">
            <div class="ws-modal-header">
              <h3 class="ws-modal-title">Ubah Identitas Pasien</h3>
              <a href="#" class="ws-modal-close" aria-label="Tutup">&times;</a>
            </div>
            <form action="detail-pasien.php?id=<?= $id_pasien; ?>" method="POST">
              <input type="hidden" name="action" value="update_pasien">
              <div class="ws-modal-body p-4">
                <div class="mb-3">
                  <label class="form-label fw-bold">Nama Lengkap Pasien <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="nama_pasien" value="<?= htmlspecialchars($pasien['nama_pasien']); ?>" required>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-6">
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" class="form-control" name="tempat_lahir" value="<?= htmlspecialchars($pasien['tempat_lahir'] ?? ''); ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="tanggal_lahir" value="<?= htmlspecialchars($pasien['tanggal_lahir']); ?>" required>
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-6">
                    <label class="form-label">Jenis Kelamin</label>
                    <select class="form-select" name="jenis_kelamin">
                      <option value="Laki-laki" <?= ($pasien['jenis_kelamin'] === 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                      <option value="Perempuan" <?= ($pasien['jenis_kelamin'] === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Golongan Darah</label>
                    <select class="form-select" name="golongan_darah">
                      <option value="-" <?= ($pasien['golongan_darah'] === '-') ? 'selected' : ''; ?>>-</option>
                      <option value="A" <?= ($pasien['golongan_darah'] === 'A') ? 'selected' : ''; ?>>A</option>
                      <option value="B" <?= ($pasien['golongan_darah'] === 'B') ? 'selected' : ''; ?>>B</option>
                      <option value="AB" <?= ($pasien['golongan_darah'] === 'AB') ? 'selected' : ''; ?>>AB</option>
                      <option value="O" <?= ($pasien['golongan_darah'] === 'O') ? 'selected' : ''; ?>>O</option>
                    </select>
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-6">
                    <label class="form-label">Agama</label>
                    <select class="form-select" name="agama">
                      <option value="Islam" <?= ($pasien['agama'] === 'Islam') ? 'selected' : ''; ?>>Islam</option>
                      <option value="Kristen" <?= ($pasien['agama'] === 'Kristen') ? 'selected' : ''; ?>>Kristen</option>
                      <option value="Katolik" <?= ($pasien['agama'] === 'Katolik') ? 'selected' : ''; ?>>Katolik</option>
                      <option value="Hindu" <?= ($pasien['agama'] === 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                      <option value="Buddha" <?= ($pasien['agama'] === 'Buddha') ? 'selected' : ''; ?>>Buddha</option>
                      <option value="Konghucu" <?= ($pasien['agama'] === 'Konghucu') ? 'selected' : ''; ?>>Konghucu</option>
                      <option value="Lainnya" <?= ($pasien['agama'] === 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Status Pernikahan</label>
                    <select class="form-select" name="status_pernikahan">
                      <option value="Belum Menikah" <?= ($pasien['status_pernikahan'] === 'Belum Menikah') ? 'selected' : ''; ?>>Belum Menikah</option>
                      <option value="Menikah" <?= ($pasien['status_pernikahan'] === 'Menikah') ? 'selected' : ''; ?>>Menikah</option>
                      <option value="Cerai Hidup" <?= ($pasien['status_pernikahan'] === 'Cerai Hidup') ? 'selected' : ''; ?>>Cerai Hidup</option>
                      <option value="Cerai Mati" <?= ($pasien['status_pernikahan'] === 'Cerai Mati') ? 'selected' : ''; ?>>Cerai Mati</option>
                    </select>
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-6">
                    <label class="form-label">Pekerjaan</label>
                    <input type="text" class="form-control" name="pekerjaan" value="<?= htmlspecialchars($pasien['pekerjaan'] ?? ''); ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label">Nomor Telepon / WA <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" name="no_telp" value="<?= htmlspecialchars($pasien['no_telp']); ?>" required>
                  </div>
                </div>

                <div class="mb-0">
                  <label class="form-label">Alamat Domisili <span class="text-danger">*</span></label>
                  <textarea class="form-control" name="alamat" rows="3" required><?= htmlspecialchars($pasien['alamat']); ?></textarea>
                </div>
              </div>
              <div class="ws-modal-footer p-3 bg-light d-flex justify-content-end gap-2">
                <a href="#" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
              </div>
            </form>
          </div>
        </div>

      </main>

<?php include '../../includes/footer.php'; ?>
