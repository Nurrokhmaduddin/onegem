<?php
/**
 * master/supplier/list.php
 * Daftar Supplier
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth();
require_permission('SUPPLIER_VIEW');

$search   = get_param('search');
$type     = get_param('type');
$isActive = get_param('is_active') !== '' ? (int)get_param('is_active') : -1;
$sortBy   = get_param('sort', 's.name');
$sortDir  = get_param('dir', 'ASC');
$page     = max(1, (int)get_param('page', 1));
$perPage  = DEFAULT_PER_PAGE;
$offset   = pagination_offset($page, $perPage);

$total     = SupplierRepository::countList($search, $type, $isActive);
$suppliers = SupplierRepository::getList($search, $type, $isActive, $sortBy, $sortDir, $perPage, $offset);
$pagData   = pagination_data($total, $page, $perPage);
$stats     = SupplierRepository::getStats();

$typeConfig = [
    'consignment' => ['label'=>'Konsinyasi',       'class'=>'bg-info-subtle text-info border border-info-subtle'],
    'purchase'    => ['label'=>'Pembelian',         'class'=>'bg-success-subtle text-success border border-success-subtle'],
    'both'        => ['label'=>'Konsinyasi & Beli', 'class'=>'bg-purple-subtle text-purple border border-purple-subtle'],
];

$pageTitle   = 'Data Supplier';
$breadcrumbs = [['label'=>'Master Data'],['label'=>'Supplier']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-building"></i></div>
      <div><div class="stat-label">Total Supplier</div><div class="stat-value"><?= e($stats['total']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
      <div><div class="stat-label">Aktif</div><div class="stat-value"><?= e($stats['active']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon amber"><i class="bi bi-arrow-repeat"></i></div>
      <div><div class="stat-label">Konsinyasi</div><div class="stat-value"><?= e(($stats['consignment']??0) + ($stats['both_type']??0)) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-cart-fill"></i></div>
      <div><div class="stat-label">Pembelian</div><div class="stat-value"><?= e(($stats['purchase']??0) + ($stats['both_type']??0)) ?></div></div>
    </div>
  </div>
</div>

<div class="table-card">
  <div class="table-toolbar">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
      <div class="input-group" style="width:260px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control"
          placeholder="Cari nama, kode, kontak..."
          value="<?= e($search) ?>" autocomplete="off">
      </div>
      <select name="type" class="form-select" style="width:180px" onchange="this.form.submit()">
        <option value="">Semua Jenis</option>
        <option value="consignment" <?= $type==='consignment'?'selected':'' ?>>Konsinyasi</option>
        <option value="purchase"    <?= $type==='purchase'   ?'selected':'' ?>>Pembelian</option>
        <option value="both"        <?= $type==='both'       ?'selected':'' ?>>Keduanya</option>
      </select>
      <select name="is_active" class="form-select" style="width:140px" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="1" <?= $isActive===1?'selected':'' ?>>Aktif</option>
        <option value="0" <?= $isActive===0?'selected':'' ?>>Nonaktif</option>
      </select>
      <button type="submit" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <?php if ($search || $type || $isActive >= 0): ?>
        <a href="<?= url('master/supplier') ?>" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>
    <?php if (can('SUPPLIER_CREATE')): ?>
      <a href="<?= url('master/supplier/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Supplier
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($suppliers)): ?>
    <div class="table-empty">
      <i class="bi bi-building text-muted"></i>
      <p class="mb-2">Belum ada data supplier.</p>
      <?php if (can('SUPPLIER_CREATE')): ?>
        <a href="<?= url('master/supplier/create') ?>" class="btn btn-primary btn-sm">Tambah Pertama</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Supplier</th>
          <th>Jenis</th>
          <th>Mata Uang</th>
          <th>Diskon</th>
          <th>Kontak</th>
          <th>Status</th>
          <th style="width:100px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($suppliers as $i => $s): ?>
        <tr>
          <td class="text-muted small"><?= $offset+$i+1 ?></td>
          <td>
            <div class="fw-600">
              <a href="<?= url('master/supplier/detail') ?>?id=<?= $s['id'] ?>"
                 class="text-decoration-none text-dark">
                <?= e($s['name']) ?>
              </a>
            </div>
            <small class="text-muted font-mono"><?= e($s['supplier_code']) ?></small>
          </td>
          <td>
            <?php $tc = $typeConfig[$s['type']] ?? $typeConfig['both']; ?>
            <span class="badge <?= $tc['class'] ?>"><?= $tc['label'] ?></span>
          </td>
          <td>
            <span class="badge <?= $s['currency']==='USD'?'bg-success-subtle text-success border border-success-subtle':'bg-warning-subtle text-warning-emphasis border border-warning-subtle' ?>">
              <?= e($s['currency']) ?>
            </span>
          </td>
          <td><?= $s['discount_percent'] > 0 ? e($s['discount_percent']).'%' : '—' ?></td>
          <td class="small">
            <?php if ($s['contact_person']): ?>
              <div class="fw-500"><?= e($s['contact_person']) ?></div>
            <?php endif; ?>
            <?php if ($s['phone']): ?>
              <div class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($s['phone']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($s['is_active']): ?>
              <span class="status-badge badge-active">Aktif</span>
            <?php else: ?>
              <span class="status-badge badge-inactive">Nonaktif</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('master/supplier/detail') ?>?id=<?= $s['id'] ?>"
                 class="btn btn-icon btn-outline-secondary btn-sm" title="Detail">
                <i class="bi bi-eye"></i>
              </a>
              <?php if (can('SUPPLIER_EDIT')): ?>
              <a href="<?= url('master/supplier/edit') ?>?id=<?= $s['id'] ?>"
                 class="btn btn-icon btn-outline-primary btn-sm" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <?php endif; ?>
              <?php if (can('SUPPLIER_DELETE')): ?>
              <button type="button"
                class="btn btn-icon btn-outline-danger btn-sm btn-delete"
                data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" title="Hapus">
                <i class="bi bi-trash"></i>
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
      Menampilkan <?= $pagData['from'] ?>–<?= $pagData['to'] ?> dari <?= number_format($total) ?> supplier
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

<?php
$extraJs = <<<'JS'
<script>
$(function () {
  $(document).on('click', '.btn-delete', function () {
    const id   = $(this).data('id');
    const name = $(this).data('name');
    if (!confirm('Hapus supplier "' + name + '"?')) return;
    erpAjax({
      url  : window.BASE_URL + '/master/supplier/delete',
      data : { supplier_id: id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
      onSuccess: function (res) { erpToast('success', res.message); setTimeout(()=>location.reload(),800); },
    });
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
