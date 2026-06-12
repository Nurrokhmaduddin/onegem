<?php // master/warehouse/save_branch.php — Simpan cabang baru
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth(); require_permission('WAREHOUSE_CREATE');
if (!is_post()) redirect(url('master/warehouse'));
csrf_validate();

$errors = [];
$code   = strtoupper(trim(post('branch_code')));
$name   = trim(post('name'));

if (empty($code)) $errors[] = 'Kode cabang wajib diisi.';
if (empty($name)) $errors[] = 'Nama cabang wajib diisi.';
if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $code)) $errors[] = 'Kode cabang hanya huruf kapital, angka, dan strip.';
if (empty($errors) && WarehouseRepository::isBranchCodeTaken($code))
    $errors[] = "Kode cabang '{$code}' sudah digunakan.";

if (!empty($errors)) {
    flash_set('error', implode(' ', $errors));
    redirect(url('master/warehouse'));
}

try {
    $id = WarehouseRepository::createBranch([
        'branch_code'    => $code,
        'name'           => $name,
        'address'        => post('address'),
        'phone'          => post('phone'),
        'email'          => post('email'),
        'is_head_office' => !empty($_POST['is_head_office']) ? 1 : 0,
    ]);
    audit_log('WAREHOUSE','CREATE',$code,'branches',(string)$id,
        null,['name'=>$name],"Cabang baru: {$name}"
    );
    flash_set('success', "Cabang <strong>{$name}</strong> berhasil ditambahkan.");
} catch (Throwable $e) {
    flash_set('error', 'Gagal menyimpan cabang: ' . $e->getMessage());
}
redirect(url('master/warehouse'));
