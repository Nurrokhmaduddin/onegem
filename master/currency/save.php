<?php // master/currency/save.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_auth(); require_permission('CURRENCY_CREATE');
if (!is_post()) redirect(url('master/currency'));
csrf_validate();

$rate = (float)post('rate_to_idr');
$date = post('effective_date');
$note = post('notes');

if ($rate <= 0)    { flash_set('error','Rate harus lebih dari 0.'); redirect(url('master/currency')); }
if (empty($date))  { flash_set('error','Tanggal berlaku wajib diisi.'); redirect(url('master/currency')); }

// Nonaktifkan kurs lama pada tanggal yang sama
Database::query("UPDATE currencies SET is_active=0 WHERE code='USD' AND effective_date=?",[$date]);

$id = Database::insert(
    "INSERT INTO currencies (code,rate_to_idr,effective_date,set_by,notes,is_active) VALUES ('USD',?,?,?,?,1)",
    [$rate,$date,$_SESSION['user_id']??null,$note?:null]
);

// Invalidate cache
// (dalam real app: bersihkan cache Redis/Memcached)

audit_log('CURRENCY','CREATE',null,'currencies',(string)$id,
    null,['rate'=>$rate,'effective_date'=>$date],
    "Kurs USD baru: Rp ".number_format($rate,0,',','.')." berlaku ".format_date($date)
);
flash_set('success','Kurs baru <strong>Rp '.number_format($rate,0,',','.').' / USD</strong> berhasil disimpan.');
redirect(url('master/currency'));
