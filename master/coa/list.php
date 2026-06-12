<?php
/**
 * master/coa/list.php
 * Chart of Accounts (Bagan Akun)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';

require_auth(); require_permission('COA_VIEW');

// Ambil semua COA terurut
$coas = Database::fetchAll(
    "SELECT * FROM chart_of_accounts ORDER BY account_code ASC"
);

// Kelompokkan per tipe
$grouped = [];
foreach ($coas as $c) {
    $grouped[$c['account_type']][] = $c;
}

$typeLabels = [
    'asset'     => ['label'=>'Aset',                  'icon'=>'bi-bank2',             'color'=>'primary'],
    'liability' => ['label'=>'Kewajiban',              'icon'=>'bi-arrow-left-circle', 'color'=>'danger'],
    'equity'    => ['label'=>'Ekuitas',                'icon'=>'bi-person-fill',       'color'=>'success'],
    'revenue'   => ['label'=>'Pendapatan',             'icon'=>'bi-graph-up-arrow',    'color'=>'info'],
    'cogs'      => ['label'=>'Harga Pokok Penjualan',  'icon'=>'bi-cart-dash',         'color'=>'warning'],
    'expense'   => ['label'=>'Beban Operasional',      'icon'=>'bi-graph-down-arrow',  'color'=>'secondary'],
];

$pageTitle   = 'Chart of Accounts';
$breadcrumbs = [['label'=>'Master Data'],['label'=>'Chart of Accounts']];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <p class="text-muted mb-0 small">Bagan akun untuk jurnal otomatis dan pelaporan keuangan.</p>
  <?php if (can('COA_CREATE')): ?>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddCoa">
    <i class="bi bi-plus-lg me-1"></i>Tambah Akun
  </button>
  <?php endif; ?>
</div>

<!-- COA per tipe -->
<?php foreach ($typeLabels as $typeKey => $typeCfg): ?>
  <?php if (empty($grouped[$typeKey])) continue; ?>
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2">
      <span class="badge bg-<?=$typeCfg['color']?>-subtle text-<?=$typeCfg['color']?> border border-<?=$typeCfg['color']?>-subtle p-2">
        <i class="bi <?=$typeCfg['icon']?> fs-5"></i>
      </span>
      <span class="card-title"><?= e($typeCfg['label']) ?></span>
      <span class="badge bg-light text-muted border ms-auto"><?= count($grouped[$typeKey]) ?> akun</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:13px">
        <thead>
          <tr>
            <th>Kode Akun</th>
            <th>Nama Akun</th>
            <th>Normal Balance</th>
            <th>Tipe</th>
            <th>Status</th>
            <?php if (can('COA_EDIT')): ?><th style="width:80px">Aksi</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grouped[$typeKey] as $coa): ?>
          <tr class="<?= $coa['is_header'] ? 'table-light fw-600' : '' ?>">
            <td class="font-mono"><?= e($coa['account_code']) ?></td>
            <td>
              <?php
              $indent = ($coa['level'] - 1) * 20;
              echo '<span style="padding-left:' . $indent . 'px">';
              if ($coa['is_header']) echo '<i class="bi bi-folder2 me-1 text-muted"></i>';
              else echo '<i class="bi bi-dash me-1 text-muted"></i>';
              echo e($coa['account_name']) . '</span>';
              ?>
            </td>
            <td>
              <span class="badge <?= $coa['normal_balance']==='debit' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-success-subtle text-success border border-success-subtle' ?>">
                <?= ucfirst($coa['normal_balance']) ?>
              </span>
            </td>
            <td>
              <?php if ($coa['is_header']): ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:10px">Header</span>
              <?php else: ?>
                <span class="badge bg-light text-dark border" style="font-size:10px">Detail</span>
              <?php endif; ?>
            </td>
            <td>
              <?= $coa['is_active']
                ? '<span class="status-badge badge-active">Aktif</span>'
                : '<span class="status-badge badge-inactive">Nonaktif</span>' ?>
            </td>
            <?php if (can('COA_EDIT')): ?>
            <td>
              <button type="button"
                class="btn btn-icon btn-outline-primary btn-sm btn-edit-coa"
                data-id="<?= $coa['id'] ?>"
                data-code="<?= e($coa['account_code']) ?>"
                data-name="<?= e($coa['account_name']) ?>"
                data-type="<?= e($coa['account_type']) ?>"
                data-balance="<?= e($coa['normal_balance']) ?>"
                data-header="<?= $coa['is_header'] ?>"
                data-active="<?= $coa['is_active'] ?>"
                title="Edit Akun">
                <i class="bi bi-pencil"></i>
              </button>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>

<!-- Modal Tambah Akun -->
<?php if (can('COA_CREATE')): ?>
<div class="modal fade" id="modalAddCoa" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Akun Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('master/coa/save') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Kode Akun <span class="required">*</span></label>
              <input type="text" name="account_code" class="form-control font-mono"
                placeholder="1101" required maxlength="20">
              <div class="form-text small">Contoh: 1101 (4 digit numerik)</div>
            </div>
            <div class="col-md-7">
              <label class="form-label">Nama Akun <span class="required">*</span></label>
              <input type="text" name="account_name" class="form-control"
                placeholder="Kas Tunai" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipe Akun <span class="required">*</span></label>
              <select name="account_type" class="form-select" required>
                <?php foreach ($typeLabels as $k=>$v): ?>
                  <option value="<?= $k ?>"><?= e($v['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Normal Balance <span class="required">*</span></label>
              <select name="normal_balance" class="form-select" required>
                <option value="debit">Debit</option>
                <option value="credit">Credit</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Level</label>
              <input type="number" name="level" class="form-control" value="3" min="1" max="5">
            </div>
            <div class="col-md-8">
              <label class="form-label">Keterangan</label>
              <input type="text" name="description" class="form-control" placeholder="Opsional">
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_header" id="isHeader">
                <label class="form-check-label" for="isHeader">Akun Header (tidak bisa diposting)</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Akun -->
<div class="modal fade" id="modalEditCoa" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Akun</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('master/coa/update') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <input type="hidden" name="coa_id" id="editCoaId">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Kode Akun</label>
              <input type="text" id="editCoaCode" class="form-control font-mono bg-light" readonly>
            </div>
            <div class="col-12">
              <label class="form-label">Nama Akun <span class="required">*</span></label>
              <input type="text" name="account_name" id="editCoaName" class="form-control" required>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" id="editCoaActive">
                <label class="form-check-label" for="editCoaActive">Akun Aktif</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraJs = <<<'JS'
<script>
$(function () {
  $(document).on('click', '.btn-edit-coa', function () {
    $('#editCoaId').val($(this).data('id'));
    $('#editCoaCode').val($(this).data('code'));
    $('#editCoaName').val($(this).data('name'));
    $('#editCoaActive').prop('checked', $(this).data('active') == 1);
    new bootstrap.Modal(document.getElementById('modalEditCoa')).show();
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
