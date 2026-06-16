-- =============================================================================
-- ERP TOKO BERLIAN — Sprint 4: Sales Order
-- Jalankan SETELAH sprint3_patch.sql
-- Compatible: MySQL 5.7+
-- =============================================================================

USE `erp_berlian`;
SET FOREIGN_KEY_CHECKS = 0;

-- Tambah kolom 'delivered' ke ENUM status diamonds jika belum ada
DROP PROCEDURE IF EXISTS `sp_patch_diamond_status`;
DELIMITER $$
CREATE PROCEDURE `sp_patch_diamond_status`()
BEGIN
  -- Cek apakah 'delivered' sudah ada di ENUM
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA   = DATABASE()
      AND TABLE_NAME     = 'diamonds'
      AND COLUMN_NAME    = 'status'
      AND COLUMN_TYPE LIKE '%delivered%'
  ) THEN
    ALTER TABLE `diamonds`
      MODIFY COLUMN `status`
        ENUM('available','reserved','sold','delivered','damaged','lost')
        NOT NULL DEFAULT 'available';
  END IF;
END$$
DELIMITER ;
CALL `sp_patch_diamond_status`();
DROP PROCEDURE IF EXISTS `sp_patch_diamond_status`;

-- Pastikan kolom price_idr ada di sales_order_items
DROP PROCEDURE IF EXISTS `sp_patch_soi`;
DELIMITER $$
CREATE PROCEDURE `sp_patch_soi`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'sales_order_items'
      AND COLUMN_NAME  = 'price_idr'
  ) THEN
    ALTER TABLE `sales_order_items`
      ADD COLUMN `price_idr` DECIMAL(20,2) NOT NULL DEFAULT 0 AFTER `price_usd`;
  END IF;
END$$
DELIMITER ;
CALL `sp_patch_soi`();
DROP PROCEDURE IF EXISTS `sp_patch_soi`;

-- =============================================================================
-- Permissions Sprint 4 — Sales Order
-- =============================================================================

INSERT IGNORE INTO `permissions` (`permission_code`, `permission_name`, `module`, `action`, `description`) VALUES
('SO_VIEW',     'Lihat Sales Order',        'SO', 'VIEW',    NULL),
('SO_CREATE',   'Buat Sales Order',         'SO', 'CREATE',  NULL),
('SO_EDIT',     'Edit Sales Order',         'SO', 'EDIT',    NULL),
('SO_DELETE',   'Hapus Sales Order',        'SO', 'DELETE',  NULL),
('SO_SUBMIT',   'Submit Sales Order',       'SO', 'ACTION',  NULL),
('SO_APPROVE',  'Approve/Reject SO',        'SO', 'APPROVE', NULL),
('SO_CANCEL',   'Batalkan Sales Order',     'SO', 'ACTION',  NULL),
('SO_COMPLETE', 'Tandai SO Selesai',        'SO', 'ACTION',  NULL);

-- OWNER — semua
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `granted_at`, `granted_by`)
SELECT r.id, p.id, NOW(), NULL
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_code = 'OWNER' AND p.module = 'SO';

-- MANAGER — semua kecuali delete
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `granted_at`, `granted_by`)
SELECT r.id, p.id, NOW(), NULL
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_code = 'MANAGER' AND p.module = 'SO' AND p.action != 'DELETE';

-- SALES — view, create, edit, submit, cancel
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `granted_at`, `granted_by`)
SELECT r.id, p.id, NOW(), NULL
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_code = 'SALES'
  AND p.module = 'SO'
  AND p.permission_code IN ('SO_VIEW','SO_CREATE','SO_EDIT','SO_SUBMIT','SO_CANCEL');

-- FINANCE — view + complete (delivery confirmation)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `granted_at`, `granted_by`)
SELECT r.id, p.id, NOW(), NULL
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_code = 'FINANCE'
  AND p.module = 'SO'
  AND p.permission_code IN ('SO_VIEW','SO_COMPLETE');

SET FOREIGN_KEY_CHECKS = 1;
