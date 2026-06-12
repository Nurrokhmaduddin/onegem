<?php
/**
 * master/currency/list.php
 * Manajemen Kurs Valuta (USD → IDR)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_auth(); require_permission('CURRENCY_VIEW');

$rates = Database::fetchAll(
    "SELECT c.*, u.full_name AS set_by_name
       FROM currencies c LEFT JOIN users u ON u.id=c.set_by
      ORDER BY c.effective_date DESC, c.id DESC LIMIT 30"
);

$activeRate = Database::fetchOne(
    "SELECT * FROM currencies WHERE code='USD' AND is_active=1
       AND effective_date<=CURDATE() ORDER BY effective_date DESC LIMIT 1"
);

$pageTitle   = 'Kurs Valuta Asing';
$breadcrumbs = [['label'=>'Master Data'],['label'=>'Kurs Valuta']];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body p-4">
        <div class="stat-label mb-1">Kurs Aktif Saat Ini</div>
        <?php if ($activeRate): ?>
          <div class="fw-700 fs-3 text-primary">
            Rp <?= number_format((float)$activeRate['rate_to_idr'],0,',','.') ?>
            <span class="fs-6 text-muted">/ USD</span>
          </div>
          <div class="text-muted small mt-1">
            Berlaku sejak <?= format_date($activeRate['effective_date']) ?>
          </div>
        <?php else: ?>
          <div class="text-danger">Kurs belum diatur</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-8 d-flex align-items-center">
    <?php if (can('CURRENCY_CREATE')): ?>
    <div class="card w-100">
      <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Input Kurs Baru</div>
      <div class="card-body p-3">
        <form method="POST" action="<?= url('master/currency/save') ?>" class="no-double-submit">
          <?= csrf_field() ?>
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="form-label small mb-1">Mata Uang</label>
              <input type="text" class="form-control form-control-sm font-mono bg-light"
                value="USD" readonly style="width:80px">
            </div>
            <div class="col">
              <label class="form-label small mb-1">Rate (IDR per 1 USD) <span class="required">*</span></label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">Rp</span>
                <input type="number" name="rate_to_idr" step="0.0001" min="1"
                  class="form-control" placeholder="16500.0000" required>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-1">Berlaku Mulai <span class="required">*</span></label>
              <input type="date" name="effective_date" class="form-control form-control-sm"
                value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-1">Catatan</label>
              <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opsional">
            </div>
            <div class="col-auto">
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check me-1"></i>Simpan Kurs
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Riwayat kurs -->
<div class="table-card">
  <div class="table-toolbar">
    <span class="fw-600"><i class="bi bi-clock-history me-2"></i>Riwayat Kurs (30 Data Terakhir)</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px">
      <thead>
        <tr><th>Tanggal Berlaku</th><th>Mata Uang</th><th>Rate (IDR)</th><th>Dicatat Oleh</th><th>Catatan</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rates as $r): ?>
        <tr <?= $r['id']==($activeRate['id']??0)?'class="table-primary fw-600"':'' ?>>
          <td><?= format_date($r['effective_date']) ?></td>
          <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= e($r['code']) ?></span></td>
          <td class="font-mono">Rp <?= number_format((float)$r['rate_to_idr'],4,',','.') ?></td>
          <td class="text-muted small"><?= e($r['set_by_name']??'Sistem') ?></td>
          <td class="text-muted small"><?= e($r['notes']??'—') ?></td>
          <td>
            <?php if ($r['id']==($activeRate['id']??0)): ?>
              <span class="badge bg-primary text-white">Aktif</span>
            <?php elseif ($r['is_active']): ?>
              <span class="status-badge badge-active">Aktif</span>
            <?php else: ?>
              <span class="status-badge badge-inactive">Nonaktif</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rates)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data kurs.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
