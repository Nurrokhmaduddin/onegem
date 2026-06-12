<?php
/**
 * master/diamond/form.php
 * Form pendaftaran / edit berlian
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

$diamondId = (int)get_param('id', 0);
$isEdit    = $diamondId > 0;
$existing  = null;

if ($isEdit) {
    require_permission('DIAMOND_EDIT');
    $existing = DiamondRepository::findById($diamondId);
    if (!$existing) { flash_set('error','Data berlian tidak ditemukan.'); redirect(url('master/diamond')); }
    if (in_array($existing['status'],['sold','retired']))
        { flash_set('error','Berlian ini tidak dapat diedit.'); redirect(url('master/diamond/detail').'?id='.$diamondId); }
} else {
    require_permission('DIAMOND_CREATE');
}

$errors = $_SESSION['form_errors'] ?? [];
$form   = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
if (empty($form) && $existing) $form = $existing;
$form = array_merge([
    'factory_barcode'=>'','supplier_id'=>'','warehouse_id'=>'','acquisition_type'=>'consignment',
    'acquired_at'=>date('Y-m-d'),'shape_id'=>'','carat_weight'=>'',
    'color_grade'=>'','clarity_grade'=>'','cut_grade'=>'','polish'=>'',
    'symmetry'=>'','fluorescence'=>'','measurements'=>'','table_percent'=>'','depth_percent'=>'',
    'stone_count'=>1,'metal_type'=>'','metal_weight_gr'=>'','karat'=>'',
    'cost_price_usd'=>'','selling_price_usd'=>'','notes'=>'',
    'cert_number'=>'','cert_type'=>'','cert_issuer'=>'','cert_issue_date'=>'',
], $form);

// Jika edit, ambil data sertifikat existing
if ($isEdit && empty($form['cert_number'])) {
    $cert = DiamondRepository::getCertificate($diamondId);
    if ($cert) {
        $form['cert_number']    = $cert['cert_number'];
        $form['cert_type']      = $cert['cert_type'];
        $form['cert_issuer']    = $cert['issuer']     ?? '';
        $form['cert_issue_date']= $cert['issue_date'] ?? '';
    }
}

$suppliers  = SupplierRepository::getDropdown();
$warehouses = WarehouseRepository::getAllGrouped();
$shapes     = DiamondRepository::getShapes();
$rate       = DiamondRepository::getActiveRate();

$pageTitle   = $isEdit ? 'Edit Data Berlian' : 'Daftarkan Berlian Baru';
$breadcrumbs = [
    ['label'=>'Master Data'],
    ['label'=>'Berlian','url'=>url('master/diamond')],
    ['label'=>$pageTitle],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">
<div class="col-lg-8">

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <i class="bi bi-exclamation-triangle me-2"></i><strong>Periksa kembali:</strong>
  <ul class="mb-0 mt-1 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST"
      action="<?= url($isEdit ? 'master/diamond/update' : 'master/diamond/save') ?>"
      id="diamondForm" class="no-double-submit" novalidate>
  <?= csrf_field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="diamond_id" value="<?= $diamondId ?>"><?php endif; ?>

  <!-- Identitas & Perolehan -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-upc-scan me-2 text-primary"></i>Identitas & Perolehan</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <?php if ($isEdit): ?>
        <div class="col-12">
          <label class="form-label text-muted small">Kode Internal</label>
          <div class="fw-600 font-mono fs-6 text-primary"><?= e($existing['internal_code']) ?></div>
        </div>
        <?php endif; ?>
        <div class="col-md-6">
          <label class="form-label">Barcode Pabrik / Supplier</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-upc"></i></span>
            <input type="text" name="factory_barcode" class="form-control"
              value="<?= e($form['factory_barcode']) ?>" placeholder="Scan atau ketik barcode pabrik">
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tanggal Masuk <span class="required">*</span></label>
          <input type="date" name="acquired_at" class="form-control <?= isset($errors['acquired_at'])?'is-invalid':'' ?>"
            value="<?= e($form['acquired_at']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Supplier <span class="required">*</span></label>
          <select name="supplier_id" class="form-select <?= isset($errors['supplier_id'])?'is-invalid':'' ?>" required>
            <option value="">-- Pilih Supplier --</option>
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $form['supplier_id']==$s['id']?'selected':'' ?>>
                <?= e($s['name']) ?> (<?= e($s['currency']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['supplier_id'])): ?><div class="invalid-feedback"><?= e($errors['supplier_id']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Jenis Perolehan <span class="required">*</span></label>
          <select name="acquisition_type" class="form-select <?= isset($errors['acquisition_type'])?'is-invalid':'' ?>">
            <option value="consignment"         <?= $form['acquisition_type']==='consignment'         ?'selected':'' ?>>Konsinyasi (Titipan)</option>
            <option value="purchase_returnable" <?= $form['acquisition_type']==='purchase_returnable' ?'selected':'' ?>>Pembelian (Bisa Retur)</option>
            <option value="purchase_final"      <?= $form['acquisition_type']==='purchase_final'      ?'selected':'' ?>>Pembelian Putus / Project</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Lokasi Penyimpanan <span class="required">*</span></label>
          <select name="warehouse_id" class="form-select <?= isset($errors['warehouse_id'])?'is-invalid':'' ?>" required>
            <option value="">-- Pilih Lokasi --</option>
            <?php foreach ($warehouses as $branch): ?>
              <optgroup label="<?= e($branch['branch_name']) ?>">
                <?php foreach ($branch['warehouses'] as $w): ?>
                  <option value="<?= $w['id'] ?>" <?= $form['warehouse_id']==$w['id']?'selected':'' ?>>
                    <?= e($w['name']) ?> (<?= e($w['type']) ?>)
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['warehouse_id'])): ?><div class="invalid-feedback"><?= e($errors['warehouse_id']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Spesifikasi 4Cs -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-gem me-2 text-primary"></i>Spesifikasi Berlian (4Cs)</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Bentuk (Shape)</label>
          <select name="shape_id" class="form-select">
            <option value="">-- Pilih --</option>
            <?php foreach ($shapes as $sh): ?>
              <option value="<?= $sh['id'] ?>" <?= $form['shape_id']==$sh['id']?'selected':'' ?>>
                <?= e($sh['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Berat (Carat) <span class="required">*</span></label>
          <div class="input-group">
            <input type="number" name="carat_weight" step="0.001" min="0.001" max="99.999"
              class="form-control <?= isset($errors['carat_weight'])?'is-invalid':'' ?>"
              value="<?= e($form['carat_weight']) ?>" placeholder="0.000" id="inputCarat">
            <span class="input-group-text">ct</span>
            <?php if (isset($errors['carat_weight'])): ?><div class="invalid-feedback"><?= e($errors['carat_weight']) ?></div><?php endif; ?>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Jumlah Batu</label>
          <input type="number" name="stone_count" min="1" class="form-control"
            value="<?= e($form['stone_count']) ?>" placeholder="1">
        </div>
        <div class="col-md-3">
          <label class="form-label">Warna (Color) <span class="required">*</span></label>
          <select name="color_grade" class="form-select <?= isset($errors['color_grade'])?'is-invalid':'' ?>">
            <option value="">--</option>
            <?php foreach (DiamondService::COLOR_GRADES as $c): ?>
              <option value="<?= $c ?>" <?= $form['color_grade']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Kejernihan (Clarity) <span class="required">*</span></label>
          <select name="clarity_grade" class="form-select <?= isset($errors['clarity_grade'])?'is-invalid':'' ?>">
            <option value="">--</option>
            <?php foreach (DiamondService::CLARITY_GRADES as $c): ?>
              <option value="<?= $c ?>" <?= $form['clarity_grade']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Potongan (Cut)</label>
          <select name="cut_grade" class="form-select">
            <option value="">--</option>
            <?php foreach (DiamondService::CUT_GRADES as $c): ?>
              <option value="<?= $c ?>" <?= $form['cut_grade']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Fluorescence</label>
          <select name="fluorescence" class="form-select">
            <option value="">--</option>
            <?php foreach (DiamondService::FLUOR_OPTIONS as $f): ?>
              <option value="<?= $f ?>" <?= $form['fluorescence']===$f?'selected':'' ?>><?= $f ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Polish</label>
          <select name="polish" class="form-select">
            <option value="">--</option>
            <?php foreach (DiamondService::CUT_GRADES as $c): ?>
              <option value="<?= $c ?>" <?= $form['polish']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Symmetry</label>
          <select name="symmetry" class="form-select">
            <option value="">--</option>
            <?php foreach (DiamondService::CUT_GRADES as $c): ?>
              <option value="<?= $c ?>" <?= $form['symmetry']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Measurements</label>
          <input type="text" name="measurements" class="form-control"
            value="<?= e($form['measurements']) ?>" placeholder="6.40-6.43x3.96">
        </div>
        <div class="col-md-3">
          <label class="form-label">Table %</label>
          <input type="number" name="table_percent" step="0.01" min="0" max="100"
            class="form-control" value="<?= e($form['table_percent']) ?>" placeholder="0.00">
        </div>
      </div>
    </div>
  </div>

  <!-- Sertifikat -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-award me-2 text-primary"></i>Sertifikat</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Tipe Sertifikat</label>
          <select name="cert_type" class="form-select" id="certType">
            <option value="">-- Tidak ada --</option>
            <option value="GIA"     <?= $form['cert_type']==='GIA'     ?'selected':'' ?>>GIA</option>
            <option value="IGI"     <?= $form['cert_type']==='IGI'     ?'selected':'' ?>>IGI</option>
            <option value="HRD"     <?= $form['cert_type']==='HRD'     ?'selected':'' ?>>HRD</option>
            <option value="LOCAL"   <?= $form['cert_type']==='LOCAL'   ?'selected':'' ?>>Lab Lokal</option>
            <option value="FACTORY" <?= $form['cert_type']==='FACTORY' ?'selected':'' ?>>Sertifikat Pabrik</option>
            <option value="OTHER"   <?= $form['cert_type']==='OTHER'   ?'selected':'' ?>>Lainnya</option>
          </select>
        </div>
        <div class="col-md-4" id="certNumberWrap">
          <label class="form-label">Nomor Sertifikat</label>
          <input type="text" name="cert_number" class="form-control"
            value="<?= e($form['cert_number']) ?>" placeholder="Nomor sertifikat">
        </div>
        <div class="col-md-4">
          <label class="form-label">Tanggal Sertifikat</label>
          <input type="date" name="cert_issue_date" class="form-control"
            value="<?= e($form['cert_issue_date']) ?>">
        </div>
        <div class="col-md-6" id="certIssuerWrap">
          <label class="form-label">Lembaga Penerbit</label>
          <input type="text" name="cert_issuer" class="form-control"
            value="<?= e($form['cert_issuer']) ?>" placeholder="Nama laboratorium/lembaga">
        </div>
      </div>
    </div>
  </div>

  <!-- Catatan -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-sticky-note me-2 text-primary"></i>Catatan</div>
    <div class="card-body p-3">
      <textarea name="notes" class="form-control" rows="2"
        placeholder="Catatan tambahan tentang berlian ini..."><?= e($form['notes']) ?></textarea>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('master/diamond') ?>" class="btn btn-secondary"><i class="bi bi-x me-1"></i>Batal</a>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check-lg me-1"></i>
      <?= $isEdit ? 'Simpan Perubahan' : 'Daftarkan Berlian' ?>
    </button>
  </div>
</form>

</div>

<!-- Sidebar: Harga & Preview -->
<div class="col-lg-4">
  <div class="card mb-3 sticky-top" style="top:80px">
    <div class="card-header"><i class="bi bi-price-tag-fill me-2 text-primary"></i>Harga</div>
    <div class="card-body p-4">
      <div class="mb-3">
        <label class="form-label">Harga Pokok (USD)</label>
        <div class="input-group">
          <span class="input-group-text fw-600">$</span>
          <input type="number" name="cost_price_usd" form="diamondForm" step="0.01" min="0"
            class="form-control" value="<?= e($form['cost_price_usd']) ?>"
            placeholder="0.00" id="inputCost">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Harga Jual (USD) <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-group-text fw-600">$</span>
          <input type="number" name="selling_price_usd" form="diamondForm" step="0.01" min="0"
            class="form-control <?= isset($errors['selling_price_usd'])?'is-invalid':'' ?>"
            value="<?= e($form['selling_price_usd']) ?>"
            placeholder="0.00" id="inputSell">
        </div>
        <?php if (isset($errors['selling_price_usd'])): ?><div class="text-danger small mt-1"><?= e($errors['selling_price_usd']) ?></div><?php endif; ?>
      </div>

      <!-- Live pricing preview -->
      <div class="bg-light border rounded p-3" id="pricingPreview" style="display:none">
        <div class="text-muted small mb-2">Kurs: Rp <?= number_format($rate,0,',','.') ?> / USD</div>
        <div class="d-flex justify-content-between mb-1">
          <span class="small">Harga Jual (IDR):</span>
          <strong class="text-primary" id="sellIdr">—</strong>
        </div>
        <div class="d-flex justify-content-between">
          <span class="small">Margin:</span>
          <strong id="marginPct">—</strong>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<?php
$rateJs = $rate;
$extraJs = <<<JS
<script>
const RATE = {$rateJs};
function formatIDR(n) {
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}
function updatePricing() {
  const cost = parseFloat(\$('#inputCost').val()) || 0;
  const sell = parseFloat(\$('#inputSell').val()) || 0;
  if (sell > 0) {
    \$('#pricingPreview').show();
    \$('#sellIdr').text(formatIDR(sell * RATE));
    if (cost > 0) {
      const margin = ((sell - cost) / cost * 100).toFixed(1);
      \$('#marginPct').text(margin + '%').css('color', margin >= 20 ? '#15803D' : margin >= 10 ? '#B45309' : '#DC2626');
    } else {
      \$('#marginPct').text('—');
    }
  } else {
    \$('#pricingPreview').hide();
  }
}
\$('#inputCost, #inputSell').on('input', updatePricing);
updatePricing();
// Sembunyikan field sertifikat jika tipe tidak dipilih
function toggleCertFields() {
  const hasType = \$('#certType').val() !== '';
  \$('#certNumberWrap, #certIssuerWrap').toggle(hasType);
}
\$('#certType').on('change', toggleCertFields);
toggleCertFields();
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
