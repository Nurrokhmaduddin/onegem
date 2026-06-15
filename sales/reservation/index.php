<?php
/**
 * sales/reservation/index.php
 * Halaman utama Reservasi — tampilan khusus reservasi AKTIF dengan countdown expiry
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

// Hanya ambil reservasi aktif, urutkan yang paling dekat expired dahulu
$activeReservations = ReservationRepository::getAll(
    ['status' => 'active'],
    100, 0
);
$statusCounts  = ReservationRepository::getStatusCounts();
$expiringToday = ReservationRepository::getExpiringToday();

$pageTitle   = 'Reservasi Aktif';
$breadcrumbs = [['label' => 'Penjualan'], ['label' => 'Reservasi']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Alert expiring today -->
<?php if (!empty($expiringToday)): ?>
<div class="alert alert-danger d-flex align-items-start gap-2 mb-4 py-3" role="alert">
  <i class="bi bi-alarm fs-5 flex-shrink-0 mt-1"></i>
  <div>
    <strong><?= count($expiringToday) ?> reservasi kedaluwarsa hari ini!</strong>
    <div class="mt-1 d-flex flex-wrap gap-2">
      <?php foreach ($expiringToday as $exp): ?>
        <a href="<?= url('sales/reservation/detail') ?>?id=<?= $exp['id'] ?>"
           class="badge bg-danger text-white text-decoration-none">
          <?= e($exp['reservation_no']) ?> — <?= e($exp['customer_name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Status Summary Cards -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['label' => 'Aktif',       'key' => 'active',    'icon' => 'bi-bookmark-check-fill', 'color' => '#185FA5', 'bg' => '#EFF6FF'],
    ['label' => 'Dilepas',     'key' => 'released',  'icon' => 'bi-bookmark-x',          'color' => '#6B7280', 'bg' => '#F9FAFB'],
    ['label' => 'Kedaluwarsa', 'key' => 'expired',   'icon' => 'bi-clock-history',       'color' => '#B45309', 'bg' => '#FFFBEB'],
    ['label' => 'Dikonversi',  'key' => 'converted', 'icon' => 'bi-arrow-right-circle',  'color' => '#15803D', 'bg' => '#F0FDF4'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-md-3">
    <a href="<?= url('sales/reservation/list') ?>?status=<?= $c['key'] ?>"
       class="text-decoration-none">
      <div class="card h-100 p-3 text-center border-0"
           style="background:<?= $c['bg'] ?>">
        <i class="<?= $c['icon'] ?> fs-3 mb-1" style="color:<?= $c['color'] ?>"></i>
        <div class="fw-700 fs-3" style="color:<?= $c['color'] ?>">
          <?= number_format($statusCounts[$c['key']] ?? 0) ?>
        </div>
        <div class="small" style="color:<?= $c['color'] ?>;opacity:.75"><?= $c['label'] ?></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- Toolbar -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <h6 class="mb-0 fw-600">
    <i class="bi bi-bookmark-check me-2 text-primary"></i>
    Reservasi Aktif
    <span class="badge bg-primary-subtle text-primary ms-1">
      <?= number_format($statusCounts['active'] ?? 0) ?>
    </span>
  </h6>
  <div class="d-flex gap-2">
    <a href="<?= url('sales/reservation/list') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-list-ul me-1"></i>Semua Reservasi
    </a>
    <?php if (can('RESERVATION_CREATE')): ?>
    <a href="<?= url('sales/reservation/create') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Buat Reservasi
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($activeReservations)): ?>
<div class="table-card">
  <div class="table-empty">
    <i class="bi bi-bookmark text-muted"></i>
    <p class="mb-2 text-muted">Tidak ada reservasi aktif saat ini.</p>
    <?php if (can('RESERVATION_CREATE')): ?>
    <a href="<?= url('sales/reservation/create') ?>" class="btn btn-primary btn-sm">
      Buat Reservasi Baru
    </a>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>

<!-- Active Reservation Cards Grid -->
<div class="row g-3" id="reservationGrid">
  <?php foreach ($activeReservations as $rsv):
    $expiryTs  = strtotime($rsv['expiry_date']);
    $daysLeft  = (int) ceil(($expiryTs - time()) / 86400);
    $isUrgent  = $daysLeft <= 1;
    $isWarning = $daysLeft <= 3 && !$isUrgent;
    $cardBorder = $isUrgent ? 'border-danger' : ($isWarning ? 'border-warning' : '');
  ?>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100 <?= $cardBorder ?> <?= $isUrgent ? 'border-2' : '' ?>">
      <!-- Card Header -->
      <div class="card-header d-flex align-items-center justify-content-between py-2
                  <?= $isUrgent ? 'bg-danger-subtle' : ($isWarning ? 'bg-warning-subtle' : '') ?>">
        <a href="<?= url('sales/reservation/detail') ?>?id=<?= $rsv['id'] ?>"
           class="fw-600 text-decoration-none font-mono text-dark" style="font-size:13px">
          <?= e($rsv['reservation_no']) ?>
        </a>
        <span class="badge <?= $isUrgent ? 'bg-danger' : ($isWarning ? 'bg-warning text-dark' : 'bg-success') ?>">
          <?php if ($daysLeft <= 0): ?>
            Hari ini!
          <?php elseif ($daysLeft === 1): ?>
            Besok
          <?php else: ?>
            <?= $daysLeft ?> hari lagi
          <?php endif; ?>
        </span>
      </div>

      <div class="card-body py-3">
        <!-- Customer -->
        <div class="d-flex align-items-start gap-2 mb-2">
          <i class="bi bi-person-circle text-muted mt-1" style="font-size:18px;flex-shrink:0"></i>
          <div>
            <div class="fw-600 lh-1"><?= e($rsv['customer_name'] ?? '—') ?></div>
            <?php if ($rsv['quotation_no']): ?>
              <div class="small text-muted mt-1">
                dari <a href="<?= url('sales/quotation/detail') ?>?id=<?= e($rsv['quotation_no']) ?>"
                        class="text-decoration-none">
                  <?= e($rsv['quotation_no']) ?>
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Items count & value -->
        <div class="row g-2 mb-2">
          <div class="col-6">
            <div class="rounded p-2 text-center" style="background:#F8FAFC">
              <div class="fw-700 fs-5 text-primary"><?= $rsv['item_count'] ?></div>
              <div class="small text-muted" style="font-size:11px">Berlian</div>
            </div>
          </div>
          <div class="col-6">
            <div class="rounded p-2 text-center" style="background:#F8FAFC">
              <div class="fw-600 text-dark" style="font-size:13px">
                $<?= number_format((float)$rsv['total_usd'], 0) ?>
              </div>
              <div class="small text-muted" style="font-size:11px">Total (USD)</div>
            </div>
          </div>
        </div>

        <!-- Expiry info -->
        <div class="d-flex align-items-center gap-1 mb-2 small">
          <i class="bi bi-calendar-x text-muted"></i>
          <span class="text-muted">Kedaluwarsa:</span>
          <span class="fw-500 <?= $isUrgent ? 'text-danger' : ($isWarning ? 'text-warning' : '') ?>">
            <?= date('d M Y', $expiryTs) ?>
          </span>
        </div>

        <!-- Salesperson -->
        <?php if ($rsv['salesperson_name']): ?>
        <div class="d-flex align-items-center gap-1 small text-muted">
          <i class="bi bi-person"></i>
          <?= e($rsv['salesperson_name']) ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Card Footer: actions -->
      <div class="card-footer bg-transparent py-2 d-flex gap-2">
        <a href="<?= url('sales/reservation/detail') ?>?id=<?= $rsv['id'] ?>"
           class="btn btn-sm btn-outline-secondary flex-fill">
          <i class="bi bi-eye me-1"></i>Detail
        </a>
        <?php if (can('RESERVATION_CREATE')): ?>
        <button type="button"
          class="btn btn-sm btn-success flex-fill btn-convert-so"
          data-id="<?= $rsv['id'] ?>"
          data-no="<?= e($rsv['reservation_no']) ?>"
          data-customer="<?= e($rsv['customer_name']) ?>">
          <i class="bi bi-arrow-right-circle me-1"></i>Buat SO
        </button>
        <?php endif; ?>
        <?php if (can('RESERVATION_RELEASE')): ?>
        <button type="button"
          class="btn btn-sm btn-outline-danger btn-release"
          data-id="<?= $rsv['id'] ?>"
          data-no="<?= e($rsv['reservation_no']) ?>"
          title="Lepas Reservasi">
          <i class="bi bi-x-circle"></i>
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal: Konversi ke SO -->
<div class="modal fade" id="modalConvertSO" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-arrow-right-circle me-2 text-success"></i>Buat Sales Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/reservation/convert') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="reservation_id" id="soReservationId">
        <div class="modal-body">
          <p class="mb-0">
            Konversi reservasi <strong id="soReservationNo"></strong>
            atas nama <strong id="soCustomerName"></strong> menjadi Sales Order?
          </p>
          <div class="alert alert-info mt-3 py-2 small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Status reservasi akan berubah menjadi <em>Dikonversi</em>.
            Berlian tetap ter-lock sampai Sales Order disetujui.
          </div>
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
        <input type="hidden" name="reservation_id" id="releaseReservationId">
        <div class="modal-body pt-0">
          <p>Lepas reservasi <strong id="releaseReservationNo"></strong>?
             Lock berlian akan dilepas dan status menjadi <em>Available</em> kembali.</p>
          <div class="mb-0">
            <label class="form-label">Alasan Pelepasan <span class="text-muted">(opsional)</span></label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Misal: Customer batal, memilih berlian lain..."></textarea>
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

<?php
$extraJs = <<<'JS'
<script>
$(function () {
  // Convert to SO
  $(document).on('click', '.btn-convert-so', function () {
    $('#soReservationId').val($(this).data('id'));
    $('#soReservationNo').text($(this).data('no'));
    $('#soCustomerName').text($(this).data('customer'));
    new bootstrap.Modal(document.getElementById('modalConvertSO')).show();
  });

  // Release
  $(document).on('click', '.btn-release', function () {
    $('#releaseReservationId').val($(this).data('id'));
    $('#releaseReservationNo').text($(this).data('no'));
    $('textarea[name=notes]').val('');
    new bootstrap.Modal(document.getElementById('modalRelease')).show();
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
