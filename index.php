<?php
// die('INDEX FRONT CONTROLLER WORKING');
/**
 * index.php — Front Controller
 * Hybrid routing: terima semua request, dispatch ke modul yang sesuai
 * ERP Toko Berlian — Only One
 * VERSI LARAGON — subfolder di localhost
 */

declare(strict_types=1);

// ─── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/shared/helper/functions.php';
require_once __DIR__ . '/shared/middleware/auth.php';
require_once __DIR__ . '/shared/middleware/audit.php';

// ─── Session setup ────────────────────────────────────────────────────────────
// session_name(SESSION_NAME);
// session_set_cookie_params([
//     'lifetime' => SESSION_LIFETIME,
//     'path'     => '/',
//     'secure'   => SESSION_SECURE,
//     'httponly' => true,
//     'samesite' => 'Lax',
// ]);
// session_start();

// ─── Routing ──────────────────────────────────────────────────────────────────
// Ambil path dari URL, hilangkan subfolder project dan query string
$requestUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Hapus BASE_FOLDER prefix dari path
// Contoh: /erp-berlian-native-sprint1/auth/login → /auth/login
$baseFolderLen = strlen(BASE_FOLDER);
if (str_starts_with($requestUri, BASE_FOLDER)) {
    $path = substr($requestUri, $baseFolderLen);
} else {
    $path = $requestUri;
}

// Pastikan selalu diawali /
$path = '/' . ltrim($path, '/');

// Hilangkan trailing slash kecuali root
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}
if ($path === '/') {
    redirect(url('auth/login'));
}
// echo '<pre>';
// echo 'REQUEST_URI = ' . $_SERVER['REQUEST_URI'] . PHP_EOL;
// echo 'BASE_FOLDER = ' . BASE_FOLDER . PHP_EOL;
// echo 'PATH = ' . $path . PHP_EOL;
// exit;
// ─── Route map ────────────────────────────────────────────────────────────────
$routes = [
    // Auth
    '/auth/login'           => __DIR__ . '/auth/login.php',
    '/auth/logout'          => __DIR__ . '/auth/logout.php',
    '/auth/change-password' => __DIR__ . '/auth/change_password.php',

    // Dashboard
    '/'          => __DIR__ . '/dashboard/index.php',
    '/dashboard' => __DIR__ . '/dashboard/index.php',

    // System — User Management
    '/system/user'                => __DIR__ . '/system/user/list.php',
    '/system/user/create'         => __DIR__ . '/system/user/form.php',
    '/system/user/edit'           => __DIR__ . '/system/user/form.php',
    '/system/user/detail'         => __DIR__ . '/system/user/detail.php',
    '/system/user/save'           => __DIR__ . '/system/user/save.php',
    '/system/user/update'         => __DIR__ . '/system/user/update.php',
    '/system/user/delete'         => __DIR__ . '/system/user/delete.php',
    '/system/user/reset-password' => __DIR__ . '/system/user/reset_password.php',
    '/system/user/toggle-status'  => __DIR__ . '/system/user/toggle_status.php',

    // System — Role Management
    '/system/role'        => __DIR__ . '/system/role/list.php',
    '/system/role/create' => __DIR__ . '/system/role/form.php',
    '/system/role/edit'   => __DIR__ . '/system/role/form.php',
    '/system/role/save'   => __DIR__ . '/system/role/save.php',
    '/system/role/update' => __DIR__ . '/system/role/update.php',
    '/system/role/delete' => __DIR__ . '/system/role/delete.php',

    // System — Permission Management
    '/system/permission'        => __DIR__ . '/system/permission/list.php',
    '/system/permission/assign' => __DIR__ . '/system/permission/assign.php',

    // AJAX — User
    '/ajax/user/check-username'  => __DIR__ . '/system/user/ajax/check_username.php',
    '/ajax/user/check-email'     => __DIR__ . '/system/user/ajax/check_email.php',
    '/ajax/user/search'          => __DIR__ . '/system/user/ajax/search.php',
    '/ajax/user/toggle-status'   => __DIR__ . '/system/user/toggle_status.php',

    // AJAX — Role
    '/ajax/role/permissions'     => __DIR__ . '/system/role/ajax/get_permissions.php',

    // AJAX — Permission
    '/ajax/permission/list'      => __DIR__ . '/system/permission/ajax/get_list.php',
    '/ajax/permission/save-role' => __DIR__ . '/system/permission/ajax/save_role_permissions.php',

    // Audit Log
    '/system/audit' => __DIR__ . '/system/audit/list.php',

    // ── SPRINT 2: Master Data ──────────────────────────────────────────────

    // Customer
    '/master/customer'        => __DIR__ . '/master/customer/list.php',
    '/master/customer/create' => __DIR__ . '/master/customer/form.php',
    '/master/customer/edit'   => __DIR__ . '/master/customer/form.php',
    '/master/customer/detail' => __DIR__ . '/master/customer/detail.php',
    '/master/customer/save'   => __DIR__ . '/master/customer/save.php',
    '/master/customer/update' => __DIR__ . '/master/customer/update.php',
    '/master/customer/delete' => __DIR__ . '/master/customer/delete.php',

    // Supplier
    '/master/supplier'        => __DIR__ . '/master/supplier/list.php',
    '/master/supplier/create' => __DIR__ . '/master/supplier/form.php',
    '/master/supplier/edit'   => __DIR__ . '/master/supplier/form.php',
    '/master/supplier/detail' => __DIR__ . '/master/supplier/detail.php',
    '/master/supplier/save'   => __DIR__ . '/master/supplier/save.php',
    '/master/supplier/update' => __DIR__ . '/master/supplier/update.php',
    '/master/supplier/delete' => __DIR__ . '/master/supplier/delete.php',

    // Diamond
    '/master/diamond'           => __DIR__ . '/master/diamond/list.php',
    '/master/diamond/create'    => __DIR__ . '/master/diamond/form.php',
    '/master/diamond/edit'      => __DIR__ . '/master/diamond/form.php',
    '/master/diamond/detail'    => __DIR__ . '/master/diamond/detail.php',
    '/master/diamond/save'      => __DIR__ . '/master/diamond/save.php',
    '/master/diamond/update'    => __DIR__ . '/master/diamond/update.php',
    '/master/diamond/delete'    => __DIR__ . '/master/diamond/delete.php',
    '/master/diamond/activate'  => __DIR__ . '/master/diamond/activate.php',
    '/master/diamond/retire'    => __DIR__ . '/master/diamond/retire.php',

    // Warehouse & Branch
    '/master/warehouse'              => __DIR__ . '/master/warehouse/list.php',
    '/master/warehouse/save'         => __DIR__ . '/master/warehouse/save.php',
    '/master/warehouse/save-branch'  => __DIR__ . '/master/warehouse/save_branch.php',

    // Chart of Accounts
    '/master/coa'        => __DIR__ . '/master/coa/list.php',
    '/master/coa/save'   => __DIR__ . '/master/coa/save.php',
    '/master/coa/update' => __DIR__ . '/master/coa/update.php',

    // Currency
    '/master/currency'      => __DIR__ . '/master/currency/list.php',
    '/master/currency/save' => __DIR__ . '/master/currency/save.php',



    // ── SPRINT 4: Sales Order ──────────────────────────────────────────────
    '/sales/so'              => __DIR__ . '/sales/so/index.php',
    '/sales/so/list'         => __DIR__ . '/sales/so/list.php',
    '/sales/so/create'       => __DIR__ . '/sales/so/form.php',
    '/sales/so/edit'         => __DIR__ . '/sales/so/form.php',
    '/sales/so/detail'       => __DIR__ . '/sales/so/detail.php',
    '/sales/so/save'         => __DIR__ . '/sales/so/save.php',
    '/sales/so/update'       => __DIR__ . '/sales/so/update.php',
    '/sales/so/submit'       => __DIR__ . '/sales/so/submit.php',
    '/sales/so/approve'      => __DIR__ . '/sales/so/approve.php',
    '/sales/so/reject'       => __DIR__ . '/sales/so/reject.php',
    '/sales/so/cancel'       => __DIR__ . '/sales/so/cancel.php',
    '/sales/so/complete'     => __DIR__ . '/sales/so/complete.php',

    // SO AJAX
    '/ajax/so/search-diamond' => __DIR__ . '/sales/so/ajax/search_diamond.php',
    '/ajax/so/add-item'       => __DIR__ . '/sales/so/ajax/add_item.php',
    '/ajax/so/remove-item'    => __DIR__ . '/sales/so/ajax/remove_item.php',

    // ── SPRINT 3: Sales Domain ─────────────────────────────────────────────

    // Lead
    '/sales/lead'                => __DIR__ . '/sales/lead/index.php',
    '/sales/lead/list'           => __DIR__ . '/sales/lead/list.php',
    '/sales/lead/create'         => __DIR__ . '/sales/lead/form.php',
    '/sales/lead/edit'           => __DIR__ . '/sales/lead/form.php',
    '/sales/lead/detail'         => __DIR__ . '/sales/lead/detail.php',
    '/sales/lead/save'           => __DIR__ . '/sales/lead/save.php',
    '/sales/lead/update'         => __DIR__ . '/sales/lead/update.php',
    '/sales/lead/delete'         => __DIR__ . '/sales/lead/delete.php',
    '/sales/lead/change-status'  => __DIR__ . '/sales/lead/change_status.php',
    '/sales/lead/add-activity'   => __DIR__ . '/sales/lead/add_activity.php',
    '/sales/lead/convert'        => __DIR__ . '/sales/lead/convert.php',

    // Quotation
    '/sales/quotation'                       => __DIR__ . '/sales/quotation/list.php',
    '/sales/quotation/list'                  => __DIR__ . '/sales/quotation/list.php',
    '/sales/quotation/create'                => __DIR__ . '/sales/quotation/form.php',
    '/sales/quotation/edit'                  => __DIR__ . '/sales/quotation/form.php',
    '/sales/quotation/detail'                => __DIR__ . '/sales/quotation/detail.php',
    '/sales/quotation/save'                  => __DIR__ . '/sales/quotation/save.php',
    '/sales/quotation/update'                => __DIR__ . '/sales/quotation/update.php',
    '/sales/quotation/delete'                => __DIR__ . '/sales/quotation/delete.php',
    '/sales/quotation/submit'                => __DIR__ . '/sales/quotation/submit.php',
    '/sales/quotation/approve'               => __DIR__ . '/sales/quotation/approve.php',
    '/sales/quotation/reject'                => __DIR__ . '/sales/quotation/reject.php',
    '/sales/quotation/accept'                => __DIR__ . '/sales/quotation/accept.php',
    '/sales/quotation/cancel'                => __DIR__ . '/sales/quotation/cancel.php',
    '/sales/quotation/convert-to-reservation'=> __DIR__ . '/sales/quotation/convert_to_reservation.php',

    // Quotation AJAX
    '/ajax/quotation/search-diamond' => __DIR__ . '/sales/quotation/ajax/search_diamond.php',
    '/ajax/quotation/add-item'       => __DIR__ . '/sales/quotation/ajax/add_item.php',
    '/ajax/quotation/remove-item'    => __DIR__ . '/sales/quotation/ajax/remove_item.php',

    // Reservation
    '/sales/reservation'          => __DIR__ . '/sales/reservation/index.php',
    '/sales/reservation/list'     => __DIR__ . '/sales/reservation/list.php',
    '/sales/reservation/create'   => __DIR__ . '/sales/reservation/form.php',
    '/sales/reservation/edit'     => __DIR__ . '/sales/reservation/edit.php',
    '/sales/reservation/detail'   => __DIR__ . '/sales/reservation/detail.php',
    '/sales/reservation/save'     => __DIR__ . '/sales/reservation/save.php',
    '/sales/reservation/update'   => __DIR__ . '/sales/reservation/update.php',
    '/sales/reservation/release'  => __DIR__ . '/sales/reservation/release.php',
    '/sales/reservation/extend'   => __DIR__ . '/sales/reservation/extend.php',
    '/sales/reservation/convert'  => __DIR__ . '/sales/reservation/convert.php',


    // AJAX — Sprint 3 aliases (dipakai di form quotation & lead)
    '/ajax/diamond/search'       => __DIR__ . '/sales/quotation/ajax/search_diamond.php',
    '/ajax/lead/search'          => __DIR__ . '/sales/lead/ajax/search.php',

    // AJAX — Sprint 2
    '/ajax/customer/search'  => __DIR__ . '/master/customer/ajax/search.php',
    '/ajax/supplier/search'  => __DIR__ . '/master/supplier/ajax/search.php',
    '/ajax/diamond/lookup'   => __DIR__ . '/master/diamond/ajax/lookup.php',
    '/ajax/diamond/stats'    => __DIR__ . '/master/diamond/ajax/stats.php',
];
// echo '<pre>';
// echo 'REQUEST_URI = ' . $_SERVER['REQUEST_URI'] . PHP_EOL;
// echo 'BASE_FOLDER = ' . BASE_FOLDER . PHP_EOL;
// echo 'PATH = ' . $path . PHP_EOL;
// exit;
// ─── Dispatch ─────────────────────────────────────────────────────────────────
if (isset($routes[$path])) {
    $file = $routes[$path];
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

// ─── 404 fallback ─────────────────────────────────────────────────────────────
http_response_code(404);
require __DIR__ . '/layout/error_404.php';
