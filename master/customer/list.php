<?php
/**
 * master/customer/list.php
 * Daftar Pelanggan
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth();
require_permission('CUSTOMER_VIEW');

$search   = get_param('search');
$tier     = get_param('tier');
$isActive = get_param('is_active') !== '' ? (int)get_param('is_active') : -1;
$sortBy   = get_param('sort', 'c.name');
$sortDir  = get_param('dir', 'ASC');
$page     = max(1, (int)get_param('page', 1));
$perPage  = DEFAULT_PER_PAGE;
$offset   = pagination_offset($page, $perPage);

$total    = CustomerRepository::countList($search, $tier, $isActive);
$customers= CustomerRepository::getList($search, $tier, $isActive, $sortBy, $sortDir, $perPage, $offset);
$pagData  = pagination_data($total, $page, $perPage);
$stats    = CustomerRepository::getStats();

$tierConfig = [
    'regular' => ['label'=>'Regular', 'class'=>'bg-secondary-subtle text-secondary border border-secondary-subtle'],
    'vip'     => ['label'=>'VIP',     'class'=>'bg-primary-subtle text-primary border border-primary-subtle'],
    'vvip'    => ['label'=>'VVIP',    'class'=>'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
];

$pageTitle   = 'Data Pelanggan';
$breadcrumbs = [['label'=>'Master Data'],['label'=>'Pelanggan']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
      <div><div class="stat-label">Total Pelanggan</div><div class="stat-value"><?= e($stats['total']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
      <div><div class="stat-label">Aktif</div><div class="stat-value"><?= e($stats['active']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon amber"><i class="bi bi-star-fill"></i></div>
      <div><div class="stat-label">VIP</div><div class="stat-value"><?= e($stats['vip']??0) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-gem"></i></div>
      <div><div class="stat-label">VVIP</div><div class="stat-value"><?= e($stats['vvip']??0) ?></div></div>
    </div>
  </div>
</div>

<div class="table-card">
  <!-- Toolbar -->
  <div class="table-toolbar">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
      <div class="input-group" style="width:260px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Cari nama, telepon, email..."
          value="<?= e($search) ?>" autocomplete="off">
      </div>
      <select name="tier" class="form-select" style="width:140px" onchange="this.form.submit()">
        <option value="">Semua Tier</option>
        <option value="regular" <?= $tier==='regular'?'selected':'' ?>>Regular</option>
        <option value="vip"     <?= $tier==='vip'    ?'selected':'' ?>>VIP</option>
        <option value="vvip"    <?= $tier==='vvip'   ?'selected':'' ?>>VVIP</option>
      </select>
      <select name="is_active" class="form-select" style="width:140px" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="1" <?= $isActive===1?'selected':'' ?>>Aktif</option>
        <option value="0" <?= $isActive===0?'selected':'' ?>>Nonaktif</option>
      </select>
      <button type="submit" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <?php if ($search || $tier || $isActive >= 0): ?>
        <a href="<?= url('master/customer') ?>" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>
    <?php if (can('CUSTOMER_CREATE')): ?>
      <a href="<?= url('master/customer/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Pelanggan
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($customers)): ?>
    <div class="table-empty">
      <i class="bi bi-people text-muted"></i>
      <p class="mb-2">Belum ada data pelanggan.</p>
      <?php if (can('CUSTOMER_CREATE')): ?>
        <a href="<?= url('master/customer/create') ?>" class="btn btn-primary btn-sm">Tambah Pertama</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>
            <a href="?<?= http_build_query(array_merge($_GET,['sort'=>'c.name','dir'=>$sortBy==='c.name'&&$sortDir==='ASC'?'DESC':'ASC'])) ?>"
               class="text-decoration-none text-muted">
              Pelanggan
              <?php if ($sortBy==='c.name'): ?><i class="bi bi-arrow-<?= $sortDir==='ASC'?'up':'down' ?>"></i><?php endif; ?>
            </a>
          </th>
          <th>Tier</th>
          <th>Kontak</th>
          <th>Ukuran Cincin</th>
          <th>Status</th>
          <th>Terdaftar</th>
          <th style="width:100px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $i => $c): ?>
        <tr>
          <td class="text-muted small"><?= $offset+$i+1 ?></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-circle" style="width:36px;height:36px;border-radius:50%;flex-shrink:0;
                background:<?= $c['tier']==='vvip'?'#FFF7ED':($c['tier']==='vip'?'#EFF6FF':'#F3F4F6') ?>;
                color:<?= $c['tier']==='vvip'?'#C2410C':($c['tier']==='vip'?'#1D4ED8':'#374151') ?>;
                display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600">
                <?= e(name_initials($c['name'])) ?>
              </div>
              <div>
                <a href="<?= url('master/customer/detail') ?>?id=<?= $c['id'] ?>"
                   class="fw-600 text-decoration-none text-dark d-block lh-1 mb-1">
                  <?= e($c['name']) ?>
                </a>
                <small class="text-muted font-mono"><?= e($c['customer_code']) ?></small>
              </div>
            </div>
          </td>
          <td>
            <?php $tc = $tierConfig[$c['tier']] ?? $tierConfig['regular']; ?>
            <span class="badge <?= $tc['class'] ?>"><?= $tc['label'] ?></span>
          </td>
          <td class="small">
            <?php if ($c['phone']): ?>
              <div><i class="bi bi-telephone me-1 text-muted"></i><?= e($c['phone']) ?></div>
            <?php endif; ?>
            <?php if ($c['email']): ?>
              <div><i class="bi bi-envelope me-1 text-muted"></i><?= e($c['email']) ?></div>
            <?php endif; ?>
            <?php if (!$c['phone'] && !$c['email']): ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-center"><?= $c['ring_size'] ? e($c['ring_size']) : '—' ?></td>
          <td>
            <?php if ($c['is_active']): ?>
              <span class="status-badge badge-active">Aktif</span>
            <?php else: ?>
              <span class="status-badge badge-inactive">Nonaktif</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted"><?= format_date($c['created_at']) ?></td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= url('master/customer/detail') ?>?id=<?= $c['id'] ?>"
                 class="btn btn-icon btn-outline-secondary btn-sm" title="Detail">
                <i class="bi bi-eye"></i>
              </a>
              <?php if (can('CUSTOMER_EDIT')): ?>
              <a href="<?= url('master/customer/edit') ?>?id=<?= $c['id'] ?>"
                 class="btn btn-icon btn-outline-primary btn-sm" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <?php endif; ?>
              <?php if (can('CUSTOMER_DELETE')): ?>
              <button type="button"
                class="btn btn-icon btn-outline-danger btn-sm btn-delete"
                data-id="<?= $c['id'] ?>" data-name="<?= e($c['name']) ?>" title="Hapus">
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

  <!-- Pagination -->
  <?php if ($pagData['total_pages'] > 1): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
    <small class="text-muted">
      Menampilkan <?= $pagData['from'] ?>–<?= $pagData['to'] ?> dari <?= number_format($total) ?> pelanggan
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
    if (!confirm('Hapus pelanggan "' + name + '"?\nData tidak dapat dipulihkan.')) return;
    erpAjax({
      url  : window.BASE_URL + '/master/customer/delete',
      data : { customer_id: id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
      onSuccess: function (res) { erpToast('success', res.message); setTimeout(()=>location.reload(),800); },
    });
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
