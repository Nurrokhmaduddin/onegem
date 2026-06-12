<?php // master/diamond/update.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth(); require_permission('DIAMOND_EDIT');
if (!is_post()) redirect(url('master/diamond'));
csrf_validate();

$diamondId = post_int('diamond_id');
if ($diamondId <= 0) { flash_set('error','ID tidak valid.'); redirect(url('master/diamond')); }

$certData = [
    'cert_number' => post('cert_number'),
    'cert_type'   => post('cert_type'),
    'issuer'      => post('cert_issuer'),
    'issue_date'  => post('cert_issue_date'),
];

try {
    DiamondService::update($diamondId, $_POST, $certData);
    flash_set('success', 'Data berlian berhasil diperbarui.');
    redirect(url('master/diamond/detail') . '?id=' . $diamondId);
} catch (RuntimeException $e) {
    $errors = json_decode($e->getMessage(), true);
    $_SESSION['form_errors'] = is_array($errors) ? array_values($errors) : [$e->getMessage()];
    $_SESSION['form_data']   = $_POST;
    redirect(url('master/diamond/edit') . '?id=' . $diamondId);
}
