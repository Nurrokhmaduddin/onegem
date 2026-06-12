# ERP Toko Berlian — Panduan Instalasi Sprint 1
## Stack: Native PHP 8.x + MySQL 8.0 + Bootstrap 5 + jQuery
## Deployment: On-Premise (Apache / Nginx)

---

## Persyaratan Server

| Komponen | Minimum       | Rekomendasi    |
|----------|--------------|----------------|
| PHP      | 8.1          | 8.2 / 8.3      |
| MySQL    | 8.0          | 8.0 / 8.4      |
| OS       | Ubuntu 22.04 | Ubuntu 24.04   |
| RAM      | 1 GB         | 2 GB+          |
| Storage  | 10 GB        | 50 GB+         |
| Web      | Apache 2.4   | Nginx 1.24     |

**PHP Extensions yang dibutuhkan:**
```
php-pdo php-pdo-mysql php-mbstring php-json
php-fileinfo php-session php-openssl
```

---

## 1. Setup Database MySQL

```bash
# Masuk ke MySQL sebagai root
mysql -u root -p

# Jalankan perintah berikut:
CREATE DATABASE erp_berlian
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'erp_user'@'localhost'
  IDENTIFIED BY 'GantiPasswordKuat!';

GRANT ALL PRIVILEGES ON erp_berlian.*
  TO 'erp_user'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import schema dan seed data Sprint 1
mysql -u erp_user -p erp_berlian < sql/sprint1_schema.sql
```

---

## 2. Konfigurasi Aplikasi

```bash
# Salin file environment
cp .env.example .env

# Edit .env sesuai environment Anda
nano .env
```

Isi minimal `.env`:
```
APP_ENV=production
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=erp_berlian
DB_USER=erp_user
DB_PASS=GantiPasswordKuat!
```

---

## 3a. Setup Apache

```bash
# Salin project ke document root
cp -r erp-berlian-native /var/www/erp-berlian

# Permissions
find /var/www/erp-berlian -type f -exec chmod 644 {} \;
find /var/www/erp-berlian -type d -exec chmod 755 {} \;
chmod 775 /var/www/erp-berlian/logs
chmod 775 /var/www/erp-berlian/uploads
chown -R www-data:www-data /var/www/erp-berlian

# Buat folder yang diperlukan
mkdir -p /var/www/erp-berlian/{logs,uploads}
```

Buat Virtual Host Apache `/etc/apache2/sites-available/erp-berlian.conf`:
```apache
<VirtualHost *:80>
    ServerName 192.168.1.100
    DocumentRoot /var/www/erp-berlian

    <Directory /var/www/erp-berlian>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/erp-berlian.error.log
    CustomLog ${APACHE_LOG_DIR}/erp-berlian.access.log combined
</VirtualHost>
```

```bash
# Aktifkan site dan mod_rewrite
a2ensite erp-berlian.conf
a2enmod rewrite
systemctl restart apache2
```

---

## 3b. Setup Nginx

```bash
# Salin konfigurasi nginx
cp nginx.conf /etc/nginx/sites-available/erp-berlian
ln -s /etc/nginx/sites-available/erp-berlian /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## 4. Direktori Wajib Ada

```bash
mkdir -p /var/www/erp-berlian/logs
mkdir -p /var/www/erp-berlian/uploads/{diamonds,certificates,avatars}
chmod 775 /var/www/erp-berlian/logs
chmod 775 /var/www/erp-berlian/uploads
chown -R www-data:www-data /var/www/erp-berlian/logs
chown -R www-data:www-data /var/www/erp-berlian/uploads
```

---

## 5. Akun Default

Setelah import SQL seed data:

| Username | Password     | Role          | Keterangan                          |
|----------|-------------|---------------|-------------------------------------|
| `admin`  | `password`  | Owner         | Akun default — **WAJIB ganti!**    |

> ⚠️ **PENTING:** Password default adalah `password` (bcrypt hash demo).
> Saat pertama login, sistem akan meminta ganti password karena `must_change_pw = 1`.

---

## 6. Struktur File Aplikasi

```
erp-berlian-native/
├── index.php                   ← Front controller (entry point semua request)
├── .htaccess                   ← Apache rewrite rules
├── nginx.conf                  ← Nginx config (referensi)
├── .env.example                ← Template konfigurasi environment
│
├── config/
│   ├── app.php                 ← Konstanta & konfigurasi aplikasi
│   └── database.php            ← PDO singleton + helper query
│
├── shared/
│   ├── helper/
│   │   └── functions.php       ← Fungsi helper global (csrf, flash, format, dll)
│   └── middleware/
│       ├── auth.php            ← Middleware autentikasi & cek permission
│       └── audit.php           ← Audit trail logger
│
├── layout/
│   ├── header.php              ← HTML head + Navbar + Sidebar (include di awal)
│   ├── footer.php              ← Script JS + closing tags (include di akhir)
│   ├── error_403.php           ← Halaman akses ditolak
│   └── error_404.php           ← Halaman tidak ditemukan
│
├── auth/
│   ├── login.php               ← Halaman & proses login
│   └── logout.php              ← Proses logout
│
├── dashboard/
│   └── index.php               ← Dashboard utama
│
├── system/
│   ├── user/
│   │   ├── repository.php      ← Query DB user
│   │   ├── service.php         ← Business rule user
│   │   ├── list.php            ← Daftar pengguna
│   │   ├── form.php            ← Form create/edit
│   │   ├── detail.php          ← Detail pengguna
│   │   ├── save.php            ← Proses create
│   │   ├── update.php          ← Proses update
│   │   ├── delete.php          ← Proses soft delete
│   │   ├── reset_password.php  ← Reset password (admin)
│   │   ├── toggle_status.php   ← Toggle aktif/nonaktif (AJAX)
│   │   └── ajax/
│   │       └── check_username.php  ← Cek username tersedia (AJAX)
│   │
│   ├── role/
│   │   ├── repository.php      ← Query DB role + permission
│   │   ├── list.php            ← Daftar role + modal create/edit
│   │   ├── save.php            ← Proses create role
│   │   ├── update.php          ← Proses update role
│   │   └── delete.php          ← Proses delete role (AJAX)
│   │
│   ├── permission/
│   │   ├── assign.php          ← Matrix assignment permission per role
│   │   └── ajax/
│   │       └── save_role_permissions.php  ← Simpan permission (AJAX)
│   │
│   └── audit/
│       └── list.php            ← Audit trail viewer
│
├── public/
│   ├── css/
│   │   └── app.css             ← Custom theme ERP (Bootstrap 5 override)
│   └── js/
│       └── app.js              ← jQuery global: CSRF, sidebar, toast, AJAX helper
│
├── sql/
│   └── sprint1_schema.sql      ← DDL + seed data Sprint 1
│
├── logs/                       ← PHP error log (auto-dibuat, tidak di-commit)
└── uploads/                    ← File upload (tidak di-commit)
```

---

## 7. Standar Pengembangan — Level 8

### Pattern setiap modul:
```
modul/
├── repository.php   ← SELECT, INSERT, UPDATE, DELETE saja — NO business rule
├── service.php      ← Validasi, business rule, orkestrasi transaksi
├── list.php         ← Page: tampilkan tabel data
├── form.php         ← Page: form create/edit
├── detail.php       ← Page: detail record
├── save.php         ← Process: simpan baru (POST → redirect)
├── update.php       ← Process: update (POST → redirect)
├── delete.php       ← Process: hapus (POST AJAX → JSON)
└── ajax/            ← Endpoint AJAX asinkron
    └── *.php
```

### Cara include layout di setiap page:
```php
// Di awal file (sebelum output apapun):
$pageTitle   = 'Judul Halaman';
$breadcrumbs = [
    ['label' => 'Modul', 'url' => url('modul')],
    ['label' => 'Sub Halaman'],
];
require_once __DIR__ . '/../../layout/header.php';

// ... konten HTML halaman ...

// Di akhir file:
$extraJs = <<<'JS'
<script>
$(function () {
  // JavaScript khusus halaman ini
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
```

### Cara cek permission di view:
```php
<?php if (can('USER_CREATE')): ?>
  <a href="...">Tambah Pengguna</a>
<?php endif; ?>
```

### Cara AJAX dengan helper:
```javascript
erpAjax({
  url      : '/system/user/toggle-status',
  data     : { user_id: 5, csrf_token: $('meta[name="csrf-token"]').attr('content') },
  onSuccess: function (res) { erpToast('success', res.message); },
  onError  : function (msg) { erpToast('danger', msg); }
});
```

---

## 8. Sprint Berikutnya

### Sprint 2 — Master Data
File yang akan ditambahkan:
```
master/
├── customer/  (list, form, detail, save, update, delete, repository, service)
├── supplier/  (list, form, detail, save, update, delete, repository, service)
├── diamond/   (list, form, detail, save, update, delete, repository, service)
├── warehouse/ (list, form, repository, service)
└── coa/       (list, form, repository, service)

sql/
└── sprint2_schema.sql   ← Tabel: customers, suppliers, diamonds, warehouses, coa
```

### Sprint 3 — Sales (Lead, Quotation, Reservation)
### Sprint 4 — Sales Order, Invoice
### Sprint 5 — Inventory, Consignment, Repair
### Sprint 6 — Cash Management, AR, AP, Accounting
### Sprint 7 — Dashboard Lengkap, Report, Notification

---

## 9. Troubleshooting

**Blank page / 500 error:**
```bash
# Aktifkan APP_ENV=development di .env
# Cek log error:
tail -f /var/log/apache2/erp-berlian.error.log
tail -f logs/php_error.log
```

**CSRF error (400):**
- Pastikan session berjalan dengan benar
- Cek `SESSION_NAME` konsisten di semua file
- Pastikan tidak ada output sebelum `session_start()`

**Permission denied pada uploads/logs:**
```bash
chmod 775 logs/ uploads/
chown www-data:www-data logs/ uploads/
```

**MySQL partition error saat import:**
```sql
-- Jika server MySQL tidak support partisi, ganti definisi audit_logs
-- Hapus bagian PARTITION BY RANGE dari CREATE TABLE audit_logs
-- dan jalankan ulang
```
