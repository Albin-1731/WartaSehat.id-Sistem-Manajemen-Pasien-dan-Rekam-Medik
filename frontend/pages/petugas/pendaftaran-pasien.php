<?php
require_once '../../backend/koneksi.php';
require_once '../../backend/auth_check.php';

check_petugas();

$page_title = 'Pendaftaran Pasien Baru - WartaSehat.id';
$current_page = 'pendaftaran';
$breadcrumb_parent = 'Dashboard';
$breadcrumb_current = 'Pendaftaran Pasien';
$breadcrumb_parent_url = 'dashboard.php';

// Hitung nomor RM otomatis berikutnya untuk Pasien Baru
$q_last = mysqli_query($connection, "SELECT id_pasien FROM pasien ORDER BY id_pasien DESC LIMIT 1");
$last_id = mysqli_fetch_assoc($q_last)['id_pasien'] ?? 0;
$next_rm = 'RM-' . date('Y') . '-' . sprintf('%04d', $last_id + 1);

// -----------------------------------------------------------------------------
// Pengecekan Pasien Berdasarkan NIK & NAMA via GET
// -----------------------------------------------------------------------------
$cek_nik = trim($_GET['cek_nik'] ?? '');
$cek_nama = trim($_GET['cek_nama'] ?? '');
$status_cek = null;
$pasien_ditemukan = null;

if (!empty($cek_nik) && !empty($cek_nama)) {
    $stmt_cek = mysqli_prepare($connection, "SELECT p.*, rm.nomor_rm, rm.id_rekam_medik 
                                             FROM pasien p 
                                             LEFT JOIN rekam_medik rm ON p.id_pasien = rm.id_pasien 
                                             WHERE p.nik = ? AND LOWER(p.nama_pasien) = LOWER(?) 
                                             ORDER BY rm.id_rekam_medik DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt_cek, "ss", $cek_nik, $cek_nama);
    mysqli_stmt_execute($stmt_cek);
    $res_cek = mysqli_stmt_get_result($stmt_cek);
    if ($row = mysqli_fetch_assoc($res_cek)) {
        $status_cek = 'lama';
        $pasien_ditemukan = $row;
    } else {
        $status_cek = 'baru';
    }
}

// -----------------------------------------------------------------------------
// Pemrosesan Formulir Simpan Pendaftaran (POST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = trim($_POST['nik'] ?? '');
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'Laki-laki';
    if ($jenis_kelamin === 'L') $jenis_kelamin = 'Laki-laki';
    if ($jenis_kelamin === 'P') $jenis_kelamin = 'Perempuan';
    $golongan_darah = $_POST['golongan_darah'] ?? '-';
    $agama = $_POST['agama'] ?? 'Islam';
    $status_pernikahan = $_POST['status_pernikahan'] ?? 'Belum Menikah';
    $pekerjaan = trim($_POST['pekerjaan'] ?? '-');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $id_poli = (int)($_POST['id_poli'] ?? 0);
    $id_dokter = (int)($_POST['id_dokter'] ?? 0);
    $keluhan_awal = trim($_POST['keluhan_awal'] ?? '');
    $id_petugas = $_SESSION['user_id'];

    if (!empty($nik) && !empty($nama_pasien) && !empty($id_poli) && !empty($id_dokter) && !empty($keluhan_awal)) {
        mysqli_begin_transaction($connection);
        try {
            // Verifikasi NIK & Nama
            $stmt_verif = mysqli_prepare($connection, "SELECT p.id_pasien, rm.id_rekam_medik, rm.nomor_rm 
                                                       FROM pasien p 
                                                       LEFT JOIN rekam_medik rm ON p.id_pasien = rm.id_pasien 
                                                       WHERE p.nik = ? AND LOWER(p.nama_pasien) = LOWER(?) 
                                                       ORDER BY rm.id_rekam_medik DESC LIMIT 1");
            mysqli_stmt_bind_param($stmt_verif, "ss", $nik, $nama_pasien);
            mysqli_stmt_execute($stmt_verif);
            $res_verif = mysqli_stmt_get_result($stmt_verif);

            if ($pasien_lama = mysqli_fetch_assoc($res_verif)) {
                // Pasien Lama
                $id_pasien = $pasien_lama['id_pasien'];
                $id_rekam_medik = $pasien_lama['id_rekam_medik'];

                if (empty($id_rekam_medik)) {
                    $nomor_rm = 'RM-' . date('Y') . '-' . sprintf('%04d', $id_pasien);
                    $stmt_new_rm = mysqli_prepare($connection, "INSERT INTO rekam_medik (id_pasien, nomor_rm) VALUES (?, ?)");
                    mysqli_stmt_bind_param($stmt_new_rm, "is", $id_pasien, $nomor_rm);
                    mysqli_stmt_execute($stmt_new_rm);
                    $id_rekam_medik = mysqli_insert_id($connection);
                }

                $stmt_lay = mysqli_prepare($connection, "INSERT INTO layanan (id_rekam_medik, id_poli, id_dokter, id_petugas, keluhan_awal, waktu_masuk, status_layanan) 
                                                         VALUES (?, ?, ?, ?, ?, NOW(), 'menunggu')");
                mysqli_stmt_bind_param($stmt_lay, "iiiis", $id_rekam_medik, $id_poli, $id_dokter, $id_petugas, $keluhan_awal);
                mysqli_stmt_execute($stmt_lay);

            } else {
                // Pasien Baru
                $stmt_pasien = mysqli_prepare($connection, "INSERT INTO pasien (nama_pasien, nik, tanggal_lahir, tempat_lahir, alamat, jenis_kelamin, golongan_darah, agama, status_pernikahan, pekerjaan, no_telp) 
                                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_pasien, "sssssssssss", $nama_pasien, $nik, $tanggal_lahir, $tempat_lahir, $alamat, $jenis_kelamin, $golongan_darah, $agama, $status_pernikahan, $pekerjaan, $no_telepon);
                mysqli_stmt_execute($stmt_pasien);
                $id_pasien = mysqli_insert_id($connection);

                $nomor_rm = 'RM-' . date('Y') . '-' . sprintf('%04d', $id_pasien);
                $stmt_rm = mysqli_prepare($connection, "INSERT INTO rekam_medik (id_pasien, nomor_rm) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt_rm, "is", $id_pasien, $nomor_rm);
                mysqli_stmt_execute($stmt_rm);
                $id_rekam_medik = mysqli_insert_id($connection);

                $stmt_lay = mysqli_prepare($connection, "INSERT INTO layanan (id_rekam_medik, id_poli, id_dokter, id_petugas, keluhan_awal, waktu_masuk, status_layanan) 
                                                         VALUES (?, ?, ?, ?, ?, NOW(), 'menunggu')");
                mysqli_stmt_bind_param($stmt_lay, "iiiis", $id_rekam_medik, $id_poli, $id_dokter, $id_petugas, $keluhan_awal);
                mysqli_stmt_execute($stmt_lay);
            }

            mysqli_commit($connection);
            header("Location: dashboard.php?pesan=berhasil_daftar");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($connection);
        }
    }
}

// Data Poli & Dokter untuk dropdown
$q_poli = mysqli_query($connection, "SELECT * FROM poli ORDER BY nama_poli ASC");
$q_dokter = mysqli_query($connection, "SELECT d.id_dokter, d.id_poli, d.nama_dokter, p.nama_poli 
                                       FROM dokter d 
                                       JOIN poli p ON d.id_poli = p.id_poli 
                                       ORDER BY p.nama_poli ASC, d.nama_dokter ASC");

// Hitung jumlah antrean hari ini per poli untuk nomor karcis dinamis
$antrean_poli_hari_ini = [];
$q_antrean_hari_ini = mysqli_query($connection, "SELECT id_poli, COUNT(*) AS total FROM layanan WHERE DATE(waktu_masuk) = CURDATE() GROUP BY id_poli");
while ($row_cnt = mysqli_fetch_assoc($q_antrean_hari_ini)) {
    $antrean_poli_hari_ini[(int)$row_cnt['id_poli']] = (int)$row_cnt['total'];
}

include '../../includes/header.php';
include '../../includes/sidebar_petugas.php';
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
            <a href="dashboard.php" class="ws-parent">Dashboard</a>
            <span class="ws-separator">/</span>
            <span class="ws-current">Pendaftaran Pasien</span>
          </nav>
        </div>

        <div class="ws-topbar-right">
          <a href="daftar-pasien.php" class="btn btn-secondary btn-sm">
            &larr; Lihat Data Pasien
          </a>
        </div>
      </header>

      <!-- Main Body Content -->
      <main class="ws-content-body">
        <div class="mb-4">
          <h1 class="h3 fw-bold mb-1">Formulir Pendaftaran Pasien</h1>
          <p class="text-muted mb-0">Lakukan pengecekan identitas pasien untuk memverifikasi riwayat rekam medis dan mendaftarkan antrean poliklinik.</p>
        </div>

        <!-- Form Pengecekan Pasien (NIK & Nama) -->
        <div class="ws-card mb-4">
          <div class="ws-card-header">
            <h2 class="ws-card-title">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              Pengecekan Pasien (NIK &amp; Nama)
            </h2>
          </div>
          <div class="ws-card-body">
            <form action="pendaftaran-pasien.php" method="GET" class="row g-3 align-items-end">
              <div class="col-12 col-md-5">
                <label for="cek_nik" class="form-label">
                  Nomor Induk Kependudukan (NIK) <span class="required">*</span>
                </label>
                <input 
                  type="text" 
                  class="form-control font-monospace" 
                  id="cek_nik" 
                  name="cek_nik" 
                  placeholder="Contoh: 3304123456780001" 
                  value="<?= htmlspecialchars($cek_nik); ?>"
                  maxlength="16" 
                  minlength="16"
                  pattern="[0-9]{16}"
                  title="NIK harus berjumlah tepat 16 angka"
                  required
                >
              </div>
              <div class="col-12 col-md-5">
                <label for="cek_nama" class="form-label">
                  Nama Lengkap Pasien <span class="required">*</span>
                </label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="cek_nama" 
                  name="cek_nama" 
                  placeholder="Contoh: Siti Rahmawati" 
                  value="<?= htmlspecialchars($cek_nama); ?>"
                  maxlength="100"
                  required
                >
              </div>
              <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                  Cek Pasien
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Main Form (POST to Backend PHP) -->
        <form action="pendaftaran-pasien.php" method="POST" id="formPendaftaran">
          <div class="row g-4">
            <!-- Left Column: Data Identitas Pasien (Tabel `pasien`) -->
            <div class="col-12 col-lg-8">
              <div class="ws-card">
                <div class="ws-card-header">
                  <h2 class="ws-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                      <circle cx="12" cy="7" r="4"/>
                    </svg>
                    A. Data Identitas Pasien (Tabel: <code>pasien</code>)
                  </h2>
                  <span class="badge badge-primary"><?= ($status_cek === 'lama') ? 'Pasien Lama' : 'Pasien Baru'; ?></span>
                </div>
                <div class="ws-card-body">
                  <!-- Row 1: Nomor RM & NIK -->
                  <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                      <label for="nomor_rm" class="form-label">
                        Nomor RM (Otomatis)
                      </label>
                      <input 
                        type="text" 
                        class="form-control bg-light fw-bold text-primary font-monospace" 
                        id="nomor_rm" 
                        name="nomor_rm" 
                        value="<?= htmlspecialchars($pasien_ditemukan['nomor_rm'] ?? $next_rm); ?>" 
                        readonly 
                        title="Dihasilkan secara otomatis oleh sistem backend"
                      >
                      <div class="form-text">Generate otomatis format tahun.</div>
                    </div>

                    <div class="col-12 col-md-8">
                      <label for="nik" class="form-label">
                        Nomor Induk Kependudukan (NIK) <span class="required">*</span>
                      </label>
                      <input 
                        type="text" 
                        class="form-control font-monospace" 
                        id="nik" 
                        name="nik" 
                        placeholder="Contoh: 3304123456780001" 
                        value="<?= htmlspecialchars($pasien_ditemukan['nik'] ?? $cek_nik); ?>"
                        required 
                        maxlength="16" 
                        minlength="16"
                        pattern="[0-9]{16}"
                        title="NIK harus berjumlah tepat 16 angka"
                      >
                      <div class="form-text">16 digit angka sesuai KTP / Kartu Keluarga.</div>
                    </div>
                  </div>

                  <!-- Row 2: Nama Lengkap -->
                  <div class="mb-3">
                    <label for="nama_pasien" class="form-label">
                      Nama Lengkap Pasien <span class="required">*</span>
                    </label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="nama_pasien" 
                      name="nama_pasien" 
                      placeholder="Masukkan nama lengkap pasien tanpa singkatan" 
                      value="<?= htmlspecialchars($pasien_ditemukan['nama_pasien'] ?? $cek_nama); ?>"
                      required 
                      maxlength="100"
                    >
                  </div>

                  <!-- Row 3: Tempat & Tanggal Lahir -->
                  <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                      <label for="tempat_lahir" class="form-label">
                        Tempat Lahir <span class="required">*</span>
                      </label>
                      <input 
                        type="text" 
                        class="form-control" 
                        id="tempat_lahir" 
                        name="tempat_lahir" 
                        placeholder="Kota / Kabupaten kelahiran" 
                        value="<?= htmlspecialchars($pasien_ditemukan['tempat_lahir'] ?? ''); ?>"
                        required 
                        maxlength="50"
                      >
                    </div>

                    <div class="col-12 col-md-6">
                      <label for="tanggal_lahir" class="form-label">
                        Tanggal Lahir <span class="required">*</span>
                      </label>
                      <input 
                        type="date" 
                        class="form-control" 
                        id="tanggal_lahir" 
                        name="tanggal_lahir" 
                        value="<?= htmlspecialchars($pasien_ditemukan['tanggal_lahir'] ?? ''); ?>"
                        required
                        max="<?= date('Y-m-d'); ?>"
                      >
                    </div>
                  </div>

                  <!-- Row 4: Jenis Kelamin & Golongan Darah -->
                  <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                      <label class="form-label d-block">
                        Jenis Kelamin <span class="required">*</span>
                      </label>
                      <?php 
                        $jk_val = $pasien_ditemukan['jenis_kelamin'] ?? 'Laki-laki'; 
                        if ($jk_val === 'L') $jk_val = 'Laki-laki';
                        if ($jk_val === 'P') $jk_val = 'Perempuan';
                      ?>
                      <div class="d-flex gap-4 pt-1">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="jenis_kelamin" id="jk_l" value="Laki-laki" required <?= ($jk_val === 'Laki-laki') ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="jk_l">
                            Laki-laki (L)
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="jenis_kelamin" id="jk_p" value="Perempuan" required <?= ($jk_val === 'Perempuan') ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="jk_p">
                            Perempuan (P)
                          </label>
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-6">
                      <label for="golongan_darah" class="form-label">
                        Golongan Darah
                      </label>
                      <?php $goldar_val = $pasien_ditemukan['golongan_darah'] ?? '-'; ?>
                      <select class="form-select" id="golongan_darah" name="golongan_darah">
                        <option value="-" <?= ($goldar_val === '-') ? 'selected' : ''; ?>>Tidak Tahu / Belum Cek (-)</option>
                        <option value="A" <?= ($goldar_val === 'A') ? 'selected' : ''; ?>>Golongan A</option>
                        <option value="B" <?= ($goldar_val === 'B') ? 'selected' : ''; ?>>Golongan B</option>
                        <option value="AB" <?= ($goldar_val === 'AB') ? 'selected' : ''; ?>>Golongan AB</option>
                        <option value="O" <?= ($goldar_val === 'O') ? 'selected' : ''; ?>>Golongan O</option>
                      </select>
                    </div>
                  </div>

                  <!-- Row 5: Agama & Status Pernikahan -->
                  <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                      <label for="agama" class="form-label">Agama</label>
                      <?php $agama_val = $pasien_ditemukan['agama'] ?? 'Islam'; ?>
                      <select class="form-select" id="agama" name="agama">
                        <option value="Islam" <?= ($agama_val === 'Islam') ? 'selected' : ''; ?>>Islam</option>
                        <option value="Kristen" <?= ($agama_val === 'Kristen') ? 'selected' : ''; ?>>Kristen</option>
                        <option value="Katolik" <?= ($agama_val === 'Katolik') ? 'selected' : ''; ?>>Katolik</option>
                        <option value="Hindu" <?= ($agama_val === 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                        <option value="Buddha" <?= ($agama_val === 'Buddha') ? 'selected' : ''; ?>>Buddha</option>
                        <option value="Konghucu" <?= ($agama_val === 'Konghucu') ? 'selected' : ''; ?>>Konghucu</option>
                        <option value="Lainnya" <?= ($agama_val === 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                      </select>
                    </div>

                    <div class="col-12 col-md-6">
                      <label for="status_pernikahan" class="form-label">Status Pernikahan</label>
                      <?php $nikah_val = $pasien_ditemukan['status_pernikahan'] ?? 'Belum Menikah'; ?>
                      <select class="form-select" id="status_pernikahan" name="status_pernikahan">
                        <option value="Belum Menikah" <?= ($nikah_val === 'Belum Menikah') ? 'selected' : ''; ?>>Belum Menikah</option>
                        <option value="Menikah" <?= ($nikah_val === 'Menikah') ? 'selected' : ''; ?>>Menikah</option>
                        <option value="Cerai Hidup" <?= ($nikah_val === 'Cerai Hidup') ? 'selected' : ''; ?>>Cerai Hidup</option>
                        <option value="Cerai Mati" <?= ($nikah_val === 'Cerai Mati') ? 'selected' : ''; ?>>Cerai Mati</option>
                      </select>
                    </div>
                  </div>

                  <!-- Row 6: Pekerjaan & No. Telepon -->
                  <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                      <label for="pekerjaan" class="form-label">Pekerjaan</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        id="pekerjaan" 
                        name="pekerjaan" 
                        placeholder="Contoh: Karyawan Swasta, Wiraswasta, PNS" 
                        value="<?= htmlspecialchars($pasien_ditemukan['pekerjaan'] ?? ''); ?>"
                        maxlength="50"
                      >
                    </div>

                    <div class="col-12 col-md-6">
                      <label for="no_telepon" class="form-label">
                        No. Telepon / WhatsApp <span class="required">*</span>
                      </label>
                      <input 
                        type="tel" 
                        class="form-control font-monospace" 
                        id="no_telepon" 
                        name="no_telepon" 
                        placeholder="Contoh: 081234567890" 
                        value="<?= htmlspecialchars($pasien_ditemukan['no_telp'] ?? ''); ?>"
                        required 
                        pattern="[0-9]{9,15}"
                        title="Masukkan nomor telepon valid (9-15 digit)"
                      >
                    </div>
                  </div>

                  <!-- Row 7: Alamat Lengkap -->
                  <div class="mb-3">
                    <label for="alamat" class="form-label">
                      Alamat Domisili Pasien <span class="required">*</span>
                    </label>
                    <textarea 
                      class="form-control" 
                      id="alamat" 
                      name="alamat" 
                      rows="3" 
                      placeholder="Tuliskan nama jalan, RT/RW, Dusun, Kelurahan, Kecamatan, dan Kota/Kabupaten" 
                      required
                    ><?= htmlspecialchars($pasien_ditemukan['alamat'] ?? ''); ?></textarea>
                  </div>

                  <!-- Row 8: Tanggal Pendaftaran -->
                  <div class="mb-0">
                    <label for="tanggal_daftar" class="form-label">Tanggal Pendaftaran</label>
                    <input 
                      type="date" 
                      class="form-control bg-light" 
                      id="tanggal_daftar" 
                      name="tanggal_daftar" 
                      value="<?= date('Y-m-d'); ?>" 
                      readonly
                    >
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Pendaftaran Antrean Poliklinik & Karcis -->
            <div class="col-12 col-lg-4">
              <!-- Card B: Antrean Poli Hari Ini -->
              <div class="ws-card mb-4">
                <div class="ws-card-header">
                  <h2 class="ws-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"/>
                      <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    B. Kunjungan Poli Hari Ini
                  </h2>
                </div>
                <div class="ws-card-body">
                  <div class="mb-3">
                    <label for="id_poli" class="form-label">
                      Poliklinik Tujuan <span class="required">*</span>
                    </label>
                    <select class="form-select" id="id_poli" name="id_poli" required onchange="filterDokterByPoli()">
                      <option value="">-- Pilih Poliklinik --</option>
                      <?php while ($poli = mysqli_fetch_assoc($q_poli)): ?>
                        <option value="<?= $poli['id_poli']; ?>">
                          <?= htmlspecialchars($poli['nama_poli']); ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label for="id_dokter" class="form-label">
                      Dokter Pemeriksa <span class="required">*</span>
                    </label>
                    <select class="form-select" id="id_dokter" name="id_dokter" required disabled>
                      <option value="">-- Pilih Poliklinik Terlebih Dahulu --</option>
                      <?php while ($dok = mysqli_fetch_assoc($q_dokter)): ?>
                        <option value="<?= $dok['id_dokter']; ?>" data-poli="<?= $dok['id_poli']; ?>">
                          <?= htmlspecialchars($dok['nama_dokter']); ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label for="keluhan_awal" class="form-label">
                      Keluhan Awal Pasien <span class="required">*</span>
                    </label>
                    <textarea 
                      class="form-control" 
                      id="keluhan_awal" 
                      name="keluhan_awal" 
                      rows="3" 
                      placeholder="Keluhan utama yang dirasakan pasien saat datang..."
                      required
                    ></textarea>
                  </div>

                  <!-- Hidden default status antrean for backend -->
                  <input type="hidden" name="status_antrean" value="Menunggu">
                  <input type="hidden" name="tanggal_kunjungan" value="<?= date('Y-m-d'); ?>">
                </div>
              </div>

              <!-- Ticket Preview Card (Dinamis dari Database) -->
              <div class="ws-queue-ticket mb-4">
                <div class="text-uppercase small fw-bold text-muted">Estimasi Karcis Antrean</div>
                <div class="ws-queue-number" id="ticketQueueNumber">--</div>
                <div class="ws-queue-poli" id="ticketPoliLabel">Poliklinik Tujuan</div>
                <div class="text-muted small mt-2">
                  Dokter: <strong id="ticketDokterLabel">--</strong>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="d-grid gap-2 ws-form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                  </svg>
                  Simpan & Daftarkan Antrean
                </button>
                <button type="reset" class="btn btn-secondary">
                  Reset Formulir
                </button>
                <a href="dashboard.php" class="btn btn-outline-danger">
                  Batal
                </a>
              </div>
            </div>
          </div>
        </form>

      </main>
    </div>
  </div>

<script>
// Estimasi nomor antrean berikutnya per poli (dihitung dinamis dari database hari ini)
const nextQueuePerPoli = {
  <?php 
    mysqli_data_seek($q_poli, 0);
    $poli_prefixes = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G'];
    while ($p = mysqli_fetch_assoc($q_poli)): 
      $p_id = (int)$p['id_poli'];
      $total_today = $antrean_poli_hari_ini[$p_id] ?? 0;
      $huruf = $poli_prefixes[$p_id] ?? 'P';
      $nomor_karcis = $huruf . '-' . sprintf('%03d', $total_today + 1);
  ?>
    "<?= $p_id; ?>": "<?= $nomor_karcis; ?>",
  <?php endwhile; ?>
};

// Data seluruh dokter dari database untuk filter chained dropdown
const allDokterOptions = [
  <?php 
    mysqli_data_seek($q_dokter, 0);
    while ($d = mysqli_fetch_assoc($q_dokter)): 
  ?>
    { id: "<?= $d['id_dokter']; ?>", poli: "<?= $d['id_poli']; ?>", nama: "<?= addslashes($d['nama_dokter']); ?>" },
  <?php endwhile; ?>
];

// Data master pasien terdaftar untuk sinkronisasi NAMA dan NIK jika diketik langsung di form utama
const masterPasien = [
  <?php
    $q_all_p = mysqli_query($connection, "SELECT p.*, rm.nomor_rm FROM pasien p LEFT JOIN rekam_medik rm ON p.id_pasien = rm.id_pasien ORDER BY rm.id_rekam_medik DESC");
    while ($mp = mysqli_fetch_assoc($q_all_p)):
  ?>
    {
      nik: "<?= $mp['nik']; ?>",
      nama: "<?= addslashes(strtolower($mp['nama_pasien'])); ?>",
      nomor_rm: "<?= $mp['nomor_rm'] ?? ''; ?>",
      tempat_lahir: "<?= addslashes($mp['tempat_lahir'] ?? ''); ?>",
      tanggal_lahir: "<?= $mp['tanggal_lahir']; ?>",
      jenis_kelamin: "<?= $mp['jenis_kelamin']; ?>",
      golongan_darah: "<?= $mp['golongan_darah'] ?? '-'; ?>",
      agama: "<?= $mp['agama'] ?? 'Islam'; ?>",
      status_pernikahan: "<?= $mp['status_pernikahan'] ?? 'Belum Menikah'; ?>",
      pekerjaan: "<?= addslashes($mp['pekerjaan'] ?? ''); ?>",
      no_telepon: "<?= $mp['no_telp']; ?>",
      alamat: "<?= addslashes(str_replace(["\r", "\n"], ' ', $mp['alamat'])); ?>"
    },
  <?php endwhile; ?>
];

function verifikasiNamaDanNik() {
  const nikInput = document.getElementById('nik').value.trim();
  const namaInput = document.getElementById('nama_pasien').value.trim().toLowerCase();
  
  if (nikInput.length === 16 && namaInput.length > 0) {
    const matched = masterPasien.find(function(p) {
      return p.nik === nikInput && p.nama === namaInput;
    });

    if (matched) {
      if (matched.nomor_rm) {
        document.getElementById('nomor_rm').value = matched.nomor_rm;
      }
      if (matched.tempat_lahir) document.getElementById('tempat_lahir').value = matched.tempat_lahir;
      if (matched.tanggal_lahir) document.getElementById('tanggal_lahir').value = matched.tanggal_lahir;
      if (matched.jenis_kelamin === 'Laki-laki') {
        document.getElementById('jk_l').checked = true;
      } else if (matched.jenis_kelamin === 'Perempuan') {
        document.getElementById('jk_p').checked = true;
      }
      if (matched.golongan_darah) document.getElementById('golongan_darah').value = matched.golongan_darah;
      if (matched.agama) document.getElementById('agama').value = matched.agama;
      if (matched.status_pernikahan) document.getElementById('status_pernikahan').value = matched.status_pernikahan;
      if (matched.pekerjaan) document.getElementById('pekerjaan').value = matched.pekerjaan;
      if (matched.no_telepon) document.getElementById('no_telepon').value = matched.no_telepon;
      if (matched.alamat) document.getElementById('alamat').value = matched.alamat;
    }
  }
}

document.getElementById('nik').addEventListener('input', verifikasiNamaDanNik);
document.getElementById('nama_pasien').addEventListener('input', verifikasiNamaDanNik);

function filterDokterByPoli() {
  const poliSelect = document.getElementById('id_poli');
  const dokterSelect = document.getElementById('id_dokter');
  const selectedPoli = poliSelect.value;
  
  dokterSelect.innerHTML = '';
  
  if (!selectedPoli) {
    dokterSelect.disabled = true;
    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = '-- Pilih Poliklinik Terlebih Dahulu --';
    dokterSelect.appendChild(defaultOpt);
    document.getElementById('ticketQueueNumber').textContent = '--';
    document.getElementById('ticketPoliLabel').textContent = 'Poliklinik Tujuan';
    document.getElementById('ticketDokterLabel').textContent = '--';
    return;
  }
  
  dokterSelect.disabled = false;
  const defaultOpt = document.createElement('option');
  defaultOpt.value = '';
  defaultOpt.textContent = '-- Pilih Dokter --';
  dokterSelect.appendChild(defaultOpt);
  
  const selectedPoliText = poliSelect.options[poliSelect.selectedIndex].text;
  document.getElementById('ticketPoliLabel').textContent = selectedPoliText;
  document.getElementById('ticketQueueNumber').textContent = nextQueuePerPoli[selectedPoli] || '--';
  
  allDokterOptions.forEach(function(dok) {
    if (dok.poli === selectedPoli) {
      const opt = document.createElement('option');
      opt.value = dok.id;
      opt.textContent = dok.nama;
      dokterSelect.appendChild(opt);
    }
  });
}

document.getElementById('id_dokter').addEventListener('change', function() {
  const dokText = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : '--';
  document.getElementById('ticketDokterLabel').textContent = dokText;
});
</script>

</body>
</html>
