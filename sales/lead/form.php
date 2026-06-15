<?php
/**
 * sales/lead/form.php — Form create/edit lead
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

$leadId   = (int)get_param('id', 0);
$isEdit   = $leadId > 0;
$existing = null;

if ($isEdit) {
    require_permission('LEAD_EDIT');
    $existing = LeadRepository::findById($leadId);
    if (!$existing) { flash_set('error','Lead tidak ditemukan.'); redirect(url('sales/lead')); }
    if ($existing['status'] === 'converted')
        { flash_set('error','Lead yang sudah dikonversi tidak dapat diedit.'); redirect(url('sales/lead/detail').'?id='.$leadId); }
} else {
    require_permission('LEAD_CREATE');
}

$errors = $_SESSION['form_errors'] ?? [];
$form   = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
if (empty($form) && $existing) $form = $existing;
$form = array_merge([
    'name'=>'','phone'=>'','email'=>'','source'=>'walk_in',
    'interest'=>'','assigned_to'=>'','notes'=>'',
], $form);

$salespersons = LeadRepository::getSalespersons();
$pageTitle    = $isEdit ? 'Edit Lead' : 'Tambah Lead Baru';
$breadcrumbs  = [
    ['label'=>'Penjualan'],
    ['label'=>'Lead','url'=>url('sales/lead')],
    ['label'=>$pageTitle],
];
require_once __DIR__ . '/../../layout/header.php';
?>
<div class="row justify-content-center">
<div class="col-lg-7">
<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <i class="bi bi-exclamation-triangle me-2"></i><strong>Periksa kembali:</strong>
  <ul class="mb-0 mt-1 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<div class="card">
  <div class="card-header"><i class="bi bi-person-plus me-2 text-primary"></i><?= e($pageTitle) ?></div>
  <div class="card-body p-4">
    <form method="POST"
          action="<?= url($isEdit ? 'sales/lead/update' : 'sales/lead/save') ?>"
          class="no-double-submit">
      <?= csrf_field() ?>
      <?php if ($isEdit): ?><input type="hidden" name="lead_id" value="<?= $leadId ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Nama <span class="required">*</span></label>
          <input type="text" name="name"
            class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
            value="<?= e($form['name']) ?>" required autofocus>
          <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Sumber <span class="required">*</span></label>
          <select name="source" class="form-select">
            <?php foreach (LeadService::SOURCE_LABELS as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $form['source']===$k?'selected':'' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">No. Telepon</label>
          <input type="text" name="phone" class="form-control <?= isset($errors['phone'])?'is-invalid':'' ?>"
            value="<?= e($form['phone']) ?>" placeholder="08xxxxxxxxxx">
          <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>"
            value="<?= e($form['email']) ?>" placeholder="email@domain.com">
          <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
        </div>
        <div class="col-12">
          <label class="form-label">Produk yang Diminati / Keterangan</label>
          <textarea name="interest" class="form-control" rows="2"
            placeholder="Contoh: Berlian cincin 1ct, budget $5000..."><?= e($form['interest']) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Assign ke Salesperson</label>
          <select name="assigned_to" class="form-select">
            <option value="">-- Belum Assigned --</option>
            <?php foreach ($salespersons as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $form['assigned_to']==$s['id']?'selected':'' ?>>
                <?= e($s['full_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Catatan Internal</label>
          <textarea name="notes" class="form-control" rows="2"
            placeholder="Catatan internal tentang lead ini..."><?= e($form['notes']) ?></textarea>
        </div>
      </div>
      <div class="d-flex gap-2 justify-content-end border-top pt-3 mt-3">
        <a href="<?= url('sales/lead') ?>" class="btn btn-secondary"><i class="bi bi-x me-1"></i>Batal</a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i><?= $isEdit?'Simpan Perubahan':'Tambah Lead' ?>
        </button>
      </div>
    </form>
  </div>
</div>
</div>
</div>
<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
