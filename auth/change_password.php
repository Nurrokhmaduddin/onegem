<?php
/**
 * auth/change_password.php
 * Ganti password sendiri (termasuk forced change saat login pertama)
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/helper/functions.php';
require_once __DIR__ . '/../shared/middleware/auth.php';
require_once __DIR__ . '/../shared/middleware/audit.php';
require_once __DIR__ . '/../system/user/repository.php';
require_once __DIR__ . '/../system/user/service.php';

session_name(SESSION_NAME);
session_set_cookie_params(['lifetime'=>SESSION_LIFETIME,'path'=>'/','secure'=>SESSION_SECURE,'httponly'=>true,'samesite'=>'Lax']);
session_start();

if (empty($_SESSION['user_id'])) redirect(url('auth/login'));

$force  = !empty($_GET['force']);
$errors = [];

if (is_post()) {
    csrf_validate();
    $current = post('current_password');
    $new     = post('new_password');
    $confirm = post('new_password_confirm');

    // Verifikasi password lama (tidak wajib jika force)
    if (!$force) {
        $user = UserRepository::findById((int)$_SESSION['user_id']);
        if (!$user || !password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'Password saat ini tidak sesuai.';
        }
    }

    if (empty($errors)) {
        $pwErrors = UserService::validatePassword($new, $confirm);
        if (!empty($pwErrors)) {
            $errors = array_merge($errors, $pwErrors);
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            UserRepository::updatePassword((int)$_SESSION['user_id'], $hash, true);
            audit_log('AUTH','APPROVE',null,'users',(string)$_SESSION['user_id'],null,null,'Password diubah oleh pengguna sendiri');
            flash_set('success','Password berhasil diubah.');
            redirect(url('dashboard'));
        }
    }
}

$pageTitle = 'Ganti Password';
require_once __DIR__ . '/../layout/header.php';
?>
<div class="row justify-content-center">
<div class="col-md-5">
<div class="card">
  <div class="card-header"><i class="bi bi-key me-2 text-primary"></i>Ganti Password</div>
  <div class="card-body p-4">
    <?php if ($force): ?>
    <div class="alert alert-warning mb-3" style="font-size:13px">
      <i class="bi bi-exclamation-triangle me-1"></i>
      Anda wajib mengganti password sebelum melanjutkan.
    </div>
    <?php endif; ?>
    <form method="POST" class="no-double-submit">
      <?= csrf_field() ?>
      <?php if (!$force): ?>
      <div class="mb-3">
        <label class="form-label">Password Saat Ini <span class="required">*</span></label>
        <input type="password" name="current_password"
          class="form-control <?= isset($errors['current_password'])?'is-invalid':'' ?>"
          placeholder="Password sekarang" required>
        <?php if (isset($errors['current_password'])): ?>
          <div class="invalid-feedback"><?= e($errors['current_password']) ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label">Password Baru <span class="required">*</span></label>
        <input type="password" name="new_password"
          class="form-control input-password <?= isset($errors['password'])?'is-invalid':'' ?>"
          placeholder="Min. 8 karakter, 1 kapital, 1 angka" required>
        <div class="pw-strength mt-1" style="height:4px;border-radius:2px;background:#E5E7EB"></div>
        <?php if (isset($errors['password'])): ?>
          <div class="text-danger small mt-1"><?= e($errors['password']) ?></div>
        <?php endif; ?>
      </div>
      <div class="mb-4">
        <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
        <input type="password" name="new_password_confirm"
          class="form-control <?= isset($errors['password_confirm'])?'is-invalid':'' ?>"
          placeholder="Ulangi password baru" required>
        <?php if (isset($errors['password_confirm'])): ?>
          <div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 justify-content-end">
        <?php if (!$force): ?>
          <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Batal</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>Simpan Password Baru
        </button>
      </div>
    </form>
  </div>
</div>
</div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
