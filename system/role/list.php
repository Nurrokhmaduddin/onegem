<?php
/**
 * system/role/list.php
 * Daftar Role
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth();
require_permission('ROLE_VIEW');

$roles       = RoleRepository::getAll(true);
$pageTitle   = 'Manajemen Role';
$breadcrumbs = [['label'=>'Sistem'],['label'=>'Role']];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0 small">Kelola role dan hak akses pengguna sistem.</p>
  <?php if (can('ROLE_CREATE')): ?>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateRole">
      <i class="bi bi-plus-lg me-1"></i>Tambah Role
    </button>
  <?php endif; ?>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Kode Role</th>
          <th>Nama Role</th>
          <th>Deskripsi</th>
          <th class="text-center">Pengguna</th>
          <th class="text-center">Status</th>
          <th style="width:120px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($roles as $i => $r): ?>
        <tr>
          <td class="text-muted"><?= $i + 1 ?></td>
          <td>
            <code class="font-mono bg-light px-2 py-1 rounded"><?= e($r['role_code']) ?></code>
          </td>
          <td class="fw-600"><?= e($r['role_name']) ?></td>
          <td class="text-muted small"><?= e($r['description'] ?? '—') ?></td>
          <td class="text-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
              <?= (int)$r['user_count'] ?>
            </span>
          </td>
          <td class="text-center">
            <?php if ($r['is_active']): ?>
              <span class="status-badge badge-active">Aktif</span>
            <?php else: ?>
              <span class="status-badge badge-inactive">Nonaktif</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <?php if (can('PERMISSION_ASSIGN')): ?>
                <a href="<?= url('system/permission/assign') ?>?role_id=<?= $r['id'] ?>"
                   class="btn btn-icon btn-outline-warning btn-sm" title="Kelola Permission">
                  <i class="bi bi-toggles"></i>
                </a>
              <?php endif; ?>
              <?php if (can('ROLE_EDIT')): ?>
                <button type="button"
                  class="btn btn-icon btn-outline-primary btn-sm btn-edit-role"
                  data-id="<?= $r['id'] ?>"
                  data-code="<?= e($r['role_code']) ?>"
                  data-name="<?= e($r['role_name']) ?>"
                  data-desc="<?= e($r['description'] ?? '') ?>"
                  data-active="<?= $r['is_active'] ?>"
                  title="Edit Role">
                  <i class="bi bi-pencil"></i>
                </button>
              <?php endif; ?>
              <?php
                $systemRoles = ['OWNER','IT_ADMIN','MANAGER','SALES','INVENTORY','FINANCE'];
                if (can('ROLE_DELETE') && !in_array($r['role_code'], $systemRoles, true) && $r['user_count'] == 0):
              ?>
                <button type="button"
                  class="btn btn-icon btn-outline-danger btn-sm btn-delete-role"
                  data-id="<?= $r['id'] ?>" data-name="<?= e($r['role_name']) ?>"
                  title="Hapus Role">
                  <i class="bi bi-trash"></i>
                </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Create Role -->
<?php if (can('ROLE_CREATE')): ?>
<div class="modal fade" id="modalCreateRole" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-shield-plus me-2"></i>Tambah Role Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('system/role/save') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Kode Role <span class="required">*</span></label>
            <input type="text" name="role_code" class="form-control font-mono text-uppercase"
              placeholder="MISAL: SUPERVISOR" maxlength="30" required
              style="text-transform:uppercase">
            <div class="form-text small">Huruf kapital, angka, underscore. Tidak bisa diubah setelah dibuat.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Role <span class="required">*</span></label>
            <input type="text" name="role_name" class="form-control" placeholder="Nama tampilan role" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control" rows="2"
              placeholder="Deskripsi singkat tugas/akses role ini"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modal Edit Role -->
<div class="modal fade" id="modalEditRole" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('system/role/update') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="role_id" id="editRoleId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Kode Role</label>
            <input type="text" id="editRoleCode" class="form-control font-mono bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Role <span class="required">*</span></label>
            <input type="text" name="role_name" id="editRoleName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" id="editRoleDesc" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="editRoleActive">
            <label class="form-check-label" for="editRoleActive">Role Aktif</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
$(function () {
  // Isi modal edit
  $(document).on('click', '.btn-edit-role', function () {
    $('#editRoleId').val($(this).data('id'));
    $('#editRoleCode').val($(this).data('code'));
    $('#editRoleName').val($(this).data('name'));
    $('#editRoleDesc').val($(this).data('desc'));
    $('#editRoleActive').prop('checked', $(this).data('active') == 1);
    new bootstrap.Modal(document.getElementById('modalEditRole')).show();
  });

  // Delete role via AJAX
  $(document).on('click', '.btn-delete-role', function () {
    const name = $(this).data('name');
    const id   = $(this).data('id');
    if (!confirm('Hapus role "' + name + '"? Tindakan ini tidak dapat dibatalkan.')) return;

    erpAjax({
      url  : '/system/role/delete',
      data : { role_id: id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
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
