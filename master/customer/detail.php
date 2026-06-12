<?php
/**
 * master/customer/detail.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth(); require_permission('CUSTOMER_VIEW');

$customerId = (int)get_param('id');
$customer   = CustomerRepository::findById($customerId);
if (!$customer) { flash_set('error','Pelanggan tidak ditemukan.'); redirect(url('master/customer')); }

$tierConfig = [
    'regular' => ['label'=>'Regular','class'=>'bg-secondary-subtle text-secondary border border-secondary-subtle'],
    'vip'     => ['label'=>'VIP',    'class'=>'bg-primary-subtle text-primary border border-primary-subtle'],
    'vvip'    => ['label'=>'VVIP',   'class'=>'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
];

$pageTitle   = 'Detail Pelanggan';
$breadcrumbs = [
    ['label'=>'Master Data'],
    ['label'=>'Pelanggan','url'=>url('master/customer')],
    ['label'=>e($customer['name'])],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">
  <!-- Kolom kiri: kartu profil -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center p-4">
        <div class="mx-auto mb-3" style="width:72px;height:72px;border-radius:50%;
          background:<?= $customer['tier']==='vvip'?'#FFF7ED':($customer['tier']==='vip'?'#EFF6FF':'#F3F4F6') ?>;
          color:<?= $customer['tier']==='vvip'?'#C2410C':($customer['tier']==='vip'?'#1D4ED8':'#374151') ?>;
          display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700">
          <?= e(name_initials($customer['name'])) ?>
        </div>
        <h5 class="mb-1"><?= e($customer['name']) ?></h5>
        <div class="font-mono text-muted small mb-2"><?= e($customer['customer_code']) ?></div>
        <?php $tc = $tierConfig[$customer['tier']] ?? $tierConfig['regular']; ?>
        <span class="badge <?= $tc['class'] ?> mb-3 d-inline-block fs-6"><?= $tc['label'] ?></span>
        <div>
          <?php if ($customer['is_active']): ?>
            <span class="status-badge badge-active">Aktif</span>
          <?php else: ?>
            <span class="status-badge badge-inactive">Nonaktif</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="card-body border-top p-3">
        <table class="table table-borderless table-sm mb-0" style="font-size:13px">
          <tr>
            <td class="text-muted" width="40%">Telepon</td>
            <td><?= $customer['phone'] ? e($customer['phone']) : '—' ?></td>
          </tr>
          <?php if ($customer['phone2']): ?>
          <tr>
            <td class="text-muted">Telp 2</td>
            <td><?= e($customer['phone2']) ?></td>
          </tr>
          <?php endif; ?>
          <tr>
            <td class="text-muted">Email</td>
            <td><?= $customer['email'] ? e($customer['email']) : '—' ?></td>
          </tr>
          <tr>
            <td class="text-muted">Tgl Lahir</td>
            <td><?= $customer['birth_date'] ? format_date($customer['birth_date']) : '—' ?></td>
          </tr>
          <tr>
            <td class="text-muted">Kelamin</td>
            <td><?= $customer['gender'] === 'M' ? 'Laki-laki' : ($customer['gender'] === 'F' ? 'Perempuan' : '—') ?></td>
          </tr>
          <tr>
            <td class="text-muted">Identitas</td>
            <td><?= $customer['identity_number'] ? strtoupper(e($customer['identity_type']??'')) . ': ' . e($customer['identity_number']) : '—' ?></td>
          </tr>
          <tr>
            <td class="text-muted">Ukuran Cincin</td>
            <td><?= $customer['ring_size'] ? e($customer['ring_size']) : '—' ?></td>
          </tr>
          <tr>
            <td class="text-muted">Terdaftar</td>
            <td><?= format_date($customer['created_at']) ?></td>
          </tr>
        </table>
      </div>

      <?php if ($customer['address']): ?>
      <div class="card-body border-top p-3">
        <div class="text-muted small mb-1"><i class="bi bi-geo-alt me-1"></i>Alamat</div>
        <div style="font-size:13px"><?= nl2br(e($customer['address'])) ?></div>
      </div>
      <?php endif; ?>

      <div class="card-body border-top p-3 d-flex flex-column gap-2">
        <?php if (can('CUSTOMER_EDIT')): ?>
          <a href="<?= url('master/customer/edit') ?>?id=<?= $customerId ?>"
             class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Data
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Kolom kanan: preferensi & riwayat -->
  <div class="col-lg-8">

    <?php if ($customer['preferences'] || $customer['notes']): ?>
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-sticky-note me-2"></i>Preferensi & Catatan</div>
      <div class="card-body p-4">
        <?php if ($customer['preferences']): ?>
          <div class="mb-3">
            <label class="form-label fw-600 small text-muted text-uppercase">Preferensi Desain</label>
            <p class="mb-0" style="font-size:14px"><?= nl2br(e($customer['preferences'])) ?></p>
          </div>
        <?php endif; ?>
        <?php if ($customer['notes']): ?>
          <div>
            <label class="form-label fw-600 small text-muted text-uppercase">Catatan Internal</label>
            <p class="mb-0" style="font-size:14px"><?= nl2br(e($customer['notes'])) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Placeholder riwayat transaksi (akan terisi Sprint 3-4) -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Riwayat Transaksi</span>
        <span class="badge bg-secondary">Tersedia di Sprint 4</span>
      </div>
      <div class="table-empty" style="padding:40px">
        <i class="bi bi-receipt text-muted"></i>
        <p class="text-muted small">Riwayat quotation, sales order, dan invoice akan tampil di sini setelah Sprint 3-4 selesai.</p>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
