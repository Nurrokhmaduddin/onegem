<?php
/**
 * system/permission/assign.php
 * Halaman matrix assignment permission per role
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/../role/repository.php';

require_auth();
require_permission('PERMISSION_ASSIGN');

$roleId   = (int) get_param('role_id');
$allRoles = RoleRepository::getAll();
$role     = $roleId ? RoleRepository::findById($roleId) : null;
$grouped  = $roleId ? RoleRepository::getPermissions($roleId) : [];

// Label modul yang lebih ramah
$moduleLabels = [
    'USER'       => 'Manajemen Pengguna',
    'ROLE'       => 'Manajemen Role',
    'PERMISSION' => 'Manajemen Permission',
    'AUDIT'      => 'Audit Trail',
    'SYSTEM'     => 'Pengaturan Sistem',
    'DIAMOND'    => 'Berlian (Diamond)',
    'SALES'      => 'Penjualan',
    'OPERATION'  => 'Operasional',
    'FINANCE'    => 'Keuangan',
];

$actionLabels = [
    'VIEW'    => ['icon' => 'bi-eye',           'color' => 'secondary'],
    'CREATE'  => ['icon' => 'bi-plus-circle',   'color' => 'success'],
    'EDIT'    => ['icon' => 'bi-pencil',         'color' => 'primary'],
    'DELETE'  => ['icon' => 'bi-trash',          'color' => 'danger'],
    'APPROVE' => ['icon' => 'bi-check-circle',   'color' => 'warning'],
    'POST'    => ['icon' => 'bi-send',           'color' => 'info'],
];

$pageTitle   = 'Assign Permission — ' . ($role ? e($role['role_name']) : 'Pilih Role');
$breadcrumbs = [
    ['label'=>'Sistem'],
    ['label'=>'Role','url'=> url('system/role')],
    ['label'=>'Assign Permission'],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">
  <!-- Pilih Role -->
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex align-items-center gap-3 flex-wrap">
        <label class="fw-600 mb-0">Pilih Role:</label>
        <select id="roleSelector" class="form-select" style="max-width:280px">
          <option value="">-- Pilih Role --</option>
          <?php foreach ($allRoles as $r): ?>
            <option value="<?= $r['id'] ?>" <?= $roleId == $r['id'] ? 'selected' : '' ?>>
              <?= e($r['role_name']) ?> (<?= e($r['role_code']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($role): ?>
          <span class="badge bg-primary"><?= e($role['role_code']) ?></span>
          <?php
          $systemRoles = ['OWNER'];
          if (in_array($role['role_code'], $systemRoles, true)):
          ?>
            <span class="badge bg-warning text-dark">
              <i class="bi bi-shield-fill me-1"></i>Role Sistem — Semua Permission Aktif
            </span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($role && !empty($grouped)): ?>
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">
          <i class="bi bi-toggles me-2"></i>
          Permission untuk <strong><?= e($role['role_name']) ?></strong>
        </span>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCheckAll">
            <i class="bi bi-check-all me-1"></i>Pilih Semua
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm" id="btnUncheckAll">
            <i class="bi bi-x-lg me-1"></i>Bersihkan Semua
          </button>
        </div>
      </div>
      <div class="card-body p-0">

        <?php foreach ($grouped as $module => $permissions): ?>
        <table class="table table-bordered mb-0 permission-matrix">
          <thead>
            <tr>
              <th colspan="3" class="permission-module-header">
                <i class="bi bi-grid me-2"></i>
                <?= e($moduleLabels[$module] ?? $module) ?>
                <span class="float-end text-muted fw-400" style="font-size:11px">
                  <?= count(array_filter($permissions, fn($p) => $p['is_granted'])) ?>/<?= count($permissions) ?> aktif
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($permissions as $perm): ?>
            <tr>
              <td style="width:44px" class="text-center">
                <input
                  type="checkbox"
                  class="perm-toggle perm-check"
                  data-perm-id="<?= $perm['id'] ?>"
                  <?= $perm['is_granted'] ? 'checked' : '' ?>
                  <?= in_array($role['role_code'], ['OWNER'], true) ? 'disabled checked' : '' ?>
                >
              </td>
              <td style="width:160px">
                <?php $act = $actionLabels[$perm['action']] ?? ['icon'=>'bi-dot','color'=>'secondary']; ?>
                <span class="badge bg-<?= $act['color'] ?>-subtle text-<?= $act['color'] ?> border border-<?= $act['color'] ?>-subtle">
                  <i class="bi <?= $act['icon'] ?> me-1"></i><?= e($perm['action']) ?>
                </span>
              </td>
              <td>
                <div class="fw-500 small"><?= e($perm['permission_name']) ?></div>
                <div class="font-mono text-muted" style="font-size:11px"><?= e($perm['permission_code']) ?></div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endforeach; ?>

      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?= url('system/role') ?>" class="btn btn-secondary">
          <i class="bi bi-x me-1"></i>Batal
        </a>
        <?php if (!in_array($role['role_code'], ['OWNER'], true)): ?>
        <button type="button" class="btn btn-primary" id="btnSavePermissions">
          <i class="bi bi-check-lg me-1"></i>Simpan Permission
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php elseif ($roleId && empty($grouped)): ?>
    <div class="col-12">
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>Tidak ada permission tersedia. Tambahkan permission di modul Permission Management.
      </div>
    </div>
  <?php endif; ?>
</div>

<?php
$extraJs = <<<JS
<script>
$(function () {
  // Redirect saat ganti role
  $('#roleSelector').on('change', function () {
    const id = $(this).val();
    if (id) window.location.href = '?role_id=' + id;
  });

  // Check/uncheck all
  $('#btnCheckAll').on('click', function () { $('.perm-check:not(:disabled)').prop('checked', true); });
  $('#btnUncheckAll').on('click', function () { $('.perm-check:not(:disabled)').prop('checked', false); });

  // Simpan permission
  $('#btnSavePermissions').on('click', function () {
    const ids = [];
    $('.perm-check:checked').each(function () { ids.push($(this).data('perm-id')); });

    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');

    erpAjax({
      url  : '/ajax/permission/save-role',
      data : {
        role_id        : {$roleId},
        permission_ids : ids.join(','),
        csrf_token     : $('meta[name="csrf-token"]').attr('content')
      },
      onSuccess: function (res) {
        erpToast('success', res.message);
        setTimeout(() => location.reload(), 800);
      },
      onError: function (msg) {
        erpToast('danger', msg);
        $('#btnSavePermissions').prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Simpan Permission');
      }
    });
  });
});
</script>
JS;

require_once __DIR__ . '/../../layout/footer.php';
