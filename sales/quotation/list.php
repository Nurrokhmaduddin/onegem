<?php
/**
 * sales/quotation/list.php — Daftar Quotation
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

$search   = get_param('search');
$status   = get_param('status');
$sortBy   = get_param('sort', 'q.created_at');
$sortDir  = get_param('dir', 'DESC');
$page     = max(1, (int)get_param('page', 1));
$perPage  = DEFAULT_PER_PAGE;
$offset   = pagination_offset($page, $perPage);

$total      = QuotationRepository::countList($search, $status);
$quotations = QuotationRepository::getList($search, $status, 0, 0, $sortBy, $sortDir, $perPage, $offset);
$pagData    = pagination_data($total, $page, $perPage);
$stats      = QuotationRepository::getStats();

$statusConfig = [
    'draft'     => ['label'=>'Draft',              'color'=>'secondary'],
    'submitted' => ['label'=>'Diajukan',            'color'=>'info'],
    'approved'  => ['label'=>'Disetujui',           'color'=>'primary'],
    'rejected'  => ['label'=>'Ditolak',             'color'=>'danger'],
    'accepted'  => ['label'=>'Diterima Customer',   'color'=>'success'],
    'converted' => ['label'=>'Konversi Reservasi',  'color'=>'dark'],
    'cancelled' => ['label'=>'Dibatalkan',          'color'=>'warning'],
];

$pageTitle   = 'Quotation';
$breadcrumbs = [['label'=>'Penjualan'],['label'=>'Quotation']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Stat cards -->
<div class="row g-2 mb-4">
  <?php
  $statItems = [
      ['key'=>'draft',     'label'=>'Draft',     'color'=>'secondary'],
      ['key'=>'submitted', 'label'=>'Diajukan',  'color'=>'info'],
      ['key'=>'approved',  'label'=>'Disetujui', 'color'=>'primary'],
      ['key'=>'accepted',  'label'=>'Diterima',  'color'=>'success'],
      ['key'=>'converted', 'label'=>'Konversi',  'color'=>'dark'],
      ['key'=>'total',     'label'=>'Semua',     'color'=>'light'],
  ];
  foreach ($statItems as $si):
  ?>
  <div class="col">
    <a href="?<?= http_build_query(array_merge($_GET, ['status'=>$si['key']==='total'?'':$si['key'],'page'=>1])) ?>"
       class="text-decoration-none">
      <div class="card text-center p-3 h-100 <?= ($si['key']==='total'?$status==='':$status===$si['key'])?'border-primary border-2':'' ?>">
        <div class="fw-700 fs-4 text-<?= $si['color']==='light'?'dark':$si['color'] ?>">
          <?= e($stats[$si['key']] ?? 0) ?>
        </div>
        <div class="small text-muted"><?= $si['label'] ?></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<div class="table-card">
  <div class="table-toolbar flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
      <div class="input-group" style="width:260px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control"
          placeholder="No. quotation, nama customer..."
          value="<?= e($search) ?>" autocomplete="off">
      </div>
      <select name="status" class="form-select" style="width:180px" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <?php foreach ($statusConfig as $k=>$cfg): ?>
          <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= e($cfg['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <?php if ($search||$status): ?>
        <a href="<?= url('sales/quotation') ?>" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>
    <?php if (can('QUOTATION_CREATE')): ?>
      <a href="<?= url('sales/quotation/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Buat Quotation
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($quotations)): ?>
    <div class="table-empty">
      <i class="bi bi-file-earmark-text text-muted"></i>
      <p class="mb-2">Belum ada quotation.</p>
      <?php if (can('QUOTATION_CREATE')): ?>
        <a href="<?= url('sales/quotation/create') ?>" class="btn btn-primary btn-sm">Buat Quotation</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px">
      <thead>
        <tr>
          <th>#</th>
          <th>No. Quotation</th>
          <th>Customer / Lead</th>
          <th>Salesperson</th>
          <th class="text-center">Item</th>
          <th class="text-end">Total</th>
          <th>Berlaku s/d</th>
          <th>Status</th>
          <th style="width:90px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($quotations as $i => $q): ?>
        <tr>
          <td class="text-muted"><?= $offset+$i+1 ?></td>
          <td>
            <a href="<?= url('sales/quotation/detail') ?>?id=<?= $q['id'] ?>"
               class="font-mono fw-600 text-decoration-none text-primary">
              <?= e($q['quotation_no']) ?>
            </a>
            <div class="small text-muted"><?= format_date($q['quotation_date']) ?></div>
          </td>
          <td>
            <?php if ($q['customer_name']): ?>
              <a href="<?= url('master/customer/detail') ?>?id=<?= $q['customer_id'] ?>"
                 class="text-decoration-none fw-500">
                <?= e($q['customer_name']) ?>
              </a>
              <div class="small text-muted font-mono"><?= e($q['customer_code']) ?></div>
            <?php elseif ($q['lead_name']): ?>
              <div class="fw-500"><?= e($q['lead_name']) ?></div>
              <div><span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:10px">
                Lead: <?= e($q['lead_code']) ?>
              </span></div>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="small"><?= e($q['salesperson_name'] ?? '—') ?></td>
          <td class="text-center">
            <span class="badge bg-light text-dark border"><?= (int)$q['item_count'] ?></span>
          </td>
          <td class="text-end">
            <div class="fw-600">$<?= number_format((float)$q['total_usd'],2) ?></div>
            <div class="small text-muted">Rp <?= number_format((float)$q['total_idr'],0,',','.') ?></div>
          </td>
          <td class="small <?= ($q['valid_until'] && $q['valid_until'] < date('Y-m-d') && !in_array($q['status'],['converted','cancelled'])) ? 'text-danger fw-600' : 'text-muted' ?>">
            <?= $q['valid_until'] ? format_date($q['valid_until']) : '—' ?>
          </td>
          <td>
            <?php $sc=$statusConfig[$q['status']]??$statusConfig['draft']; ?>
            <span class="badge bg-<?=$sc['color']?>-subtle text-<?=$sc['color']?> border border-<?=$sc['color']?>-subtle" style="font-size:11px">
              <?= e($sc['label']) ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('sales/quotation/detail') ?>?id=<?= $q['id'] ?>"
                 class="btn btn-icon btn-outline-secondary btn-sm" title="Detail">
                <i class="bi bi-eye"></i>
              </a>
              <?php if (can('QUOTATION_APPROVE') && $q['status']==='submitted'): ?>
              <a href="<?= url('sales/quotation/detail') ?>?id=<?= $q['id'] ?>"
                 class="btn btn-icon btn-outline-success btn-sm" title="Review & Approve">
                <i class="bi bi-check-circle"></i>
              </a>
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
    <small class="text-muted"><?= $pagData['from'] ?>–<?= $pagData['to'] ?> dari <?= number_format($total) ?> quotation</small>
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

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
