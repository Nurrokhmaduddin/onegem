<?php
/**
 * system/user/list.php
 * Daftar pengguna — page file User Management
 * ERP Toko Berlian — Only One
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth();
require_permission('USER_VIEW');

// ─── Parameter filter & paginasi ──────────────────────────────────────────────
$search   = get_param('search');
$roleId   = (int) get_param('role_id', 0);
$isActive = get_param('is_active', '') !== '' ? (int) get_param('is_active') : -1;
$sortBy   = get_param('sort', 'u.created_at');
$sortDir  = get_param('dir', 'DESC');
$page     = max(1, (int) get_param('page', 1));
$perPage  = DEFAULT_PER_PAGE;
$offset   = pagination_offset($page, $perPage);

$total   = UserRepository::countList($search, $roleId, $isActive);
$users   = UserRepository::getList($search, $roleId, $isActive, $sortBy, $sortDir, $perPage, $offset);
$roles   = UserRepository::getRoles();
$pagData = pagination_data($total, $page, $perPage);

// Stat cards
$stats = [
    'total'  => Database::fetchOne("SELECT COUNT(*) AS n FROM users WHERE deleted_at IS NULL")['n'] ?? 0,
    'active' => Database::fetchOne("SELECT COUNT(*) AS n FROM users WHERE is_active = 1 AND deleted_at IS NULL")['n'] ?? 0,
    'locked' => Database::fetchOne("SELECT COUNT(*) AS n FROM users WHERE locked_until > NOW() AND deleted_at IS NULL")['n'] ?? 0,
];

// ─── Layout ───────────────────────────────────────────────────────────────────
$pageTitle   = 'Manajemen Pengguna';
$breadcrumbs = [
    ['label' => 'Sistem'],
    ['label' => 'Pengguna'],
];

require_once __DIR__ . '/../../layout/header.php';
?>

<!-- ── Stat cards ──────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="stat-label">Total Pengguna</div>
        <div class="stat-value"><?= e($stats['total']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
      <div>
        <div class="stat-label">Aktif</div>
        <div class="stat-value"><?= e($stats['active']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-lock-fill"></i></div>
      <div>
        <div class="stat-label">Akun Terkunci</div>
        <div class="stat-value"><?= e($stats['locked']) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- ── Tabel ─────────────────────────────────────────────────────────────────── -->
<div class="table-card">

  <!-- Toolbar -->
  <div class="table-toolbar">
    <form method="GET" action="" id="filterForm" class="d-flex gap-2 flex-wrap align-items-center">
      <!-- Search -->
      <div class="input-group" style="width:240px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Cari nama, username, email..."
          value="<?= e($search) ?>" autocomplete="off">
      </div>
      <!-- Filter role -->
      <select name="role_id" class="form-select" style="width:160px" onchange="this.form.submit()">
        <option value="0">Semua Role</option>
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r['id'] ?>" <?= $roleId == $r['id'] ? 'selected' : '' ?>>
            <?= e($r['role_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <!-- Filter status -->
      <select name="is_active" class="form-select" style="width:150px" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="1" <?= $isActive === 1 ? 'selected' : '' ?>>Aktif</option>
        <option value="0" <?= $isActive === 0 ? 'selected' : '' ?>>Nonaktif</option>
      </select>
      <button type="submit" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <?php if ($search || $roleId || $isActive >= 0): ?>
        <a href="<?= url('system/user') ?>" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>

    <?php if (can('USER_CREATE')): ?>
      <a href="<?= url('system/user/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Pengguna
      </a>
    <?php endif; ?>
  </div>

  <!-- Tabel data -->
  <?php if (empty($users)): ?>
    <div class="table-empty">
      <i class="bi bi-people text-muted"></i>
      <p class="mb-2">Tidak ada pengguna ditemukan.</p>
      <?php if (can('USER_CREATE')): ?>
        <a href="<?= url('system/user/create') ?>" class="btn btn-primary btn-sm">Tambah Pengguna Pertama</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'u.full_name','dir'=>$sortBy==='u.full_name'&&$sortDir==='ASC'?'DESC':'ASC'])) ?>"
               class="text-decoration-none text-muted">
              Pengguna
              <?php if ($sortBy === 'u.full_name'): ?>
                <i class="bi bi-arrow-<?= $sortDir === 'ASC' ? 'up' : 'down' ?>"></i>
              <?php endif; ?>
            </a>
          </th>
          <th>Role</th>
          <th>Status</th>
          <th>Login Terakhir</th>
          <th>Terdaftar</th>
          <th style="width:120px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $i => $u): ?>
        <tr>
          <td class="text-muted"><?= $offset + $i + 1 ?></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="user-avatar-table" style="width:34px;height:34px;border-radius:50%;background:#EFF6FF;color:#185FA5;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0">
                <?= e(name_initials($u['full_name'])) ?>
              </div>
              <div>
                <div class="fw-600 lh-1 mb-1">
                  <a href="<?= url('system/user/detail') ?>?id=<?= $u['id'] ?>" class="text-decoration-none text-dark">
                    <?= e($u['full_name']) ?>
                  </a>
                  <?php if ($u['must_change_pw']): ?>
                    <span class="badge bg-warning text-dark ms-1" title="Wajib ganti password">
                      <i class="bi bi-key"></i>
                    </span>
                  <?php endif; ?>
                </div>
                <small class="text-muted font-mono">@<?= e($u['username']) ?></small>
                <div><small class="text-muted"><?= e($u['email']) ?></small></div>
              </div>
            </div>
          </td>
          <td>
            <?php $rc = strtolower($u['role_code']); ?>
            <span class="role-badge badge-<?= e($rc) ?>"><?= e($u['role_name']) ?></span>
          </td>
          <td>
            <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?>
              <span class="status-badge badge-inactive"><i class="bi bi-lock me-1"></i>Terkunci</span>
            <?php elseif ($u['is_active']): ?>
              <span class="status-badge badge-active"><i class="bi bi-check-circle me-1"></i>Aktif</span>
            <?php else: ?>
              <span class="status-badge badge-inactive"><i class="bi bi-x-circle me-1"></i>Nonaktif</span>
            <?php endif; ?>
          </td>
          <td class="text-muted small">
            <?= $u['last_login_at'] ? time_ago($u['last_login_at']) : '—' ?>
          </td>
          <td class="text-muted small"><?= format_date($u['created_at']) ?></td>
          <td>
            <div class="d-flex gap-1">
              <?php if (can('USER_VIEW')): ?>
                <a href="<?= url('system/user/detail') ?>?id=<?= $u['id'] ?>"
                   class="btn btn-icon btn-outline-secondary btn-sm" title="Detail">
                  <i class="bi bi-eye"></i>
                </a>
              <?php endif; ?>
              <?php if (can('USER_EDIT')): ?>
                <a href="<?= url('system/user/edit') ?>?id=<?= $u['id'] ?>"
                   class="btn btn-icon btn-outline-primary btn-sm" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
              <?php endif; ?>
              <?php if (can('USER_DELETE') && $u['id'] != $_SESSION['user_id']): ?>
                <button type="button"
                   class="btn btn-icon btn-outline-danger btn-sm btn-toggle-status"
                   data-id="<?= $u['id'] ?>"
                   data-active="<?= $u['is_active'] ?>"
                   title="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                  <i class="bi bi-<?= $u['is_active'] ? 'person-dash' : 'person-check' ?>"></i>
                </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pagData['total_pages'] > 1): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
    <small class="text-muted">
      Menampilkan <?= $pagData['from'] ?>–<?= $pagData['to'] ?> dari <?= number_format($total) ?> pengguna
    </small>
    <nav>
      <ul class="pagination mb-0">
        <li class="page-item <?= !$pagData['has_prev'] ? 'disabled' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>
        <?php for ($p = max(1, $page - 2); $p <= min($pagData['total_pages'], $page + 2); $p++): ?>
          <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= !$pagData['has_next'] ? 'disabled' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
      </ul>
    </nav>
  </div>
  <?php endif; ?>

  <?php endif; // end if users ?>
</div>

<?php
$extraJs = <<<JS
<script>
$(function () {
  // Toggle status aktif/nonaktif via AJAX
  $(document).on('click', '.btn-toggle-status', function () {
    const id     = $(this).data('id');
    const active = $(this).data('active');
    const label  = active ? 'nonaktifkan' : 'aktifkan';

    if (!confirm('Apakah Anda yakin ingin ' + label + ' pengguna ini?')) return;

    erpAjax({
      url  : '/ajax/user/toggle-status',
      data : { user_id: id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
      onSuccess: function (res) {
        erpToast('success', res.message);
        setTimeout(() => location.reload(), 800);
      }
    });
  });
});
</script>
JS;

require_once __DIR__ . '/../../layout/footer.php';
