<?php
/**
 * auth/login.php
 * Halaman login ERP
 * VERSI LARAGON — session_start() sebelum require config
 */



declare(strict_types=1);

// Bootstrap — urutan WAJIB: app dulu, baru database
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/helper/functions.php';
require_once __DIR__ . '/../shared/middleware/audit.php';

// Session init — WAJIB sebelum akses $_SESSION
// session_name(SESSION_NAME);
// session_set_cookie_params([
//     'lifetime' => SESSION_LIFETIME,
//     'path'     => '/',
//     'secure'   => SESSION_SECURE,
//     'httponly' => true,
//     'samesite' => 'Lax',
// ]);
// session_start();

// Sudah login? redirect ke dashboard
if (!empty($_SESSION['user_id'])) {
    redirect(url('dashboard'));
}

$errors      = [];
$formData    = ['username' => ''];
$loginReason = $_GET['reason'] ?? '';

// -cek aja
// $hash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXktnTyem';



// ─── Proses POST ───────────────────────────────────────────────────────────────
if (is_post()) {
    csrf_validate();

    $username = trim(post('username'));
    $password = post('password');
    $remember = !empty($_POST['remember']);

    if (empty($username)) $errors['username'] = 'Username wajib diisi.';
    if (empty($password)) $errors['password'] = 'Password wajib diisi.';

    if (empty($errors)) {
        $user = Database::fetchOne(
            "SELECT u.*, r.role_code, r.role_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE (u.username = ? OR u.email = ?)
                AND u.deleted_at IS NULL",
            [$username, $username]
        );
// echo '<pre>';
// var_dump($user);
// exit;
        // Akun terkunci?
        if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remainMin = (int) ceil((strtotime($user['locked_until']) - time()) / 60);
            $errors['general'] = "Akun terkunci. Coba lagi dalam {$remainMin} menit.";

        // } elseif (!$user || !password_verify($password, $user['password_hash'])) {
          
          } elseif (!$user || $password !== $user['password_hash']) { 
            if ($user) {
                $attempt   = $user['login_attempt'] + 1;
                $lockUntil = null;
                if ($attempt >= MAX_LOGIN_ATTEMPT) {
                    $lockUntil = date('Y-m-d H:i:s', time() + LOCK_DURATION_MIN * 60);
                    $errors['general'] = "Akun dikunci " . LOCK_DURATION_MIN . " menit karena terlalu banyak percobaan.";
                } else {
                    $sisa = MAX_LOGIN_ATTEMPT - $attempt;
                    $errors['general'] = "Username atau password salah. Sisa percobaan: {$sisa}x.";
                }
                Database::query(
                    "UPDATE users SET login_attempt = ?, locked_until = ? WHERE id = ?",
                    [$attempt, $lockUntil, $user['id']]
                );
                audit_login($user['id'], $username, false);
            } else {
                $errors['general'] = 'Username atau password salah.';
                audit_login(0, $username, false);
            }

        } elseif (!$user['is_active']) {
            $errors['general'] = 'Akun Anda tidak aktif. Hubungi administrator.';

        } else {
            // ─── LOGIN BERHASIL ────────────────────────────────────────────────
            Database::query(
                "UPDATE users SET login_attempt = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?",
                [$user['id']]
            );

            $sessionToken    = bin2hex(random_bytes(32));
            $sessionLifetime = $remember ? 86400 * 30 : SESSION_LIFETIME;

            Database::query(
                "INSERT INTO user_sessions
                    (user_id, session_token, ip_address, user_agent, expires_at)
                 VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))",
                [
                    $user['id'],
                    $sessionToken,
                    get_client_ip(),
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $sessionLifetime,
                ]
            );

            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['role_code']     = $user['role_code'];
            $_SESSION['role_name']     = $user['role_name'];
            $_SESSION['session_token'] = $sessionToken;

            audit_login($user['id'], $username, true);

            if ($user['must_change_pw']) {
                redirect(url('auth/change-password') . '?force=1');
            }

            redirect(url('dashboard'));
            
        }
    }

    $formData['username'] = $username ?? '';
}

$reasonMessages = [
    'session_expired'  => 'Sesi Anda telah berakhir. Silakan login kembali.',
    'session_invalid'  => 'Sesi tidak valid. Silakan login kembali.',
    'account_inactive' => 'Akun Anda tidak aktif.',
];
$reasonMsg = $reasonMessages[$loginReason] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= APP_NAME ?></title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <style>
    :root { --erp-primary: #185FA5; --erp-dark: #0E3F72; }
    body {
      background: linear-gradient(135deg, #0E3F72 0%, #185FA5 50%, #1E7BC0 100%);
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      font-family: "Segoe UI", system-ui, sans-serif;
    }
    .login-wrapper { width: 100%; max-width: 420px; padding: 20px; }
    .login-card {
      background: #fff; border-radius: 16px; padding: 36px 32px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    }
    .login-brand { text-align: center; margin-bottom: 28px; }
    .login-brand .brand-icon {
      width: 56px; height: 56px; border-radius: 14px;
      background: var(--erp-primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 26px; margin: 0 auto 10px;
    }
    .login-brand h1 { font-size: 20px; font-weight: 700; margin: 0; color: #1A1F2E; }
    .login-brand p  { font-size: 13px; color: #6B7280; margin: 4px 0 0; }
    .form-label     { font-size: 13px; font-weight: 500; margin-bottom: 5px; }
    .form-control   { font-size: 13px; padding: 9px 12px; border-radius: 8px; border-color: #E2E6ED; }
    .form-control:focus {
      border-color: var(--erp-primary); box-shadow: 0 0 0 3px rgba(24,95,165,0.12);
    }
    .btn-login {
      width: 100%; padding: 10px; font-size: 14px; font-weight: 600;
      background: var(--erp-primary); border: none; border-radius: 8px;
      color: #fff; transition: background 0.15s; cursor: pointer;
    }
    .btn-login:hover { background: var(--erp-dark); }
    .input-group-text { border-color: #E2E6ED; background: #F9FAFB; cursor: pointer; }
    .login-footer { text-align: center; margin-top: 20px; font-size: 12px; color: #9CA3AF; }

    /* Info box kredensial default */
    .default-cred {
      background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px;
      padding: 10px 14px; margin-bottom: 18px; font-size: 12px; color: #1E40AF;
    }
    .default-cred strong { display: block; margin-bottom: 4px; }
  </style>
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">

    <div class="login-brand">
      <div class="brand-icon"><i class="bi bi-gem"></i></div>
      <h1><?= APP_NAME ?></h1>
      <p>Sistem Informasi Toko Berlian</p>
    </div>

    <!-- Info kredensial default (development only) -->
    <?php if (APP_ENV === 'development'): ?>
    <div class="default-cred">
      <strong><i class="bi bi-info-circle me-1"></i>Akun Default (Development)</strong>
      Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>password</code>
    </div>
    <?php endif; ?>

    <?php if ($reasonMsg): ?>
      <div class="alert alert-warning py-2 mb-3" style="font-size:13px">
        <i class="bi bi-info-circle me-1"></i> <?= e($reasonMsg) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-danger py-2 mb-3" style="font-size:13px">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= e($errors['general']) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label">Username / Email</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input
            type="text" name="username"
            class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>"
            value="<?= e($formData['username']) ?>"
            placeholder="admin" autocomplete="username" autofocus>
          <?php if (!empty($errors['username'])): ?>
            <div class="invalid-feedback"><?= e($errors['username']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input
            type="password" name="password" id="inputPassword"
            class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
            placeholder="password" autocomplete="current-password">
          <span class="input-group-text" id="togglePw" title="Tampilkan/sembunyikan password">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </span>
          <?php if (!empty($errors['password'])): ?>
            <div class="invalid-feedback"><?= e($errors['password']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
          <label class="form-check-label" for="rememberMe" style="font-size:13px">
            Ingat saya selama 30 hari
          </label>
        </div>
      </div>

      <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
      </button>
    </form>

    <div class="login-footer">
      &copy; <?= date('Y') ?> <?= APP_NAME ?> &mdash; v<?= APP_VERSION ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePw').addEventListener('click', function () {
  const input = document.getElementById('inputPassword');
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
});
</script>
</body>
</html>
