<?php
/**
 * sales/lead/detail.php — Detail Lead
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
require_permission('LEAD_VIEW');

$leadId     = (int)get_param('id');
$lead       = LeadRepository::findById($leadId);
if (!$lead) { flash_set('error','Lead tidak ditemukan.'); redirect(url('sales/lead')); }
$activities = LeadRepository::getActivities($leadId);

$statusConfig = [
    'new'       => ['label'=>'Baru',              'color'=>'secondary'],
    'contacted' => ['label'=>'Dihubungi',          'color'=>'info'],
    'qualified' => ['label'=>'Qualified',          'color'=>'primary'],
    'quoted'    => ['label'=>'Penawaran Terkirim', 'color'=>'warning'],
    'converted' => ['label'=>'Konversi',           'color'=>'success'],
    'lost'      => ['label'=>'Tidak Jadi',         'color'=>'danger'],
];
$activityIcons = [
    'call'=>'bi-telephone-fill','meeting'=>'bi-people-fill',
    'whatsapp'=>'bi-whatsapp','email'=>'bi-envelope-fill','note'=>'bi-sticky-fill',
];
$activityColors = [
    'call'=>'success','meeting'=>'primary','whatsapp'=>'success',
    'email'=>'info','note'=>'secondary',
];

$sc = $statusConfig[$lead['status']] ?? $statusConfig['new'];
$allowedTransitions = LeadService::STATUS_TRANSITIONS[$lead['status']] ?? [];
$statusLabels = LeadService::STATUS_LABELS;

$pageTitle   = 'Detail Lead — ' . $lead['name'];
$breadcrumbs = [
    ['label'=>'Penjualan'],
    ['label'=>'Lead','url'=>url('sales/lead')],
    ['label'=>e($lead['name'])],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">
  <!-- Kolom kiri: info lead -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:52px;height:52px;border-radius:12px;background:#EFF6FF;color:#185FA5;
            display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;flex-shrink:0">
            <?= e(name_initials($lead['name'])) ?>
          </div>
          <div>
            <div class="fw-600 fs-6"><?= e($lead['name']) ?></div>
            <div class="font-mono text-muted small"><?= e($lead['lead_code']) ?></div>
          </div>
        </div>

        <span class="badge bg-<?=$sc['color']?>-subtle text-<?=$sc['color']?> border border-<?=$sc['color']?>-subtle mb-3">
          <?= e($sc['label']) ?>
        </span>

        <table class="table table-borderless table-sm mb-0" style="font-size:13px">
          <tr><td class="text-muted" width="40%">Sumber</td>
              <td><?= e(LeadService::SOURCE_LABELS[$lead['source']] ?? $lead['source']) ?></td></tr>
          <tr><td class="text-muted">Telepon</td>
              <td><?= $lead['phone'] ? e($lead['phone']) : '—' ?></td></tr>
          <tr><td class="text-muted">Email</td>
              <td><?= $lead['email'] ? e($lead['email']) : '—' ?></td></tr>
          <tr><td class="text-muted">Salesperson</td>
              <td><?= $lead['assigned_name'] ? e($lead['assigned_name']) : '<span class="text-muted">Belum assigned</span>' ?></td></tr>
          <?php if ($lead['customer_id']): ?>
          <tr><td class="text-muted">Customer</td>
              <td>
                <a href="<?= url('master/customer/detail') ?>?id=<?= $lead['customer_id'] ?>" class="text-decoration-none">
                  <?= e($lead['customer_name']) ?>
                </a>
              </td></tr>
          <?php endif; ?>
          <tr><td class="text-muted">Masuk</td>
              <td><?= format_datetime($lead['created_at']) ?></td></tr>
        </table>

        <?php if ($lead['interest']): ?>
        <div class="border-top pt-3 mt-2">
          <div class="text-muted small mb-1"><i class="bi bi-gem me-1"></i>Minat Produk</div>
          <div style="font-size:13px"><?= nl2br(e($lead['interest'])) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Aksi -->
      <div class="card-body border-top p-3 d-flex flex-column gap-2">
        <?php if (can('LEAD_EDIT') && $lead['status'] !== 'converted'): ?>
          <a href="<?= url('sales/lead/edit') ?>?id=<?= $leadId ?>"
             class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Data
          </a>
        <?php endif; ?>

        <?php if (can('LEAD_EDIT') && !empty($allowedTransitions)): ?>
          <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100"
                    data-bs-toggle="dropdown">
              <i class="bi bi-arrow-right-circle me-1"></i>Ubah Status
            </button>
            <ul class="dropdown-menu w-100">
              <?php foreach ($allowedTransitions as $ts): ?>
                <?php $tc = $statusConfig[$ts] ?? []; ?>
                <li>
                  <button class="dropdown-item btn-change-status"
                    data-id="<?= $leadId ?>"
                    data-status="<?= $ts ?>"
                    data-label="<?= e($statusLabels[$ts] ?? $ts) ?>">
                    <span class="badge bg-<?=$tc['color']??'secondary'?>-subtle text-<?=$tc['color']??'secondary'?> border me-1">
                      <?= e($statusLabels[$ts] ?? $ts) ?>
                    </span>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (can('QUOTATION_CREATE') && in_array($lead['status'],['qualified','quoted'])): ?>
          <a href="<?= url('sales/quotation/create') ?>?lead_id=<?= $leadId ?>"
             class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>Buat Quotation
          </a>
        <?php endif; ?>

        <?php if (can('LEAD_CONVERT') && in_array($lead['status'],['qualified','quoted'])): ?>
          <button type="button" class="btn btn-success btn-sm"
            data-bs-toggle="modal" data-bs-target="#modalConvert">
            <i class="bi bi-person-check me-1"></i>Konversi ke Customer
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quotations dari lead ini -->
    <?php
    $quotations = Database::fetchAll(
        "SELECT quotation_no,status,total_idr,created_at
           FROM quotations WHERE lead_id=? AND deleted_at IS NULL
           ORDER BY created_at DESC LIMIT 5",
        [$leadId]
    );
    ?>
    <?php if (!empty($quotations)): ?>
    <div class="card">
      <div class="card-header small fw-600">
        <i class="bi bi-file-earmark-text me-2"></i>Quotation Terkait
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($quotations as $q): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center py-2" style="font-size:13px">
          <div>
            <div class="font-mono fw-500"><?= e($q['quotation_no']) ?></div>
            <small class="text-muted"><?= format_date($q['created_at']) ?></small>
          </div>
          <div class="text-end">
            <div class="small">Rp <?= number_format((float)$q['total_idr'],0,',','.') ?></div>
            <span class="badge bg-secondary-subtle text-secondary border" style="font-size:10px">
              <?= e($q['status']) ?>
            </span>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>

  <!-- Kolom kanan: aktivitas -->
  <div class="col-lg-8">
    <!-- Form tambah aktivitas -->
    <?php if (can('LEAD_EDIT') && $lead['status'] !== 'converted'): ?>
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Catat Aktivitas</div>
      <div class="card-body p-3">
        <form method="POST" action="<?= url('sales/lead/add-activity') ?>" class="no-double-submit">
          <?= csrf_field() ?>
          <input type="hidden" name="lead_id" value="<?= $leadId ?>">
          <div class="row g-2">
            <div class="col-md-3">
              <select name="activity_type" class="form-select form-select-sm">
                <option value="call">📞 Telepon</option>
                <option value="whatsapp">💬 WhatsApp</option>
                <option value="meeting">🤝 Meeting</option>
                <option value="email">📧 Email</option>
                <option value="note" selected>📝 Catatan</option>
              </select>
            </div>
            <div class="col-md-7">
              <input type="text" name="description" class="form-control form-control-sm"
                placeholder="Catatan aktivitas follow-up..." required>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-check-lg me-1"></i>Simpan
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Timeline aktivitas -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Riwayat Aktivitas</span>
        <span class="badge bg-secondary-subtle text-secondary border"><?= count($activities) ?> aktivitas</span>
      </div>
      <div class="card-body p-3">
        <?php if (empty($activities)): ?>
          <div class="text-center py-4 text-muted">
            <i class="bi bi-clock-history d-block fs-3 mb-2"></i>
            <small>Belum ada aktivitas dicatat.</small>
          </div>
        <?php else: ?>
        <div class="timeline">
          <?php foreach ($activities as $act): ?>
          <div class="d-flex gap-3 mb-3">
            <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;
              background:var(--bs-<?=$activityColors[$act['activity_type']]??'secondary'?>-bg-subtle,#F3F4F6);
              color:var(--bs-<?=$activityColors[$act['activity_type']]??'secondary'?>-text-emphasis,#374151);
              display:flex;align-items:center;justify-content:center;font-size:15px">
              <i class="bi <?=$activityIcons[$act['activity_type']]??'bi-dot'?>"></i>
            </div>
            <div class="flex-1">
              <div style="font-size:13px"><?= nl2br(e($act['description'])) ?></div>
              <div class="text-muted" style="font-size:11px;margin-top:3px">
                <?php if ($act['created_by_name']): ?>
                  <i class="bi bi-person me-1"></i><?= e($act['created_by_name']) ?> &bull;
                <?php endif; ?>
                <i class="bi bi-clock me-1"></i><?= format_datetime($act['created_at']) ?>
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

<!-- Modal Ubah Status -->
<div class="modal fade" id="modalChangeStatus" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ubah Status Lead</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/lead/change-status') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="lead_id" value="<?= $leadId ?>">
        <input type="hidden" name="new_status" id="newStatusInput">
        <div class="modal-body">
          <p class="mb-2 small">
            Ubah ke: <strong id="newStatusLabel"></strong>
          </p>
          <label class="form-label small">Catatan (opsional)</label>
          <textarea name="notes" class="form-control form-control-sm" rows="2"
            placeholder="Alasan / keterangan perubahan status..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm">Ubah Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Convert ke Customer -->
<div class="modal fade" id="modalConvert" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-check me-2"></i>Konversi ke Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/lead/convert') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="lead_id" value="<?= $leadId ?>">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nama <span class="required">*</span></label>
              <input type="text" name="name" class="form-control"
                value="<?= e($lead['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Telepon</label>
              <input type="text" name="phone" class="form-control"
                value="<?= e($lead['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control"
                value="<?= e($lead['email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tier</label>
              <select name="tier" class="form-select">
                <option value="regular">Regular</option>
                <option value="vip">VIP</option>
                <option value="vvip">VVIP</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-lg me-1"></i>Konversi
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
  $(document).on('click', '.btn-change-status', function () {
    const status = $(this).data('status');
    const label  = $(this).data('label');
    $('#newStatusInput').val(status);
    $('#newStatusLabel').text(label);
    new bootstrap.Modal(document.getElementById('modalChangeStatus')).show();
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
