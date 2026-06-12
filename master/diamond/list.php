<?php
/**
 * master/diamond/list.php
 * Daftar Berlian — Inventori Utama
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../supplier/repository.php';
require_once __DIR__ . '/../warehouse/repository.php';

require_auth();
require_permission('DIAMOND_VIEW');

$search      = get_param('search');
$status      = get_param('status');
$acqType     = get_param('acquisition_type');
$supplierId  = (int)get_param('supplier_id', 0);
$warehouseId = (int)get_param('warehouse_id', 0);
$sortBy      = get_param('sort', 'd.created_at');
$sortDir     = get_param('dir', 'DESC');
$page        = max(1, (int)get_param('page', 1));
$perPage     = DEFAULT_PER_PAGE;
$offset      = pagination_offset($page, $perPage);

$total    = DiamondRepository::countList($search, $status, $acqType, $supplierId, $warehouseId);
$diamonds = DiamondRepository::getList($search, $status, $acqType, $supplierId, $warehouseId, $sortBy, $sortDir, $perPage, $offset);
$pagData  = pagination_data($total, $page, $perPage);
$stats    = DiamondRepository::getStats();
$rate     = DiamondRepository::getActiveRate();
$shapes   = DiamondRepository::getShapes();
$suppliers = SupplierRepository::getDropdown();
$warehouses = WarehouseRepository::getAll();

$statusConfig = [
    'registered' => ['label'=>'Terdaftar',     'color'=>'secondary'],
    'available'  => ['label'=>'Tersedia',       'color'=>'success'],
    'reserved'   => ['label'=>'Direservasi',    'color'=>'warning'],
    'sold'       => ['label'=>'Terjual',        'color'=>'primary'],
    'returned'   => ['label'=>'Diretur',        'color'=>'danger'],
    'in_repair'  => ['label'=>'Reparasi',       'color'=>'purple'],
    'retired'    => ['label'=>'Nonaktif',       'color'=>'dark'],
];
$acqConfig = [
    'consignment'         => ['label'=>'Konsinyasi',       'color'=>'info'],
    'purchase_returnable' => ['label'=>'Beli (Bisa Retur)','color'=>'success'],
    'purchase_final'      => ['label'=>'Beli Putus',       'color'=>'warning'],
];

$pageTitle   = 'Inventori Berlian';
$breadcrumbs = [['label'=>'Master Data'],['label'=>'Berlian']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-gem"></i></div>
      <div><div class="stat-label">Total Item</div><div class="stat-value"><?= e($stats['total']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon" style="background:#F0FDF4;color:#15803D"><i class="bi bi-check-circle-fill"></i></div>
      <div><div class="stat-label">Tersedia</div><div class="stat-value"><?= e($stats['available']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon amber"><i class="bi bi-lock-fill"></i></div>
      <div><div class="stat-label">Direservasi</div><div class="stat-value"><?= e($stats['reserved']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-bag-check-fill"></i></div>
      <div><div class="stat-label">Terjual</div><div class="stat-value"><?= e($stats['sold']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon" style="background:#FFF7ED;color:#C2410C"><i class="bi bi-arrow-repeat"></i></div>
      <div><div class="stat-label">Konsinyasi</div><div class="stat-value"><?= e($stats['consignment']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon" style="background:#F0F9FF;color:#0369A1"><i class="bi bi-currency-exchange"></i></div>
      <div>
        <div class="stat-label">Kurs USD</div>
        <div class="stat-value" style="font-size:14px">Rp <?= number_format($rate,0,',','.') ?></div>
      </div>
    </div>
  </div>
</div>

<div class="table-card">
  <!-- Toolbar -->
  <div class="table-toolbar flex-wrap gap-2">
    <form method="GET" id="filterForm" class="d-flex gap-2 flex-wrap align-items-center">
      <!-- Search: barcode/kode -->
      <div class="input-group" style="width:260px">
        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
        <input type="text" name="search" class="form-control"
          placeholder="Scan barcode / kode internal..." value="<?= e($search) ?>" autocomplete="off"
          id="barcodeInput">
      </div>
      <select name="status" class="form-select" style="width:150px" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <?php foreach ($statusConfig as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= e($v['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="acquisition_type" class="form-select" style="width:180px" onchange="this.form.submit()">
        <option value="">Semua Jenis</option>
        <?php foreach ($acqConfig as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $acqType===$k?'selected':'' ?>><?= e($v['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="supplier_id" class="form-select" style="width:180px" onchange="this.form.submit()">
        <option value="0">Semua Supplier</option>
        <?php foreach ($suppliers as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $supplierId==$s['id']?'selected':'' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="warehouse_id" class="form-select" style="width:180px" onchange="this.form.submit()">
        <option value="0">Semua Lokasi</option>
        <?php foreach ($warehouses as $w): ?>
          <option value="<?= $w['id'] ?>" <?= $warehouseId==$w['id']?'selected':'' ?>>
            <?= e($w['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <?php if ($search||$status||$acqType||$supplierId||$warehouseId): ?>
        <a href="<?= url('master/diamond') ?>" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>
    <?php if (can('DIAMOND_CREATE')): ?>
      <a href="<?= url('master/diamond/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Daftarkan Berlian
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($diamonds)): ?>
    <div class="table-empty">
      <i class="bi bi-gem text-muted"></i>
      <p class="mb-2">Belum ada data berlian.</p>
      <?php if (can('DIAMOND_CREATE')): ?>
        <a href="<?= url('master/diamond/create') ?>" class="btn btn-primary btn-sm">Daftarkan Berlian Pertama</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px">
      <thead>
        <tr>
          <th>#</th>
          <th>Kode Internal</th>
          <th>Spesifikasi 4Cs</th>
          <th>Sertifikat</th>
          <th>Supplier / Lokasi</th>
          <th class="text-end">Harga Jual</th>
          <th>Jenis</th>
          <th>Status</th>
          <th style="width:90px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($diamonds as $i => $d): ?>
        <tr>
          <td class="text-muted"><?= $offset+$i+1 ?></td>
          <td>
            <a href="<?= url('master/diamond/detail') ?>?id=<?= $d['id'] ?>"
               class="fw-600 font-mono text-decoration-none text-dark d-block">
              <?= e($d['internal_code']) ?>
            </a>
            <?php if ($d['factory_barcode']): ?>
              <small class="text-muted"><i class="bi bi-upc me-1"></i><?= e($d['factory_barcode']) ?></small>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1 flex-wrap mb-1">
              <span class="badge bg-light text-dark border"><?= e($d['carat_weight']) ?> ct</span>
              <?php if ($d['color_grade']): ?><span class="badge bg-light text-dark border"><?= e($d['color_grade']) ?></span><?php endif; ?>
              <?php if ($d['clarity_grade']): ?><span class="badge bg-light text-dark border"><?= e($d['clarity_grade']) ?></span><?php endif; ?>
              <?php if ($d['cut_grade']): ?><span class="badge bg-light text-dark border"><?= e($d['cut_grade']) ?></span><?php endif; ?>
            </div>
            <?php if ($d['shape_name']): ?><small class="text-muted"><?= e($d['shape_name']) ?></small><?php endif; ?>
          </td>
          <td>
            <?php if ($d['cert_number']): ?>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                <?= e($d['cert_type']) ?>
              </span>
              <div class="font-mono text-muted" style="font-size:11px"><?= e($d['cert_number']) ?></div>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-500"><?= e($d['supplier_name']??'—') ?></div>
            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($d['warehouse_name']??'—') ?></small>
          </td>
          <td class="text-end">
            <div class="fw-600">$<?= number_format((float)$d['selling_price_usd'],2) ?></div>
            <small class="text-muted">Rp <?= number_format((float)$d['selling_price_usd']*$rate,0,',','.') ?></small>
          </td>
          <td>
            <?php $ac=$acqConfig[$d['acquisition_type']]??$acqConfig['consignment']; ?>
            <span class="badge bg-<?=$ac['color']?>-subtle text-<?=$ac['color']?> border border-<?=$ac['color']?>-subtle" style="font-size:11px">
              <?= e($ac['label']) ?>
            </span>
          </td>
          <td>
            <?php $sc=$statusConfig[$d['status']]??$statusConfig['retired']; ?>
            <span class="badge bg-<?=$sc['color']?>-subtle text-<?=$sc['color']?> border border-<?=$sc['color']?>-subtle">
              <?= e($sc['label']) ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('master/diamond/detail') ?>?id=<?= $d['id'] ?>"
                 class="btn btn-icon btn-outline-secondary btn-sm" title="Detail">
                <i class="bi bi-eye"></i>
              </a>
              <?php if (can('DIAMOND_EDIT') && !in_array($d['status'],['sold','retired'])): ?>
              <a href="<?= url('master/diamond/edit') ?>?id=<?= $d['id'] ?>"
                 class="btn btn-icon btn-outline-primary btn-sm" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <?php endif; ?>
              <?php if (can('DIAMOND_EDIT') && $d['status']==='registered'): ?>
              <button type="button" class="btn btn-icon btn-outline-success btn-sm btn-activate"
                data-id="<?= $d['id'] ?>" data-code="<?= e($d['internal_code']) ?>" title="Aktifkan (Terima Barang)">
                <i class="bi bi-check-circle"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pagData['total_pages'] > 1): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
    <small class="text-muted">
      Menampilkan <?= $pagData['from'] ?>–<?= $pagData['to'] ?> dari <?= number_format($total) ?> item
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
  // Auto-submit saat barcode discan (tekan Enter)
  $('#barcodeInput').on('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); $('#filterForm').submit(); }
  });

  // Aktivasi berlian
  $(document).on('click', '.btn-activate', function () {
    const id   = $(this).data('id');
    const code = $(this).data('code');
    if (!confirm('Aktifkan berlian ' + code + '?\nStatus akan berubah menjadi Tersedia.')) return;
    erpAjax({
      url  : window.BASE_URL + '/master/diamond/activate',
      data : { diamond_id: id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
      onSuccess: function (res) { erpToast('success', res.message); setTimeout(()=>location.reload(),800); },
    });
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
