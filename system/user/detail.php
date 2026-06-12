<?php
/**
 * system/user/detail.php
 * Halaman detail pengguna dengan tab info, aktivitas, dan aksi admin
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

$userId = (int) get_param('id');
$user   = UserRepository::findById($userId);

if (!$user) {
    flash_set('error', 'Pengguna tidak ditemukan.');
    redirect(url('system/user'));
}

// Riwayat login dari audit log
$loginHistory = Database::fetchAll(
    "SELECT action, ip_address, created_at, description
       FROM audit_logs
      WHERE user_id = ? AND module = 'AUTH'
      ORDER BY created_at DESC LIMIT 10",
    [$userId]
);

// Aktivitas terakhir
$recentActivity = Database::fetchAll(
    "SELECT module, action, document_no, table_name, record_id, description, created_at
       FROM audit_logs
      WHERE user_id = ? AND module != 'AUTH'
      ORDER BY created_at DESC LIMIT 15",
    [$userId]
);

$pageTitle   = 'Detail Pengguna';
$breadcrumbs = [
    ['label' => 'Sistem'],
    ['label' => 'Pengguna', 'url' => url('system/user')],
    ['label' => e($user['full_name'])],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">

  <!-- ── Kolom kiri: Profil card ─────────────────────────────────────────── -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center p-4">
        <!-- Avatar -->
        <div class="mx-auto mb-3" style="width:72px;height:72px;border-radius:50%;background:#EFF6FF;color:#185FA5;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700">
          <?= e(name_initials($user['full_name'])) ?>
        </div>
        <h5 class="mb-1"><?= e($user['full_name']) ?></h5>
        <div class="text-muted small mb-2 font-mono">@<?= e($user['username']) ?></div>

        <?php $rc = strtolower($user['role_code']); ?>
        <span class="role-badge badge-<?= e($rc) ?> mb-3 d-inline-block">
          <?= e($user['role_name']) ?>
        </span>

        <?php if ($user['locked_until'] && strtotime($user['locked_until']) > time()): ?>
          <div><span class="status-badge badge-inactive"><i class="bi bi-lock me-1"></i>Akun Terkunci</span></div>
        <?php elseif ($user['is_active']): ?>
          <div><span class="status-badge badge-active"><i class="bi bi-check-circle me-1"></i>Aktif</span></div>
        <?php else: ?>
          <div><span class="status-badge badge-inactive"><i class="bi bi-x-circle me-1"></i>Nonaktif</span></div>
        <?php endif; ?>

        <?php if ($user['must_change_pw']): ?>
          <div class="mt-2">
            <span class="badge bg-warning text-dark small">
              <i class="bi bi-key me-1"></i>Wajib Ganti Password
            </span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Info dasar -->
      <div class="card-body border-top p-3">
        <table class="table table-borderless table-sm mb-0" style="font-size:13px">
          <tr>
            <td class="text-muted" style="width:40%">Kode Karyawan</td>
            <td class="font-mono"><?= $user['employee_code'] ? e($user['employee_code']) : '—' ?></td>
          </tr>
          <tr>
            <td class="text-muted">Email</td>
            <td><?= e($user['email']) ?></td>
          </tr>
          <tr>
            <td class="text-muted">Telepon</td>
            <td><?= $user['phone'] ? e($user['phone']) : '—' ?></td>
          </tr>
          <tr>
            <td class="text-muted">Login Terakhir</td>
            <td><?= $user['last_login_at'] ? format_datetime($user['last_login_at']) : '—' ?></td>
          </tr>
          <tr>
            <td class="text-muted">Terdaftar</td>
            <td><?= format_datetime($user['created_at']) ?></td>
          </tr>
          <?php if ($user['login_attempt'] > 0): ?>
          <tr>
            <td class="text-muted">Percobaan Login Gagal</td>
            <td class="text-danger"><?= (int)$user['login_attempt'] ?>x</td>
          </tr>
          <?php endif; ?>
        </table>
      </div>

      <!-- Tombol aksi -->
      <div class="card-body border-top p-3 d-flex flex-column gap-2">
        <?php if (can('USER_EDIT')): ?>
          <a href="<?= url('system/user/edit') ?>?id=<?= $userId ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Data
          </a>
        <?php endif; ?>
        <?php if (can('USER_RESET_PW')): ?>
          <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalResetPw">
            <i class="bi bi-key me-1"></i>Reset Password
          </button>
        <?php endif; ?>
        <?php if (can('USER_DELETE') && $userId != ($_SESSION['user_id'] ?? 0)): ?>
          <button type="button" class="btn btn-outline-<?= $user['is_active'] ? 'danger' : 'success' ?> btn-sm btn-toggle-status"
            data-id="<?= $userId ?>" data-active="<?= $user['is_active'] ?>">
            <i class="bi bi-person-<?= $user['is_active'] ? 'dash' : 'check' ?> me-1"></i>
            <?= $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> Akun
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── Kolom kanan: Tab aktivitas ──────────────────────────────────────── -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="detailTabs">
          <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tabLogin">
              <i class="bi bi-clock-history me-1"></i>Riwayat Login
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tabActivity">
              <i class="bi bi-activity me-1"></i>Aktivitas
            </a>
          </li>
        </ul>
      </div>
      <div class="tab-content card-body p-0">

        <!-- Tab: Riwayat Login -->
        <div class="tab-pane fade show active" id="tabLogin">
          <?php if (empty($loginHistory)): ?>
            <div class="table-empty"><i class="bi bi-clock-history"></i><p>Belum ada riwayat login.</p></div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Aksi</th>
                  <th>IP Address</th>
                  <th>Waktu</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($loginHistory as $log): ?>
                <tr>
                  <td>
                    <?php if ($log['action'] === 'LOGIN'): ?>
                      <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-check-circle me-1"></i>Berhasil Login
                      </span>
                    <?php elseif ($log['action'] === 'LOGOUT'): ?>
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                      </span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="bi bi-x-circle me-1"></i>Gagal Login
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="font-mono small"><?= e($log['ip_address'] ?? '—') ?></td>
                  <td class="small text-muted"><?= format_datetime($log['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

        <!-- Tab: Aktivitas -->
        <div class="tab-pane fade" id="tabActivity">
          <?php if (empty($recentActivity)): ?>
            <div class="table-empty"><i class="bi bi-activity"></i><p>Belum ada aktivitas tercatat.</p></div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr><th>Modul</th><th>Aksi</th><th>Keterangan</th><th>Waktu</th></tr>
              </thead>
              <tbody>
                <?php foreach ($recentActivity as $act): ?>
                <tr>
                  <td><span class="badge bg-light text-dark border"><?= e($act['module']) ?></span></td>
                  <td><small class="font-mono"><?= e($act['action']) ?></small></td>
                  <td class="small text-muted"><?= e($act['description'] ?? '—') ?></td>
                  <td class="small text-muted"><?= format_datetime($act['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ── Modal Reset Password ─────────────────────────────────────────────────── -->
<?php if (can('USER_RESET_PW')): ?>
<div class="modal fade" id="modalResetPw" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">
          Reset password untuk <strong><?= e($user['full_name']) ?></strong>.
          Pengguna wajib mengganti password saat login berikutnya.
        </p>
        <div class="mb-3">
          <label class="form-label">Password Baru <span class="required">*</span></label>
          <input type="password" id="newPwInput" class="form-control input-password"
            placeholder="Min. 8 karakter, 1 kapital, 1 angka">
          <div class="pw-strength mt-1" style="height:4px;border-radius:2px;background:#E5E7EB"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
          <input type="password" id="newPwConfirmInput" class="form-control"
            placeholder="Ulangi password baru">
        </div>
        <div id="resetPwError" class="alert alert-danger d-none py-2 small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-warning" id="btnDoResetPw">
          <i class="bi bi-key me-1"></i>Reset Password
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraJs = <<<JS
<script>
$(function () {
  const userId = {$userId};

  // Toggle status
  $('.btn-toggle-status').on('click', function () {
    const active = $(this).data('active');
    const label  = active ? 'nonaktifkan' : 'aktifkan';
    if (!confirm('Apakah Anda yakin ingin ' + label + ' akun ini?')) return;
    erpAjax({
      url  : '/ajax/user/toggle-status',
      data : { user_id: userId, csrf_token: $('meta[name="csrf-token"]').attr('content') },
      onSuccess: function (res) {
        erpToast('success', res.message);
        setTimeout(() => location.reload(), 800);
      }
    });
  });

  // Reset password
  $('#btnDoResetPw').on('click', function () {
    const pw  = $('#newPwInput').val();
    const cfm = $('#newPwConfirmInput').val();
    $('#resetPwError').addClass('d-none').text('');

    erpAjax({
      url  : '/system/user/reset-password',
      data : {
        user_id              : userId,
        new_password         : pw,
        new_password_confirm : cfm,
        csrf_token           : $('meta[name="csrf-token"]').attr('content')
      },
      onSuccess: function (res) {
        bootstrap.Modal.getInstance(document.getElementById('modalResetPw')).hide();
        erpToast('success', res.message);
      },
      onError: function (msg) {
        $('#resetPwError').removeClass('d-none').html('<i class="bi bi-exclamation-triangle me-1"></i>' + msg);
      }
    });
  });
});
</script>
JS;

require_once __DIR__ . '/../../layout/footer.php';
