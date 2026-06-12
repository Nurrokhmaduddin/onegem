<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>403 — Akses Ditolak</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <style>
    body{background:#F4F6FA;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:"Segoe UI",sans-serif}
    .error-box{text-align:center;max-width:400px;padding:40px 24px}
    .error-icon{font-size:64px;color:#DC2626;margin-bottom:16px}
    .error-code{font-size:80px;font-weight:800;color:#1A1F2E;line-height:1}
    .error-title{font-size:20px;font-weight:600;margin:12px 0 8px}
    .error-desc{color:#6B7280;font-size:14px;margin-bottom:24px}
  </style>
</head>
<body>
  <div class="error-box">
    <div class="error-icon"><i class="bi bi-shield-x"></i></div>
    <div class="error-code">403</div>
    <div class="error-title">Akses Ditolak</div>
    <div class="error-desc">Anda tidak memiliki izin untuk mengakses halaman atau melakukan aksi ini. Hubungi administrator jika ini sebuah kesalahan.</div>
    <a href="javascript:history.back()" class="btn btn-outline-secondary me-2">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <a href="/" class="btn btn-primary">
      <i class="bi bi-house me-1"></i>Dashboard
    </a>
  </div>
</body>
</html>
