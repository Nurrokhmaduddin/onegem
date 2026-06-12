<?php
/**
 * master/warehouse/list.php
 * Manajemen Gudang & Cabang
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth(); require_permission('WAREHOUSE_VIEW');

$branches   = WarehouseRepository::getAllBranches();
$warehouses = WarehouseRepository::getAllGrouped(false);
$summary    = WarehouseRepository::getStockSummary();
$rate       = Database::fetchOne(
    "SELECT rate_to_idr FROM currencies WHERE code='USD' AND is_active=1 AND effective_date<=CURDATE() ORDER BY effective_date DESC LIMIT 1"
)['rate_to_idr'] ?? 16000;

$typeLabels = ['main'=>'Vault Utama','display'=>'Etalase','sales'=>'Tas Sales','transit'=>'Transit'];
$typeColors = ['main'=>'primary','display'=>'success','sales'=>'warning','transit'=>'secondary'];

$pageTitle   = 'Gudang & Cabang';
$breadcrumbs = [['label'=>'Master Data'],['label'=>'Gudang & Cabang']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Tab: Rangkuman Stok per Gudang -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Ringkasan Stok per Gudang</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px">
      <thead>
        <tr>
          <th>Cabang</th><th>Gudang</th><th>Tipe</th>
          <th class="text-center">Total Item</th>
          <th class="text-center">Tersedia</th>
          <th class="text-center">Reservasi</th>
          <th class="text-end">Nilai Stok (USD)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($summary)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data stok.</td></tr>
        <?php else: ?>
          <?php foreach ($summary as $row): ?>
          <tr>
            <td class="fw-500"><?= e($row['branch_name']) ?></td>
            <td><?= e($row['warehouse_name']) ?></td>
            <td>
              <?php $tc=$typeColors[$row['type']]??'secondary'; ?>
              <span class="badge bg-<?=$tc?>-subtle text-<?=$tc?> border border-<?=$tc?>-subtle">
                <?= e($typeLabels[$row['type']]??$row['type']) ?>
              </span>
            </td>
            <td class="text-center fw-600"><?= (int)$row['total_items'] ?></td>
            <td class="text-center text-success fw-600"><?= (int)$row['available'] ?></td>
            <td class="text-center text-warning fw-600"><?= (int)$row['reserved'] ?></td>
            <td class="text-end">$<?= number_format((float)$row['total_value_usd'],2) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-4">

  <!-- Cabang -->
  <div class="col-lg-5">
    <div class="table-card">
      <div class="table-toolbar">
        <span class="fw-600"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Cabang Toko</span>
        <?php if (can('WAREHOUSE_CREATE')): ?>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddBranch">
            <i class="bi bi-plus-lg me-1"></i>Tambah Cabang
          </button>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:13px">
          <thead><tr><th>Kode</th><th>Nama</th><th>Gudang</th><th>Tipe</th></tr></thead>
          <tbody>
            <?php foreach ($branches as $b): ?>
            <tr>
              <td class="font-mono fw-600"><?= e($b['branch_code']) ?></td>
              <td>
                <?= e($b['name']) ?>
                <?php if ($b['is_head_office']): ?>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size:10px">HO</span>
                <?php endif; ?>
              </td>
              <td>
                <?php $wCount = Database::fetchOne("SELECT COUNT(*) n FROM warehouses WHERE branch_id=?",[$b['id']])['n']??0; ?>
                <span class="badge bg-light text-dark border"><?= $wCount ?></span>
              </td>
              <td>
                <?php if ($b['is_active']): ?>
                  <span class="status-badge badge-active">Aktif</span>
                <?php else: ?>
                  <span class="status-badge badge-inactive">Nonaktif</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($branches)): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">Belum ada cabang.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Gudang -->
  <div class="col-lg-7">
    <div class="table-card">
      <div class="table-toolbar">
        <span class="fw-600"><i class="bi bi-archive-fill me-2 text-primary"></i>Gudang / Lokasi</span>
        <?php if (can('WAREHOUSE_CREATE')): ?>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddWarehouse">
            <i class="bi bi-plus-lg me-1"></i>Tambah Gudang
          </button>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:13px">
          <thead><tr><th>Kode</th><th>Nama</th><th>Cabang</th><th>Tipe</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($warehouses as $group): ?>
              <?php foreach ($group['warehouses'] as $w): ?>
              <tr>
                <td class="font-mono fw-600"><?= e($w['warehouse_code']) ?></td>
                <td><?= e($w['name']) ?></td>
                <td class="text-muted small"><?= e($w['branch_name']) ?></td>
                <td>
                  <?php $tc=$typeColors[$w['type']]??'secondary'; ?>
                  <span class="badge bg-<?=$tc?>-subtle text-<?=$tc?> border border-<?=$tc?>-subtle" style="font-size:11px">
                    <?= e($typeLabels[$w['type']]??$w['type']) ?>
                  </span>
                </td>
                <td>
                  <?= $w['is_active']
                    ? '<span class="status-badge badge-active">Aktif</span>'
                    : '<span class="status-badge badge-inactive">Nonaktif</span>' ?>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (empty($warehouses)): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">Belum ada gudang.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah Cabang -->
<?php if (can('WAREHOUSE_CREATE')): ?>
<div class="modal fade" id="modalAddBranch" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-geo-alt me-2"></i>Tambah Cabang Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('master/warehouse/save-branch') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-4">
              <label class="form-label">Kode Cabang <span class="required">*</span></label>
              <input type="text" name="branch_code" class="form-control text-uppercase font-mono"
                placeholder="HO, SMG..." maxlength="20" required style="text-transform:uppercase">
            </div>
            <div class="col-8">
              <label class="form-label">Nama Cabang <span class="required">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="Nama toko / cabang" required>
            </div>
            <div class="col-12">
              <label class="form-label">Alamat</label>
              <textarea name="address" class="form-control" rows="2" placeholder="Alamat lengkap"></textarea>
            </div>
            <div class="col-6">
              <label class="form-label">Telepon</label>
              <input type="text" name="phone" class="form-control" placeholder="024-xxxxxxx">
            </div>
            <div class="col-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="cabang@email.com">
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_head_office" id="isHO">
                <label class="form-check-label" for="isHO">Kantor Pusat (Head Office)</label>
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

<!-- Modal Tambah Gudang -->
<div class="modal fade" id="modalAddWarehouse" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-archive me-2"></i>Tambah Gudang Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('master/warehouse/save') ?>" class="no-double-submit">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Cabang <span class="required">*</span></label>
              <select name="branch_id" class="form-select" required>
                <option value="">-- Pilih Cabang --</option>
                <?php foreach ($branches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-5">
              <label class="form-label">Kode Gudang <span class="required">*</span></label>
              <input type="text" name="warehouse_code" class="form-control font-mono text-uppercase"
                placeholder="HO-MAIN" maxlength="20" required style="text-transform:uppercase">
            </div>
            <div class="col-7">
              <label class="form-label">Nama Gudang <span class="required">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="Vault Utama, Etalase..." required>
            </div>
            <div class="col-12">
              <label class="form-label">Tipe <span class="required">*</span></label>
              <select name="type" class="form-select" required>
                <option value="main">Vault Utama (main)</option>
                <option value="display">Etalase (display)</option>
                <option value="sales">Tas Sales (sales)</option>
                <option value="transit">Transit / Reparasi (transit)</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Keterangan</label>
              <input type="text" name="description" class="form-control" placeholder="Opsional">
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

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
