<?php
/**
 * sales/lead/list.php
 * Daftar Lead / Prospek
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

$search     = get_param('search');
$status     = get_param('status');
$assignedTo = (int)get_param('assigned_to', 0);
$sortBy     = get_param('sort', 'l.created_at');
$sortDir    = get_param('dir', 'DESC');
$page       = max(1, (int)get_param('page', 1));
$perPage    = DEFAULT_PER_PAGE;
$offset     = pagination_offset($page, $perPage);

$total       = LeadRepository::countList($search, $status, $assignedTo);
$leads       = LeadRepository::getList($search, $status, $assignedTo, $sortBy, $sortDir, $perPage, $offset);
$pagData     = pagination_data($total, $page, $perPage);
$stats       = LeadRepository::getStats();
$salespersons = LeadRepository::getSalespersons();

$statusConfig = [
    'new'       => ['label'=>'Baru',              'color'=>'secondary', 'bg'=>'#F3F4F6', 'text'=>'#374151'],
    'contacted' => ['label'=>'Dihubungi',          'color'=>'info',      'bg'=>'#EFF6FF', 'text'=>'#1D4ED8'],
    'qualified' => ['label'=>'Qualified',          'color'=>'primary',   'bg'=>'#EFF6FF', 'text'=>'#185FA5'],
    'quoted'    => ['label'=>'Penawaran Terkirim', 'color'=>'warning',   'bg'=>'#FFFBEB', 'text'=>'#B45309'],
    'converted' => ['label'=>'Konversi',           'color'=>'success',   'bg'=>'#F0FDF4', 'text'=>'#15803D'],
    'lost'      => ['label'=>'Tidak Jadi',         'color'=>'danger',    'bg'=>'#FEF2F2', 'text'=>'#DC2626'],
];
$sourceLabels = LeadService::SOURCE_LABELS;

$pageTitle   = 'Manajemen Lead';
$breadcrumbs = [['label'=>'Penjualan'],['label'=>'Lead']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Funnel cards -->
<div class="row g-2 mb-4">
  <?php foreach ($statusConfig as $k => $cfg): ?>
  <div class="col">
    <a href="?<?= http_build_query(array_merge($_GET,['status'=>$k,'page'=>1])) ?>"
       class="text-decoration-none">
      <div class="card text-center p-3 h-100 <?= $status===$k?'border-primary border-2':'' ?>"
           style="background:<?= $cfg['bg'] ?>">
        <div class="fw-700 fs-4" style="color:<?= $cfg['text'] ?>">
          <?= e($stats[$k==='new'?'new_count':($k==='converted'?'converted':($k==='lost'?'lost':$k))] ?? 0) ?>
        </div>
        <div class="small" style="color:<?= $cfg['text'] ?>;opacity:.8"><?= $cfg['label'] ?></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
  <div class="col">
    <a href="?<?= http_build_query(array_merge($_GET,['status'=>'','page'=>1])) ?>"
       class="text-decoration-none">
      <div class="card text-center p-3 h-100 <?= $status===''?'border-primary border-2':'' ?>">
        <div class="fw-700 fs-4 text-dark"><?= e($stats['total'] ?? 0) ?></div>
        <div class="small text-muted">Semua</div>
      </div>
    </a>
  </div>
</div>

<div class="table-card">
  <!-- Toolbar -->
  <div class="table-toolbar flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
      <div class="input-group" style="width:240px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control"
          placeholder="Nama, telepon, email..."
          value="<?= e($search) ?>" autocomplete="off">
      </div>
      <select name="status" class="form-select" style="width:180px" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <?php foreach ($statusConfig as $k=>$cfg): ?>
          <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= e($cfg['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="assigned_to" class="form-select" style="width:180px" onchange="this.form.submit()">
        <option value="0">Semua Salesperson</option>
        <?php foreach ($salespersons as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $assignedTo==$s['id']?'selected':'' ?>>
            <?= e($s['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <?php if ($search||$status||$assignedTo): ?>
        <a href="<?= url('sales/lead') ?>" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>
    <?php if (can('LEAD_CREATE')): ?>
      <a href="<?= url('sales/lead/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Lead
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($leads)): ?>
    <div class="table-empty">
      <i class="bi bi-person-plus text-muted"></i>
      <p class="mb-2">Belum ada data lead.</p>
      <?php if (can('LEAD_CREATE')): ?>
        <a href="<?= url('sales/lead/create') ?>" class="btn btn-primary btn-sm">Tambah Lead Pertama</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Lead</th>
          <th>Sumber</th>
          <th>Salesperson</th>
          <th>Status</th>
          <th>Terakhir Update</th>
          <th style="width:100px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $i => $l): ?>
        <tr>
          <td class="text-muted small"><?= $offset+$i+1 ?></td>
          <td>
            <a href="<?= url('sales/lead/detail') ?>?id=<?= $l['id'] ?>"
               class="fw-600 text-decoration-none text-dark d-block lh-1 mb-1">
              <?= e($l['name']) ?>
            </a>
            <small class="font-mono text-muted"><?= e($l['lead_code']) ?></small>
            <?php if ($l['phone']): ?>
              <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= e($l['phone']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge bg-light text-dark border" style="font-size:11px">
              <?= e($sourceLabels[$l['source']] ?? $l['source']) ?>
            </span>
          </td>
          <td class="small">
            <?php if ($l['assigned_name']): ?>
              <div class="d-flex align-items-center gap-1">
                <div style="width:24px;height:24px;border-radius:50%;background:#EFF6FF;color:#185FA5;
                  display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;flex-shrink:0">
                  <?= e(name_initials($l['assigned_name'])) ?>
                </div>
                <span><?= e($l['assigned_name']) ?></span>
              </div>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php $sc = $statusConfig[$l['status']] ?? $statusConfig['new']; ?>
            <span class="badge bg-<?=$sc['color']?>-subtle text-<?=$sc['color']?> border border-<?=$sc['color']?>-subtle">
              <?= e($sc['label']) ?>
            </span>
          </td>
          <td class="small text-muted"><?= time_ago($l['updated_at']) ?></td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('sales/lead/detail') ?>?id=<?= $l['id'] ?>"
                 class="btn btn-icon btn-outline-secondary btn-sm" title="Detail">
                <i class="bi bi-eye"></i>
              </a>
              <?php if (can('LEAD_EDIT') && !in_array($l['status'],['converted'])): ?>
              <a href="<?= url('sales/lead/edit') ?>?id=<?= $l['id'] ?>"
                 class="btn btn-icon btn-outline-primary btn-sm" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <?php endif; ?>
              <?php if (can('LEAD_CONVERT') && in_array($l['status'],['qualified','quoted'])): ?>
              <button type="button"
                class="btn btn-icon btn-outline-success btn-sm btn-convert"
                data-id="<?= $l['id'] ?>" data-name="<?= e($l['name']) ?>"
                title="Konversi ke Customer">
                <i class="bi bi-person-check"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pagData['total_pages'] > 1): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
    <small class="text-muted">
      <?= $pagData['from'] ?>–<?= $pagData['to'] ?> dari <?= number_format($total) ?> lead
    </small>
    <nav><ul class="pagination mb-0">
      <li class="page-item <?= !$pagData['has_prev']?'disabled':'' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">
          <i class="bi bi-chevron-left"></i></a>
      </li>
      <?php for ($p=max(1,$page-2);$p<=min($pagData['total_pages'],$page+2);$p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= !$pagData['has_next']?'disabled':'' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">
          <i class="bi bi-chevron-right"></i></a>
      </li>
    </ul></nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal Convert ke Customer -->
<div class="modal fade" id="modalConvert" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-check me-2"></i>Konversi Lead ke Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('sales/lead/convert') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="lead_id" id="convertLeadId">
        <div class="modal-body">
          <div class="alert alert-info py-2 mb-3 small">
            <i class="bi bi-info-circle me-1"></i>
            Lead <strong id="convertLeadName"></strong> akan dibuat menjadi data customer baru.
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nama Lengkap <span class="required">*</span></label>
              <input type="text" name="name" id="convertName" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">No. Telepon</label>
              <input type="text" name="phone" id="convertPhone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" id="convertEmail" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tier Customer</label>
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
  $(document).on('click', '.btn-convert', function () {
    const id   = $(this).data('id');
    const name = $(this).data('name');
    $('#convertLeadId').val(id);
    $('#convertLeadName').text(name);
    $('#convertName').val(name);
    new bootstrap.Modal(document.getElementById('modalConvert')).show();
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
