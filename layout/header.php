<?php
/**
 * layout/header.php
 * Global HTML header + Navbar + Sidebar
 * VERSI LARAGON — asset() dan url() sudah pakai BASE_URL dengan subfolder
 */

declare(strict_types=1);

$pageTitle   = $pageTitle   ?? APP_NAME;
$breadcrumbs = $breadcrumbs ?? [];

$currentUser = Database::fetchOne(
    "SELECT u.id, u.full_name, u.username, u.avatar_path, r.role_name
       FROM users u JOIN roles r ON r.id = u.role_id
      WHERE u.id = ?",
    [$_SESSION['user_id']]
);

// Ambil semua permission user (cached di session)
get_user_permissions();

// Path saat ini untuk highlight menu aktif
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Hilangkan BASE_FOLDER dari path untuk perbandingan
$baseFolderLen = strlen(BASE_FOLDER);
if (str_starts_with($currentPath, BASE_FOLDER)) {
    $activePath = substr($currentPath, $baseFolderLen);
} else {
    $activePath = $currentPath;
}
$activePath = '/' . ltrim($activePath, '/');
// Hilangkan trailing slash agar konsisten (kecuali root)
if ($activePath !== '/') {
    $activePath = rtrim($activePath, '/');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>

  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">

  <meta name="csrf-token" content="<?= e(csrf_generate()) ?>">
  <meta name="base-url"   content="<?= BASE_URL ?>">
  <meta name="usd-rate"   content="<?= Database::fetchOne("SELECT rate_to_idr FROM currencies WHERE code='USD' AND is_active=1 AND effective_date<=CURDATE() ORDER BY effective_date DESC LIMIT 1")['rate_to_idr'] ?? 16000 ?>">
</head>
<body>

<!-- ══════════════════════════════════════════════════════ NAVBAR TOP ═══ -->
<nav class="navbar navbar-expand-lg erp-navbar fixed-top">
  <div class="container-fluid px-3">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('dashboard') ?>">
      <div class="brand-icon"><i class="bi bi-gem"></i></div>
      <span class="brand-name d-none d-lg-inline"><?= APP_NAME ?></span>
    </a>

    <button class="btn btn-link text-white sidebar-toggle me-2" id="sidebarToggle">
      <i class="bi bi-list fs-5"></i>
    </button>

    <div class="ms-auto d-flex align-items-center gap-2">
      <!-- Notifikasi -->
      <div class="dropdown">
        <button class="btn btn-link text-white position-relative" data-bs-toggle="dropdown">
          <i class="bi bi-bell fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="min-width:240px">
          <li><h6 class="dropdown-header">Notifikasi</h6></li>
          <li><p class="dropdown-item text-muted small mb-0">Tidak ada notifikasi baru.</p></li>
        </ul>
      </div>

      <!-- User menu -->
      <div class="dropdown">
        <button class="btn btn-link text-white d-flex align-items-center gap-2 text-decoration-none"
          data-bs-toggle="dropdown">
          <div class="user-avatar-sm">
            <?= e(name_initials($currentUser['full_name'])) ?>
          </div>
          <span class="d-none d-md-inline small"><?= e($currentUser['full_name']) ?></span>
          <i class="bi bi-chevron-down small"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <div class="dropdown-item-text py-2">
              <div class="fw-semibold"><?= e($currentUser['full_name']) ?></div>
              <small class="text-muted"><?= e($currentUser['role_name']) ?></small>
            </div>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="POST" action="<?= url('auth/logout') ?>" id="logoutForm">
              <?= csrf_field() ?>
              <button type="submit" class="dropdown-item text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>Keluar
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<!-- ══════════════════════════════════════════════════════ WRAPPER ════════ -->
<div class="erp-wrapper">

<!-- ══════════════════════════════════════════════════════ SIDEBAR ════════ -->
<aside class="erp-sidebar" id="erpSidebar">
  <div class="sidebar-inner">
    <nav class="sidebar-nav">

      <a href="<?= url('dashboard') ?>"
         class="nav-link <?= ($activePath === '/' || str_starts_with($activePath, '/dashboard')) ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
      </a>

      <?php if (can('USER_VIEW') || can('ROLE_VIEW') || can('PERMISSION_VIEW') || can('AUDIT_VIEW')): ?>
      <div class="nav-section-label">Sistem</div>

      <?php if (can('USER_VIEW')): ?>
      <a href="<?= url('system/user') ?>"
         class="nav-link <?= str_starts_with($activePath, '/system/user') ? 'active' : '' ?>">
        <i class="bi bi-people"></i><span>Pengguna</span>
      </a>
      <?php endif; ?>

      <?php if (can('ROLE_VIEW')): ?>
      <a href="<?= url('system/role') ?>"
         class="nav-link <?= str_starts_with($activePath, '/system/role') ? 'active' : '' ?>">
        <i class="bi bi-shield-lock"></i><span>Role</span>
      </a>
      <?php endif; ?>

      <?php if (can('PERMISSION_VIEW') || can('PERMISSION_ASSIGN')): ?>
      <a href="<?= url('system/permission/assign') ?>"
         class="nav-link <?= str_starts_with($activePath, '/system/permission') ? 'active' : '' ?>">
        <i class="bi bi-toggles"></i><span>Permission</span>
      </a>
      <?php endif; ?>

      <?php if (can('AUDIT_VIEW')): ?>
      <a href="<?= url('system/audit') ?>"
         class="nav-link <?= str_starts_with($activePath, '/system/audit') ? 'active' : '' ?>">
        <i class="bi bi-journal-text"></i><span>Audit Trail</span>
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <!-- MASTER DATA -->
      <?php if (can('CUSTOMER_VIEW') || can('SUPPLIER_VIEW') || can('DIAMOND_VIEW') || can('WAREHOUSE_VIEW') || can('COA_VIEW') || can('CURRENCY_VIEW')): ?>
      <div class="nav-section-label">Master Data</div>

      <?php if (can('CUSTOMER_VIEW')): ?>
      <a href="<?= url('master/customer') ?>"
         class="nav-link <?= str_starts_with($activePath,'/master/customer')?'active':'' ?>">
        <i class="bi bi-people"></i><span>Pelanggan</span>
      </a>
      <?php endif; ?>

      <?php if (can('SUPPLIER_VIEW')): ?>
      <a href="<?= url('master/supplier') ?>"
         class="nav-link <?= str_starts_with($activePath,'/master/supplier')?'active':'' ?>">
        <i class="bi bi-building"></i><span>Supplier</span>
      </a>
      <?php endif; ?>

      <?php if (can('DIAMOND_VIEW')): ?>
      <a href="<?= url('master/diamond') ?>"
         class="nav-link <?= str_starts_with($activePath,'/master/diamond')?'active':'' ?>">
        <i class="bi bi-gem"></i><span>Berlian</span>
      </a>
      <?php endif; ?>

      <?php if (can('WAREHOUSE_VIEW')): ?>
      <a href="<?= url('master/warehouse') ?>"
         class="nav-link <?= str_starts_with($activePath,'/master/warehouse')?'active':'' ?>">
        <i class="bi bi-archive"></i><span>Gudang & Cabang</span>
      </a>
      <?php endif; ?>

      <?php if (can('COA_VIEW')): ?>
      <a href="<?= url('master/coa') ?>"
         class="nav-link <?= str_starts_with($activePath,'/master/coa')?'active':'' ?>">
        <i class="bi bi-journal-bookmark"></i><span>Chart of Accounts</span>
      </a>
      <?php endif; ?>

      <?php if (can('CURRENCY_VIEW')): ?>
      <a href="<?= url('master/currency') ?>"
         class="nav-link <?= str_starts_with($activePath,'/master/currency')?'active':'' ?>">
        <i class="bi bi-currency-exchange"></i><span>Kurs Valuta</span>
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <div class="nav-section-label">Penjualan</div>

      <?php if (can('LEAD_VIEW')): ?>
      <div class="nav-group <?= str_starts_with($activePath,'/sales/lead')?'open':'' ?>">
        <button class="nav-link nav-group-toggle w-100 text-start border-0 bg-transparent"
                data-bs-toggle="collapse" data-bs-target="#navLead">
          <i class="bi bi-person-lines-fill"></i><span>Lead</span>
          <i class="bi bi-chevron-down nav-chevron ms-auto"></i>
        </button>
        <div class="collapse nav-group-items <?= str_starts_with($activePath,'/sales/lead')?'show':'' ?>" id="navLead">
          <a href="<?= url('sales/lead') ?>"
             class="nav-link <?= in_array($activePath,['/sales/lead','/sales/lead/index'])?'active':'' ?>">
            <i class="bi bi-kanban"></i>Pipeline
          </a>
          <a href="<?= url('sales/lead/list') ?>"
             class="nav-link <?= $activePath==='/sales/lead/list'?'active':'' ?>">
            <i class="bi bi-list-ul"></i>Semua Lead
          </a>
          <?php if (can('LEAD_CREATE')): ?>
          <a href="<?= url('sales/lead/create') ?>"
             class="nav-link <?= str_starts_with($activePath,'/sales/lead/create')?'active':'' ?>">
            <i class="bi bi-plus-circle"></i>Buat Lead
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (can('QUOTATION_VIEW')): ?>
      <!-- Quotation collapsible -->
      <div class="nav-group <?= str_starts_with($activePath,'/sales/quotation')?'open':'' ?>">
        <button class="nav-link nav-group-toggle w-100 text-start border-0 bg-transparent"
                data-bs-toggle="collapse" data-bs-target="#navQuotation">
          <i class="bi bi-file-earmark-text"></i><span>Quotation</span>
          <i class="bi bi-chevron-down nav-chevron ms-auto"></i>
        </button>
        <div class="collapse nav-group-items <?= str_starts_with($activePath,'/sales/quotation')?'show':'' ?>" id="navQuotation">
          <a href="<?= url('sales/quotation/list') ?>"
             class="nav-link <?= $activePath==='/sales/quotation/list'?'active':'' ?>">
            <i class="bi bi-list-ul"></i>Semua Quotation
          </a>
          <?php if (can('QUOTATION_CREATE')): ?>
          <a href="<?= url('sales/quotation/create') ?>"
             class="nav-link <?= str_starts_with($activePath,'/sales/quotation/create')?'active':'' ?>">
            <i class="bi bi-plus-circle"></i>Buat Quotation
          </a>
          <?php endif; ?>
          <?php if (can('QUOTATION_APPROVE')): ?>
          <a href="<?= url('sales/quotation/list') ?>?status=submitted"
             class="nav-link">
            <i class="bi bi-clock-history"></i>Menunggu Approval
            <?php
              try {
                $pendingQ = Database::fetchOne(
                  "SELECT COUNT(*) AS n FROM quotations WHERE status='submitted' AND deleted_at IS NULL"
                );
              } catch (Throwable $e) { $pendingQ = ['n' => 0]; }
              if (($pendingQ['n'] ?? 0) > 0):
            ?>
            <span class="badge bg-warning text-dark ms-auto"><?= $pendingQ['n'] ?></span>
            <?php endif; ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (can('RESERVATION_VIEW')): ?>
      <!-- Reservation collapsible -->
      <div class="nav-group <?= str_starts_with($activePath,'/sales/reservation')?'open':'' ?>">
        <button class="nav-link nav-group-toggle w-100 text-start border-0 bg-transparent"
                data-bs-toggle="collapse" data-bs-target="#navReservation">
          <i class="bi bi-bookmark-check"></i><span>Reservasi</span>
          <?php
            try {
              $activeRsv = Database::fetchOne(
                "SELECT COUNT(*) AS n FROM reservations WHERE status='active' AND deleted_at IS NULL"
              );
            } catch (Throwable $e) { $activeRsv = ['n' => 0]; }
            if (($activeRsv['n'] ?? 0) > 0):
          ?>
          <span class="badge bg-success ms-auto me-1"><?= $activeRsv['n'] ?></span>
          <?php endif; ?>
          <i class="bi bi-chevron-down nav-chevron <?= ($activeRsv['n'] ?? 0) > 0 ? '' : 'ms-auto' ?>"></i>
        </button>
        <div class="collapse nav-group-items <?= str_starts_with($activePath,'/sales/reservation')?'show':'' ?>" id="navReservation">
          <a href="<?= url('sales/reservation') ?>"
             class="nav-link <?= $activePath==='/sales/reservation/index'||$activePath==='/sales/reservation'?'active':'' ?>">
            <i class="bi bi-bookmark-check-fill"></i>Reservasi Aktif
          </a>
          <a href="<?= url('sales/reservation/list') ?>"
             class="nav-link <?= $activePath==='/sales/reservation/list'?'active':'' ?>">
            <i class="bi bi-list-ul"></i>Semua Reservasi
          </a>
          <?php if (can('RESERVATION_CREATE')): ?>
          <a href="<?= url('sales/reservation/create') ?>"
             class="nav-link <?= str_starts_with($activePath,'/sales/reservation/create')?'active':'' ?>">
            <i class="bi bi-plus-circle"></i>Buat Reservasi
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="nav-section-label">Operasional</div>
      <a href="#" class="nav-link disabled" title="Tersedia Sprint 5">
        <i class="bi bi-box-arrow-in-down"></i><span>Penerimaan Stok</span>
      </a>
      <a href="#" class="nav-link disabled" title="Tersedia Sprint 5">
        <i class="bi bi-arrow-repeat"></i><span>Konsinyasi</span>
      </a>

    </nav>
  </div>
</aside>

<!-- ══════════════════════════════════════════════════════ MAIN ══════════ -->
<main class="erp-main" id="erpMain">

  <!-- Topbar dalam main -->
  <div class="erp-topbar">
    <div>
      <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="<?= url('dashboard') ?>"><i class="bi bi-house"></i></a>
            </li>
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
              <?php if ($i === count($breadcrumbs) - 1): ?>
                <li class="breadcrumb-item active"><?= e($crumb['label']) ?></li>
              <?php else: ?>
                <li class="breadcrumb-item">
                  <a href="<?= e($crumb['url'] ?? '#') ?>"><?= e($crumb['label']) ?></a>
                </li>
              <?php endif; ?>
            <?php endforeach; ?>
          </ol>
        </nav>
        <h5 class="page-title mt-1"><?= e($pageTitle) ?></h5>
      <?php else: ?>
        <h5 class="page-title"><?= e($pageTitle) ?></h5>
      <?php endif; ?>
    </div>
  </div>

  <!-- Flash messages -->
  <?php foreach (['success','error','warning','info'] as $type): ?>
    <?php if ($msg = flash_get($type)): ?>
      <?php $bsType = $type === 'error' ? 'danger' : $type; ?>
      <div class="alert alert-<?= $bsType ?> alert-dismissible fade show mx-3 mt-3 mb-0" role="alert">
        <i class="bi bi-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'x-circle' : 'info-circle') ?> me-2"></i>
        <?= e($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- PAGE CONTENT -->
  <div class="erp-content">
