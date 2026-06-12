<?php
/**
 * system/user/form.php
 * Form create / edit pengguna
 * ERP Toko Berlian — Only One
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth();

$userId   = (int) get_param('id', 0);
$isEdit   = $userId > 0;
$existing = null;

if ($isEdit) {
    require_permission('USER_EDIT');
    $existing = UserRepository::findById($userId);
    if (!$existing) {
        flash_set('error', 'Pengguna tidak ditemukan.');
        redirect(url('system/user'));
    }
} else {
    require_permission('USER_CREATE');
}

$roles  = UserRepository::getRoles();
// ── BUG FIX 1: Baca errors & data dari session (repopulate setelah gagal) ──
$errors  = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data']  ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

// Nilai form (untuk repopulate setelah error)
$form = [
    'employee_code' => $existing['employee_code'] ?? '',
    'username'      => $existing['username']      ?? '',
    'full_name'     => $existing['full_name']     ?? '',
    'email'         => $existing['email']         ?? '',
    'phone'         => $existing['phone']         ?? '',
    'role_id'       => $existing['role_id']       ?? '',
    'is_active'     => $existing['is_active']     ?? 1,
    'must_change_pw'=> $existing['must_change_pw']?? 1,
];

$pageTitle   = $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru';
$breadcrumbs = [
    ['label' => 'Sistem'],
    ['label' => 'Pengguna', 'url' => url('system/user')],
    ['label' => $pageTitle],
];

require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-8 col-xl-7">
<div class="card">
  <div class="card-header d-flex align-items-center gap-2">
    <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'person-plus' ?> text-primary"></i>
    <span class="card-title"><?= e($pageTitle) ?></span>
  </div>
  <div class="card-body p-4">

    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-danger mb-3"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST"
          action="<?= url($isEdit ? 'system/user/update' : 'system/user/save') ?>"
          class="no-double-submit" novalidate>
      <?= csrf_field() ?>
      <?php if ($isEdit): ?>
        <input type="hidden" name="user_id" value="<?= $userId ?>">
      <?php endif; ?>

      <!-- Baris 1: Nama lengkap + Kode karyawan -->
      <div class="row g-3 mb-3">
        <div class="col-md-8">
          <label class="form-label">Nama Lengkap <span class="required">*</span></label>
          <input type="text" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
            value="<?= e($form['full_name']) ?>" placeholder="Nama lengkap pengguna" required autofocus>
          <?php if (isset($errors['full_name'])): ?>
            <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
          <?php endif; ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Kode Karyawan</label>
          <input type="text" name="employee_code" class="form-control"
            value="<?= e($form['employee_code']) ?>" placeholder="EMP-001" maxlength="20">
        </div>
      </div>

      <!-- Baris 2: Username + Email -->
      <div class="row g-3 mb-3">
        <div class="col-md-5">
          <label class="form-label">Username <?= !$isEdit ? '<span class="required">*</span>' : '' ?></label>
          <div class="input-group">
            <span class="input-group-text">@</span>
            <input type="text" name="username" id="inputUsername"
              class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
              value="<?= e($form['username']) ?>"
              placeholder="username_unik"
              <?= $isEdit ? 'readonly' : 'required' ?>>
            <?php if (isset($errors['username'])): ?>
              <div class="invalid-feedback"><?= e($errors['username']) ?></div>
            <?php endif; ?>
          </div>
          <?php if (!$isEdit): ?>
            <div class="form-text small" id="usernameHint"></div>
          <?php endif; ?>
        </div>
        <div class="col-md-7">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" name="email"
            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
            value="<?= e($form['email']) ?>"
            placeholder="email@domain.com" required>
          <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Baris 3: Role + Phone -->
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Role <span class="required">*</span></label>
          <select name="role_id" class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>"
            <?= ($isEdit && $userId == ($_SESSION['user_id'] ?? 0)) ? 'disabled' : '' ?>>
            <option value="">-- Pilih Role --</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>"
                <?= $form['role_id'] == $r['id'] ? 'selected' : '' ?>>
                <?= e($r['role_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if ($isEdit && $userId == ($_SESSION['user_id'] ?? 0)): ?>
            <input type="hidden" name="role_id" value="<?= e($form['role_id']) ?>">
            <div class="form-text text-warning small">Role tidak dapat diubah untuk akun sendiri.</div>
          <?php endif; ?>
          <?php if (isset($errors['role_id'])): ?>
            <div class="invalid-feedback"><?= e($errors['role_id']) ?></div>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">No. Telepon</label>
          <input type="text" name="phone" class="form-control"
            value="<?= e($form['phone']) ?>" placeholder="08xxxxxxxxxx" maxlength="20">
        </div>
      </div>

      <?php if (!$isEdit): ?>
      <!-- Password (hanya saat create) -->
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Password <span class="required">*</span></label>
          <div class="input-group">
            <input type="password" name="password" id="inputPw"
              class="form-control input-password <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
              placeholder="Min. 8 karakter" required>
            <span class="input-group-text show-pw-btn" data-target="inputPw">
              <i class="bi bi-eye"></i>
            </span>
          </div>
          <div class="pw-strength mt-1" style="height:4px;border-radius:2px;background:#E5E7EB"></div>
          <div class="form-text small">Min. 8 karakter, 1 huruf kapital, 1 angka.</div>
          <?php if (isset($errors['password'])): ?>
            <div class="text-danger small mt-1"><?= e($errors['password']) ?></div>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Konfirmasi Password <span class="required">*</span></label>
          <div class="input-group">
            <input type="password" name="password_confirm" id="inputPwConfirm"
              class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
              placeholder="Ulangi password" required>
            <span class="input-group-text show-pw-btn" data-target="inputPwConfirm">
              <i class="bi bi-eye"></i>
            </span>
          </div>
          <?php if (isset($errors['password_confirm'])): ?>
            <div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Opsi tambahan -->
      <div class="row g-3 mb-4">
        <?php if ($isEdit): ?>
        <div class="col-auto">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
              <?= $form['is_active'] ? 'checked' : '' ?>
              <?= ($userId == ($_SESSION['user_id'] ?? 0)) ? 'disabled' : '' ?>>
            <label class="form-check-label" for="isActive">Pengguna Aktif</label>
          </div>
        </div>
        <?php endif; ?>
        <div class="col-auto">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="must_change_pw" id="mustChangePw"
              <?= $form['must_change_pw'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="mustChangePw">Wajib Ganti Password Saat Login Pertama</label>
          </div>
        </div>
      </div>

      <!-- Tombol aksi -->
      <div class="d-flex gap-2 justify-content-end border-top pt-3">
        <a href="<?= url('system/user') ?>" class="btn btn-secondary">
          <i class="bi bi-x me-1"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>
          <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Pengguna' ?>
        </button>
      </div>
    </form>

  </div>
</div>
</div>
</div>

<?php
$extraJs = <<<'JS'
<script>
$(function () {
  // Toggle show/hide password
  $(document).on('click', '.show-pw-btn', function () {
    const target = $(this).data('target') || 'inputPw';
    const input  = $('#' + target);
    const icon   = $(this).find('i');
    if (input.attr('type') === 'password') {
      input.attr('type', 'text');
      icon.removeClass('bi-eye').addClass('bi-eye-slash');
    } else {
      input.attr('type', 'password');
      icon.removeClass('bi-eye-slash').addClass('bi-eye');
    }
  });

  // Live username availability check
  let usernameTimer;
  $('#inputUsername').on('input', function () {
    const val = $(this).val().trim();
    clearTimeout(usernameTimer);
    if (val.length < 4) { $('#usernameHint').text(''); return; }
    usernameTimer = setTimeout(function () {
      $.get('/ajax/user/check-username', { username: val }, function (res) {
        if (res.available) {
          $('#usernameHint').html('<span class="text-success"><i class="bi bi-check-circle me-1"></i>Username tersedia.</span>');
        } else {
          $('#usernameHint').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Username sudah digunakan.</span>');
        }
      }, 'json');
    }, 400);
  });
});
</script>
JS;

require_once __DIR__ . '/../../layout/footer.php';
