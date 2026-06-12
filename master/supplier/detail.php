<?php
/**
 * master/supplier/detail.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_auth(); require_permission('SUPPLIER_VIEW');

$supplierId = (int)get_param('id');
$supplier   = SupplierRepository::findById($supplierId);
if (!$supplier) { flash_set('error','Supplier tidak ditemukan.'); redirect(url('master/supplier')); }

// Statistik berlian dari supplier ini
$diamondStats = Database::fetchOne(
    "SELECT COUNT(*) total,
       SUM(status='available') available,
       SUM(status='sold') sold,
       SUM(status='reserved') reserved
     FROM diamonds WHERE supplier_id=? AND deleted_at IS NULL",
    [$supplierId]
) ?? [];

$typeLabels = ['consignment'=>'Konsinyasi','purchase'=>'Pembelian','both'=>'Konsinyasi & Pembelian'];

$pageTitle   = 'Detail Supplier';
$breadcrumbs = [
    ['label'=>'Master Data'],
    ['label'=>'Supplier','url'=>url('master/supplier')],
    ['label'=>e($supplier['name'])],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:52px;height:52px;border-radius:12px;background:#EFF6FF;color:#185FA5;
            display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;flex-shrink:0">
            <?= e(name_initials($supplier['name'])) ?>
          </div>
          <div>
            <div class="fw-600 fs-6"><?= e($supplier['name']) ?></div>
            <div class="font-mono text-muted small"><?= e($supplier['supplier_code']) ?></div>
          </div>
        </div>

        <table class="table table-borderless table-sm mb-0" style="font-size:13px">
          <tr><td class="text-muted" width="40%">Jenis</td>
              <td><?= e($typeLabels[$supplier['type']] ?? $supplier['type']) ?></td></tr>
          <tr><td class="text-muted">Kontak</td>
              <td><?= $supplier['contact_person'] ? e($supplier['contact_person']) : '—' ?></td></tr>
          <tr><td class="text-muted">Telepon</td>
              <td><?= $supplier['phone'] ? e($supplier['phone']) : '—' ?></td></tr>
          <tr><td class="text-muted">Email</td>
              <td><?= $supplier['email'] ? e($supplier['email']) : '—' ?></td></tr>
          <tr><td class="text-muted">Mata Uang</td>
              <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= e($supplier['currency']) ?></span></td></tr>
          <tr><td class="text-muted">Diskon Default</td>
              <td><?= $supplier['discount_percent'] > 0 ? e($supplier['discount_percent']).'%' : '—' ?></td></tr>
          <tr><td class="text-muted">Payment Terms</td>
              <td><?= $supplier['payment_terms'] ? e($supplier['payment_terms']) : '—' ?></td></tr>
          <tr><td class="text-muted">Status</td>
              <td><?= $supplier['is_active']
                ? '<span class="status-badge badge-active">Aktif</span>'
                : '<span class="status-badge badge-inactive">Nonaktif</span>' ?></td></tr>
        </table>

        <?php if ($supplier['bank_name'] || $supplier['bank_account']): ?>
        <div class="border-top pt-3 mt-2">
          <div class="text-muted small fw-600 mb-2"><i class="bi bi-bank me-1"></i>Rekening Bank</div>
          <div style="font-size:13px">
            <div><?= e($supplier['bank_name']??'') ?></div>
            <div class="font-mono"><?= e($supplier['bank_account']??'') ?></div>
            <div class="text-muted"><?= e($supplier['bank_holder']??'') ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="card-body border-top p-3 d-flex flex-column gap-2">
        <?php if (can('SUPPLIER_EDIT')): ?>
          <a href="<?= url('master/supplier/edit') ?>?id=<?= $supplierId ?>"
             class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Data
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <!-- Stat berlian -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="bi bi-gem"></i></div>
          <div><div class="stat-label">Total Berlian</div><div class="stat-value"><?= e($diamondStats['total']??0) ?></div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
          <div><div class="stat-label">Tersedia</div><div class="stat-value"><?= e($diamondStats['available']??0) ?></div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon amber"><i class="bi bi-lock"></i></div>
          <div><div class="stat-label">Direservasi</div><div class="stat-value"><?= e($diamondStats['reserved']??0) ?></div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="bi bi-bag-check"></i></div>
          <div><div class="stat-label">Terjual</div><div class="stat-value"><?= e($diamondStats['sold']??0) ?></div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-gem me-2"></i>Daftar Berlian dari Supplier Ini</span>
        <?php if (can('DIAMOND_VIEW')): ?>
          <a href="<?= url('master/diamond') ?>?supplier_id=<?= $supplierId ?>"
             class="btn btn-outline-secondary btn-sm">Lihat Semua</a>
        <?php endif; ?>
      </div>
      <?php
      $diamonds = Database::fetchAll(
          "SELECT d.internal_code, d.carat_weight, d.color_grade, d.clarity_grade,
                  d.status, d.selling_price_usd, w.name AS warehouse_name
             FROM diamonds d
             LEFT JOIN warehouses w ON w.id = d.warehouse_id
            WHERE d.supplier_id = ? AND d.deleted_at IS NULL
            ORDER BY d.created_at DESC LIMIT 10",
          [$supplierId]
      );
      $statusLabels = [
          'registered'=>'Terdaftar','available'=>'Tersedia','reserved'=>'Direservasi',
          'sold'=>'Terjual','returned'=>'Retur','in_repair'=>'Reparasi','retired'=>'Nonaktif'
      ];
      $statusColors = [
          'registered'=>'secondary','available'=>'success','reserved'=>'warning',
          'sold'=>'primary','returned'=>'danger','in_repair'=>'purple','retired'=>'dark'
      ];
      ?>
      <?php if (empty($diamonds)): ?>
        <div class="table-empty" style="padding:40px">
          <i class="bi bi-gem text-muted"></i>
          <p class="small">Belum ada berlian dari supplier ini.</p>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:13px">
          <thead>
            <tr><th>Kode Internal</th><th>Spesifikasi</th><th>Lokasi</th><th>Harga (USD)</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($diamonds as $d): ?>
            <tr>
              <td class="font-mono"><?= e($d['internal_code']) ?></td>
              <td><?= e($d['carat_weight']) ?>ct | <?= e($d['color_grade']) ?> | <?= e($d['clarity_grade']) ?></td>
              <td class="small text-muted"><?= e($d['warehouse_name']??'—') ?></td>
              <td class="text-end">$<?= number_format($d['selling_price_usd'],2) ?></td>
              <td>
                <?php $sc = $statusColors[$d['status']]??'secondary'; ?>
                <span class="badge bg-<?=$sc?>-subtle text-<?=$sc?> border border-<?=$sc?>-subtle">
                  <?= e($statusLabels[$d['status']]??$d['status']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
