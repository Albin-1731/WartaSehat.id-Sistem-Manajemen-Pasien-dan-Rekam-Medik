-- =============================================================================
-- Database Target: db_project_praktikum
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `db_project_praktikum` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_project_praktikum`;

-- -----------------------------------------------------------------------------
-- 1. TABEL: poli
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `layanan`;
DROP TABLE IF EXISTS `rekam_medik`;
DROP TABLE IF EXISTS `pasien`;
DROP TABLE IF EXISTS `dokter`;
DROP TABLE IF EXISTS `petugas`;
DROP TABLE IF EXISTS `poli`;

CREATE TABLE `poli` (
  `id_poli` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_poli` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. TABEL: petugas
-- -----------------------------------------------------------------------------
CREATE TABLE `petugas` (
  `id_petugas` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_petugas` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. TABEL: dokter
-- -----------------------------------------------------------------------------
CREATE TABLE `dokter` (
  `id_dokter` INT AUTO_INCREMENT PRIMARY KEY,
  `id_poli` INT NOT NULL,
  `nama_dokter` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  CONSTRAINT `fk_dokter_poli` FOREIGN KEY (`id_poli`) 
    REFERENCES `poli` (`id_poli`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. TABEL: pasien
-- -----------------------------------------------------------------------------
CREATE TABLE `pasien` (
  `id_pasien` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_pasien` VARCHAR(100) NOT NULL,
  `nik` VARCHAR(16) NOT NULL UNIQUE,
  `tanggal_lahir` DATE NOT NULL,
  `tempat_lahir` VARCHAR(50) DEFAULT NULL,
  `alamat` TEXT NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL,
  `golongan_darah` VARCHAR(5) DEFAULT '-',
  `agama` VARCHAR(20) DEFAULT NULL,
  `status_pernikahan` VARCHAR(30) DEFAULT NULL,
  `pekerjaan` VARCHAR(50) DEFAULT NULL,
  `no_telp` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TABEL: rekam_medik
-- -----------------------------------------------------------------------------
CREATE TABLE `rekam_medik` (
  `id_rekam_medik` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pasien` INT NOT NULL,
  `nomor_rm` VARCHAR(30) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rekam_medik_pasien` FOREIGN KEY (`id_pasien`) 
    REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TABEL: layanan
-- Terdiri dari 2 fase pengisian:
--   a. REGIST - PETUGAS: data loket & keluhan awal
--   b. PEMERIKSAAN - DOKTER: hasil diagnosis, tindakan, resep & status akhir
-- -----------------------------------------------------------------------------
CREATE TABLE `layanan` (
  `id_layanan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rekam_medik` INT NOT NULL,
  `id_poli` INT NOT NULL,
  `id_dokter` INT NOT NULL,
  `id_petugas` INT NOT NULL,
  `keluhan_awal` TEXT NOT NULL,
  `waktu_masuk` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_layanan` ENUM('menunggu', 'diperiksa', 'selesai') NOT NULL DEFAULT 'menunggu',
  
  -- Bagian Pemeriksaan Dokter
  `pemeriksaan_awal` TEXT DEFAULT NULL,
  `diagnosis_primer` VARCHAR(255) DEFAULT NULL,
  `diagnosis_sekunder` VARCHAR(255) DEFAULT NULL,
  `tindakan` TEXT DEFAULT NULL,
  `pemeriksaan_akhir` TEXT DEFAULT NULL,
  `catatan_dokter` TEXT DEFAULT NULL,
  `waktu_keluar` DATETIME DEFAULT NULL,
  `status_kondisi` ENUM('Rawat Jalan', 'Rawat Inap', 'Sembuh', 'Meninggal') DEFAULT NULL,

  CONSTRAINT `fk_layanan_rekam_medik` FOREIGN KEY (`id_rekam_medik`) 
    REFERENCES `rekam_medik` (`id_rekam_medik`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_layanan_poli` FOREIGN KEY (`id_poli`) 
    REFERENCES `poli` (`id_poli`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_layanan_dokter` FOREIGN KEY (`id_dokter`) 
    REFERENCES `dokter` (`id_dokter`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_layanan_petugas` FOREIGN KEY (`id_petugas`) 
    REFERENCES `petugas` (`id_petugas`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DATA SEEDER AWAL (Testing & Demo)
-- Catatan: Password default seluruh akun adalah "password123"
-- Hash: $2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa
-- =============================================================================

-- 1. Seeder Poliklinik
INSERT INTO `poli` (`id_poli`, `nama_poli`) VALUES
(1, 'Poli Umum'),
(2, 'Poli Penyakit Dalam'),
(3, 'Poli Gigi & Mulut'),
(4, 'Poli Anak'),
(5, 'Poli Mata'),
(6, 'Poli THT'),
(7, 'Poli Saraf');

-- 2. Seeder Petugas (Beberapa Petugas)
INSERT INTO `petugas` (`id_petugas`, `nama_petugas`, `username`, `password`) VALUES
(1, 'Budi Santoso', 'petugas_budi', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(2, 'Siti Rahma', 'petugas_siti', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(3, 'Dewi Sartika', 'petugas_dewi', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa');

-- 3. Seeder Dokter (2 Dokter per Poliklinik untuk Semua Poli)
INSERT INTO `dokter` (`id_dokter`, `id_poli`, `nama_dokter`, `username`, `password`) VALUES
(1, 2, 'dr. Hendra Pratama, Sp.PD', 'dr_hendra', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(2, 1, 'dr. Siti Nurhaliza', 'dr_siti', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(3, 3, 'drg. Maya Sari', 'drg_maya', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(4, 4, 'dr. Ratna Juwita, Sp.A', 'dr_ratna', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(5, 5, 'dr. Budi Setiawan, Sp.M', 'dr_budi_spm', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(6, 6, 'dr. Bambang Irawan, Sp.THT-KL', 'dr_bambang', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(7, 7, 'dr. Dian Anggraini, Sp.S', 'dr_dian', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(8, 1, 'dr. Rizky Kurniawan', 'dr_rizky', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(9, 2, 'dr. Farah Nabila, Sp.PD', 'dr_farah', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(10, 3, 'drg. Fajar Ramadhan', 'drg_fajar', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(11, 4, 'dr. Kevin Sanjaya, Sp.A', 'dr_kevin', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(12, 5, 'dr. Anita Kusuma, Sp.M', 'dr_anita', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(13, 6, 'dr. Rio Wicaksono, Sp.THT-KL', 'dr_rio', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa'),
(14, 7, 'dr. Nadya Putri, Sp.S', 'dr_nadya', '$2y$10$NmC5WoUeVR9Yi0U/eXrm0uJDjg4RiIVmKOvns7RJiRheJ898jXXQa');

-- 4. Seeder Pasien Awal
INSERT INTO `pasien` (`id_pasien`, `nama_pasien`, `nik`, `tanggal_lahir`, `tempat_lahir`, `alamat`, `jenis_kelamin`, `golongan_darah`, `agama`, `status_pernikahan`, `pekerjaan`, `no_telp`) VALUES
(1, 'Ahmad Fauzi', '3304123456780001', '1981-05-12', 'Yogyakarta', 'Jl. Malioboro No. 12, Danurejan, Kota Yogyakarta', 'Laki-laki', 'O', 'Islam', 'Menikah', 'Wiraswasta', '081234567890'),
(2, 'Siti Rahmawati', '3304123456780002', '1992-05-14', 'Bantul', 'Jl. Anggrek No. 5 RT 03/RW 02, Banguntapan, Bantul', 'Perempuan', 'B', 'Islam', 'Menikah', 'Guru SD', '081398765432'),
(3, 'Bambang Suryono', '3304123456780003', '1975-11-20', 'Sleman', 'Jl. Kaliurang KM 7 No. 45, Depok, Sleman', 'Laki-laki', 'A', 'Islam', 'Menikah', 'PNS', '081223344556'),
(4, 'Dewi Lestari', '3304123456780004', '1998-03-08', 'Yogyakarta', 'Jl. Gejayan No. 88, Caturtunggal, Sleman', 'Perempuan', 'AB', 'Kristen', 'Belum Menikah', 'Karyawan Swasta', '081778899001'),
(5, 'Ananda Putri', '3304123456780005', '2018-09-15', 'Bantul', 'Jl. Imogiri Timur KM 10, Pleret, Bantul', 'Perempuan', 'O', 'Islam', 'Belum Menikah', 'Pelajar', '081556677889');

-- 5. Seeder Rekam Medik
INSERT INTO `rekam_medik` (`id_rekam_medik`, `id_pasien`, `nomor_rm`, `created_at`) VALUES
(1, 1, 'RM-2026-0038', '2026-01-10 08:00:00'),
(2, 2, 'RM-2026-0012', '2026-01-15 08:30:00'),
(3, 3, 'RM-2026-0015', '2026-01-20 09:00:00'),
(4, 4, 'RM-2026-0022', '2026-02-01 10:15:00'),
(5, 5, 'RM-2026-0045', '2026-02-10 11:00:00');

-- 6. Seeder Layanan (Contoh Antrean & Pemeriksaan)
INSERT INTO `layanan` (
  `id_layanan`, `id_rekam_medik`, `id_poli`, `id_dokter`, `id_petugas`, 
  `keluhan_awal`, `waktu_masuk`, `status_layanan`,
  `pemeriksaan_awal`, `diagnosis_primer`, `diagnosis_sekunder`, `tindakan`, 
  `pemeriksaan_akhir`, `catatan_dokter`, `waktu_keluar`, `status_kondisi`
) VALUES
-- Kunjungan 1: Pasien Ahmad Fauzi di Poli Penyakit Dalam (Sedang Diperiksa)
(1, 1, 2, 1, 1, 
 'Nyeri dada sebelah kiri tembus ke punggung dan sesak napas ringan setelah beraktivitas fisik sejak kemarin malam.', 
 '2026-11-16 08:15:00', 'diperiksa',
 'TD: 135/85 mmHg, Suhu: 36.7 C, BB: 68 kg, TB: 172 cm. Anamnesis: Nyeri dada kiri menjalar ke punggung, durasi 15-20 menit. Tidak ada riwayat alergi obat.',
 'I20.9 - Angina Pectoris, Unspecified', 'I10 - Essential Hypertension',
 'Pemeriksaan Rekam Jantung EKG 12 Lead, Pemasangan O2 nasal kanul 3 Lpm',
 'TD Akhir: 125/80 mmHg, Suhu: 36.5 C, Nadi: 82 x/mnt, RR: 18 x/mnt. Nyeri berkurang.',
 'R/ ISDN 5mg tab No. X, R/ Aspilet 80mg No. XXX, R/ Atorvastatin 20mg No. XXX. Hindari rokok dan aktivitas berat.',
 NULL, NULL),

-- Kunjungan 2: Pasien Siti Rahmawati di Poli Penyakit Dalam (Menunggu / Selesai Pemeriksaan Kunjungan Sebelumnya)
(2, 2, 2, 1, 1,
 'Demam naik turun sejak 3 hari disertai mual dan lemas.',
 '2026-11-16 08:30:00', 'menunggu',
 NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),

-- Kunjungan 3: Pasien Bambang Suryono di Poli Umum (Selesai)
(3, 3, 1, 2, 2,
 'Batuk berdahak sudah 4 hari disertai pilek dan tenggorokan sakit.',
 '2026-11-16 08:00:00', 'selesai',
 'TD: 120/80 mmHg, Suhu: 37.2 C, BB: 72 kg, TB: 168 cm. Faring hiperemis (+).',
 'J00 - Acute Nasopharyngitis (Common Cold)', NULL,
 'Edukasi istirahat, banyak minum air hangat',
 'Kondisi stabil, keluhan batuk terkontrol',
 'R/ Paracetamol 500mg No. X, R/ Ambroxol 30mg No. X, R/ Vitamin C 500mg No. X.',
 '2026-11-16 08:25:00', 'Rawat Jalan'),

-- Kunjungan 4: Pasien Dewi Lestari di Poli Gigi & Mulut (Menunggu)
(4, 4, 3, 3, 2,
 'Gigi geraham bawah kanan berlubang dan ngilu saat makan/minum dingin.',
 '2026-11-16 08:45:00', 'menunggu',
 NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),

-- Kunjungan 5: Pasien Ananda Putri di Poli Anak (Menunggu)
(5, 5, 4, 4, 1,
 'Batuk pilek dan demam ringan sejak semalam.',
 '2026-11-16 09:00:00', 'menunggu',
 NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
