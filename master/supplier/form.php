<?php
/**
 * master/supplier/form.php
 * Form create / edit supplier
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';

require_auth();

$supplierId = (int)get_param('id', 0);
$isEdit     = $supplierId > 0;
$existing   = null;

if ($isEdit) {
    require_permission('SUPPLIER_EDIT');
    $existing = SupplierRepository::findById($supplierId);
    if (!$existing) { flash_set('error','Supplier tidak ditemukan.'); redirect(url('master/supplier')); }
} else {
    require_permission('SUPPLIER_CREATE');
}

$errors = $_SESSION['form_errors'] ?? [];
$form   = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

if (empty($form) && $existing) $form = $existing;
$form = array_merge([
    'name'=>'','contact_person'=>'','phone'=>'','phone2'=>'','email'=>'','address'=>'',
    'type'=>'both','currency'=>'USD','discount_percent'=>'0',
    'payment_terms'=>'','bank_name'=>'','bank_account'=>'','bank_holder'=>'',
    'notes'=>'','is_active'=>1,
], $form);

$pageTitle   = $isEdit ? 'Edit Supplier' : 'Tambah Supplier Baru';
$breadcrumbs = [
    ['label'=>'Master Data'],
    ['label'=>'Supplier','url'=>url('master/supplier')],
    ['label'=>$pageTitle],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-9">

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <i class="bi bi-exclamation-triangle me-2"></i><strong>Periksa kembali:</strong>
  <ul class="mb-0 mt-1 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST"
      action="<?= url($isEdit ? 'master/supplier/update' : 'master/supplier/save') ?>"
      class="no-double-submit" novalidate>
  <?= csrf_field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="supplier_id" value="<?= $supplierId ?>"><?php endif; ?>

  <!-- Identitas -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-building me-2 text-primary"></i>Identitas Supplier</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Nama Supplier <span class="required">*</span></label>
          <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
            value="<?= e($form['name']) ?>" placeholder="Nama perusahaan/supplier" required autofocus>
          <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Jenis <span class="required">*</span></label>
          <select name="type" class="form-select <?= isset($errors['type'])?'is-invalid':'' ?>">
            <option value="consignment" <?= $form['type']==='consignment'?'selected':'' ?>>Konsinyasi</option>
            <option value="purchase"    <?= $form['type']==='purchase'   ?'selected':'' ?>>Pembelian</option>
            <option value="both"        <?= $form['type']==='both'       ?'selected':'' ?>>Konsinyasi & Pembelian</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Kontak Person</label>
          <input type="text" name="contact_person" class="form-control"
            value="<?= e($form['contact_person']) ?>" placeholder="Nama PIC">
        </div>
        <div class="col-md-4">
          <label class="form-label">No. Telepon</label>
          <input type="text" name="phone" class="form-control"
            value="<?= e($form['phone']) ?>" placeholder="021-xxxxxxx">
        </div>
        <div class="col-md-4">
          <label class="form-label">No. Telepon 2</label>
          <input type="text" name="phone2" class="form-control"
            value="<?= e($form['phone2']) ?>" placeholder="021-xxxxxxx">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>"
            value="<?= e($form['email']) ?>" placeholder="supplier@domain.com">
        </div>
        <div class="col-md-6">
          <label class="form-label">Payment Terms</label>
          <input type="text" name="payment_terms" class="form-control"
            value="<?= e($form['payment_terms']) ?>" placeholder="NET30, NET60, COD">
        </div>
        <div class="col-12">
          <label class="form-label">Alamat</label>
          <textarea name="address" class="form-control" rows="2"
            placeholder="Alamat lengkap supplier"><?= e($form['address']) ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Harga & Diskon -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-currency-exchange me-2 text-primary"></i>Harga & Diskon</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Mata Uang <span class="required">*</span></label>
          <select name="currency" class="form-select">
            <option value="USD" <?= $form['currency']==='USD'?'selected':'' ?>>USD (Dollar)</option>
            <option value="IDR" <?= $form['currency']==='IDR'?'selected':'' ?>>IDR (Rupiah)</option>
          </select>
          <div class="form-text small">Mata uang yang digunakan di invoice dari supplier ini.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Default Diskon (%)</label>
          <div class="input-group">
            <input type="number" name="discount_percent" step="0.01" min="0" max="100"
              class="form-control" value="<?= e($form['discount_percent']) ?>" placeholder="0.00">
            <span class="input-group-text">%</span>
          </div>
          <div class="form-text small">Diskon default dari supplier ini untuk semua item.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Rekening Bank -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-bank me-2 text-primary"></i>Rekening Bank (untuk settlement)</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nama Bank</label>
          <input type="text" name="bank_name" class="form-control"
            value="<?= e($form['bank_name']) ?>" placeholder="BCA, Mandiri, BNI, dll">
        </div>
        <div class="col-md-4">
          <label class="form-label">No. Rekening</label>
          <input type="text" name="bank_account" class="form-control"
            value="<?= e($form['bank_account']) ?>" placeholder="1234567890">
        </div>
        <div class="col-md-4">
          <label class="form-label">Atas Nama</label>
          <input type="text" name="bank_holder" class="form-control"
            value="<?= e($form['bank_holder']) ?>" placeholder="Nama pemilik rekening">
        </div>
      </div>
    </div>
  </div>

  <!-- Catatan -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-sticky-note me-2 text-primary"></i>Catatan</div>
    <div class="card-body p-4">
      <textarea name="notes" class="form-control" rows="3"
        placeholder="Catatan internal tentang supplier ini..."><?= e($form['notes']) ?></textarea>
      <?php if ($isEdit): ?>
      <div class="form-check form-switch mt-3">
        <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
          <?= $form['is_active'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="isActive">Supplier Aktif</label>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('master/supplier') ?>" class="btn btn-secondary">
      <i class="bi bi-x me-1"></i>Batal
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check-lg me-1"></i>
      <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Supplier' ?>
    </button>
  </div>
</form>
</div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
