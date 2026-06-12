-- =============================================================================
-- ERP TOKO BERLIAN — ONLY ONE
-- Sprint 2: Master Data
-- Tabel: branches, warehouses, customers, suppliers,
--        diamond_shapes, diamonds, diamond_certificates,
--        chart_of_accounts, currencies
-- Versi  : Laragon / MySQL 8.0 (tanpa partisi)
-- Import : Jalankan SETELAH sprint1_schema.sql
-- =============================================================================

USE `erp_berlian`;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Table: branches
-- Cabang toko / reseller outlets
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `branches` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `branch_code`   VARCHAR(20)   NOT NULL,
  `name`          VARCHAR(100)  NOT NULL,
  `address`       TEXT          NULL,
  `phone`         VARCHAR(30)   NULL,
  `email`         VARCHAR(100)  NULL,
  `is_head_office` TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `created_by`    INT UNSIGNED  NULL,
  `updated_by`    INT UNSIGNED  NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branches_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cabang toko dan reseller';

-- -----------------------------------------------------------------------------
-- Table: warehouses
-- Lokasi penyimpanan barang per cabang
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `branch_id`      INT UNSIGNED  NOT NULL,
  `warehouse_code` VARCHAR(20)   NOT NULL,
  `name`           VARCHAR(100)  NOT NULL,
  `type`           ENUM('main','display','sales','transit') NOT NULL DEFAULT 'main',
  `description`    VARCHAR(200)  NULL,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `created_by`     INT UNSIGNED  NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_warehouses_code` (`warehouse_code`),
  KEY `idx_warehouses_branch` (`branch_id`),
  CONSTRAINT `fk_warehouses_branch`
    FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lokasi penyimpanan stok per cabang';

-- -----------------------------------------------------------------------------
-- Table: customers
-- Data pelanggan / pembeli
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `customer_code`   VARCHAR(20)   NOT NULL,
  `name`            VARCHAR(150)  NOT NULL,
  `identity_type`   ENUM('ktp','passport','sim','other') NULL DEFAULT 'ktp',
  `identity_number` VARCHAR(30)   NULL COMMENT 'Nomor KTP/Passport',
  `phone`           VARCHAR(30)   NULL,
  `phone2`          VARCHAR(30)   NULL,
  `email`           VARCHAR(100)  NULL,
  `address`         TEXT          NULL,
  `birth_date`      DATE          NULL,
  `gender`          ENUM('M','F') NULL,
  `tier`            ENUM('regular','vip','vvip') NOT NULL DEFAULT 'regular',
  `ring_size`       VARCHAR(10)   NULL,
  `preferences`     TEXT          NULL COMMENT 'Catatan preferensi desain/gaya',
  `notes`           TEXT          NULL,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_by`      INT UNSIGNED  NULL,
  `updated_by`      INT UNSIGNED  NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      TIMESTAMP     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_code` (`customer_code`),
  KEY `idx_customers_name`   (`name`),
  KEY `idx_customers_phone`  (`phone`),
  KEY `idx_customers_tier`   (`tier`),
  KEY `idx_customers_deleted`(`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Data pelanggan toko berlian';

-- -----------------------------------------------------------------------------
-- Table: suppliers
-- Data supplier berlian (konsinyasi & pembelian)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `supplier_code`    VARCHAR(20)   NOT NULL,
  `name`             VARCHAR(150)  NOT NULL,
  `contact_person`   VARCHAR(100)  NULL,
  `phone`            VARCHAR(30)   NULL,
  `phone2`           VARCHAR(30)   NULL,
  `email`            VARCHAR(100)  NULL,
  `address`          TEXT          NULL,
  `type`             ENUM('consignment','purchase','both') NOT NULL DEFAULT 'both',
  `currency`         ENUM('USD','IDR') NOT NULL DEFAULT 'USD',
  `discount_percent` DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `payment_terms`    VARCHAR(100)  NULL COMMENT 'NET30, NET60, COD, dll',
  `bank_name`        VARCHAR(100)  NULL,
  `bank_account`     VARCHAR(50)   NULL,
  `bank_holder`      VARCHAR(100)  NULL,
  `notes`            TEXT          NULL,
  `is_active`        TINYINT(1)    NOT NULL DEFAULT 1,
  `created_by`       INT UNSIGNED  NULL,
  `updated_by`       INT UNSIGNED  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`       TIMESTAMP     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_suppliers_code` (`supplier_code`),
  KEY `idx_suppliers_name`   (`name`),
  KEY `idx_suppliers_type`   (`type`),
  KEY `idx_suppliers_deleted`(`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Data supplier berlian';

-- -----------------------------------------------------------------------------
-- Table: diamond_shapes
-- Referensi bentuk berlian (Round, Princess, Oval, dll)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diamond_shapes` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`      VARCHAR(20)  NOT NULL,
  `name`      VARCHAR(50)  NOT NULL,
  `is_active` TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_diamond_shapes_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Referensi bentuk / shape berlian';

-- -----------------------------------------------------------------------------
-- Table: diamonds
-- Entitas utama — setiap baris = 1 item berlian unik
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diamonds` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `internal_code`    VARCHAR(50)   NOT NULL COMMENT 'Kode internal sistem: OO-YYYY-NNNNN',
  `factory_barcode`  VARCHAR(100)  NULL     COMMENT 'Barcode dari pabrik/supplier',
  `supplier_id`      INT UNSIGNED  NOT NULL,
  `warehouse_id`     INT UNSIGNED  NOT NULL,

  -- Jenis perolehan
  `acquisition_type` ENUM('consignment','purchase_returnable','purchase_final')
                     NOT NULL DEFAULT 'consignment'
                     COMMENT 'consignment=titipan; purchase_returnable=bisa retur; purchase_final=putus',
  `acquired_at`      DATE          NOT NULL,

  -- Spesifikasi 4Cs
  `shape_id`         INT UNSIGNED  NULL,
  `carat_weight`     DECIMAL(8,3)  NOT NULL,
  `color_grade`      VARCHAR(10)   NULL COMMENT 'D-Z atau Fancy',
  `clarity_grade`    VARCHAR(10)   NULL COMMENT 'FL,IF,VVS1,VVS2,VS1,VS2,SI1,SI2,I1,I2,I3',
  `cut_grade`        VARCHAR(15)   NULL COMMENT 'Excellent,Very Good,Good,Fair,Poor',
  `polish`           VARCHAR(15)   NULL,
  `symmetry`         VARCHAR(15)   NULL,
  `fluorescence`     VARCHAR(20)   NULL,
  `measurements`     VARCHAR(30)   NULL COMMENT 'misal: 6.40-6.43x3.96',
  `table_percent`    DECIMAL(5,2)  NULL,
  `depth_percent`    DECIMAL(5,2)  NULL,
  `stone_count`      INT           NOT NULL DEFAULT 1,

  -- Setting / logam
  `metal_type`       VARCHAR(30)   NULL COMMENT 'Gold 18K, Platinum, dll',
  `metal_weight_gr`  DECIMAL(8,3)  NULL,
  `karat`            TINYINT       NULL COMMENT '14, 18, 22, 24',

  -- Harga (selalu dalam USD, dikonversi saat tampil)
  `cost_price_usd`    DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'HPP dari supplier',
  `selling_price_usd` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Harga jual dasar',

  -- Status lifecycle
  `status`           ENUM('registered','available','reserved','sold','returned','in_repair','retired')
                     NOT NULL DEFAULT 'registered',

  -- Foto utama (path)
  `photo_path`       VARCHAR(500)  NULL,

  -- Audit
  `notes`            TEXT          NULL,
  `registered_by`    INT UNSIGNED  NULL,
  `created_by`       INT UNSIGNED  NULL,
  `updated_by`       INT UNSIGNED  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`       TIMESTAMP     NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_diamonds_internal_code` (`internal_code`),
  KEY `idx_diamonds_factory_barcode` (`factory_barcode`),
  KEY `idx_diamonds_supplier`  (`supplier_id`),
  KEY `idx_diamonds_warehouse` (`warehouse_id`),
  KEY `idx_diamonds_status`    (`status`),
  KEY `idx_diamonds_shape`     (`shape_id`),
  KEY `idx_diamonds_acq_type`  (`acquisition_type`),
  KEY `idx_diamonds_deleted`   (`deleted_at`),
  CONSTRAINT `fk_diamonds_supplier`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_diamonds_warehouse`
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_diamonds_shape`
    FOREIGN KEY (`shape_id`) REFERENCES `diamond_shapes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Entitas utama berlian — setiap baris = 1 item unik';

-- -----------------------------------------------------------------------------
-- Table: diamond_certificates
-- Sertifikat GIA/IGI/local per berlian
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diamond_certificates` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `diamond_id`  INT UNSIGNED NOT NULL,
  `cert_number` VARCHAR(50)  NOT NULL,
  `cert_type`   ENUM('GIA','IGI','HRD','LOCAL','FACTORY','OTHER') NOT NULL DEFAULT 'GIA',
  `issuer`      VARCHAR(100) NULL,
  `issue_date`  DATE         NULL,
  `file_path`   VARCHAR(500) NULL COMMENT 'Path file sertifikat (PDF/gambar)',
  `is_primary`  TINYINT(1)   NOT NULL DEFAULT 1,
  `notes`       TEXT         NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_diamond_certs_diamond` (`diamond_id`),
  CONSTRAINT `fk_diamond_certs_diamond`
    FOREIGN KEY (`diamond_id`) REFERENCES `diamonds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sertifikat GIA/IGI/lokal per berlian';

-- -----------------------------------------------------------------------------
-- Table: diamond_state_histories
-- Audit trail perubahan status berlian
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diamond_state_histories` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `diamond_id`  INT UNSIGNED    NOT NULL,
  `from_status` VARCHAR(30)     NULL,
  `to_status`   VARCHAR(30)     NOT NULL,
  `event_name`  VARCHAR(100)    NOT NULL,
  `ref_type`    VARCHAR(50)     NULL,
  `ref_id`      INT UNSIGNED    NULL,
  `actor_id`    INT UNSIGNED    NULL,
  `notes`       TEXT            NULL,
  `changed_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dsh_diamond` (`diamond_id`),
  KEY `idx_dsh_event`   (`event_name`),
  CONSTRAINT `fk_dsh_diamond`
    FOREIGN KEY (`diamond_id`) REFERENCES `diamonds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Riwayat perubahan status berlian';

-- -----------------------------------------------------------------------------
-- Table: currencies
-- Riwayat kurs USD ke IDR
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `currencies` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`           VARCHAR(5)    NOT NULL DEFAULT 'USD',
  `rate_to_idr`    DECIMAL(15,4) NOT NULL,
  `effective_date` DATE          NOT NULL,
  `set_by`         INT UNSIGNED  NULL,
  `notes`          VARCHAR(200)  NULL,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_currencies_code_date` (`code`, `effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Riwayat kurs valuta asing';

-- -----------------------------------------------------------------------------
-- Table: chart_of_accounts (COA)
-- Bagan akun untuk modul akuntansi
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `account_code` VARCHAR(20)   NOT NULL,
  `account_name` VARCHAR(150)  NOT NULL,
  `account_type` ENUM('asset','liability','equity','revenue','expense','cogs') NOT NULL,
  `parent_id`    INT UNSIGNED  NULL COMMENT 'Untuk COA hierarki',
  `level`        TINYINT       NOT NULL DEFAULT 1,
  `is_header`    TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1=header (tidak bisa posting)',
  `normal_balance` ENUM('debit','credit') NOT NULL,
  `description`  VARCHAR(255)  NULL,
  `is_active`    TINYINT(1)    NOT NULL DEFAULT 1,
  `created_by`   INT UNSIGNED  NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coa_code` (`account_code`),
  KEY `idx_coa_type`   (`account_type`),
  KEY `idx_coa_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bagan akun (Chart of Accounts)';

-- Update tabel users: tambah branch_id FK sekarang branches sudah ada
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch`
    FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SEED DATA Sprint 2
-- =============================================================================

-- Branches
INSERT INTO `branches` (`branch_code`,`name`,`address`,`phone`,`is_head_office`) VALUES
  ('HO',  'Only One — Pusat',      'Jl. Pemuda No. 1, Semarang',       '024-1234567', 1),
  ('SMG', 'Only One — Semarang',   'Jl. Pandanaran No. 50, Semarang',  '024-9876543', 0),
  ('PWK', 'Only One — Purwokerto', 'Jl. Jend. Sudirman No. 10, PWK',   '0281-555123', 0);

-- Warehouses
INSERT INTO `warehouses` (`branch_id`,`warehouse_code`,`name`,`type`) VALUES
  (1,'HO-MAIN',   'Vault Utama Pusat',        'main'),
  (1,'HO-DISPLAY','Etalase Pusat',            'display'),
  (1,'HO-SALES',  'Tas Sales Pusat',          'sales'),
  (1,'HO-TRANSIT','Transit / Reparasi Pusat', 'transit'),
  (2,'SMG-MAIN',  'Vault Semarang',           'main'),
  (2,'SMG-DISPLAY','Etalase Semarang',        'display'),
  (3,'PWK-MAIN',  'Vault Purwokerto',         'main'),
  (3,'PWK-DISPLAY','Etalase Purwokerto',      'display');

-- Diamond Shapes
INSERT INTO `diamond_shapes` (`code`,`name`) VALUES
  ('ROUND',    'Round Brilliant'),
  ('PRINCESS', 'Princess'),
  ('OVAL',     'Oval'),
  ('CUSHION',  'Cushion'),
  ('EMERALD',  'Emerald'),
  ('PEAR',     'Pear'),
  ('MARQUISE', 'Marquise'),
  ('RADIANT',  'Radiant'),
  ('ASSCHER',  'Asscher'),
  ('HEART',    'Heart');

-- Sample Suppliers
INSERT INTO `suppliers` (`supplier_code`,`name`,`contact_person`,`phone`,`type`,`currency`,`discount_percent`) VALUES
  ('SUP-001','PT Mahkota Diamond',     'Bpk. Hendra',    '021-5551001','consignment', 'USD',5.00),
  ('SUP-002','Rio Diamond Jakarta',    'Ibu Santi',      '021-5552002','purchase',    'USD',3.50),
  ('SUP-003','Star Gems International','Mr. David Lee',  '021-5553003','both',        'USD',4.00),
  ('SUP-004','Berlian Nusantara',      'Bpk. Suryo',     '022-5554004','purchase',    'IDR',0.00);

-- Sample Customers
INSERT INTO `customers` (`customer_code`,`name`,`phone`,`email`,`tier`,`ring_size`) VALUES
  ('CUS-0001','Bpk. Andi Wijaya',  '08123456789','andi@email.com', 'vvip',   '20'),
  ('CUS-0002','Ibu Sari Rahayu',   '08234567890','sari@email.com', 'vip',    '17'),
  ('CUS-0003','Bpk. Toni Santoso', '08345678901','toni@email.com', 'regular', NULL),
  ('CUS-0004','Ibu Dewi Putri',    '08456789012','dewi@email.com', 'vvip',   '16');

-- Currency rates
INSERT INTO `currencies` (`code`,`rate_to_idr`,`effective_date`,`notes`) VALUES
  ('USD',16250.0000,'2026-01-01','Opening rate Jan 2026'),
  ('USD',16380.0000,'2026-02-01','Rate Feb 2026'),
  ('USD',16420.0000,'2026-03-01','Rate Mar 2026'),
  ('USD',16500.0000,'2026-04-01','Rate Apr 2026'),
  ('USD',16475.0000,'2026-05-01','Rate May 2026'),
  ('USD',16510.0000,'2026-06-01','Rate Jun 2026');

-- COA dasar toko berlian
INSERT INTO `chart_of_accounts` (`account_code`,`account_name`,`account_type`,`level`,`is_header`,`normal_balance`) VALUES
  -- ASET
  ('1000','ASET',                    'asset',    1,1,'debit'),
  ('1100','Kas & Bank',              'asset',    2,1,'debit'),
  ('1101','Kas Tunai',               'asset',    3,0,'debit'),
  ('1102','Bank BCA',                'asset',    3,0,'debit'),
  ('1103','Bank Mandiri',            'asset',    3,0,'debit'),
  ('1200','Piutang Usaha',           'asset',    2,1,'debit'),
  ('1201','Piutang Dagang',          'asset',    3,0,'debit'),
  ('1300','Persediaan',              'asset',    2,1,'debit'),
  ('1301','Persediaan Berlian',      'asset',    3,0,'debit'),
  ('1302','Persediaan Konsinyasi',   'asset',    3,0,'debit'),
  -- KEWAJIBAN
  ('2000','KEWAJIBAN',               'liability',1,1,'credit'),
  ('2100','Hutang Usaha',            'liability',2,1,'credit'),
  ('2101','Hutang Dagang Supplier',  'liability',3,0,'credit'),
  ('2102','Hutang Konsinyasi',       'liability',3,0,'credit'),
  -- EKUITAS
  ('3000','EKUITAS',                 'equity',   1,1,'credit'),
  ('3101','Modal Pemilik',           'equity',   2,0,'credit'),
  ('3102','Laba Ditahan',            'equity',   2,0,'credit'),
  -- PENDAPATAN
  ('4000','PENDAPATAN',              'revenue',  1,1,'credit'),
  ('4101','Penjualan Berlian',       'revenue',  2,0,'credit'),
  ('4102','Pendapatan Jasa Reparasi','revenue',  2,0,'credit'),
  -- HPP
  ('5000','HARGA POKOK PENJUALAN',   'cogs',     1,1,'debit'),
  ('5101','HPP Berlian',             'cogs',     2,0,'debit'),
  -- BEBAN
  ('6000','BEBAN OPERASIONAL',       'expense',  1,1,'debit'),
  ('6101','Beban Gaji',              'expense',  2,0,'debit'),
  ('6102','Beban Sewa',              'expense',  2,0,'debit'),
  ('6103','Beban Listrik & Air',     'expense',  2,0,'debit'),
  ('6104','Beban Pemasaran',         'expense',  2,0,'debit'),
  ('6105','Beban Administrasi',      'expense',  2,0,'debit');

-- Tambahkan permission Sprint 2 ke tabel permissions
INSERT INTO `permissions` (`permission_code`,`permission_name`,`module`,`action`) VALUES
  -- Customer
  ('CUSTOMER_VIEW',  'Lihat Data Pelanggan',   'CUSTOMER','VIEW'),
  ('CUSTOMER_CREATE','Tambah Pelanggan',        'CUSTOMER','CREATE'),
  ('CUSTOMER_EDIT',  'Edit Data Pelanggan',     'CUSTOMER','EDIT'),
  ('CUSTOMER_DELETE','Hapus Pelanggan',         'CUSTOMER','DELETE'),
  -- Supplier
  ('SUPPLIER_VIEW',  'Lihat Data Supplier',     'SUPPLIER','VIEW'),
  ('SUPPLIER_CREATE','Tambah Supplier',         'SUPPLIER','CREATE'),
  ('SUPPLIER_EDIT',  'Edit Data Supplier',      'SUPPLIER','EDIT'),
  ('SUPPLIER_DELETE','Hapus Supplier',          'SUPPLIER','DELETE'),
  -- Diamond
  ('DIAMOND_VIEW',   'Lihat Data Berlian',      'DIAMOND', 'VIEW'),
  ('DIAMOND_CREATE', 'Daftarkan Berlian Baru',  'DIAMOND', 'CREATE'),
  ('DIAMOND_EDIT',   'Edit Data Berlian',       'DIAMOND', 'EDIT'),
  ('DIAMOND_DELETE', 'Nonaktifkan Berlian',     'DIAMOND', 'DELETE'),
  -- Warehouse
  ('WAREHOUSE_VIEW', 'Lihat Gudang',            'WAREHOUSE','VIEW'),
  ('WAREHOUSE_CREATE','Tambah Gudang',          'WAREHOUSE','CREATE'),
  ('WAREHOUSE_EDIT', 'Edit Gudang',             'WAREHOUSE','EDIT'),
  -- COA
  ('COA_VIEW',       'Lihat Chart of Accounts', 'COA',    'VIEW'),
  ('COA_CREATE',     'Tambah Akun',             'COA',    'CREATE'),
  ('COA_EDIT',       'Edit Akun',               'COA',    'EDIT'),
  -- Currency
  ('CURRENCY_VIEW',  'Lihat Kurs Valuta',       'CURRENCY','VIEW'),
  ('CURRENCY_CREATE','Input Kurs Baru',         'CURRENCY','CREATE');

-- Grant semua permission baru ke OWNER
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.role_code = 'OWNER'
  AND p.permission_code IN (
    'CUSTOMER_VIEW','CUSTOMER_CREATE','CUSTOMER_EDIT','CUSTOMER_DELETE',
    'SUPPLIER_VIEW','SUPPLIER_CREATE','SUPPLIER_EDIT','SUPPLIER_DELETE',
    'DIAMOND_VIEW','DIAMOND_CREATE','DIAMOND_EDIT','DIAMOND_DELETE',
    'WAREHOUSE_VIEW','WAREHOUSE_CREATE','WAREHOUSE_EDIT',
    'COA_VIEW','COA_CREATE','COA_EDIT',
    'CURRENCY_VIEW','CURRENCY_CREATE'
  )
ON DUPLICATE KEY UPDATE granted_at = NOW();

-- Grant ke IT_ADMIN
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.role_code = 'IT_ADMIN'
  AND p.permission_code IN (
    'CUSTOMER_VIEW','SUPPLIER_VIEW','DIAMOND_VIEW',
    'WAREHOUSE_VIEW','WAREHOUSE_CREATE','WAREHOUSE_EDIT',
    'COA_VIEW','COA_CREATE','COA_EDIT',
    'CURRENCY_VIEW','CURRENCY_CREATE'
  )
ON DUPLICATE KEY UPDATE granted_at = NOW();

-- Grant ke INVENTORY
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.role_code = 'INVENTORY'
  AND p.permission_code IN (
    'DIAMOND_VIEW','DIAMOND_CREATE','DIAMOND_EDIT',
    'WAREHOUSE_VIEW','SUPPLIER_VIEW','CUSTOMER_VIEW'
  )
ON DUPLICATE KEY UPDATE granted_at = NOW();

-- Grant ke SALES
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.role_code = 'SALES'
  AND p.permission_code IN (
    'CUSTOMER_VIEW','CUSTOMER_CREATE','CUSTOMER_EDIT',
    'DIAMOND_VIEW','SUPPLIER_VIEW','WAREHOUSE_VIEW'
  )
ON DUPLICATE KEY UPDATE granted_at = NOW();

-- Grant ke FINANCE
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.role_code = 'FINANCE'
  AND p.permission_code IN (
    'COA_VIEW','COA_CREATE','COA_EDIT',
    'CURRENCY_VIEW','CURRENCY_CREATE',
    'CUSTOMER_VIEW','SUPPLIER_VIEW','DIAMOND_VIEW'
  )
ON DUPLICATE KEY UPDATE granted_at = NOW();
