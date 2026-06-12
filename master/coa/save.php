<?php // master/coa/save.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_auth(); require_permission('COA_CREATE');
if (!is_post()) redirect(url('master/coa'));
csrf_validate();

$code    = trim(post('account_code'));
$name    = trim(post('account_name'));
$type    = post('account_type');
$balance = post('normal_balance');
$level   = post_int('level', 3);
$errors  = [];

if (empty($code))    $errors[] = 'Kode akun wajib diisi.';
if (empty($name))    $errors[] = 'Nama akun wajib diisi.';
if (!in_array($type, ['asset','liability','equity','revenue','cogs','expense'], true))
    $errors[] = 'Tipe akun tidak valid.';
if (!in_array($balance, ['debit','credit'], true))
    $errors[] = 'Normal balance tidak valid.';

// Cek duplikasi
if (empty($errors) && Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code=?",[$code]))
    $errors[] = "Kode akun '{$code}' sudah digunakan.";

if (!empty($errors)) { flash_set('error', implode(' ', $errors)); redirect(url('master/coa')); }

try {
    $id = Database::insert(
        "INSERT INTO chart_of_accounts
           (account_code,account_name,account_type,normal_balance,level,is_header,description,is_active,created_by)
         VALUES (?,?,?,?,?,?,?,1,?)",
        [$code,$name,$type,$balance,$level,
         !empty($_POST['is_header'])?1:0,
         post('description')?:null,$_SESSION['user_id']??null]
    );
    audit_log('COA','CREATE',$code,'chart_of_accounts',(string)$id,
        null,['name'=>$name,'type'=>$type],"COA baru: {$code} — {$name}"
    );
    flash_set('success',"Akun <strong>{$code} — {$name}</strong> berhasil ditambahkan.");
} catch (Throwable $e) {
    flash_set('error','Gagal menyimpan akun: '.$e->getMessage());
}
redirect(url('master/coa'));
