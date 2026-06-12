<?php // master/coa/update.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_auth(); require_permission('COA_EDIT');
if (!is_post()) redirect(url('master/coa'));
csrf_validate();

$coaId = post_int('coa_id');
$name  = trim(post('account_name'));
if (empty($name)) { flash_set('error','Nama akun wajib diisi.'); redirect(url('master/coa')); }

$old = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE id=?",[$coaId]);
if (!$old) { flash_set('error','Akun tidak ditemukan.'); redirect(url('master/coa')); }

Database::query(
    "UPDATE chart_of_accounts SET account_name=?,is_active=? WHERE id=?",
    [$name, !empty($_POST['is_active'])?1:0, $coaId]
);
audit_log('COA','UPDATE',$old['account_code'],'chart_of_accounts',(string)$coaId,
    ['name'=>$old['account_name']],['name'=>$name],
    "COA diperbarui: {$old['account_code']}"
);
flash_set('success','Akun berhasil diperbarui.');
redirect(url('master/coa'));
