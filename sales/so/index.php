<?php // sales/so/index.php — redirect ke list
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
redirect(url('sales/so/list'));
