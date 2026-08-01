-- Database: sig_tps
-- Format: MySQL / MariaDB (Laragon phpMyAdmin)

CREATE DATABASE IF NOT EXISTS `sig_tps`;
USE `sig_tps`;

-- Table structure for table `data_latih`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `data_latih`;
CREATE TABLE `data_latih` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_fasilitas` varchar(255) NOT NULL,
  `alamat_desa` varchar(255) NOT NULL,
  `jenis_fasilitas` enum('TPS 3R','Biodigester','Bank Sampah') NOT NULL DEFAULT 'TPS 3R',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `kepadatan` enum('Rendah','Sedang','Tinggi') NOT NULL,
  `jarak_permukiman` varchar(50) NOT NULL,
  `jarak_air` varchar(50) NOT NULL,
  `status` enum('layak','tidak_layak') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table `usulan_lokasi`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `usulan_lokasi`;
CREATE TABLE `usulan_lokasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `kecamatan` varchar(100) NOT NULL,
  `jenis_fasilitas` enum('TPS 3R','Biodigester','Bank Sampah') NOT NULL DEFAULT 'TPS 3R',
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `kepadatan` varchar(50) NOT NULL,
  `jarak_permukiman` varchar(50) NOT NULL,
  `jarak_air` varchar(50) NOT NULL,
  `status` enum('layak','tidak_layak','proses') DEFAULT 'proses',
  `rule` varchar(255) DEFAULT NULL,
  `confidence` varchar(10) DEFAULT NULL,
  `rule_id` varchar(50) DEFAULT NULL,
  `rule_detail` text DEFAULT NULL,
  `predicted_at` timestamp NULL DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table `evaluasi_model`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `evaluasi_model`;
CREATE TABLE `evaluasi_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iteration` varchar(20) NOT NULL,
  `accuracy` decimal(5,2) NOT NULL,
  `precision` decimal(5,2) NOT NULL,
  `recall` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table `c45_model`
-- Decision Tree model storage for C4.5 algorithm
-- --------------------------------------------------------
DROP TABLE IF EXISTS `c45_model`;
CREATE TABLE `c45_model` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(50) NOT NULL DEFAULT 'belum_dibentuk',
  `tree_json` longtext DEFAULT NULL,
  `rules_json` longtext DEFAULT NULL,
  `root_attribute` varchar(100) DEFAULT NULL,
  `total_nodes` int NOT NULL DEFAULT 0,
  `total_rules` int NOT NULL DEFAULT 0,
  `total_data_latih` int NOT NULL DEFAULT 0,
  `entropy_total` float NOT NULL DEFAULT 0,
  `training_log` longtext DEFAULT NULL,
  `trained_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
