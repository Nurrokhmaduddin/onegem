-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for erp_berlian
CREATE DATABASE IF NOT EXISTS `erp_berlian` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `erp_berlian`;

-- Dumping structure for table erp_berlian.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL COMMENT 'NULL jika aksi sistem otomatis',
  `username` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Snapshot username saat aksi (tidak berubah walau user diedit)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'USER, DIAMOND, SALES, dll',
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'LOGIN, LOGOUT, CREATE, UPDATE, DELETE, APPROVE, POST',
  `document_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nomor dokumen terkait (jika ada)',
  `table_name` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID record yang diubah',
  `before_value` json DEFAULT NULL COMMENT 'Nilai sebelum perubahan',
  `after_value` json DEFAULT NULL COMMENT 'Nilai setelah perubahan',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Deskripsi bebas aktivitas',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_table` (`table_name`,`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail seluruh aktivitas sistem — retensi 7 tahun';

-- Dumping data for table erp_berlian.audit_logs: ~111 rows (approximately)
INSERT INTO `audit_logs` (`id`, `user_id`, `username`, `ip_address`, `user_agent`, `module`, `action`, `document_no`, `table_name`, `record_id`, `before_value`, `after_value`, `description`, `created_at`) VALUES
	(1, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:01:55'),
	(2, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:02:12'),
	(3, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:02:31'),
	(4, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:03:11'),
	(5, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:05:12'),
	(6, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:26:20'),
	(7, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:37:50'),
	(8, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:42:37'),
	(9, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:42:46'),
	(10, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:43:06'),
	(11, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:43:15'),
	(12, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:43:30'),
	(13, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:45:20'),
	(14, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:45:27'),
	(15, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:45:40'),
	(16, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:46:08'),
	(17, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:46:26'),
	(18, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:52:47'),
	(19, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:52:53'),
	(20, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:53:50'),
	(21, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:53:55'),
	(22, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:54:09'),
	(23, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:58:46'),
	(24, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 13:59:00'),
	(25, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:03:25'),
	(26, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:04:11'),
	(27, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:04:49'),
	(28, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:08:41'),
	(29, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 14:10:55'),
	(30, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:11:09'),
	(31, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:31:53'),
	(32, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 14:42:58'),
	(33, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 14:44:29'),
	(34, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 14:44:40'),
	(35, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-06 14:52:34'),
	(36, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:52:38'),
	(37, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:53:53'),
	(38, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:54:15'),
	(39, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:54:27'),
	(40, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:54:54'),
	(41, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:55:00'),
	(42, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:55:06'),
	(43, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:55:44'),
	(44, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:56:07'),
	(45, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:56:11'),
	(46, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:56:18'),
	(47, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:56:29'),
	(48, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:58:18'),
	(49, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 14:58:57'),
	(50, 1, 'admin', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:03:11'),
	(51, 1, 'admin', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:04:47'),
	(52, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:07:50'),
	(53, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:08:39'),
	(54, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:09:38'),
	(55, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:15:30'),
	(56, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:16:55'),
	(57, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:17:20'),
	(58, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:19:40'),
	(59, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:19:52'),
	(60, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:19:56'),
	(61, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:21:03'),
	(62, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:22:03'),
	(63, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:22:57'),
	(64, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:29:30'),
	(65, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:34:24'),
	(66, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-06 15:51:55'),
	(67, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:54:05'),
	(68, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-06 15:54:09'),
	(69, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:54:13'),
	(70, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-06 15:57:23'),
	(71, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-06 15:57:28'),
	(72, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'ROLE', 'UPDATE', NULL, 'roles', '6', '{"role_name": "Administrator IT"}', '{"role_name": "Administrator ITS"}', 'Role diperbarui: ID 6', '2026-06-06 18:28:19'),
	(73, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'ROLE', 'UPDATE', NULL, 'roles', '6', '{"role_name": "Administrator ITS"}', '{"role_name": "Administrator IT"}', 'Role diperbarui: ID 6', '2026-06-06 18:28:23'),
	(74, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-07 02:17:23'),
	(75, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-08 01:09:07'),
	(76, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-08 23:20:37'),
	(77, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-08 23:21:35'),
	(78, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-08 23:21:41'),
	(79, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'CURRENCY', 'CREATE', NULL, 'currencies', '7', NULL, '{"rate": 18000, "effective_date": "2026-06-09"}', 'Kurs USD baru: Rp 18.000 berlaku 09 Jun 2026', '2026-06-08 23:24:17'),
	(80, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'USER', 'UPDATE', NULL, 'users', '1', '{"email": "admin@onlyone.id", "role_id": 1, "full_name": "Administrator", "is_active": 1}', '{"email": "admin@onlyone.id", "role_id": 1, "full_name": "Administratori"}', 'Data user diperbarui: ID 1', '2026-06-08 23:26:34'),
	(81, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'USER', 'UPDATE', NULL, 'users', '1', '{"email": "admin@onlyone.id", "role_id": 1, "full_name": "Administratori", "is_active": 1}', '{"email": "admin@onlyone.id", "role_id": 1, "full_name": "Administratori"}', 'Data user diperbarui: ID 1', '2026-06-08 23:26:42'),
	(82, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'USER', 'UPDATE', NULL, 'users', '1', '{"email": "admin@onlyone.id", "role_id": 1, "full_name": "Administratori", "is_active": 1}', '{"email": "admin@onlyone.id", "role_id": 1, "full_name": "Administrator"}', 'Data user diperbarui: ID 1', '2026-06-08 23:26:49'),
	(83, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'SUPPLIER', 'CREATE', 'SUP-005', 'suppliers', '5', NULL, '{"name": "ID. Nurrokhmaduddin", "type": "purchase"}', 'Supplier baru: ID. Nurrokhmaduddin', '2026-06-08 23:29:05'),
	(84, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'SUPPLIER', 'UPDATE', 'SUP-005', 'suppliers', '5', '{"name": "ID. Nurrokhmaduddin"}', '{"name": "ID. Nurrokhmaduddin"}', 'Supplier diperbarui: SUP-005', '2026-06-08 23:29:17'),
	(85, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-09 11:22:41'),
	(86, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-09 11:23:34'),
	(87, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-09 11:23:42'),
	(88, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-09 11:24:38'),
	(89, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN_FAILED', NULL, NULL, NULL, NULL, NULL, 'Gagal login dari IP ::1', '2026-06-09 11:26:12'),
	(90, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-09 11:26:20'),
	(91, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-09 11:26:31'),
	(92, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'USER', 'CREATE', NULL, 'users', '2', NULL, '{"role_id": 2, "username": "wrrrr", "full_name": "gwerwerwer"}', 'User baru dibuat: wrrrr', '2026-06-09 11:30:33'),
	(93, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'USER', 'UPDATE', NULL, 'users', '2', '{"email": "wrrr@mail.com", "role_id": 2, "full_name": "gwerwerwer", "is_active": 1}', '{"email": "wrrr@mail.com", "role_id": 2, "full_name": "gwerwerwer"}', 'Data user diperbarui: ID 2', '2026-06-09 11:30:56'),
	(94, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'USER', 'UPDATE', NULL, 'users', '2', '{"email": "wrrr@mail.com", "role_id": 2, "full_name": "gwerwerwer", "is_active": 1}', '{"email": "wrrr@mail.com", "role_id": 2, "full_name": "gwerwerwer"}', 'Data user diperbarui: ID 2', '2026-06-09 11:31:06'),
	(95, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'USER', 'UPDATE', NULL, 'users', '2', '{"email": "wrrr@mail.com", "role_id": 2, "full_name": "gwerwerwer", "is_active": 1}', '{"email": "wrrr@mail.com", "role_id": 2, "full_name": "gwerwerwer"}', 'Data user diperbarui: ID 2', '2026-06-09 11:31:11'),
	(96, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-10 05:29:27'),
	(97, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-10 23:57:00'),
	(98, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-11 01:14:57'),
	(99, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-11 01:15:03'),
	(100, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-12 09:19:44'),
	(101, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-12 11:11:18'),
	(102, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-12 11:11:53'),
	(103, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-12 11:11:59'),
	(104, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-12 11:12:10'),
	(105, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'DIAMOND', 'CREATE', 'OO-2026-00001', 'diamonds', '1', NULL, '{"carat": "22", "color": "P", "clarity": "FL"}', 'Berlian baru didaftarkan: OO-2026-00001', '2026-06-12 11:13:30'),
	(106, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'DIAMOND', 'APPROVE', 'OO-2026-00001', 'diamonds', '1', '{"status": "registered"}', '{"status": "available"}', 'Berlian diaktifkan: OO-2026-00001', '2026-06-12 11:13:36'),
	(107, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-12 11:23:01'),
	(108, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-12 11:23:07'),
	(109, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-12 11:45:23'),
	(110, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGIN', NULL, NULL, NULL, NULL, NULL, 'Login berhasil dari IP ::1', '2026-06-12 11:45:34'),
	(111, 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'AUTH', 'LOGOUT', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-12 12:10:26');

-- Dumping structure for table erp_berlian.branches
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `branch_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_head_office` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branches_code` (`branch_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cabang toko dan reseller';

-- Dumping data for table erp_berlian.branches: ~3 rows (approximately)
INSERT INTO `branches` (`id`, `branch_code`, `name`, `address`, `phone`, `email`, `is_head_office`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
	(1, 'HO', 'Only One — Pusat', 'Jl. Pemuda No. 1, Semarang', '024-1234567', NULL, 1, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(2, 'SMG', 'Only One — Semarang', 'Jl. Pandanaran No. 50, Semarang', '024-9876543', NULL, 0, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(3, 'PWK', 'Only One — Purwokerto', 'Jl. Jend. Sudirman No. 10, PWK', '0281-555123', NULL, 0, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19');

-- Dumping structure for table erp_berlian.chart_of_accounts
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense','cogs') COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int unsigned DEFAULT NULL COMMENT 'Untuk COA hierarki',
  `level` tinyint NOT NULL DEFAULT '1',
  `is_header` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=header (tidak bisa posting)',
  `normal_balance` enum('debit','credit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coa_code` (`account_code`),
  KEY `idx_coa_type` (`account_type`),
  KEY `idx_coa_parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bagan akun (Chart of Accounts)';

-- Dumping data for table erp_berlian.chart_of_accounts: ~28 rows (approximately)
INSERT INTO `chart_of_accounts` (`id`, `account_code`, `account_name`, `account_type`, `parent_id`, `level`, `is_header`, `normal_balance`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
	(1, '1000', 'ASET', 'asset', NULL, 1, 1, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(2, '1100', 'Kas & Bank', 'asset', NULL, 2, 1, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(3, '1101', 'Kas Tunai', 'asset', NULL, 3, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(4, '1102', 'Bank BCA', 'asset', NULL, 3, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(5, '1103', 'Bank Mandiri', 'asset', NULL, 3, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(6, '1200', 'Piutang Usaha', 'asset', NULL, 2, 1, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(7, '1201', 'Piutang Dagang', 'asset', NULL, 3, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(8, '1300', 'Persediaan', 'asset', NULL, 2, 1, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(9, '1301', 'Persediaan Berlian', 'asset', NULL, 3, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(10, '1302', 'Persediaan Konsinyasi', 'asset', NULL, 3, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(11, '2000', 'KEWAJIBAN', 'liability', NULL, 1, 1, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(12, '2100', 'Hutang Usaha', 'liability', NULL, 2, 1, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(13, '2101', 'Hutang Dagang Supplier', 'liability', NULL, 3, 0, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(14, '2102', 'Hutang Konsinyasi', 'liability', NULL, 3, 0, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(15, '3000', 'EKUITAS', 'equity', NULL, 1, 1, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(16, '3101', 'Modal Pemilik', 'equity', NULL, 2, 0, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(17, '3102', 'Laba Ditahan', 'equity', NULL, 2, 0, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(18, '4000', 'PENDAPATAN', 'revenue', NULL, 1, 1, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(19, '4101', 'Penjualan Berlian', 'revenue', NULL, 2, 0, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(20, '4102', 'Pendapatan Jasa Reparasi', 'revenue', NULL, 2, 0, 'credit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(21, '5000', 'HARGA POKOK PENJUALAN', 'cogs', NULL, 1, 1, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(22, '5101', 'HPP Berlian', 'cogs', NULL, 2, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(23, '6000', 'BEBAN OPERASIONAL', 'expense', NULL, 1, 1, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(24, '6101', 'Beban Gaji', 'expense', NULL, 2, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(25, '6102', 'Beban Sewa', 'expense', NULL, 2, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(26, '6103', 'Beban Listrik & Air', 'expense', NULL, 2, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(27, '6104', 'Beban Pemasaran', 'expense', NULL, 2, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20'),
	(28, '6105', 'Beban Administrasi', 'expense', NULL, 2, 0, 'debit', NULL, 1, NULL, '2026-06-08 23:21:20', '2026-06-08 23:21:20');

-- Dumping structure for table erp_berlian.currencies
CREATE TABLE IF NOT EXISTS `currencies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `rate_to_idr` decimal(15,4) NOT NULL,
  `effective_date` date NOT NULL,
  `set_by` int unsigned DEFAULT NULL,
  `notes` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_currencies_code_date` (`code`,`effective_date`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Riwayat kurs valuta asing';

-- Dumping data for table erp_berlian.currencies: ~6 rows (approximately)
INSERT INTO `currencies` (`id`, `code`, `rate_to_idr`, `effective_date`, `set_by`, `notes`, `is_active`, `created_at`) VALUES
	(1, 'USD', 16250.0000, '2026-01-01', NULL, 'Opening rate Jan 2026', 1, '2026-06-08 23:21:19'),
	(2, 'USD', 16380.0000, '2026-02-01', NULL, 'Rate Feb 2026', 1, '2026-06-08 23:21:19'),
	(3, 'USD', 16420.0000, '2026-03-01', NULL, 'Rate Mar 2026', 1, '2026-06-08 23:21:19'),
	(4, 'USD', 16500.0000, '2026-04-01', NULL, 'Rate Apr 2026', 1, '2026-06-08 23:21:19'),
	(5, 'USD', 16475.0000, '2026-05-01', NULL, 'Rate May 2026', 1, '2026-06-08 23:21:19'),
	(6, 'USD', 16510.0000, '2026-06-01', NULL, 'Rate Jun 2026', 1, '2026-06-08 23:21:19'),
	(7, 'USD', 18000.0000, '2026-06-09', 1, NULL, 1, '2026-06-08 23:24:17');

-- Dumping structure for table erp_berlian.customers
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identity_type` enum('ktp','passport','sim','other') COLLATE utf8mb4_unicode_ci DEFAULT 'ktp',
  `identity_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nomor KTP/Passport',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone2` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `birth_date` date DEFAULT NULL,
  `gender` enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tier` enum('regular','vip','vvip') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `ring_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferences` text COLLATE utf8mb4_unicode_ci COMMENT 'Catatan preferensi desain/gaya',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_code` (`customer_code`),
  KEY `idx_customers_name` (`name`),
  KEY `idx_customers_phone` (`phone`),
  KEY `idx_customers_tier` (`tier`),
  KEY `idx_customers_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Data pelanggan toko berlian';

-- Dumping data for table erp_berlian.customers: ~4 rows (approximately)
INSERT INTO `customers` (`id`, `customer_code`, `name`, `identity_type`, `identity_number`, `phone`, `phone2`, `email`, `address`, `birth_date`, `gender`, `tier`, `ring_size`, `preferences`, `notes`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(1, 'CUS-0001', 'Bpk. Andi Wijaya', 'ktp', NULL, '08123456789', NULL, 'andi@email.com', NULL, NULL, NULL, 'vvip', '20', NULL, NULL, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19', NULL),
	(2, 'CUS-0002', 'Ibu Sari Rahayu', 'ktp', NULL, '08234567890', NULL, 'sari@email.com', NULL, NULL, NULL, 'vip', '17', NULL, NULL, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19', NULL),
	(3, 'CUS-0003', 'Bpk. Toni Santoso', 'ktp', NULL, '08345678901', NULL, 'toni@email.com', NULL, NULL, NULL, 'regular', NULL, NULL, NULL, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19', NULL),
	(4, 'CUS-0004', 'Ibu Dewi Putri', 'ktp', NULL, '08456789012', NULL, 'dewi@email.com', NULL, NULL, NULL, 'vvip', '16', NULL, NULL, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19', NULL);

-- Dumping structure for table erp_berlian.diamonds
CREATE TABLE IF NOT EXISTS `diamonds` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `internal_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode internal sistem: OO-YYYY-NNNNN',
  `factory_barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Barcode dari pabrik/supplier',
  `supplier_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `acquisition_type` enum('consignment','purchase_returnable','purchase_final') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'consignment' COMMENT 'consignment=titipan; purchase_returnable=bisa retur; purchase_final=putus',
  `acquired_at` date NOT NULL,
  `shape_id` int unsigned DEFAULT NULL,
  `carat_weight` decimal(8,3) NOT NULL,
  `color_grade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'D-Z atau Fancy',
  `clarity_grade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'FL,IF,VVS1,VVS2,VS1,VS2,SI1,SI2,I1,I2,I3',
  `cut_grade` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Excellent,Very Good,Good,Fair,Poor',
  `polish` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `symmetry` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fluorescence` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `measurements` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'misal: 6.40-6.43x3.96',
  `table_percent` decimal(5,2) DEFAULT NULL,
  `depth_percent` decimal(5,2) DEFAULT NULL,
  `stone_count` int NOT NULL DEFAULT '1',
  `metal_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Gold 18K, Platinum, dll',
  `metal_weight_gr` decimal(8,3) DEFAULT NULL,
  `karat` tinyint DEFAULT NULL COMMENT '14, 18, 22, 24',
  `cost_price_usd` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'HPP dari supplier',
  `selling_price_usd` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Harga jual dasar',
  `status` enum('registered','available','reserved','sold','returned','in_repair','retired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `registered_by` int unsigned DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_diamonds_internal_code` (`internal_code`),
  KEY `idx_diamonds_factory_barcode` (`factory_barcode`),
  KEY `idx_diamonds_supplier` (`supplier_id`),
  KEY `idx_diamonds_warehouse` (`warehouse_id`),
  KEY `idx_diamonds_status` (`status`),
  KEY `idx_diamonds_shape` (`shape_id`),
  KEY `idx_diamonds_acq_type` (`acquisition_type`),
  KEY `idx_diamonds_deleted` (`deleted_at`),
  CONSTRAINT `fk_diamonds_shape` FOREIGN KEY (`shape_id`) REFERENCES `diamond_shapes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_diamonds_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_diamonds_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Entitas utama berlian — setiap baris = 1 item unik';

-- Dumping data for table erp_berlian.diamonds: ~1 rows (approximately)
INSERT INTO `diamonds` (`id`, `internal_code`, `factory_barcode`, `supplier_id`, `warehouse_id`, `acquisition_type`, `acquired_at`, `shape_id`, `carat_weight`, `color_grade`, `clarity_grade`, `cut_grade`, `polish`, `symmetry`, `fluorescence`, `measurements`, `table_percent`, `depth_percent`, `stone_count`, `metal_type`, `metal_weight_gr`, `karat`, `cost_price_usd`, `selling_price_usd`, `status`, `photo_path`, `notes`, `registered_by`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(1, 'OO-2026-00001', NULL, 4, 6, 'consignment', '2026-06-12', NULL, 22.000, 'P', 'FL', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1, NULL, 0.000, NULL, 1600.0000, 1800.0000, 'available', NULL, NULL, 1, 1, 1, '2026-06-12 11:13:30', '2026-06-12 11:13:36', NULL);

-- Dumping structure for table erp_berlian.diamond_certificates
CREATE TABLE IF NOT EXISTS `diamond_certificates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `diamond_id` int unsigned NOT NULL,
  `cert_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cert_type` enum('GIA','IGI','HRD','LOCAL','FACTORY','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GIA',
  `issuer` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path file sertifikat (PDF/gambar)',
  `is_primary` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_diamond_certs_diamond` (`diamond_id`),
  CONSTRAINT `fk_diamond_certs_diamond` FOREIGN KEY (`diamond_id`) REFERENCES `diamonds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sertifikat GIA/IGI/lokal per berlian';

-- Dumping data for table erp_berlian.diamond_certificates: ~0 rows (approximately)

-- Dumping structure for table erp_berlian.diamond_shapes
CREATE TABLE IF NOT EXISTS `diamond_shapes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_diamond_shapes_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Referensi bentuk / shape berlian';

-- Dumping data for table erp_berlian.diamond_shapes: ~10 rows (approximately)
INSERT INTO `diamond_shapes` (`id`, `code`, `name`, `is_active`) VALUES
	(1, 'ROUND', 'Round Brilliant', 1),
	(2, 'PRINCESS', 'Princess', 1),
	(3, 'OVAL', 'Oval', 1),
	(4, 'CUSHION', 'Cushion', 1),
	(5, 'EMERALD', 'Emerald', 1),
	(6, 'PEAR', 'Pear', 1),
	(7, 'MARQUISE', 'Marquise', 1),
	(8, 'RADIANT', 'Radiant', 1),
	(9, 'ASSCHER', 'Asscher', 1),
	(10, 'HEART', 'Heart', 1);

-- Dumping structure for table erp_berlian.diamond_state_histories
CREATE TABLE IF NOT EXISTS `diamond_state_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `diamond_id` int unsigned NOT NULL,
  `from_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_id` int unsigned DEFAULT NULL,
  `actor_id` int unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dsh_diamond` (`diamond_id`),
  KEY `idx_dsh_event` (`event_name`),
  CONSTRAINT `fk_dsh_diamond` FOREIGN KEY (`diamond_id`) REFERENCES `diamonds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Riwayat perubahan status berlian';

-- Dumping data for table erp_berlian.diamond_state_histories: ~2 rows (approximately)
INSERT INTO `diamond_state_histories` (`id`, `diamond_id`, `from_status`, `to_status`, `event_name`, `ref_type`, `ref_id`, `actor_id`, `notes`, `changed_at`) VALUES
	(1, 1, NULL, 'registered', 'DIAMOND_REGISTERED', NULL, NULL, 1, 'Berlian baru didaftarkan', '2026-06-12 11:13:30'),
	(2, 1, 'registered', 'available', 'DIAMOND_RECEIVED', NULL, NULL, 1, 'Barang diterima dan siap jual', '2026-06-12 11:13:36');

-- Dumping structure for table erp_berlian.permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `permission_code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. USER_CREATE, DIAMOND_VIEW',
  `permission_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kelompok modul: USER, DIAMOND, SALES, dll',
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'VIEW, CREATE, EDIT, DELETE, APPROVE, POST',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_code` (`permission_code`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Katalog hak akses atomik per modul dan aksi';

-- Dumping data for table erp_berlian.permissions: ~34 rows (approximately)
INSERT INTO `permissions` (`id`, `permission_code`, `permission_name`, `module`, `action`, `description`, `created_at`) VALUES
	(1, 'USER_VIEW', 'Lihat Daftar Pengguna', 'USER', 'VIEW', NULL, '2026-06-06 04:06:16'),
	(2, 'USER_CREATE', 'Tambah Pengguna Baru', 'USER', 'CREATE', NULL, '2026-06-06 04:06:16'),
	(3, 'USER_EDIT', 'Edit Data Pengguna', 'USER', 'EDIT', NULL, '2026-06-06 04:06:16'),
	(4, 'USER_DELETE', 'Nonaktifkan Pengguna', 'USER', 'DELETE', NULL, '2026-06-06 04:06:16'),
	(5, 'USER_RESET_PW', 'Reset Password Pengguna', 'USER', 'APPROVE', NULL, '2026-06-06 04:06:16'),
	(6, 'ROLE_VIEW', 'Lihat Daftar Role', 'ROLE', 'VIEW', NULL, '2026-06-06 04:06:16'),
	(7, 'ROLE_CREATE', 'Tambah Role Baru', 'ROLE', 'CREATE', NULL, '2026-06-06 04:06:16'),
	(8, 'ROLE_EDIT', 'Edit Data Role', 'ROLE', 'EDIT', NULL, '2026-06-06 04:06:16'),
	(9, 'ROLE_DELETE', 'Hapus Role', 'ROLE', 'DELETE', NULL, '2026-06-06 04:06:16'),
	(10, 'PERMISSION_VIEW', 'Lihat Permission', 'PERMISSION', 'VIEW', NULL, '2026-06-06 04:06:16'),
	(11, 'PERMISSION_ASSIGN', 'Assign Permission ke Role', 'PERMISSION', 'APPROVE', NULL, '2026-06-06 04:06:16'),
	(12, 'AUDIT_VIEW', 'Lihat Audit Trail', 'AUDIT', 'VIEW', NULL, '2026-06-06 04:06:16'),
	(13, 'SYSTEM_BACKUP', 'Backup Database', 'SYSTEM', 'APPROVE', NULL, '2026-06-06 04:06:16'),
	(14, 'SYSTEM_CONFIG', 'Konfigurasi Sistem', 'SYSTEM', 'EDIT', NULL, '2026-06-06 04:06:16'),
	(15, 'CUSTOMER_VIEW', 'Lihat Data Pelanggan', 'CUSTOMER', 'VIEW', NULL, '2026-06-08 23:21:20'),
	(16, 'CUSTOMER_CREATE', 'Tambah Pelanggan', 'CUSTOMER', 'CREATE', NULL, '2026-06-08 23:21:20'),
	(17, 'CUSTOMER_EDIT', 'Edit Data Pelanggan', 'CUSTOMER', 'EDIT', NULL, '2026-06-08 23:21:20'),
	(18, 'CUSTOMER_DELETE', 'Hapus Pelanggan', 'CUSTOMER', 'DELETE', NULL, '2026-06-08 23:21:20'),
	(19, 'SUPPLIER_VIEW', 'Lihat Data Supplier', 'SUPPLIER', 'VIEW', NULL, '2026-06-08 23:21:20'),
	(20, 'SUPPLIER_CREATE', 'Tambah Supplier', 'SUPPLIER', 'CREATE', NULL, '2026-06-08 23:21:20'),
	(21, 'SUPPLIER_EDIT', 'Edit Data Supplier', 'SUPPLIER', 'EDIT', NULL, '2026-06-08 23:21:20'),
	(22, 'SUPPLIER_DELETE', 'Hapus Supplier', 'SUPPLIER', 'DELETE', NULL, '2026-06-08 23:21:20'),
	(23, 'DIAMOND_VIEW', 'Lihat Data Berlian', 'DIAMOND', 'VIEW', NULL, '2026-06-08 23:21:20'),
	(24, 'DIAMOND_CREATE', 'Daftarkan Berlian Baru', 'DIAMOND', 'CREATE', NULL, '2026-06-08 23:21:20'),
	(25, 'DIAMOND_EDIT', 'Edit Data Berlian', 'DIAMOND', 'EDIT', NULL, '2026-06-08 23:21:20'),
	(26, 'DIAMOND_DELETE', 'Nonaktifkan Berlian', 'DIAMOND', 'DELETE', NULL, '2026-06-08 23:21:20'),
	(27, 'WAREHOUSE_VIEW', 'Lihat Gudang', 'WAREHOUSE', 'VIEW', NULL, '2026-06-08 23:21:20'),
	(28, 'WAREHOUSE_CREATE', 'Tambah Gudang', 'WAREHOUSE', 'CREATE', NULL, '2026-06-08 23:21:20'),
	(29, 'WAREHOUSE_EDIT', 'Edit Gudang', 'WAREHOUSE', 'EDIT', NULL, '2026-06-08 23:21:20'),
	(30, 'COA_VIEW', 'Lihat Chart of Accounts', 'COA', 'VIEW', NULL, '2026-06-08 23:21:20'),
	(31, 'COA_CREATE', 'Tambah Akun', 'COA', 'CREATE', NULL, '2026-06-08 23:21:20'),
	(32, 'COA_EDIT', 'Edit Akun', 'COA', 'EDIT', NULL, '2026-06-08 23:21:20'),
	(33, 'CURRENCY_VIEW', 'Lihat Kurs Valuta', 'CURRENCY', 'VIEW', NULL, '2026-06-08 23:21:20'),
	(34, 'CURRENCY_CREATE', 'Input Kurs Baru', 'CURRENCY', 'CREATE', NULL, '2026-06-08 23:21:20');

-- Dumping structure for table erp_berlian.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode unik peran, e.g. OWNER, MANAGER, SALES',
  `role_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_code` (`role_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Peran / role pengguna sistem';

-- Dumping data for table erp_berlian.roles: ~6 rows (approximately)
INSERT INTO `roles` (`id`, `role_code`, `role_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'OWNER', 'Owner', 'Akses penuh ke seluruh sistem', 1, '2026-06-06 04:06:16', '2026-06-06 04:06:16'),
	(2, 'MANAGER', 'Manajer Toko', 'Approval transaksi, laporan performa', 1, '2026-06-06 04:06:16', '2026-06-06 04:06:16'),
	(3, 'SALES', 'Staff Penjualan', 'Kelola lead, quotation, reservasi', 1, '2026-06-06 04:06:16', '2026-06-06 04:06:16'),
	(4, 'INVENTORY', 'Staff Gudang', 'Kelola penerimaan dan mutasi stok', 1, '2026-06-06 04:06:16', '2026-06-06 04:06:16'),
	(5, 'FINANCE', 'Staff Keuangan', 'Kelola invoice, pembayaran, jurnal', 1, '2026-06-06 04:06:16', '2026-06-06 04:06:16'),
	(6, 'IT_ADMIN', 'Administrator IT', 'Konfigurasi sistem, user, backup', 1, '2026-06-06 04:06:16', '2026-06-06 18:28:23');

-- Dumping structure for table erp_berlian.role_permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `granted_by` int unsigned DEFAULT NULL COMMENT 'FK ke users.id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permission` (`role_id`,`permission_id`),
  KEY `idx_rp_role` (`role_id`),
  KEY `idx_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pemetaan role ke permission';

-- Dumping data for table erp_berlian.role_permissions: ~82 rows (approximately)
INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `granted_at`, `granted_by`) VALUES
	(1, 1, 12, '2026-06-06 04:06:16', NULL),
	(2, 1, 10, '2026-06-06 04:06:16', NULL),
	(3, 1, 11, '2026-06-06 04:06:16', NULL),
	(4, 1, 6, '2026-06-06 04:06:16', NULL),
	(5, 1, 7, '2026-06-06 04:06:16', NULL),
	(6, 1, 8, '2026-06-06 04:06:16', NULL),
	(7, 1, 9, '2026-06-06 04:06:16', NULL),
	(8, 1, 13, '2026-06-06 04:06:16', NULL),
	(9, 1, 14, '2026-06-06 04:06:16', NULL),
	(10, 1, 1, '2026-06-06 04:06:16', NULL),
	(11, 1, 2, '2026-06-06 04:06:16', NULL),
	(12, 1, 3, '2026-06-06 04:06:16', NULL),
	(13, 1, 4, '2026-06-06 04:06:16', NULL),
	(14, 1, 5, '2026-06-06 04:06:16', NULL),
	(16, 2, 12, '2026-06-06 04:06:16', NULL),
	(17, 2, 6, '2026-06-06 04:06:16', NULL),
	(18, 2, 1, '2026-06-06 04:06:16', NULL),
	(19, 6, 12, '2026-06-06 04:06:16', NULL),
	(20, 6, 11, '2026-06-06 04:06:16', NULL),
	(21, 6, 10, '2026-06-06 04:06:16', NULL),
	(22, 6, 7, '2026-06-06 04:06:16', NULL),
	(23, 6, 9, '2026-06-06 04:06:16', NULL),
	(24, 6, 8, '2026-06-06 04:06:16', NULL),
	(25, 6, 6, '2026-06-06 04:06:16', NULL),
	(26, 6, 13, '2026-06-06 04:06:16', NULL),
	(27, 6, 14, '2026-06-06 04:06:16', NULL),
	(28, 6, 2, '2026-06-06 04:06:16', NULL),
	(29, 6, 4, '2026-06-06 04:06:16', NULL),
	(30, 6, 3, '2026-06-06 04:06:16', NULL),
	(31, 6, 5, '2026-06-06 04:06:16', NULL),
	(32, 6, 1, '2026-06-06 04:06:16', NULL),
	(33, 1, 31, '2026-06-08 23:21:20', NULL),
	(34, 1, 32, '2026-06-08 23:21:20', NULL),
	(35, 1, 30, '2026-06-08 23:21:20', NULL),
	(36, 1, 34, '2026-06-08 23:21:20', NULL),
	(37, 1, 33, '2026-06-08 23:21:20', NULL),
	(38, 1, 16, '2026-06-08 23:21:20', NULL),
	(39, 1, 18, '2026-06-08 23:21:20', NULL),
	(40, 1, 17, '2026-06-08 23:21:20', NULL),
	(41, 1, 15, '2026-06-08 23:21:20', NULL),
	(42, 1, 24, '2026-06-08 23:21:20', NULL),
	(43, 1, 26, '2026-06-08 23:21:20', NULL),
	(44, 1, 25, '2026-06-08 23:21:20', NULL),
	(45, 1, 23, '2026-06-08 23:21:20', NULL),
	(46, 1, 20, '2026-06-08 23:21:20', NULL),
	(47, 1, 22, '2026-06-08 23:21:20', NULL),
	(48, 1, 21, '2026-06-08 23:21:20', NULL),
	(49, 1, 19, '2026-06-08 23:21:20', NULL),
	(50, 1, 28, '2026-06-08 23:21:20', NULL),
	(51, 1, 29, '2026-06-08 23:21:20', NULL),
	(52, 1, 27, '2026-06-08 23:21:20', NULL),
	(64, 6, 31, '2026-06-08 23:21:20', NULL),
	(65, 6, 32, '2026-06-08 23:21:20', NULL),
	(66, 6, 30, '2026-06-08 23:21:20', NULL),
	(67, 6, 34, '2026-06-08 23:21:20', NULL),
	(68, 6, 33, '2026-06-08 23:21:20', NULL),
	(69, 6, 15, '2026-06-08 23:21:20', NULL),
	(70, 6, 23, '2026-06-08 23:21:20', NULL),
	(71, 6, 19, '2026-06-08 23:21:20', NULL),
	(72, 6, 28, '2026-06-08 23:21:20', NULL),
	(73, 6, 29, '2026-06-08 23:21:20', NULL),
	(74, 6, 27, '2026-06-08 23:21:20', NULL),
	(79, 4, 15, '2026-06-08 23:21:20', NULL),
	(80, 4, 24, '2026-06-08 23:21:20', NULL),
	(81, 4, 25, '2026-06-08 23:21:20', NULL),
	(82, 4, 23, '2026-06-08 23:21:20', NULL),
	(83, 4, 19, '2026-06-08 23:21:20', NULL),
	(84, 4, 27, '2026-06-08 23:21:20', NULL),
	(86, 3, 16, '2026-06-08 23:21:20', NULL),
	(87, 3, 17, '2026-06-08 23:21:20', NULL),
	(88, 3, 15, '2026-06-08 23:21:20', NULL),
	(89, 3, 23, '2026-06-08 23:21:20', NULL),
	(90, 3, 19, '2026-06-08 23:21:20', NULL),
	(91, 3, 27, '2026-06-08 23:21:20', NULL),
	(93, 5, 31, '2026-06-08 23:21:20', NULL),
	(94, 5, 32, '2026-06-08 23:21:20', NULL),
	(95, 5, 30, '2026-06-08 23:21:20', NULL),
	(96, 5, 34, '2026-06-08 23:21:20', NULL),
	(97, 5, 33, '2026-06-08 23:21:20', NULL),
	(98, 5, 15, '2026-06-08 23:21:20', NULL),
	(99, 5, 23, '2026-06-08 23:21:20', NULL),
	(100, 5, 19, '2026-06-08 23:21:20', NULL);

-- Dumping structure for table erp_berlian.suppliers
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone2` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `type` enum('consignment','purchase','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `currency` enum('USD','IDR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `payment_terms` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NET30, NET60, COD, dll',
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_holder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_suppliers_code` (`supplier_code`),
  KEY `idx_suppliers_name` (`name`),
  KEY `idx_suppliers_type` (`type`),
  KEY `idx_suppliers_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Data supplier berlian';

-- Dumping data for table erp_berlian.suppliers: ~5 rows (approximately)
INSERT INTO `suppliers` (`id`, `supplier_code`, `name`, `contact_person`, `phone`, `phone2`, `email`, `address`, `type`, `currency`, `discount_percent`, `payment_terms`, `bank_name`, `bank_account`, `bank_holder`, `notes`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(1, 'SUP-001', 'PT Mahkota Diamond', 'Bpk. Hendra', '021-5551001', NULL, NULL, NULL, 'consignment', 'USD', 5.00, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19', NULL),
	(2, 'SUP-002', 'Rio Diamond Jakarta', 'Ibu Santi', '021-5552002', NULL, NULL, NULL, 'purchase', 'USD', 3.50, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19', NULL),
	(3, 'SUP-003', 'Star Gems International', 'Mr. David Lee', '021-5553003', NULL, NULL, NULL, 'both', 'USD', 4.00, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19', NULL),
	(4, 'SUP-004', 'Berlian Nusantara', 'Bpk. Suryo', '022-5554004', NULL, NULL, NULL, 'purchase', 'IDR', 0.00, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19', NULL),
	(5, 'SUP-005', 'ID. Nurrokhmaduddin', NULL, NULL, NULL, 'hh@mail.com', NULL, 'both', 'IDR', 0.00, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, '2026-06-08 23:29:05', '2026-06-08 23:29:17', NULL);

-- Dumping structure for table erp_berlian.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `employee_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Kode karyawan internal',
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Username untuk login',
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'password_hash() PHP, algoritma bcrypt',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_id` int unsigned DEFAULT NULL COMMENT 'FK ke branches (ditambah Sprint 2)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_pw` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Wajib ganti password saat login pertama',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `login_attempt` tinyint NOT NULL DEFAULT '0' COMMENT 'Counter gagal login, reset saat berhasil',
  `locked_until` timestamp NULL DEFAULT NULL COMMENT 'Akun terkunci sementara jika login attempt melebihi batas',
  `avatar_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_active` (`is_active`),
  KEY `idx_users_deleted` (`deleted_at`),
  KEY `fk_users_branch` (`branch_id`),
  CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pengguna sistem ERP';

-- Dumping data for table erp_berlian.users: ~2 rows (approximately)
INSERT INTO `users` (`id`, `role_id`, `employee_code`, `username`, `full_name`, `email`, `password_hash`, `phone`, `branch_id`, `is_active`, `must_change_pw`, `last_login_at`, `login_attempt`, `locked_until`, `avatar_path`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(1, 1, 'EMP-001', 'admin', 'Administrator', 'admin@onlyone.id', 'password', NULL, NULL, 1, 0, '2026-06-12 11:45:34', 0, NULL, NULL, NULL, 1, '2026-06-06 04:06:16', '2026-06-12 11:45:34', NULL),
	(2, 2, 'wer', 'wrrrr', 'gwerwerwer', 'wrrr@mail.com', '$2y$12$4VotF5VQLOXX2K0t.anUOuvVP9uuwp8o1JfH6iKAyvwXsFVx.t2t6', NULL, NULL, 0, 1, NULL, 0, NULL, NULL, 1, 1, '2026-06-09 11:30:33', '2026-06-09 11:31:34', NULL);

-- Dumping structure for table erp_berlian.user_sessions
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `session_token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token sesi unik, di-hash SHA-256',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sessions_token` (`session_token`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_active` (`is_active`,`expires_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesi aktif pengguna';

-- Dumping data for table erp_berlian.user_sessions: ~23 rows (approximately)
INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `last_activity`, `created_at`, `expires_at`, `is_active`) VALUES
	(1, 1, '5e172a7b3cf896e95a43817eb11cb4805ccf30a1ed022d9cf4262664504b4541', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-06 15:22:03', '2026-06-06 15:22:03', '2026-06-06 23:22:03', 1),
	(2, 1, '234442e8f49276cb0bfe97d91cbabc5aaedce6686464e3839e7c2e309a2c5b47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-06 15:22:57', '2026-06-06 15:22:57', '2026-06-06 23:22:57', 1),
	(3, 1, 'a539be29e4ca4239a50a733207f9e79b5a91f0cf13efa4be638cc267e255c8fd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-06 15:29:30', '2026-06-06 15:29:30', '2026-06-06 23:29:30', 1),
	(4, 1, 'ae86ae2965471941b579194dd9e96ac03bdd46c12f4f943e9742eb78016d982d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-06 15:51:55', '2026-06-06 15:34:24', '2026-06-06 23:34:24', 0),
	(5, 1, '7403dfab0fe25bfce2954db6c5c569544e2ff793dcd8dc1365eb020d63e45408', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-06 15:54:09', '2026-06-06 15:54:05', '2026-06-06 23:54:05', 0),
	(6, 1, '9427332d93ebf7e6219d04560e415179e042de8b13ca3bdfaca65bd2a76a097d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-06 15:57:23', '2026-06-06 15:54:13', '2026-06-06 23:54:13', 0),
	(7, 1, 'bb5944e69cc3b40682b8e9bf1f3ca50c4b398e7743ef7e58629943a503660eb8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-06 18:28:57', '2026-06-06 15:57:28', '2026-06-06 23:57:28', 1),
	(8, 1, '8ea5313b2123afb36d0a7e1eb19c3dd33ac1386aa18e228f17950b66a5225a53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-07 03:10:09', '2026-06-07 02:17:23', '2026-06-07 10:17:23', 1),
	(9, 1, '50c2ff89ea3de3d0f06cd42f78699395b412db5280642eb1bd9c8547d249c31b', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 05:35:40', '2026-06-08 01:09:07', '2026-06-08 09:09:07', 1),
	(10, 1, '420beda131c9882c1720fd97eec6592c48f69def276937cd9040d290f3528e42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 00:59:01', '2026-06-08 23:21:41', '2026-06-09 07:21:41', 1),
	(11, 1, '416809e7d0f5e0b744bb9d0200e42cc4f4d9d63ccf34f3e7ade352b865e19c3d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 11:22:41', '2026-06-09 11:22:41', '2026-06-09 19:22:41', 1),
	(12, 1, '28ebba12fa4257154b17f2f15e650e6cabc5aea93b853498114fb7c6d2bc42f6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 11:23:34', '2026-06-09 11:23:34', '2026-06-09 19:23:34', 1),
	(13, 1, '2bc96076da1251ce4de0310bbe5c0e014b481f9f90ac62fd357dcffa8f54ced2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 11:23:42', '2026-06-09 11:23:42', '2026-06-09 19:23:42', 1),
	(14, 1, '09433a2c8f30c12a59acc9516218c6516c32483f59c9c8eed3cd18e317986749', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 11:26:20', '2026-06-09 11:26:20', '2026-06-09 19:26:20', 1),
	(15, 1, 'f5bdf24f5cbaaaddd9b76d1d5590aeb66155e33840726b81f5c3491ed3b44268', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 16:19:37', '2026-06-09 11:26:31', '2026-06-09 19:26:31', 1),
	(16, 1, '4e24ced14ce1777e40b9b599dabcba37efd4a4099c2f52d0b0577638f008f5e2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 05:29:48', '2026-06-10 05:29:27', '2026-06-10 13:29:27', 1),
	(17, 1, '1fa3101e483d1f1cf4fe9f83e65af6e03b6cddbf266af22524d76ced112b7a74', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-11 01:14:57', '2026-06-10 23:57:00', '2026-06-11 07:57:00', 0),
	(18, 1, 'eb85c548a06be42da567ab1e4725b8f64022719218edbcf4fca2a7571b21bdad', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-11 01:15:03', '2026-06-11 01:15:03', '2026-06-11 09:15:03', 1),
	(19, 1, '1e571ec48a0efd0fccff94c4a49890d4770c89140e1b3466562256b53f5f4d43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:11:18', '2026-06-12 09:19:44', '2026-06-12 17:19:44', 0),
	(20, 1, 'a27e12a08e18ac07cb29fbbe5caed131f2c0e625ac1e973fa98a8a135f864972', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:11:59', '2026-06-12 11:11:53', '2026-06-12 19:11:53', 0),
	(21, 1, 'a16e5f6caddd9ae989a47a09c404d7afc6dc321654f669cafa10dcae1c90843b', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:23:01', '2026-06-12 11:12:10', '2026-06-12 19:12:10', 0),
	(22, 1, '4f0ba8bc09ef1e8139ec7a8161c0bec3fb71dc81adb0b00bd26a2e815d2c3f15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:45:23', '2026-06-12 11:23:07', '2026-06-12 19:23:07', 0),
	(23, 1, 'ab954a455f3cde510510cc1c94ae97348cc7ad51221728ac70d5aa70adac4e08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 12:10:26', '2026-06-12 11:45:34', '2026-06-12 19:45:34', 0);

-- Dumping structure for table erp_berlian.warehouses
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int unsigned NOT NULL,
  `warehouse_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('main','display','sales','transit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main',
  `description` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_warehouses_code` (`warehouse_code`),
  KEY `idx_warehouses_branch` (`branch_id`),
  CONSTRAINT `fk_warehouses_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lokasi penyimpanan stok per cabang';

-- Dumping data for table erp_berlian.warehouses: ~8 rows (approximately)
INSERT INTO `warehouses` (`id`, `branch_id`, `warehouse_code`, `name`, `type`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
	(1, 1, 'HO-MAIN', 'Vault Utama Pusat', 'main', NULL, 1, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(2, 1, 'HO-DISPLAY', 'Etalase Pusat', 'display', NULL, 1, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(3, 1, 'HO-SALES', 'Tas Sales Pusat', 'sales', NULL, 1, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(4, 1, 'HO-TRANSIT', 'Transit / Reparasi Pusat', 'transit', NULL, 1, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(5, 2, 'SMG-MAIN', 'Vault Semarang', 'main', NULL, 1, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(6, 2, 'SMG-DISPLAY', 'Etalase Semarang', 'display', NULL, 1, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(7, 3, 'PWK-MAIN', 'Vault Purwokerto', 'main', NULL, 1, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19'),
	(8, 3, 'PWK-DISPLAY', 'Etalase Purwokerto', 'display', NULL, 1, NULL, '2026-06-08 23:21:19', '2026-06-08 23:21:19');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
