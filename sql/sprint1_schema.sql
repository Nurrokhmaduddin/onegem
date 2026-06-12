-- =============================================================================
-- ERP TOKO BERLIAN — ONLY ONE
-- Sprint 1: Authentication, User, Role, Permission
-- Engine  : MySQL 8.0+ / MariaDB 10.6+
-- Charset : utf8mb4_unicode_ci
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

CREATE DATABASE IF NOT EXISTS `erp_berlian`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `erp_berlian`;

-- -----------------------------------------------------------------------------
-- Table: roles
-- Mendefinisikan peran pengguna dalam sistem
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `role_code`   VARCHAR(30)      NOT NULL COMMENT 'Kode unik peran, e.g. OWNER, MANAGER, SALES',
  `role_name`   VARCHAR(80)      NOT NULL,
  `description` VARCHAR(255)     NULL,
  `is_active`   TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Peran / role pengguna sistem';

-- -----------------------------------------------------------------------------
-- Table: permissions
-- Mendefinisikan hak akses atomik per modul/aksi
-- Format: MODULE_ACTION, contoh: USER_CREATE, DIAMOND_VIEW
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `permission_code` VARCHAR(80)   NOT NULL COMMENT 'e.g. USER_CREATE, DIAMOND_VIEW',
  `permission_name` VARCHAR(120)  NOT NULL,
  `module`          VARCHAR(50)   NOT NULL COMMENT 'Kelompok modul: USER, DIAMOND, SALES, dll',
  `action`          VARCHAR(30)   NOT NULL COMMENT 'VIEW, CREATE, EDIT, DELETE, APPROVE, POST',
  `description`     VARCHAR(255)  NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_code` (`permission_code`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Katalog hak akses atomik per modul dan aksi';

-- -----------------------------------------------------------------------------
-- Table: role_permissions
-- Pemetaan role ke permission (many-to-many)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`       INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  `granted_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `granted_by`    INT UNSIGNED NULL COMMENT 'FK ke users.id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permission` (`role_id`, `permission_id`),
  KEY `idx_rp_role`       (`role_id`),
  KEY `idx_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_role`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_permission`
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pemetaan role ke permission';

-- -----------------------------------------------------------------------------
-- Table: users
-- Pengguna sistem ERP
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `role_id`         INT UNSIGNED  NOT NULL,
  `employee_code`   VARCHAR(20)   NULL     COMMENT 'Kode karyawan internal',
  `username`        VARCHAR(60)   NOT NULL COMMENT 'Username untuk login',
  `full_name`       VARCHAR(150)  NOT NULL,
  `email`           VARCHAR(150)  NOT NULL,
  `password_hash`   VARCHAR(255)  NOT NULL COMMENT 'password_hash() PHP, algoritma bcrypt',
  `phone`           VARCHAR(20)   NULL,
  `branch_id`       INT UNSIGNED  NULL     COMMENT 'FK ke branches (ditambah Sprint 2)',
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `must_change_pw`  TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Wajib ganti password saat login pertama',
  `last_login_at`   TIMESTAMP     NULL,
  `login_attempt`   TINYINT       NOT NULL DEFAULT 0 COMMENT 'Counter gagal login, reset saat berhasil',
  `locked_until`    TIMESTAMP     NULL     COMMENT 'Akun terkunci sementara jika login attempt melebihi batas',
  `avatar_path`     VARCHAR(500)  NULL,
  `created_by`      INT UNSIGNED  NULL,
  `updated_by`      INT UNSIGNED  NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      TIMESTAMP     NULL     COMMENT 'Soft delete',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email`    (`email`),
  KEY `idx_users_role`      (`role_id`),
  KEY `idx_users_active`    (`is_active`),
  KEY `idx_users_deleted`   (`deleted_at`),
  CONSTRAINT `fk_users_role`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pengguna sistem ERP';

-- -----------------------------------------------------------------------------
-- Table: user_sessions
-- Menyimpan sesi aktif pengguna (untuk tracking & force logout)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED    NOT NULL,
  `session_token` VARCHAR(128)   NOT NULL COMMENT 'Token sesi unik, di-hash SHA-256',
  `ip_address`   VARCHAR(45)     NULL,
  `user_agent`   VARCHAR(300)    NULL,
  `last_activity` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`   TIMESTAMP       NOT NULL,
  `is_active`    TINYINT(1)      NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sessions_token` (`session_token`),
  KEY `idx_sessions_user`   (`user_id`),
  KEY `idx_sessions_active` (`is_active`, `expires_at`),
  CONSTRAINT `fk_sessions_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sesi aktif pengguna';

-- -----------------------------------------------------------------------------
-- Table: audit_logs
-- Audit trail semua aktivitas sistem (Sprint 1 sampai seterusnya)
-- Retensi: 7 tahun sesuai regulasi
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED    NULL     COMMENT 'NULL jika aksi sistem otomatis',
  `username`      VARCHAR(60)     NULL     COMMENT 'Snapshot username saat aksi (tidak berubah walau user diedit)',
  `ip_address`    VARCHAR(45)     NULL,
  `user_agent`    VARCHAR(300)    NULL,
  `module`        VARCHAR(50)     NOT NULL COMMENT 'USER, DIAMOND, SALES, dll',
  `action`        VARCHAR(30)     NOT NULL COMMENT 'LOGIN, LOGOUT, CREATE, UPDATE, DELETE, APPROVE, POST',
  `document_no`   VARCHAR(50)     NULL     COMMENT 'Nomor dokumen terkait (jika ada)',
  `table_name`    VARCHAR(80)     NULL,
  `record_id`     VARCHAR(30)     NULL     COMMENT 'ID record yang diubah',
  `before_value`  JSON            NULL     COMMENT 'Nilai sebelum perubahan',
  `after_value`   JSON            NULL     COMMENT 'Nilai setelah perubahan',
  `description`   TEXT            NULL     COMMENT 'Deskripsi bebas aktivitas',
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user`       (`user_id`),
  KEY `idx_audit_module`     (`module`),
  KEY `idx_audit_action`     (`action`),
  KEY `idx_audit_created`    (`created_at`),
  KEY `idx_audit_table`      (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail seluruh aktivitas sistem — retensi 7 tahun'
  PARTITION BY RANGE (YEAR(`created_at`)) (
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p2027 VALUES LESS THAN (2028),
    PARTITION p2028 VALUES LESS THAN (2029),
    PARTITION p2029 VALUES LESS THAN (2030),
    PARTITION p2030 VALUES LESS THAN (2031),
    PARTITION p2031 VALUES LESS THAN (2032),
    PARTITION p_future VALUES LESS THAN MAXVALUE
  );

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SEED DATA — Sprint 1
-- =============================================================================

-- Roles
INSERT INTO `roles` (`role_code`, `role_name`, `description`) VALUES
  ('OWNER',     'Owner',           'Akses penuh ke seluruh sistem'),
  ('MANAGER',   'Manajer Toko',    'Approval transaksi, laporan performa'),
  ('SALES',     'Staff Penjualan', 'Kelola lead, quotation, reservasi'),
  ('INVENTORY', 'Staff Gudang',    'Kelola penerimaan dan mutasi stok'),
  ('FINANCE',   'Staff Keuangan',  'Kelola invoice, pembayaran, jurnal'),
  ('IT_ADMIN',  'Administrator IT','Konfigurasi sistem, user, backup');

-- Permissions (Sprint 1: USER & ROLE module)
INSERT INTO `permissions` (`permission_code`, `permission_name`, `module`, `action`) VALUES
  -- User Management
  ('USER_VIEW',   'Lihat Daftar Pengguna',    'USER', 'VIEW'),
  ('USER_CREATE', 'Tambah Pengguna Baru',     'USER', 'CREATE'),
  ('USER_EDIT',   'Edit Data Pengguna',       'USER', 'EDIT'),
  ('USER_DELETE', 'Nonaktifkan Pengguna',     'USER', 'DELETE'),
  ('USER_RESET_PW','Reset Password Pengguna','USER', 'APPROVE'),
  -- Role Management
  ('ROLE_VIEW',   'Lihat Daftar Role',        'ROLE', 'VIEW'),
  ('ROLE_CREATE', 'Tambah Role Baru',         'ROLE', 'CREATE'),
  ('ROLE_EDIT',   'Edit Data Role',           'ROLE', 'EDIT'),
  ('ROLE_DELETE', 'Hapus Role',               'ROLE', 'DELETE'),
  -- Permission Management
  ('PERMISSION_VIEW',   'Lihat Permission',       'PERMISSION', 'VIEW'),
  ('PERMISSION_ASSIGN', 'Assign Permission ke Role','PERMISSION','APPROVE'),
  -- Audit Log
  ('AUDIT_VIEW',  'Lihat Audit Trail',        'AUDIT', 'VIEW'),
  -- System
  ('SYSTEM_BACKUP',  'Backup Database',       'SYSTEM', 'APPROVE'),
  ('SYSTEM_CONFIG',  'Konfigurasi Sistem',    'SYSTEM', 'EDIT');

-- Assign semua permission ke OWNER
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.role_code = 'OWNER';

-- Assign permission terbatas ke MANAGER
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.permission_code IN ('USER_VIEW','ROLE_VIEW','AUDIT_VIEW')
WHERE r.role_code = 'MANAGER';

-- Assign ke IT_ADMIN
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.permission_code IN (
  'USER_VIEW','USER_CREATE','USER_EDIT','USER_DELETE','USER_RESET_PW',
  'ROLE_VIEW','ROLE_CREATE','ROLE_EDIT','ROLE_DELETE',
  'PERMISSION_VIEW','PERMISSION_ASSIGN',
  'AUDIT_VIEW','SYSTEM_BACKUP','SYSTEM_CONFIG'
)
WHERE r.role_code = 'IT_ADMIN';

-- User default: admin (password: Admin@2026!)
-- password_hash('Admin@2026!', PASSWORD_BCRYPT)
INSERT INTO `users`
  (`role_id`, `employee_code`, `username`, `full_name`, `email`, `password_hash`, `must_change_pw`)
VALUES (
  (SELECT id FROM `roles` WHERE role_code = 'OWNER'),
  'EMP-001',
  'admin',
  'Administrator',
  'admin@onlyone.id',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXktnTyem',
  1
);
