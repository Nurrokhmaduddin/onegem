<?php
/**
 * master/customer/form.php
 * Form create / edit pelanggan
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

$customerId = (int)get_param('id', 0);
$isEdit     = $customerId > 0;
$existing   = null;

if ($isEdit) {
    require_permission('CUSTOMER_EDIT');
    $existing = CustomerRepository::findById($customerId);
    if (!$existing) { flash_set('error','Pelanggan tidak ditemukan.'); redirect(url('master/customer')); }
} else {
    require_permission('CUSTOMER_CREATE');
}

// Repopulate dari session jika ada error
$errors = $_SESSION['form_errors'] ?? [];
$form   = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

if (empty($form) && $existing) $form = $existing;

$form = array_merge([
    'name'=>'','identity_type'=>'ktp','identity_number'=>'',
    'phone'=>'','phone2'=>'','email'=>'','address'=>'',
    'birth_date'=>'','gender'=>'','tier'=>'regular',
    'ring_size'=>'','preferences'=>'','notes'=>'','is_active'=>1,
], $form);

$pageTitle   = $isEdit ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru';
$breadcrumbs = [
    ['label'=>'Master Data'],
    ['label'=>'Pelanggan','url'=>url('master/customer')],
    ['label'=>$pageTitle],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-9 col-xl-8">

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <i class="bi bi-exclamation-triangle me-2"></i>
  <strong>Terdapat kesalahan pada form:</strong>
  <ul class="mb-0 mt-1 ps-3">
    <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST"
      action="<?= url($isEdit ? 'master/customer/update' : 'master/customer/save') ?>"
      class="no-double-submit" novalidate>
  <?= csrf_field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="customer_id" value="<?= $customerId ?>"><?php endif; ?>

  <!-- Card: Identitas -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-person-badge me-2 text-primary"></i>Identitas Pelanggan</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Nama Lengkap <span class="required">*</span></label>
          <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
            value="<?= e($form['name']) ?>" placeholder="Nama lengkap pelanggan" required autofocus>
          <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tier <span class="required">*</span></label>
          <select name="tier" class="form-select <?= isset($errors['tier'])?'is-invalid':'' ?>">
            <option value="regular" <?= $form['tier']==='regular'?'selected':'' ?>>Regular</option>
            <option value="vip"     <?= $form['tier']==='vip'    ?'selected':'' ?>>VIP</option>
            <option value="vvip"    <?= $form['tier']==='vvip'   ?'selected':'' ?>>VVIP</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Jenis Identitas</label>
          <select name="identity_type" class="form-select">
            <option value="">-- Pilih --</option>
            <option value="ktp"      <?= $form['identity_type']==='ktp'      ?'selected':'' ?>>KTP</option>
            <option value="passport" <?= $form['identity_type']==='passport' ?'selected':'' ?>>Passport</option>
            <option value="sim"      <?= $form['identity_type']==='sim'      ?'selected':'' ?>>SIM</option>
            <option value="other"    <?= $form['identity_type']==='other'    ?'selected':'' ?>>Lainnya</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nomor Identitas</label>
          <input type="text" name="identity_number" class="form-control"
            value="<?= e($form['identity_number']) ?>" placeholder="Nomor KTP/Passport">
        </div>
        <div class="col-md-4">
          <label class="form-label">Jenis Kelamin</label>
          <select name="gender" class="form-select">
            <option value="">-- Pilih --</option>
            <option value="M" <?= $form['gender']==='M'?'selected':'' ?>>Laki-laki</option>
            <option value="F" <?= $form['gender']==='F'?'selected':'' ?>>Perempuan</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tanggal Lahir</label>
          <input type="date" name="birth_date" class="form-control"
            value="<?= e($form['birth_date']) ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Card: Kontak -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-telephone me-2 text-primary"></i>Informasi Kontak</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">No. Telepon Utama</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="text" name="phone" class="form-control <?= isset($errors['phone'])?'is-invalid':'' ?>"
              value="<?= e($form['phone']) ?>" placeholder="08xxxxxxxxxx">
            <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">No. Telepon Alternatif</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="text" name="phone2" class="form-control"
              value="<?= e($form['phone2']) ?>" placeholder="08xxxxxxxxxx">
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>"
              value="<?= e($form['email']) ?>" placeholder="email@domain.com">
            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Ukuran Cincin</label>
          <input type="text" name="ring_size" class="form-control"
            value="<?= e($form['ring_size']) ?>" placeholder="misal: 17, 18, 20" maxlength="10">
        </div>
        <div class="col-12">
          <label class="form-label">Alamat</label>
          <textarea name="address" class="form-control" rows="2"
            placeholder="Alamat lengkap pelanggan"><?= e($form['address']) ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Card: Preferensi & Catatan -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-sticky-note me-2 text-primary"></i>Preferensi & Catatan</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Preferensi Desain / Gaya</label>
          <textarea name="preferences" class="form-control" rows="2"
            placeholder="Contoh: suka cincin minimalis, preferensi berlian VVS1..."><?= e($form['preferences']) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Catatan Internal</label>
          <textarea name="notes" class="form-control" rows="2"
            placeholder="Catatan internal tentang pelanggan ini..."><?= e($form['notes']) ?></textarea>
        </div>
        <?php if ($isEdit): ?>
        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
              <?= $form['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActive">Pelanggan Aktif</label>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('master/customer') ?>" class="btn btn-secondary">
      <i class="bi bi-x me-1"></i>Batal
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check-lg me-1"></i>
      <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Pelanggan' ?>
    </button>
  </div>
</form>

</div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
