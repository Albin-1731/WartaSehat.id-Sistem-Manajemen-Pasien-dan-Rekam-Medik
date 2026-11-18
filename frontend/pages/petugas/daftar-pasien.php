<?php
require_once '../../../backend/koneksi.php';
require_once '../../../backend/auth_check.php';

check_petugas();

$page_title = 'Data Pasien - WartaSehat.id';
$current_page = 'daftar_pasien';
$breadcrumb_parent = 'Petugas';
$breadcrumb_current = 'Data Pasien';
$breadcrumb_parent_url = 'dashboard.php';

// -----------------------------------------------------------------------------
// Filter & Pencarian Pasien
// -----------------------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$filter_jk = trim($_GET['jenis_kelamin'] ?? '');
$filter_gol_darah = trim($_GET['golongan_darah'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');

$sql = "SELECT p.*, 
               (SELECT rm.nomor_rm FROM rekam_medik rm WHERE rm.id_pasien = p.id_pasien ORDER BY rm.id_rekam_medik DESC LIMIT 1) AS nomor_rm,
               (SELECT rm.id_rekam_medik FROM rekam_medik rm WHERE rm.id_pasien = p.id_pasien ORDER BY rm.id_rekam_medik DESC LIMIT 1) AS id_rekam_medik,
               (SELECT COUNT(*) FROM layanan l JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik WHERE rm.id_pasien = p.id_pasien) AS total_kunjungan,
               (SELECT MAX(l.waktu_masuk) FROM layanan l JOIN rekam_medik rm ON l.id_rekam_medik = rm.id_rekam_medik WHERE rm.id_pasien = p.id_pasien) AS kunjungan_terakhir
        FROM pasien p
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (p.nama_pasien LIKE ? OR p.nik LIKE ? OR rm.nomor_rm LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($filter_jk)) {
    $sql .= " AND p.jenis_kelamin = ?";
    $params[] = $filter_jk;
    $types .= "s";
}

if (!empty($filter_gol_darah)) {
    $sql .= " AND p.golongan_darah = ?";
    $params[] = $filter_gol_darah;
    $types .= "s";
}

// Pengurutan (Sorting)
switch ($sort) {
    case 'terlama':
        $sql .= " ORDER BY p.id_pasien ASC";
        break;
    case 'nama_asc':
        $sql .= " ORDER BY p.nama_pasien ASC";
        break;
    case 'rm_asc':
        $sql .= " ORDER BY rm.nomor_rm ASC";
        break;
    case 'terbaru':
    default:
        $sql .= " ORDER BY p.id_pasien DESC";
        break;
}

$stmt = mysqli_prepare($connection, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_ditemukan = mysqli_num_rows($result);

include '../../includes/header.php';
include '../../includes/sidebar_petugas.php';
include '../../includes/topbar.php';
?>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        <div class="d-flex justify-content-between align-items-center mb-4 ws-page-header">
          <div>
            <h1 class="h3 fw-bold mb-1">Master Data Pasien</h1>
            <p class="text-muted mb-0">Kelola identitas pasien, riwayat rekam medis, dan pendaftaran kunjungan langsung.</p>
          </div>
          <div>
            <a href="pendaftaran-pasien.php" class="btn btn-primary">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              <span>Tambah Pasien Baru</span>
            </a>
          </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="ws-card mb-4">
          <div class="ws-card-body p-3">
            <form action="daftar-pasien.php" method="GET" class="d-flex gap-3 align-items-center flex-wrap ws-filter-bar">
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

              <div style="flex: 1; min-width: 160px;">
                <select name="jenis_kelamin" class="form-select">
                  <option value="">Semua Jenis Kelamin</option>
                  <option value="Laki-laki" <?= ($filter_jk === 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                  <option value="Perempuan" <?= ($filter_jk === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
              </div>

              <div style="flex: 1; min-width: 150px;">
                <select name="golongan_darah" class="form-select">
                  <option value="">Golongan Darah</option>
                  <option value="A" <?= ($filter_gol_darah === 'A') ? 'selected' : ''; ?>>Golongan A</option>
                  <option value="B" <?= ($filter_gol_darah === 'B') ? 'selected' : ''; ?>>Golongan B</option>
                  <option value="AB" <?= ($filter_gol_darah === 'AB') ? 'selected' : ''; ?>>Golongan AB</option>
                  <option value="O" <?= ($filter_gol_darah === 'O') ? 'selected' : ''; ?>>Golongan O</option>
                </select>
              </div>

              <div style="flex: 1; min-width: 160px;">
                <select name="sort" class="form-select">
                  <option value="terbaru" <?= ($sort === 'terbaru') ? 'selected' : ''; ?>>Terbaru Terdaftar</option>
                  <option value="terlama" <?= ($sort === 'terlama') ? 'selected' : ''; ?>>Terlama Terdaftar</option>
                  <option value="nama_asc" <?= ($sort === 'nama_asc') ? 'selected' : ''; ?>>Nama (A - Z)</option>
                  <option value="rm_asc" <?= ($sort === 'rm_asc') ? 'selected' : ''; ?>>No. RM (A - Z)</option>
                </select>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  Cari Data
                </button>
                <a href="daftar-pasien.php" class="btn btn-secondary">
                  Reset
                </a>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabel Master Pasien -->
        <div class="ws-card">
          <div class="ws-card-header">
            <div>
              <h2 class="ws-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                  <circle cx="9" cy="7" r="4"/>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Direktori Pasien Terdaftar
              </h2>
              <div class="ws-card-subtitle">Menampilkan <?= $total_ditemukan; ?> pasien dari database</div>
            </div>
            <span class="badge badge-primary"><?= $total_ditemukan; ?> Pasien</span>
          </div>

          <div class="table-responsive">
            <table class="table ws-table align-middle">
              <thead>
                <tr>
                  <th style="width: 130px;">No. RM</th>
                  <th>Nama Pasien & NIK</th>
                  <th>JK / Tgl Lahir</th>
                  <th>No. Telepon</th>
                  <th>Alamat Domisili</th>
                  <th class="text-center" style="width: 100px;">Kunjungan</th>
                  <th class="text-center" style="width: 200px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($total_ditemukan > 0): ?>
                  <?php while ($p = mysqli_fetch_assoc($result)): ?>
                    <?php 
                      $tgl_lahir = new DateTime($p['tanggal_lahir']);
                      $usia = $tgl_lahir->diff(new DateTime())->y;
                    ?>
                    <tr>
                      <td>
                        <a href="detail-pasien.php?id=<?= $p['id_pasien']; ?>" class="cell-code fw-bold">
                          <?= htmlspecialchars($p['nomor_rm'] ?? 'RM-' . $p['id_rekam_medik']); ?>
                        </a>
                      </td>
                      <td>
                        <div class="fw-bold fs-6"><?= htmlspecialchars($p['nama_pasien']); ?></div>
                        <div class="text-muted small font-monospace">NIK: <?= htmlspecialchars($p['nik']); ?></div>
                      </td>
                      <td>
                        <div><?= htmlspecialchars($p['jenis_kelamin']); ?> (<?= $usia; ?> Thn)</div>
                        <div class="text-muted small"><?= date('d M Y', strtotime($p['tanggal_lahir'])); ?></div>
                      </td>
                      <td>
                        <span class="font-monospace"><?= htmlspecialchars($p['no_telp']); ?></span>
                      </td>
                      <td class="small text-muted" style="max-width: 220px;">
                        <?= htmlspecialchars(mb_strimwidth($p['alamat'], 0, 45, '...')); ?>
                      </td>
                      <td class="text-center">
                        <span class="badge badge-secondary"><?= $p['total_kunjungan']; ?>x</span>
                      </td>
                      <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                          <a href="detail-pasien.php?id=<?= $p['id_pasien']; ?>" class="btn btn-sm btn-secondary" title="Detail Profil">
                            Profil
                          </a>
                          <a href="riwayat-rekam-medik.php?id=<?= $p['id_pasien']; ?>" class="btn btn-sm btn-outline-primary" title="Riwayat RM">
                            RM
                          </a>
                          <a href="pendaftaran-pasien.php?cek_nik=<?= $p['nik']; ?>" class="btn btn-sm btn-primary" title="Daftarkan ke Antrean Poli">
                            &plus; Poli
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                      <div class="fw-semibold">Tidak Ada Data Pasien Ditemukan</div>
                      <div class="small">Silakan ubah kriteria pencarian atau tambahkan pasien baru.</div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>

<?php include '../../includes/footer.php'; ?>
