<?php
/**
 * sales/quotation/detail.php — Detail Quotation
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
require_permission('QUOTATION_VIEW');

$quotationId = (int)get_param('id');
$quotation   = QuotationRepository::findById($quotationId);
if (!$quotation) { flash_set('error','Quotation tidak ditemukan.'); redirect(url('sales/quotation')); }

$items     = QuotationRepository::getItems($quotationId);
$histories = QuotationRepository::getStateHistories($quotationId);

$statusConfig = [
    'draft'     => ['label'=>'Draft',             'color'=>'secondary'],
    'submitted' => ['label'=>'Diajukan',           'color'=>'info'],
    'approved'  => ['label'=>'Disetujui',          'color'=>'primary'],
    'rejected'  => ['label'=>'Ditolak',            'color'=>'danger'],
    'accepted'  => ['label'=>'Diterima Customer',  'color'=>'success'],
    'converted' => ['label'=>'Konversi Reservasi', 'color'=>'dark'],
    'cancelled' => ['label'=>'Dibatalkan',         'color'=>'warning'],
];
$sc = $statusConfig[$quotation['status']] ?? $statusConfig['draft'];

$pageTitle   = 'Detail Quotation — ' . $quotation['quotation_no'];
$breadcrumbs = [
    ['label'=>'Penjualan'],
    ['label'=>'Quotation','url'=>url('sales/quotation')],
    ['label'=>e($quotation['quotation_no'])],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">

  <!-- Kolom kiri: info quotation -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body p-4">
        <div class="font-mono fw-700 fs-5 text-primary mb-1"><?= e($quotation['quotation_no']) ?></div>
        <div class="mb-3">
          <span class="badge bg-<?=$sc['color']?>-subtle text-<?=$sc['color']?> border border-<?=$sc['color']?>-subtle fs-6">
            <?= e($sc['label']) ?>
          </span>
        </div>

        <table class="table table-borderless table-sm mb-0" style="font-size:13px">
          <tr>
            <td class="text-muted" width="45%">Customer</td>
            <td>
              <?php if ($quotation['customer_id']): ?>
                <a href="<?= url('master/customer/detail') ?>?id=<?= $quotation['customer_id'] ?>"
                   class="text-decoration-none fw-500">
                  <?= e($quotation['customer_name']) ?>
                </a>
              <?php elseif ($quotation['lead_id']): ?>
                <span class="fw-500"><?= e($quotation['lead_name']) ?></span>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1" style="font-size:10px">Lead</span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <td class="text-muted">Salesperson</td>
            <td><?= e($quotation['salesperson_name'] ?? '—') ?></td>
          </tr>
          <tr>
            <td class="text-muted">Tanggal</td>
            <td><?= format_date($quotation['quotation_date']) ?></td>
          </tr>
          <tr>
            <td class="text-muted">Berlaku s/d</td>
            <td class="<?= ($quotation['valid_until'] && $quotation['valid_until'] < date('Y-m-d') && !in_array($quotation['status'],['converted','cancelled'])) ? 'text-danger fw-600' : '' ?>">
              <?= $quotation['valid_until'] ? format_date($quotation['valid_until']) : '—' ?>
            </td>
          </tr>
          <tr>
            <td class="text-muted">Kurs</td>
            <td class="font-mono">Rp <?= number_format((float)$quotation['rate_usd_idr'],0,',','.') ?></td>
          </tr>
          <?php if ($quotation['approved_by']): ?>
          <tr>
            <td class="text-muted">Disetujui</td>
            <td><?= e($quotation['approved_by_name']) ?></td>
          </tr>
          <?php endif; ?>
          <?php if ($quotation['reject_reason']): ?>
          <tr>
            <td class="text-muted">Alasan Tolak</td>
            <td class="text-danger small"><?= e($quotation['reject_reason']) ?></td>
          </tr>
          <?php endif; ?>
        </table>
      </div>

      <!-- Harga total -->
      <div class="card-body border-top p-3">
        <div class="d-flex justify-content-between small mb-1">
          <span class="text-muted">Subtotal USD</span>
          <span>$<?= number_format((float)$quotation['subtotal_usd'],2) ?></span>
        </div>
        <?php if ($quotation['discount_usd'] > 0): ?>
        <div class="d-flex justify-content-between small mb-1">
          <span class="text-muted">Diskon</span>
          <span class="text-danger">-$<?= number_format((float)$quotation['discount_usd'],2) ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between fw-700 border-top pt-2 mt-1">
          <span>Total USD</span>
          <span class="text-primary fs-6">$<?= number_format((float)$quotation['total_usd'],2) ?></span>
        </div>
        <div class="d-flex justify-content-between small text-muted">
          <span>Total IDR</span>
          <span>Rp <?= number_format((float)$quotation['total_idr'],0,',','.') ?></span>
        </div>
      </div>

      <!-- Tombol aksi -->
      <div class="card-body border-top p-3 d-flex flex-column gap-2">
        <?php if ($quotation['status'] === 'draft' && can('QUOTATION_EDIT')): ?>
          <a href="<?= url('sales/quotation/edit') ?>?id=<?= $quotationId ?>"
             class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
          </a>
          <button class="btn btn-primary btn-sm btn-action" data-action="submit">
            <i class="bi bi-send me-1"></i>Ajukan untuk Approval
          </button>
        <?php endif; ?>
        <?php if ($quotation['status'] === 'submitted' && can('QUOTATION_APPROVE')): ?>
          <button class="btn btn-success btn-sm btn-action" data-action="approve">
            <i class="bi bi-check-circle me-1"></i>Setujui
          </button>
          <button class="btn btn-outline-danger btn-sm btn-action" data-action="reject">
            <i class="bi bi-x-circle me-1"></i>Tolak
          </button>
        <?php endif; ?>
        <?php if ($quotation['status'] === 'approved' && can('QUOTATION_EDIT')): ?>
          <button class="btn btn-success btn-sm btn-action" data-action="accept">
            <i class="bi bi-person-check me-1"></i>Customer Menerima
          </button>
        <?php endif; ?>
        <?php if ($quotation['status'] === 'accepted' && can('RESERVATION_CREATE')): ?>
          <a href="<?= url('sales/reservation/create') ?>?quotation_id=<?= $quotationId ?>"
             class="btn btn-primary btn-sm">
            <i class="bi bi-lock me-1"></i>Buat Reservasi
          </a>
        <?php endif; ?>
        <?php if (!in_array($quotation['status'],['converted','cancelled']) && can('QUOTATION_EDIT')): ?>
          <button class="btn btn-outline-danger btn-sm btn-action" data-action="cancel">
            <i class="bi bi-slash-circle me-1"></i>Batalkan
          </button>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($quotation['notes']): ?>
    <div class="card mb-3">
      <div class="card-header small fw-600"><i class="bi bi-chat-text me-1"></i>Catatan</div>
      <div class="card-body p-3 small"><?= nl2br(e($quotation['notes'])) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Kolom kanan: items + history -->
  <div class="col-lg-8">
    <!-- Tab -->
    <div class="card">
      <div class="card-header p-0">
        <ul class="nav nav-tabs border-0 px-3 pt-2">
          <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tabItems">
              <i class="bi bi-gem me-1"></i>Item Berlian
              <span class="badge bg-secondary-subtle text-secondary border ms-1"><?= count($items) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tabHistory">
              <i class="bi bi-clock-history me-1"></i>Riwayat
            </a>
          </li>
        </ul>
      </div>
      <div class="tab-content">

        <!-- Tab Items -->
        <div class="tab-pane fade show active p-0" id="tabItems">
          <?php if (empty($items)): ?>
            <div class="table-empty" style="padding:40px">
              <i class="bi bi-gem text-muted"></i>
              <p class="small">Belum ada item berlian.</p>
            </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Kode Internal</th>
                  <th>Spesifikasi</th>
                  <th>Sertifikat</th>
                  <th class="text-end">Harga Jual</th>
                  <th class="text-center">Diskon</th>
                  <th class="text-end">Final</th>
                  <?php if ($quotation['status']==='draft' && can('QUOTATION_EDIT')): ?>
                  <th></th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $idx => $item): ?>
                <tr>
                  <td class="text-muted"><?= $idx+1 ?></td>
                  <td>
                    <a href="<?= url('master/diamond/detail') ?>?id=<?= $item['diamond_id'] ?>"
                       class="font-mono fw-600 text-decoration-none text-primary">
                      <?= e($item['internal_code']) ?>
                    </a>
                    <?php
                    $dstColors = ['available'=>'success','reserved'=>'warning','sold'=>'primary'];
                    $dstColor  = $dstColors[$item['diamond_status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?=$dstColor?>-subtle text-<?=$dstColor?> border ms-1" style="font-size:10px">
                      <?= e($item['diamond_status']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1 flex-wrap">
                      <span class="badge bg-light text-dark border"><?= e($item['carat_weight']) ?>ct</span>
                      <?php if ($item['color_grade']): ?>
                        <span class="badge bg-light text-dark border"><?= e($item['color_grade']) ?></span>
                      <?php endif; ?>
                      <?php if ($item['clarity_grade']): ?>
                        <span class="badge bg-light text-dark border"><?= e($item['clarity_grade']) ?></span>
                      <?php endif; ?>
                    </div>
                    <?php if ($item['shape_name']): ?>
                      <div class="small text-muted"><?= e($item['shape_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($item['cert_number']): ?>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:11px">
                        <?= e($item['cert_type']) ?>
                      </span>
                      <div class="font-mono text-muted" style="font-size:10px"><?= e($item['cert_number']) ?></div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">$<?= number_format((float)$item['selling_price_usd'],2) ?></td>
                  <td class="text-center">
                    <?= $item['discount_pct'] > 0
                        ? '<span class="text-danger">'.e($item['discount_pct']).'%</span>'
                        : '—' ?>
                  </td>
                  <td class="text-end">
                    <div class="fw-600">$<?= number_format((float)$item['final_price_usd'],2) ?></div>
                    <div class="small text-muted">Rp <?= number_format((float)$item['final_price_idr'],0,',','.') ?></div>
                  </td>
                  <?php if ($quotation['status']==='draft' && can('QUOTATION_EDIT')): ?>
                  <td>
                    <button type="button"
                      class="btn btn-icon btn-outline-danger btn-sm btn-remove-item"
                      data-id="<?= $item['id'] ?>"
                      data-code="<?= e($item['internal_code']) ?>"
                      title="Hapus item">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                  <?php endif; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="fw-600 table-light">
                  <td colspan="6" class="text-end">Total:</td>
                  <td class="text-end text-primary">
                    $<?= number_format((float)$quotation['total_usd'],2) ?>
                    <div class="small text-muted">Rp <?= number_format((float)$quotation['total_idr'],0,',','.') ?></div>
                  </td>
                  <?php if ($quotation['status']==='draft' && can('QUOTATION_EDIT')): ?><td></td><?php endif; ?>
                </tr>
              </tfoot>
            </table>
          </div>
          <?php endif; ?>
        </div>

        <!-- Tab History -->
        <div class="tab-pane fade p-4" id="tabHistory">
          <?php if (empty($histories)): ?>
            <div class="text-center py-4 text-muted"><p class="small">Belum ada riwayat.</p></div>
          <?php else: ?>
          <?php foreach ($histories as $h): ?>
          <div class="d-flex gap-3 mb-3">
            <div style="width:30px;height:30px;border-radius:50%;flex-shrink:0;
              background:#EFF6FF;color:#185FA5;display:flex;align-items:center;
              justify-content:center;font-size:14px">
              <i class="bi bi-arrow-right-circle-fill"></i>
            </div>
            <div>
              <div class="fw-600 small font-mono"><?= e($h['event_name']) ?></div>
              <div class="d-flex align-items-center gap-2 my-1">
                <?php if ($h['from_status']): ?>
                  <?php $fsc=$statusConfig[$h['from_status']]??['color'=>'secondary']; ?>
                  <span class="badge bg-<?=$fsc['color']?>-subtle text-<?=$fsc['color']?> border" style="font-size:10px">
                    <?= e(QuotationService::STATUS_LABELS[$h['from_status']] ?? $h['from_status']) ?>
                  </span>
                  <i class="bi bi-arrow-right text-muted small"></i>
                <?php endif; ?>
                <?php $tsc=$statusConfig[$h['to_status']]??['color'=>'secondary']; ?>
                <span class="badge bg-<?=$tsc['color']?>-subtle text-<?=$tsc['color']?> border" style="font-size:10px">
                  <?= e(QuotationService::STATUS_LABELS[$h['to_status']] ?? $h['to_status']) ?>
                </span>
              </div>
              <div class="text-muted" style="font-size:11px">
                <?php if ($h['actor_name']): ?><i class="bi bi-person me-1"></i><?= e($h['actor_name']) ?> &bull; <?php endif; ?>
                <i class="bi bi-clock me-1"></i><?= format_datetime($h['changed_at']) ?>
              </div>
              <?php if ($h['notes']): ?>
                <div class="text-muted small fst-italic mt-1"><?= e($h['notes']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Modal konfirmasi aksi -->
<div class="modal fade" id="modalAction" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="actionTitle">Konfirmasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="actionForm" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="quotation_id" value="<?= $quotationId ?>">
        <div class="modal-body">
          <p class="small mb-2" id="actionDesc"></p>
          <div id="notesField" class="d-none">
            <label class="form-label small">Catatan / Alasan <span class="required" id="notesRequired"></span></label>
            <textarea name="notes" class="form-control form-control-sm" rows="2"
              placeholder="Tuliskan alasan atau catatan..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm" id="actionBtn">Konfirmasi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$baseUrl = BASE_URL;
$extraJs = <<<JS
<script>
const BASE = '{$baseUrl}';
const QUOID = {$quotationId};

// ── Aksi tombol ───────────────────────────────────────────────────────
const actions = {
  submit : { title:'Ajukan Quotation',     desc:'Quotation akan diajukan untuk persetujuan manager.',     url:BASE+'/sales/quotation/submit',  notes:false, req:false, btnClass:'btn-primary' },
  approve: { title:'Setujui Quotation',    desc:'Quotation akan disetujui. Semua berlian akan di-lock.',  url:BASE+'/sales/quotation/approve', notes:true,  req:false, btnClass:'btn-success' },
  reject : { title:'Tolak Quotation',      desc:'Quotation akan ditolak.',                                url:BASE+'/sales/quotation/reject',  notes:true,  req:true,  btnClass:'btn-danger'  },
  accept : { title:'Customer Menerima',    desc:'Konfirmasi bahwa customer sudah menerima penawaran ini.',url:BASE+'/sales/quotation/accept',  notes:false, req:false, btnClass:'btn-success' },
  cancel : { title:'Batalkan Quotation',   desc:'Quotation akan dibatalkan. Lock berlian akan dilepas.',  url:BASE+'/sales/quotation/cancel',  notes:true,  req:false, btnClass:'btn-danger'  },
};
\$(document).on('click', '.btn-action', function () {
  const act = actions[\$(this).data('action')];
  if (!act) return;
  \$('#actionTitle').text(act.title);
  \$('#actionDesc').text(act.desc);
  \$('#actionForm').attr('action', act.url);
  \$('#actionBtn').removeClass().addClass('btn btn-sm ' + act.btnClass);
  if (act.notes) {
    \$('#notesField').removeClass('d-none');
    \$('#notesRequired').text(act.req ? '*' : '');
    \$('#notesField textarea').prop('required', act.req);
  } else {
    \$('#notesField').addClass('d-none');
    \$('#notesField textarea').prop('required', false);
  }
  new bootstrap.Modal(document.getElementById('modalAction')).show();
});

// ── Hapus item dari quotation draft ──────────────────────────────────
\$(document).on('click', '.btn-remove-item', function () {
  const itemId = \$(this).data('id');
  const code   = \$(this).data('code');
  if (!confirm('Hapus berlian ' + code + ' dari quotation ini?')) return;
  erpAjax({
    url  : BASE + '/sales/quotation/remove-item',
    data : { item_id: itemId, quotation_id: QUOID, csrf_token: \$('meta[name="csrf-token"]').attr('content') },
    onSuccess: function () { location.reload(); },
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
