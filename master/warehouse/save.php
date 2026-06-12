<?php // master/warehouse/save.php — Simpan gudang baru
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
$code   = strtoupper(trim(post('warehouse_code')));
$name   = trim(post('name'));
$type   = post('type');
$branchId = post_int('branch_id');

if (empty($code))     $errors[] = 'Kode gudang wajib diisi.';
if (empty($name))     $errors[] = 'Nama gudang wajib diisi.';
if (empty($branchId)) $errors[] = 'Cabang wajib dipilih.';
if (!in_array($type, ['main','display','sales','transit'], true)) $errors[] = 'Tipe gudang tidak valid.';
if (empty($errors) && WarehouseRepository::isCodeTaken($code))
    $errors[] = "Kode gudang '{$code}' sudah digunakan.";

if (!empty($errors)) {
    flash_set('error', implode(' ', $errors));
    redirect(url('master/warehouse'));
}

try {
    $id = WarehouseRepository::create([
        'branch_id'      => $branchId,
        'warehouse_code' => $code,
        'name'           => $name,
        'type'           => $type,
        'description'    => post('description'),
    ]);
    audit_log('WAREHOUSE','CREATE',$code,'warehouses',(string)$id,
        null,['name'=>$name,'type'=>$type],"Gudang baru: {$name}"
    );
    flash_set('success', "Gudang <strong>{$name}</strong> berhasil ditambahkan.");
} catch (Throwable $e) {
    flash_set('error', 'Gagal menyimpan gudang: ' . $e->getMessage());
}
redirect(url('master/warehouse'));
