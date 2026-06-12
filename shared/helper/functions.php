<?php
/**
 * shared/helper/functions.php
 * Fungsi helper global — tersedia di seluruh aplikasi
 * ERP Toko Berlian — Only One
 * VERSI LARAGON — url() helper disesuaikan dengan subfolder
 */

declare(strict_types=1);

// ─── CSRF ─────────────────────────────────────────────────────────────────────

function csrf_generate(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        if (is_ajax_request()) {
            json_response(false, 'Token keamanan tidak valid. Refresh halaman dan coba lagi.');
        }
        die('Token keamanan tidak valid. <a href="javascript:history.back()">Kembali</a>');
    }
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_generate(), ENT_QUOTES) . '">';
}

// ─── Response ─────────────────────────────────────────────────────────────────

function json_response(bool $success, string $message, mixed $data = null, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function is_ajax_request(): bool
{
    return (
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
    );
}

// ─── Redirect ─────────────────────────────────────────────────────────────────

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function redirect_with_message(string $url, string $type, string $message): never
{
    $_SESSION['flash'][$type] = $message;
    redirect($url);
}

// ─── Flash messages ───────────────────────────────────────────────────────────

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}

function flash_has(string $type): bool
{
    return !empty($_SESSION['flash'][$type]);
}

// ─── Security / Sanitize ──────────────────────────────────────────────────────

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function post(string $key, mixed $default = ''): mixed
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get_param(string $key, mixed $default = ''): mixed
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function post_int(string $key, int $default = 0): int
{
    return filter_var($_POST[$key] ?? $default, FILTER_VALIDATE_INT) !== false
        ? (int) $_POST[$key]
        : $default;
}

function sanitize_like(string $value): string
{
    return str_replace(['%', '_'], ['\\%', '\\_'], $value);
}

// ─── Formatting ───────────────────────────────────────────────────────────────

function format_idr(float|int|string $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function format_usd(float|int|string $amount): string
{
    return '$' . number_format((float) $amount, 2, '.', ',');
}

function format_date(?string $date): string
{
    if (!$date) return '—';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    $ts    = strtotime($date);
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function format_datetime(?string $datetime): string
{
    if (!$datetime) return '—';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    $ts    = strtotime($datetime);
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts)
         . ', ' . date('H:i', $ts);
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    return match(true) {
        $diff < 60     => 'Baru saja',
        $diff < 3600   => (int)($diff/60) . ' menit lalu',
        $diff < 86400  => (int)($diff/3600) . ' jam lalu',
        $diff < 604800 => (int)($diff/86400) . ' hari lalu',
        default        => format_date($datetime),
    };
}

// ─── Pagination ───────────────────────────────────────────────────────────────

function pagination_offset(int $page, int $perPage): int
{
    return max(0, ($page - 1) * $perPage);
}

function pagination_data(int $total, int $page, int $perPage): array
{
    $totalPages = (int) ceil($total / max(1, $perPage));
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $page,
        'total_pages' => $totalPages,
        'from'        => $total > 0 ? pagination_offset($page, $perPage) + 1 : 0,
        'to'          => min($total, pagination_offset($page, $perPage) + $perPage),
        'has_prev'    => $page > 1,
        'has_next'    => $page < $totalPages,
    ];
}

// ─── URL / Asset ──────────────────────────────────────────────────────────────

/**
 * Generate URL dengan BASE_URL (termasuk subfolder project)
 * Contoh: url('system/user') → http://localhost/erp-berlian-native-sprint1/system/user
 */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Generate URL untuk asset publik (CSS, JS, gambar)
 * Contoh: asset('css/app.css') → http://localhost/erp-berlian-native-sprint1/public/css/app.css
 */
function asset(string $path): string
{
    return BASE_URL . '/public/' . ltrim($path, '/');
}

// ─── Document Number ──────────────────────────────────────────────────────────

function generate_doc_number(string $prefix, string $branchCode = 'HO'): string
{
    $year  = date('Y');
    $month = date('m');

    $last = Database::fetchOne(
        "SELECT document_no FROM doc_number_sequences
          WHERE prefix = ? AND branch_code = ? AND year = ? AND month = ?
          FOR UPDATE",
        [$prefix, $branchCode, $year, $month]
    );

    $seq = $last ? ((int) substr($last['document_no'], -5)) + 1 : 1;
    $no  = sprintf('%s/%s/%s/%s/%05d', $prefix, $branchCode, $year, $month, $seq);

    Database::query(
        "INSERT INTO doc_number_sequences (prefix, branch_code, year, month, last_seq, document_no)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE last_seq = ?, document_no = ?",
        [$prefix, $branchCode, $year, $month, $seq, $no, $seq, $no]
    );

    return $no;
}

// ─── Misc ─────────────────────────────────────────────────────────────────────

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function name_initials(string $name): string
{
    $words = array_filter(explode(' ', $name));
    $init  = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $init .= strtoupper($word[0]);
    }
    return $init ?: 'U';
}
