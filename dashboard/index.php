<?php
/**
 * dashboard/index.php
 * Halaman dashboard utama Sprint 1
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/helper/functions.php';
require_once __DIR__ . '/../shared/middleware/auth.php';
require_once __DIR__ . '/../shared/middleware/audit.php';

require_auth();


// Statistik Sprint 1 (user & role)
$stats = [
    'total_users'   => Database::fetchOne("SELECT COUNT(*) n FROM users WHERE deleted_at IS NULL")['n'] ?? 0,
    'active_users'  => Database::fetchOne("SELECT COUNT(*) n FROM users WHERE is_active=1 AND deleted_at IS NULL")['n'] ?? 0,
    'total_roles'   => Database::fetchOne("SELECT COUNT(*) n FROM roles")['n'] ?? 0,
    'locked_users'  => Database::fetchOne("SELECT COUNT(*) n FROM users WHERE locked_until > NOW() AND deleted_at IS NULL")['n'] ?? 0,
    'login_today'   => Database::fetchOne("SELECT COUNT(*) n FROM audit_logs WHERE action='LOGIN' AND DATE(created_at)=CURDATE()")['n'] ?? 0,
];

// Aktivitas terbaru
$recentActivity = Database::fetchAll(
    "SELECT a.module, a.action, a.description, a.created_at, u.full_name
       FROM audit_logs a
       LEFT JOIN users u ON u.id = a.user_id
      WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
      ORDER BY a.created_at DESC LIMIT 10"
);

// User baru minggu ini
$newUsers = Database::fetchAll(
    "SELECT u.full_name, u.username, r.role_name, u.created_at
       FROM users u JOIN roles r ON r.id = u.role_id
      WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND u.deleted_at IS NULL
      ORDER BY u.created_at DESC LIMIT 5"
);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../layout/header.php';
// echo '<pre>';
// print_r(get_user_permissions());
?>
<!-- Stat cards master data -->
<?php
$mdStats = [
    'customers' => Database::fetchOne("SELECT COUNT(*) n FROM customers WHERE deleted_at IS NULL AND is_active=1")['n']??0,
    'suppliers' => Database::fetchOne("SELECT COUNT(*) n FROM suppliers WHERE deleted_at IS NULL AND is_active=1")['n']??0,
    'diamonds'  => Database::fetchOne("SELECT COUNT(*) n FROM diamonds WHERE deleted_at IS NULL AND status='available'")['n']??0,
    'rate'      => Database::fetchOne("SELECT rate_to_idr FROM currencies WHERE code='USD' AND is_active=1 AND effective_date<=CURDATE() ORDER BY effective_date DESC LIMIT 1")['rate_to_idr']??0,
];
?>
<!-- Greeting -->
<div class="mb-4">
  <h4 class="mb-1">Selamat datang, <?= e(explode(' ', $_SESSION['full_name'] ?? 'User')[0]) ?> 👋</h4>
  <p class="text-muted mb-0 small">
    <?= e($_SESSION['role_name'] ?? '') ?> &mdash; <?= format_datetime(date('Y-m-d H:i:s')) ?>
  </p>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-lg-2-4">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="stat-label">Total Pengguna</div>
        <div class="stat-value"><?= e($stats['total_users']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2-4">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
      <div>
        <div class="stat-label">Pengguna Aktif</div>
        <div class="stat-value"><?= e($stats['active_users']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2-4">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-shield-lock-fill"></i></div>
      <div>
        <div class="stat-label">Total Role</div>
        <div class="stat-value"><?= e($stats['total_roles']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2-4">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-lock-fill"></i></div>
      <div>
        <div class="stat-label">Akun Terkunci</div>
        <div class="stat-value"><?= e($stats['locked_users']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2-4">
    <div class="stat-card">
      <div class="stat-icon amber"><i class="bi bi-box-arrow-in-right"></i></div>
      <div>
        <div class="stat-label">Login Hari Ini</div>
        <div class="stat-value"><?= e($stats['login_today']) ?></div>
      </div>
    </div>
  </div>
    <div class="col-6 col-md-4 col-lg-2-4">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-gem"></i></div>
      <div>
        <div class="stat-label">Berlian Tersedia</div>
        <div class="stat-value"><?= e($mdStats['diamonds']) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Sprint info banner -->
<!-- <div class="alert alert-success border-0 mb-4" style="background:#F0FDF4">
  <div class="d-flex align-items-start gap-3">
    <i class="bi bi-check-circle-fill fs-4 text-success flex-shrink-0"></i>
    <div>
      <strong>Sprint 1 ✅ &nbsp;Sprint 2 ✅</strong> — Master Data siap digunakan
      <div class="text-muted small mt-1">
        Sprint berikutnya: Lead, Quotation, Reservation (Sprint 3)
      </div>
    </div>
  </div>
</div> -->


<!-- <div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="<?= url('master/customer') ?>" class="text-decoration-none">
      <div class="stat-card">
        <div class="stat-icon" style="background:#FFF7ED;color:#C2410C"><i class="bi bi-people-fill"></i></div>
        <div><div class="stat-label">Pelanggan Aktif</div>
        <div class="stat-value" style="color:#C2410C"><?= e($mdStats['customers']) ?></div></div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?= url('master/supplier') ?>" class="text-decoration-none">
      <div class="stat-card">
        <div class="stat-icon" style="background:#F0FDF4;color:#15803D"><i class="bi bi-building"></i></div>
        <div><div class="stat-label">Supplier Aktif</div>
        <div class="stat-value" style="color:#15803D"><?= e($mdStats['suppliers']) ?></div></div>
      </div>
    </a>
  </div> -->
<!--   <div class="col-6 col-md-3">
    <a href="<?= url('master/diamond') ?>?status=available" class="text-decoration-none">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-gem"></i></div>
        <div><div class="stat-label">Berlian Tersedia</div>
        <div class="stat-value"><?= e($mdStats['diamonds']) ?></div></div>
      </div>
    </a>
  </div> -->
  <!-- <div class="col-6 col-md-3">
    <a href="<?= url('master/currency') ?>" class="text-decoration-none">
      <div class="stat-card">
        <div class="stat-icon" style="background:#EFF6FF;color:#1D4ED8"><i class="bi bi-currency-exchange"></i></div>
        <div><div class="stat-label">Kurs USD</div>
        <div class="stat-value" style="font-size:15px">
          <?= $mdStats['rate'] ? 'Rp '.number_format($mdStats['rate'],0,',','.') : '—' ?>
        </div></div>
      </div>
    </a>
  </div> -->
</div>

<!-- Row: aktivitas + user baru -->
<div class="row g-4">
  <!-- Aktivitas 24 jam terakhir -->
  <div class="col-lg-8">
    <div class="table-card">
      <div class="table-toolbar">
        <span class="fw-600"><i class="bi bi-activity me-2 text-primary"></i>Aktivitas 24 Jam Terakhir</span>
        <?php if (can('AUDIT_VIEW')): ?>
          <a href="<?= url('system/audit') ?>" class="btn btn-outline-secondary btn-sm">
            Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
          </a>
        <?php endif; ?>
      </div>
      <?php if (empty($recentActivity)): ?>
        <div class="table-empty"><i class="bi bi-activity"></i><p>Belum ada aktivitas.</p></div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>Modul</th><th>Aksi</th><th>Pengguna</th><th>Keterangan</th><th>Waktu</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentActivity as $act): ?>
            <tr>
              <td><span class="badge bg-light text-dark border" style="font-size:11px"><?= e($act['module']) ?></span></td>
              <td><small class="font-mono text-muted"><?= e($act['action']) ?></small></td>
              <td class="small"><?= e($act['full_name'] ?? 'Sistem') ?></td>
              <td class="small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= e($act['description'] ?? '—') ?>
              </td>
              <td class="small text-muted"><?= time_ago($act['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pengguna baru minggu ini -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title"><i class="bi bi-person-plus me-2 text-success"></i>Pengguna Baru (7 Hari)</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($newUsers)): ?>
          <div class="table-empty" style="padding:32px">
            <i class="bi bi-people text-muted"></i>
            <p class="small">Tidak ada pengguna baru.</p>
          </div>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($newUsers as $u): ?>
            <li class="list-group-item d-flex align-items-center gap-3 py-3">
              <div style="width:36px;height:36px;border-radius:50%;background:#EFF6FF;color:#185FA5;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0">
                <?= e(name_initials($u['full_name'])) ?>
              </div>
              <div class="flex-1 min-w-0">
                <div class="fw-600 small text-truncate"><?= e($u['full_name']) ?></div>
                <div class="text-muted" style="font-size:11px">
                  @<?= e($u['username']) ?> &bull; <?= e($u['role_name']) ?>
                </div>
              </div>
              <div class="text-muted" style="font-size:11px;white-space:nowrap">
                <?= time_ago($u['created_at']) ?>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<style>
@media (min-width:992px) {
  .col-lg-2-4 { flex: 0 0 20%; max-width: 20%; }
}
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
