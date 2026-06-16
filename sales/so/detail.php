<?php
/**
 * sales/so/detail.php
 * Detail Sales Order — info lengkap, item berlian, history, action buttons
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
require_permission('SO_VIEW');

$id = (int) get_param('id', 0);
$so = SalesOrderRepository::findById($id);
if (!$so) not_found('Sales Order tidak ditemukan.');

$items = SalesOrderRepository::getItems($id);

$history = Database::fetchAll(
    "SELECT sosh.*, u.full_name AS actor_name
       FROM sales_order_state_histories sosh
  LEFT JOIN users u ON u.id = sosh.actor_id
      WHERE sosh.sales_order_id = ?
      ORDER BY sosh.created_at ASC",
    [$id]
);

$statusConfig = [
    'draft'     => ['label' => 'Draft',             'color' => 'secondary', 'icon' => 'bi-file-earmark'],
    'submitted' => ['label' => 'Menunggu Approval', 'color' => 'warning',   'icon' => 'bi-hourglass-split'],
    'approved'  => ['label' => 'Disetujui',         'color' => 'success',   'icon' => 'bi-check-circle-fill'],
    'cancelled' => ['label' => 'Dibatalkan',        'color' => 'danger',    'icon' => 'bi-x-circle-fill'],
    'completed' => ['label' => 'Selesai',           'color' => 'primary',   'icon' => 'bi-trophy-fill'],
];
$sc = $statusConfig[$so['status']] ?? ['label' => $so['status'], 'color' => 'secondary', 'icon' => 'bi-circle'];

// Cek invoice jika SO sudah approved (Sprint 5)
$invoice = null;
// $invoice = Database::fetchOne("SELECT id, invoice_no FROM sales_invoices WHERE so_id=?", [$id]);

$pageTitle   = 'Detail SO — ' . $so['so_no'];
$breadcrumbs = [
    ['label' => 'Penjualan'],
    ['label' => 'Sales Order', 'url' => url('sales/so/list')],
    ['label' => $so['so_no']],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<?php flash_show(); ?>

<!-- Header bar -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h4 class="mb-0 fw-700 font-mono"><?= e($so['so_no']) ?></h4>
      <span class="badge bg-<?= $sc['color'] ?>-subtle text-<?= $sc['color'] ?> border border-<?= $sc['color'] ?>-subtle fs-6">
        <i class="<?= $sc['icon'] ?> me-1"></i><?= $sc['label'] ?>
      </span>
    </div>
    <div class="text-muted small mt-1">
      Dibuat <?= format_datetime($so['created_at']) ?>
      oleh <?= e($so['created_by_name'] ?? '—') ?>
      <?php if ($so['reservation_no']): ?>
        · dari Reservasi
        <a href="<?= url('sales/reservation/detail') ?>?id=<?= $so['reservation_id'] ?>"
           class="text-decoration-none"><?= e($so['reservation_no']) ?></a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($so['status'] === 'draft' && can('SO_EDIT')): ?>
    <a href="<?= url('sales/so/edit') ?>?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <button type="button" class="btn btn-primary btn-sm" id="btnSubmit">
      <i class="bi bi-send me-1"></i>Ajukan Approval
    </button>
    <?php endif; ?>

    <?php if ($so['status'] === 'submitted' && can('SO_APPROVE')): ?>
    <button type="button" class="btn btn-success btn-sm" id="btnApprove">
      <i class="bi bi-check-lg me-1"></i>Setujui
    </button>
    <button type="button" class="btn btn-outline-warning btn-sm" id="btnReject">
      <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan
    </button>
    <?php endif; ?>

    <?php if ($so['status'] === 'approved' && can('SO_COMPLETE')): ?>
    <button type="button" class="btn btn-primary btn-sm" id="btnComplete">
      <i class="bi bi-trophy me-1"></i>Tandai Selesai
    </button>
    <?php endif; ?>

    <?php if (in_array($so['status'], ['draft','submitted']) && can('SO_CANCEL')): ?>
    <button type="button" class="btn btn-outline-danger btn-sm" id="btnCancel">
      <i class="bi bi-x-circle me-1"></i>Batalkan SO
    </button>
    <?php endif; ?>

    <?php if ($so['status'] === 'approved' && !$invoice && can('INVOICE_CREATE')): ?>
    <a href="<?= url('sales/invoice/create') ?>?so_id=<?= $id ?>"
       class="btn btn-warning btn-sm">
      <i class="bi bi-receipt me-1"></i>Buat Invoice
    </a>
    <?php endif; ?>
    <?php if ($invoice): ?>
    <a href="<?= url('sales/invoice/detail') ?>?id=<?= $invoice['id'] ?>"
       class="btn btn-outline-primary btn-sm">
      <i class="bi bi-receipt me-1"></i>Lihat Invoice
    </a>
    <?php endif; ?>

    <a href="<?= url('sales/so/list') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
  </div>
</div>

<!-- Status notices -->
<?php if ($so['status'] === 'cancelled'): ?>
<div class="alert alert-danger mb-4">
  <i class="bi bi-x-circle me-2"></i>Sales Order ini telah <strong>dibatalkan</strong>.
  Berlian telah dilepas kembali ke status Available.
</div>
<?php elseif ($so['status'] === 'completed'): ?>
<div class="alert alert-primary mb-4">
  <i class="bi bi-trophy me-2"></i>Sales Order ini telah <strong>selesai</strong>.
  Berlian sudah terkirim ke customer.
</div>
<?php endif; ?>

<div class="row g-4">
<!-- Kolom Utama -->
<div class="col-lg-8">

  <!-- Info Dasar -->
  <div class="card mb-4">
    <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Sales Order</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="detail-label">Customer</div>
          <div class="detail-value fw-600"><?= e($so['customer_name'] ?? '—') ?></div>
          <div class="small text-muted">
            <?= e($so['customer_code'] ?? '') ?>
            <?php if ($so['customer_phone']): ?> · <?= e($so['customer_phone']) ?><?php endif; ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="detail-label">Salesperson</div>
          <div class="detail-value"><?= e($so['salesperson_name'] ?? '—') ?></div>
        </div>
        <div class="col-md-6">
          <div class="detail-label">Kurs (USD/IDR)</div>
          <div class="detail-value">Rp <?= number_format((float) $so['rate_usd_idr'], 0, ',', '.') ?></div>
        </div>
        <?php if ($so['approved_by']): ?>
        <div class="col-md-6">
          <div class="detail-label">Disetujui oleh</div>
          <div class="detail-value">
            <?= e($so['approved_by_name']) ?>
            <span class="text-muted small ms-1"><?= format_datetime($so['approved_at']) ?></span>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($so['notes']): ?>
        <div class="col-12">
          <div class="detail-label">Catatan</div>
          <div class="detail-value"><?= nl2br(e($so['notes'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Item Berlian -->
  <div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
      <span><i class="bi bi-gem me-2 text-primary"></i>Berlian dalam Sales Order</span>
      <span class="badge bg-primary-subtle text-primary"><?= count($items) ?> pcs</span>
    </div>
    <?php if (empty($items)): ?>
    <div class="card-body text-center py-5 text-muted">
      <i class="bi bi-gem opacity-25 fs-2 d-block mb-2"></i>
      Belum ada berlian.
      <?php if ($so['status'] === 'draft' && can('SO_EDIT')): ?>
      <div class="mt-2">
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddDiamond">
          <i class="bi bi-plus-lg me-1"></i>Tambah Berlian
        </button>
      </div>
      <?php endif; ?>
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
            <th>Harga (IDR)</th>
            <th>Status</th>
            <?php if ($so['status'] === 'draft' && can('SO_EDIT')): ?>
            <th></th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i => $item):
            $dstatus = $item['diamond_status'];
            $dcolor  = match($dstatus) {
              'reserved'  => 'warning',
              'available' => 'success',
              'sold'      => 'danger',
              'delivered' => 'primary',
              default     => 'secondary',
            };
          ?>
          <tr>
            <td class="text-muted small"><?= $i + 1 ?></td>
            <td>
              <a href="<?= url('operation/diamond/detail') ?>?id=<?= $item['diamond_id'] ?>"
                 class="fw-600 font-mono text-decoration-none text-dark">
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
              <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
            <td class="fw-600 text-primary">$<?= number_format((float) $item['price_usd'], 0) ?></td>
            <td class="small text-muted">Rp <?= number_format((float) $item['price_idr'], 0, ',', '.') ?></td>
            <td>
              <span class="badge bg-<?= $dcolor ?>-subtle text-<?= $dcolor ?> border border-<?= $dcolor ?>-subtle">
                <?= ucfirst($dstatus) ?>
              </span>
            </td>
            <?php if ($so['status'] === 'draft' && can('SO_EDIT')): ?>
            <td>
              <button class="btn btn-icon btn-outline-danger btn-sm btn-remove-item"
                      data-item-id="<?= $item['id'] ?>"
                      data-sku="<?= e($item['sku']) ?>"
                      title="Hapus item">
                <i class="bi bi-trash"></i>
              </button>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <td colspan="<?= ($so['status']==='draft' && can('SO_EDIT')) ? 4 : 3 ?>"
                class="text-end fw-600">Total</td>
            <td class="fw-700 text-primary fs-6">$<?= number_format((float) $so['total_usd'], 0) ?></td>
            <td class="text-muted fw-500">Rp <?= number_format((float) $so['total_idr'], 0, ',', '.') ?></td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php if ($so['status'] === 'draft' && can('SO_EDIT')): ?>
    <div class="card-footer bg-transparent">
      <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddDiamond">
        <i class="bi bi-plus-lg me-1"></i>Tambah Berlian
      </button>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

</div>

<!-- Sidebar kanan -->
<div class="col-lg-4">

  <!-- Summary -->
  <div class="card mb-3 border-0" style="background:#EFF6FF">
    <div class="card-body p-4 text-center">
      <div class="small text-muted mb-1">Total Nilai</div>
      <div class="fw-700 text-primary" style="font-size:1.6rem">
        $<?= number_format((float) $so['total_usd'], 0) ?>
      </div>
      <div class="text-muted small mb-3">
        Rp <?= number_format((float) $so['total_idr'], 0, ',', '.') ?>
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
            <div class="fw-700 fs-5">
              <?= number_format((float)$so['total_usd'] / max(1, count($items)), 0) ?>
            </div>
            <div class="small text-muted" style="font-size:11px">Avg USD/pcs</div>
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
      <?php foreach ($history as $h):
        $isCurrent = end($history) === $h;
        $sc2 = $statusConfig[$h['to_status']] ?? ['color' => 'secondary'];
      ?>
      <div class="px-4 py-3 <?= !$isCurrent ? 'border-bottom' : '' ?>">
        <div class="d-flex align-items-start gap-2">
          <div class="mt-1 rounded-circle flex-shrink-0"
               style="width:10px;height:10px;background:<?= $isCurrent ? 'var(--erp-primary)' : '#CBD5E1' ?>"></div>
          <div class="flex-fill">
            <div class="small fw-600">
              <?php if ($h['from_status']): ?>
                <span class="text-muted"><?= e($h['from_status']) ?></span>
                <i class="bi bi-arrow-right mx-1 text-muted"></i>
              <?php endif; ?>
              <span class="text-<?= $sc2['color'] ?>"><?= e($h['to_status']) ?></span>
            </div>
            <div class="small text-muted">
              <?= e($h['actor_name'] ?? 'Sistem') ?> ·
              <?= format_datetime($h['created_at']) ?>
            </div>
            <?php if ($h['notes']): ?>
            <div class="small text-muted mt-1 fst-italic">"<?= e($h['notes']) ?>"</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>
</div>

<!-- ═══════════════ MODALS ═══════════════ -->

<!-- Modal Submit -->
<div class="modal fade" id="modalSubmit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-send me-2 text-primary"></i>Ajukan Approval</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/so/submit') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="so_id" value="<?= $id ?>">
        <div class="modal-body">
          <p>Ajukan <strong><?= e($so['so_no']) ?></strong> untuk persetujuan manager?</p>
          <div class="alert alert-info py-2 small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            SO harus memiliki minimal 1 berlian sebelum dapat diajukan.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1"></i>Ya, Ajukan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Approve -->
<div class="modal fade" id="modalApprove" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-success"><i class="bi bi-check-circle me-2"></i>Setujui Sales Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/so/approve') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="so_id" value="<?= $id ?>">
        <div class="modal-body">
          <p>Setujui Sales Order <strong><?= e($so['so_no']) ?></strong>?</p>
          <ul class="small text-muted ps-3 mb-3">
            <li>Status SO berubah menjadi <strong>Disetujui</strong>.</li>
            <li>Status semua berlian dalam SO berubah menjadi <strong>Sold</strong>.</li>
            <li>SO yang sudah disetujui dapat dibuatkan Invoice.</li>
          </ul>
          <div class="mb-0">
            <label class="form-label">Catatan Approval <span class="text-muted">(opsional)</span></label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="Catatan dari manager..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-lg me-1"></i>Ya, Setujui
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Reject / Kembalikan -->
<div class="modal fade" id="modalReject" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-warning">
          <i class="bi bi-arrow-counterclockwise me-2"></i>Kembalikan ke Draft
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/so/reject') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="so_id" value="<?= $id ?>">
        <div class="modal-body">
          <p>Kembalikan SO <strong><?= e($so['so_no']) ?></strong> ke status Draft untuk direvisi?</p>
          <div class="mb-0">
            <label class="form-label">Alasan <span class="required">*</span></label>
            <textarea name="reason" class="form-control" rows="3" required
                      placeholder="Tuliskan alasan pengembalian..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Cancel -->
<div class="modal fade" id="modalCancel" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Batalkan Sales Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/so/cancel') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="so_id" value="<?= $id ?>">
        <div class="modal-body pt-0">
          <p>Batalkan Sales Order <strong><?= e($so['so_no']) ?></strong>?</p>
          <div class="alert alert-warning py-2 small mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Semua berlian dalam SO akan dibebaskan kembali ke status <strong>Available</strong>.
          </div>
          <div class="mb-0">
            <label class="form-label">Alasan <span class="text-muted">(opsional)</span></label>
            <textarea name="reason" class="form-control" rows="2"
                      placeholder="Misal: Customer batal, harga tidak cocok..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-x-circle me-1"></i>Ya, Batalkan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Complete -->
<div class="modal fade" id="modalComplete" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-primary"><i class="bi bi-trophy me-2"></i>Tandai Selesai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/so/complete') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="so_id" value="<?= $id ?>">
        <div class="modal-body">
          <p>Tandai Sales Order <strong><?= e($so['so_no']) ?></strong> sebagai selesai?</p>
          <div class="alert alert-info py-2 small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Status berlian akan berubah menjadi <strong>Delivered</strong>.
          </div>
          <div class="mb-0">
            <label class="form-label">Catatan Pengiriman <span class="text-muted">(opsional)</span></label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="Misal: Dikirim via JNE, resi #123..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-trophy me-1"></i>Ya, Tandai Selesai
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Tambah Berlian (draft only) -->
<?php if ($so['status'] === 'draft' && can('SO_EDIT')): ?>
<div class="modal fade" id="modalAddDiamond" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-gem me-2 text-primary"></i>Tambah Berlian ke SO</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" id="diamondSearchSO" class="form-control"
                 placeholder="Cari SKU, warna, clarity, nomor sertifikat...">
        </div>
        <div id="diamondResultsSO" style="min-height:180px">
          <div class="text-center py-4 text-muted">
            <i class="bi bi-search fs-2 d-block mb-2 opacity-25"></i>Ketik untuk mencari.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraJs = <<<JS
<script>
$(function () {
  // Modal triggers
  $('#btnSubmit').on('click',   () => new bootstrap.Modal(document.getElementById('modalSubmit')).show());
  $('#btnApprove').on('click',  () => new bootstrap.Modal(document.getElementById('modalApprove')).show());
  $('#btnReject').on('click',   () => new bootstrap.Modal(document.getElementById('modalReject')).show());
  $('#btnCancel').on('click',   () => new bootstrap.Modal(document.getElementById('modalCancel')).show());
  $('#btnComplete').on('click', () => new bootstrap.Modal(document.getElementById('modalComplete')).show());
  $('#btnAddDiamond').on('click', function () {
    $('#diamondSearchSO').val('');
    $('#diamondResultsSO').html('<div class="text-center py-4 text-muted"><i class="bi bi-search fs-2 d-block mb-2 opacity-25"></i>Ketik untuk mencari.</div>');
    new bootstrap.Modal(document.getElementById('modalAddDiamond')).show();
    setTimeout(() => $('#diamondSearchSO').focus(), 300);
  });

  // Search diamond untuk SO
  let dsTimer;
  const BASE = document.querySelector('meta[name="base-url"]')?.content ?? '';
  const soId = {$id};

  $('#diamondSearchSO').on('input', function () {
    clearTimeout(dsTimer);
    const q = $(this).val().trim();
    dsTimer = setTimeout(() => {
      if (q.length < 2) return;
      $.get(BASE + '/ajax/so/search-diamond', { q }, function (res) {
        if (!res.success || !res.data.length) {
          $('#diamondResultsSO').html('<div class="text-center py-4 text-muted">Tidak ada berlian available.</div>');
          return;
        }
        const rows = res.data.map(d => `
          <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <div>
              <span class="fw-600 font-mono">\${d.sku}</span>
              <span class="ms-2 text-muted small">\${d.carat}ct · \${d.color} · \${d.clarity} · \${d.cut}</span>
              \${d.certificate_no ? `<span class="badge bg-light text-dark border ms-1" style="font-size:10px">\${d.lab} \${d.certificate_no}</span>` : ''}
            </div>
            <div class="d-flex align-items-center gap-3">
              <span class="fw-600 text-primary">\$\${parseFloat(d.price_usd).toLocaleString()}</span>
              <button type="button" class="btn btn-sm btn-primary btn-add-so-diamond"
                      data-id="\${d.id}" data-sku="\${d.sku}">Pilih</button>
            </div>
          </div>
        `).join('');
        $('#diamondResultsSO').html(rows);
      }, 'json');
    }, 300);
  });

  $(document).on('click', '.btn-add-so-diamond', function () {
    const btn = $(this);
    btn.prop('disabled', true).text('Menambahkan...');
    $.post(BASE + '/ajax/so/add-item', {
      so_id: soId,
      diamond_id: btn.data('id'),
      _token: $('input[name=_token]').first().val()
    }, function (res) {
      if (res.success) {
        location.reload();
      } else {
        alert(res.message || 'Gagal menambahkan berlian.');
        btn.prop('disabled', false).text('Pilih');
      }
    }, 'json').fail(() => {
      alert('Terjadi kesalahan. Silakan coba lagi.');
      btn.prop('disabled', false).text('Pilih');
    });
  });

  // Remove item
  $(document).on('click', '.btn-remove-item', function () {
    const sku    = $(this).data('sku');
    const itemId = $(this).data('item-id');
    if (!confirm('Hapus berlian ' + sku + ' dari Sales Order ini?')) return;
    $.post(BASE + '/ajax/so/remove-item', {
      item_id: itemId,
      so_id: soId,
      _token: $('input[name=_token]').first().val()
    }, function (res) {
      if (res.success) location.reload();
      else alert(res.message || 'Gagal menghapus item.');
    }, 'json');
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
