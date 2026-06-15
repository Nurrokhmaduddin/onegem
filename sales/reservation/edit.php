<?php
/**
 * sales/reservation/edit.php — Edit reservasi aktif (expiry_date & notes)
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
require_permission('RESERVATION_EDIT');

$id          = (int) get_param('id', 0);
$reservation = ReservationRepository::findById($id);
if (!$reservation) not_found('Reservasi tidak ditemukan.');
if ($reservation['status'] !== 'active')
    redirect_with_error(url('sales/reservation/detail') . '?id=' . $id,
        'Hanya reservasi aktif yang dapat diedit.');

$errors   = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

$maxExpiry = date('Y-m-d', strtotime($reservation['created_at'] . ' +' . ReservationService::MAX_DAYS . ' days'));
$form = array_merge([
    'expiry_date' => $reservation['expiry_date'],
    'notes'       => $reservation['notes'],
], $formData);

$pageTitle   = 'Edit Reservasi — ' . $reservation['reservation_no'];
$breadcrumbs = [
    ['label' => 'Penjualan'],
    ['label' => 'Reservasi', 'url' => url('sales/reservation')],
    ['label' => $reservation['reservation_no'], 'url' => url('sales/reservation/detail') . '?id=' . $id],
    ['label' => 'Edit'],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4 justify-content-center">
<div class="col-lg-7">

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <i class="bi bi-exclamation-triangle me-2"></i><strong>Periksa kembali:</strong>
  <ul class="mb-0 mt-1 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-bookmark-check me-2 text-primary"></i>
    Edit Reservasi — <span class="font-mono"><?= e($reservation['reservation_no']) ?></span>
  </div>
  <div class="card-body p-4">
    <form method="POST" action="<?= url('sales/reservation/update') ?>" class="no-double-submit">
      <?= csrf_field() ?>
      <input type="hidden" name="reservation_id" value="<?= $id ?>">

      <div class="mb-3">
        <label class="form-label">Customer</label>
        <input type="text" class="form-control bg-light" value="<?= e($reservation['customer_name']) ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">
          Tanggal Kedaluwarsa <span class="required">*</span>
          <span class="text-muted small">(maks <?= date('d M Y', strtotime($maxExpiry)) ?>)</span>
        </label>
        <input type="date" name="expiry_date" class="form-control"
               value="<?= e($form['expiry_date']) ?>"
               min="<?= date('Y-m-d') ?>"
               max="<?= $maxExpiry ?>" required>
        <div class="form-text">Saat ini: <?= date('d M Y', strtotime($reservation['expiry_date'])) ?></div>
      </div>

      <div class="mb-4">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control" rows="3"
                  placeholder="Catatan untuk customer atau internal..."><?= e($form['notes']) ?></textarea>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="<?= url('sales/reservation/detail') ?>?id=<?= $id ?>"
           class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Item list (read-only) -->
<div class="card">
  <div class="card-header"><i class="bi bi-gem me-2 text-primary"></i>Berlian dalam Reservasi ini</div>
  <div class="card-body p-0">
    <?php
    $items = ReservationRepository::getItems($id);
    if (empty($items)): ?>
    <div class="p-4 text-center text-muted small">Tidak ada item berlian.</div>
    <?php else: ?>
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>SKU</th><th>Spesifikasi</th><th>Sertifikat</th><th>Harga (USD)</th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td class="fw-600 font-mono"><?= e($item['sku']) ?></td>
          <td><?= e($item['carat']) ?>ct · <?= e($item['color']) ?> · <?= e($item['clarity']) ?> · <?= e($item['cut']) ?></td>
          <td class="small"><?= $item['certificate_no'] ? e($item['lab'] . ' ' . $item['certificate_no']) : '—' ?></td>
          <td class="fw-500 text-primary">$<?= number_format((float)$item['price_usd'], 0) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="px-3 py-2 border-top text-end">
      <span class="fw-600">Total: </span>
      <span class="fw-700 text-primary">$<?= number_format((float)$reservation['total_usd'], 0) ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>

</div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
