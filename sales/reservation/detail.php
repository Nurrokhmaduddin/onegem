<?php
/**
 * sales/reservation/detail.php
 * Detail Reservasi — info lengkap, item berlian, history, action buttons
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
require_permission('RESERVATION_VIEW');

$id          = (int) get_param('id', 0);
$reservation = ReservationRepository::findById($id);
if (!$reservation) not_found('Reservasi tidak ditemukan.');

$items   = ReservationRepository::getItems($id);
$history = Database::fetchAll(
    "SELECT rsh.*, u.full_name AS actor_name
       FROM reservation_state_histories rsh
  LEFT JOIN users u ON u.id = rsh.actor_id
      WHERE rsh.reservation_id = ?
      ORDER BY rsh.created_at ASC",
    [$id]
);

$expiryTs   = strtotime($reservation['expiry_date']);
$daysLeft   = (int) ceil(($expiryTs - time()) / 86400);
$isActive   = $reservation['status'] === 'active';
$isUrgent   = $isActive && $daysLeft <= 1;
$isWarning  = $isActive && $daysLeft <= 3 && !$isUrgent;

$statusConfig = [
    'active'    => ['label' => 'Aktif',        'color' => 'success',   'icon' => 'bi-bookmark-check-fill'],
    'released'  => ['label' => 'Dilepas',       'color' => 'secondary', 'icon' => 'bi-bookmark-x'],
    'expired'   => ['label' => 'Kedaluwarsa',   'color' => 'warning',   'icon' => 'bi-clock-history'],
    'converted' => ['label' => 'Dikonversi',    'color' => 'primary',   'icon' => 'bi-arrow-right-circle'],
];
$sc = $statusConfig[$reservation['status']] ?? ['label' => $reservation['status'], 'color' => 'secondary', 'icon' => 'bi-circle'];

// Ambil sales order jika sudah dikonversi
$salesOrder = null;
if ($reservation['status'] === 'converted') {
    $salesOrder = Database::fetchOne(
        "SELECT id, so_no, status FROM sales_orders WHERE reservation_id = ? AND deleted_at IS NULL LIMIT 1",
        [$id]
    );
}

$pageTitle   = 'Detail Reservasi — ' . $reservation['reservation_no'];
$breadcrumbs = [
    ['label' => 'Penjualan'],
    ['label' => 'Reservasi', 'url' => url('sales/reservation')],
    ['label' => $reservation['reservation_no']],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<?php flash_show(); ?>

<!-- Header bar -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h4 class="mb-0 fw-700 font-mono"><?= e($reservation['reservation_no']) ?></h4>
      <span class="badge bg-<?= $sc['color'] ?>-subtle text-<?= $sc['color'] ?> border border-<?= $sc['color'] ?>-subtle fs-6">
        <i class="<?= $sc['icon'] ?> me-1"></i><?= $sc['label'] ?>
      </span>
      <?php if ($isUrgent): ?>
        <span class="badge bg-danger fs-6"><i class="bi bi-alarm me-1"></i>Kedaluwarsa Hari Ini!</span>
      <?php elseif ($isWarning): ?>
        <span class="badge bg-warning text-dark fs-6"><i class="bi bi-exclamation-triangle me-1"></i><?= $daysLeft ?> hari lagi</span>
      <?php endif; ?>
    </div>
    <div class="text-muted small mt-1">
      Dibuat <?= date('d M Y H:i', strtotime($reservation['created_at'])) ?>
      <?php if ($reservation['quotation_no']): ?>
        · dari Quotation
        <a href="<?= url('sales/quotation/detail') ?>?id=<?= e($reservation['quotation_id']) ?>" class="text-decoration-none">
          <?= e($reservation['quotation_no']) ?>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($isActive && can('RESERVATION_EDIT')): ?>
    <a href="<?= url('sales/reservation/edit') ?>?id=<?= $id ?>"
       class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <button type="button" class="btn btn-outline-warning btn-sm" id="btnExtend">
      <i class="bi bi-calendar-plus me-1"></i>Perpanjang
    </button>
    <?php endif; ?>
    <?php if ($isActive && can('RESERVATION_RELEASE')): ?>
    <button type="button" class="btn btn-outline-danger btn-sm" id="btnRelease">
      <i class="bi bi-x-circle me-1"></i>Lepas Reservasi
    </button>
    <?php endif; ?>
    <?php if ($isActive && can('RESERVATION_CREATE')): ?>
    <button type="button" class="btn btn-success btn-sm" id="btnConvertSO">
      <i class="bi bi-arrow-right-circle me-1"></i>Konversi ke Sales Order
    </button>
    <?php endif; ?>
    <?php if ($salesOrder): ?>
    <a href="<?= url('sales/so/detail') ?>?id=<?= $salesOrder['id'] ?>"
       class="btn btn-primary btn-sm">
      <i class="bi bi-file-text me-1"></i>Lihat SO <?= e($salesOrder['so_no']) ?>
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Expired / released notice -->
<?php if ($reservation['status'] === 'expired'): ?>
<div class="alert alert-warning mb-4">
  <i class="bi bi-clock-history me-2"></i>
  Reservasi ini kedaluwarsa pada <strong><?= date('d M Y', $expiryTs) ?></strong>.
  Lock berlian telah dilepas oleh sistem.
</div>
<?php elseif ($reservation['status'] === 'released'): ?>
<div class="alert alert-secondary mb-4">
  <i class="bi bi-bookmark-x me-2"></i>
  Reservasi ini telah dilepas. Lock berlian sudah dibebaskan.
</div>
<?php elseif ($reservation['status'] === 'converted' && $salesOrder): ?>
<div class="alert alert-primary mb-4">
  <i class="bi bi-arrow-right-circle me-2"></i>
  Reservasi ini telah dikonversi menjadi
  <a href="<?= url('sales/so/detail') ?>?id=<?= $salesOrder['id'] ?>" class="fw-600">
    Sales Order <?= e($salesOrder['so_no']) ?>
  </a>.
</div>
<?php endif; ?>

<div class="row g-4">
<!-- Kolom Utama -->
<div class="col-lg-8">

  <!-- Info Dasar -->
  <div class="card mb-4">
    <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Reservasi</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="detail-label">Customer</div>
          <div class="detail-value fw-600"><?= e($reservation['customer_name'] ?? '—') ?></div>
          <?php if ($reservation['customer_no']): ?>
          <div class="small text-muted"><?= e($reservation['customer_no']) ?></div>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <div class="detail-label">Salesperson</div>
          <div class="detail-value"><?= e($reservation['salesperson_name'] ?? '—') ?></div>
        </div>
        <div class="col-md-6">
          <div class="detail-label">Tanggal Kedaluwarsa</div>
          <div class="detail-value <?= $isUrgent ? 'text-danger fw-600' : ($isWarning ? 'text-warning fw-500' : '') ?>">
            <?= date('d M Y', $expiryTs) ?>
            <?php if ($isActive): ?>
            <span class="ms-2 badge <?= $isUrgent ? 'bg-danger' : ($isWarning ? 'bg-warning text-dark' : 'bg-success') ?>">
              <?php if ($daysLeft <= 0): ?> Hari ini!
              <?php elseif ($daysLeft === 1): ?> Besok
              <?php else: ?> <?= $daysLeft ?> hari lagi
              <?php endif; ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="detail-label">Kurs (USD/IDR)</div>
          <div class="detail-value">Rp <?= number_format((float) $reservation['rate_usd_idr'], 0, ',', '.') ?></div>
        </div>
        <?php if ($reservation['notes']): ?>
        <div class="col-12">
          <div class="detail-label">Catatan</div>
          <div class="detail-value"><?= nl2br(e($reservation['notes'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Item Berlian -->
  <div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
      <span><i class="bi bi-gem me-2 text-primary"></i>Berlian yang Direservasi</span>
      <span class="badge bg-primary-subtle text-primary"><?= count($items) ?> pcs</span>
    </div>
    <?php if (empty($items)): ?>
    <div class="card-body text-center py-4 text-muted">
      <i class="bi bi-gem opacity-25 fs-2 d-block mb-2"></i>
      Tidak ada berlian dalam reservasi ini.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>SKU</th>
            <th>Spesifikasi</th>
            <th>Sertifikat</th>
            <th>Harga (USD)</th>
            <th>Status Berlian</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i => $item):
            $dstatus = $item['diamond_status'];
            $dcolor  = match($dstatus) {
              'reserved'  => 'warning',
              'available' => 'success',
              'sold'      => 'danger',
              default     => 'secondary',
            };
          ?>
          <tr>
            <td class="text-muted small"><?= $i + 1 ?></td>
            <td>
              <a href="<?= url('operation/diamond/detail') ?>?id=<?= $item['diamond_id'] ?>"
                 class="fw-600 font-mono text-decoration-none">
                <?= e($item['sku']) ?>
              </a>
            </td>
            <td>
              <span class="fw-500"><?= e($item['carat']) ?>ct</span>
              <span class="text-muted ms-1"><?= e($item['color']) ?> · <?= e($item['clarity']) ?> · <?= e($item['cut']) ?></span>
            </td>
            <td>
              <?php if ($item['certificate_no']): ?>
              <span class="badge bg-light text-dark border" style="font-size:11px">
                <?= e($item['lab']) ?> <?= e($item['certificate_no']) ?>
              </span>
              <?php else: ?>
              <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td class="fw-600 text-primary">$<?= number_format((float) $item['price_usd'], 0) ?></td>
            <td>
              <span class="badge bg-<?= $dcolor ?>-subtle text-<?= $dcolor ?> border border-<?= $dcolor ?>-subtle">
                <?= ucfirst($dstatus) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <td colspan="4" class="text-end fw-600">Total</td>
            <td class="fw-700 text-primary fs-6" colspan="2">
              $<?= number_format((float) $reservation['total_usd'], 0) ?>
              <div class="small text-muted fw-400">
                Rp <?= number_format((float) $reservation['total_idr'], 0, ',', '.') ?>
              </div>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Sidebar kanan -->
<div class="col-lg-4">

  <!-- Summary card -->
  <div class="card mb-3 border-0" style="background:<?= $isUrgent ? '#FEF2F2' : ($isWarning ? '#FFFBEB' : '#F0FDF4') ?>">
    <div class="card-body p-4 text-center">
      <div class="small text-muted mb-1">Total Nilai</div>
      <div class="fw-700 text-primary" style="font-size:1.6rem">
        $<?= number_format((float) $reservation['total_usd'], 0) ?>
      </div>
      <div class="text-muted small mb-3">
        Rp <?= number_format((float) $reservation['total_idr'], 0, ',', '.') ?>
      </div>
      <div class="row g-2 text-center">
        <div class="col-6">
          <div class="rounded p-2" style="background:rgba(255,255,255,.7)">
            <div class="fw-700 fs-5"><?= count($items) ?></div>
            <div class="small text-muted" style="font-size:11px">Berlian</div>
          </div>
        </div>
        <div class="col-6">
          <div class="rounded p-2" style="background:rgba(255,255,255,.7)">
            <div class="fw-700 fs-5 <?= $isUrgent ? 'text-danger' : '' ?>">
              <?= $isActive ? ($daysLeft <= 0 ? '0' : $daysLeft) : '—' ?>
            </div>
            <div class="small text-muted" style="font-size:11px">Hari Tersisa</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- History -->
  <div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Status</div>
    <div class="card-body p-0">
      <?php if (empty($history)): ?>
      <div class="p-4 text-center text-muted small">Belum ada riwayat.</div>
      <?php else: ?>
      <div class="timeline-list">
        <?php foreach ($history as $h):
          $isCurrent = end($history) === $h;
        ?>
        <div class="timeline-item px-4 py-3 <?= !$isCurrent ? 'border-bottom' : '' ?>">
          <div class="d-flex align-items-start gap-2">
            <div class="timeline-dot mt-1 <?= $isCurrent ? 'bg-primary' : 'bg-light border' ?>"></div>
            <div class="flex-fill">
              <div class="small fw-600">
                <?= e($h['from_status'] ?: '—') ?> → <span class="text-primary"><?= e($h['to_status']) ?></span>
              </div>
              <div class="small text-muted">
                <?= e($h['actor_name'] ?? 'Sistem') ?> ·
                <?= date('d M Y H:i', strtotime($h['created_at'])) ?>
              </div>
              <?php if ($h['notes']): ?>
              <div class="small text-muted mt-1 fst-italic">"<?= e($h['notes']) ?>"</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>
</div>

<!-- Modal: Perpanjang -->
<div class="modal fade" id="modalExtend" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-plus me-2 text-warning"></i>Perpanjang Reservasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/reservation/extend') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="reservation_id" value="<?= $id ?>">
        <div class="modal-body">
          <div class="alert alert-info py-2 small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Reservasi dapat diperpanjang maksimal sampai
            <strong><?= date('d M Y', strtotime($reservation['created_at'] . ' +' . ReservationService::MAX_DAYS . ' days')) ?></strong>
            (7 hari dari tanggal buat).
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal Kedaluwarsa Baru <span class="required">*</span></label>
            <input type="date" name="new_expiry_date" class="form-control" required
                   value="<?= e($reservation['expiry_date']) ?>"
                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                   max="<?= date('Y-m-d', strtotime($reservation['created_at'] . ' +' . ReservationService::MAX_DAYS . ' days')) ?>">
            <div class="form-text">Kedaluwarsa saat ini: <?= date('d M Y', $expiryTs) ?></div>
          </div>
          <div class="mb-0">
            <label class="form-label">Alasan Perpanjangan <span class="text-muted">(opsional)</span></label>
            <textarea name="reason" class="form-control" rows="2"
                      placeholder="Misal: Customer masih mempertimbangkan..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-calendar-check me-1"></i>Perpanjang
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Release -->
<div class="modal fade" id="modalRelease" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Lepas Reservasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/reservation/release') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="reservation_id" value="<?= $id ?>">
        <div class="modal-body pt-0">
          <p>Lepas reservasi <strong><?= e($reservation['reservation_no']) ?></strong>?
             Lock berlian akan dilepas dan statusnya kembali menjadi <em>Available</em>.</p>
          <div class="mb-0">
            <label class="form-label">Alasan <span class="text-muted">(opsional)</span></label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="Misal: Customer batal, memilih berlian lain..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-x-circle me-1"></i>Ya, Lepas Reservasi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Konversi ke SO -->
<div class="modal fade" id="modalConvertSO" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-arrow-right-circle me-2 text-success"></i>Konversi ke Sales Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/reservation/convert') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="reservation_id" value="<?= $id ?>">
        <div class="modal-body">
          <p>Konversi reservasi <strong><?= e($reservation['reservation_no']) ?></strong>
             atas nama <strong><?= e($reservation['customer_name']) ?></strong>
             menjadi Sales Order?</p>
          <ul class="small text-muted ps-3 mb-0">
            <li>Status reservasi akan berubah menjadi <em>Dikonversi</em>.</li>
            <li>Sales Order baru akan dibuat dengan status <em>Draft</em>.</li>
            <li>Berlian tetap ter-lock sampai Sales Order disetujui.</li>
          </ul>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-lg me-1"></i>Ya, Buat Sales Order
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
$(function () {
  $('#btnExtend').on('click', function () {
    new bootstrap.Modal(document.getElementById('modalExtend')).show();
  });
  $('#btnRelease').on('click', function () {
    new bootstrap.Modal(document.getElementById('modalRelease')).show();
  });
  $('#btnConvertSO').on('click', function () {
    new bootstrap.Modal(document.getElementById('modalConvertSO')).show();
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
