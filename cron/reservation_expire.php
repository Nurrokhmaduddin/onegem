<?php
/**
 * cron/reservation_expire.php
 * Sistem auto-expire reservasi yang melewati tanggal kedaluwarsa.
 * Jalankan via cron setiap jam: 0 * * * * php /path/to/cron/reservation_expire.php
 * Atau setiap 15 menit: *\/15 * * * * ...
 */
declare(strict_types=1);

// Pastikan hanya bisa dijalankan via CLI atau dari IP internal
if (PHP_SAPI !== 'cli') {
    $allowedIps = ['127.0.0.1', '::1'];
    if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/helper/functions.php';
require_once __DIR__ . '/../sales/reservation/repository.php';
require_once __DIR__ . '/../sales/reservation/service.php';

$started = microtime(true);
$count   = 0;
$errors  = [];

try {
    $count = ReservationService::expireOverdue();
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$elapsed = round(microtime(true) - $started, 2);
$ts      = date('Y-m-d H:i:s');

if (PHP_SAPI === 'cli') {
    echo "[{$ts}] Reservation auto-expire selesai.\n";
    echo "  Jumlah diproses : {$count}\n";
    echo "  Waktu           : {$elapsed}s\n";
    if (!empty($errors)) {
        echo "  Errors:\n";
        foreach ($errors as $e) echo "    - {$e}\n";
    }
} else {
    // Response JSON jika dipanggil via HTTP (internal only)
    header('Content-Type: application/json');
    echo json_encode([
        'timestamp' => $ts,
        'expired'   => $count,
        'elapsed_s' => $elapsed,
        'errors'    => $errors,
    ]);
}
