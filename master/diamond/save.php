<?php // master/diamond/save.php — Proses simpan berlian baru
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth(); require_permission('DIAMOND_CREATE');
if (!is_post()) redirect(url('master/diamond'));
csrf_validate();

// Pisahkan data sertifikat dari data utama
$certData = [
    'cert_number'    => post('cert_number'),
    'cert_type'      => post('cert_type'),
    'issuer'         => post('cert_issuer'),
    'issue_date'     => post('cert_issue_date'),
];

try {
    $id = DiamondService::register($_POST, $certData);
    flash_set('success', 'Berlian berhasil didaftarkan dengan kode internal yang digenerate otomatis.');
    redirect(url('master/diamond/detail') . '?id=' . $id);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('master/diamond/create'));
}
