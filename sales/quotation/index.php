<?php
/**
 * sales/quotation/index.php
 * Halaman utama Manajemen Quotation — redirect ke list
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_auth();
require_permission('QUOTATION_VIEW');
redirect(url('sales/quotation/list'));
