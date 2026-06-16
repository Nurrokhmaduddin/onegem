<?php
/**
 * sales/so/form.php — Form create/edit Sales Order (manual)
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

$id   = (int) get_param('id', 0);
$mode = $id ? 'edit' : 'create';

if ($mode === 'create') {
    require_permission('SO_CREATE');
    $so = null;
} else {
    require_permission('SO_EDIT');
    $so = SalesOrderRepository::findById($id);
    if (!$so) not_found('Sales Order tidak ditemukan.');
    if ($so['status'] !== 'draft')
        redirect_with_message(url('sales/so/detail') . '?id=' . $id,
            'error', 'Hanya SO berstatus Draft yang dapat diedit.');
}

$errors   = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

$salespersons = Database::fetchAll(
    "SELECT u.id, u.full_name FROM users u
       JOIN roles r ON r.id = u.role_id
      WHERE u.is_active = 1 AND r.role_code IN ('SALES','MANAGER','OWNER')
      ORDER BY u.full_name ASC"
);

$form = array_merge([
    'customer_id'    => '',
    'salesperson_id' => $_SESSION['user_id'] ?? '',
    'notes'          => '',
], $so ?? [], $formData);

$pageTitle   = $mode === 'create' ? 'Buat Sales Order' : 'Edit SO — ' . ($so['so_no'] ?? '');
$breadcrumbs = [
    ['label' => 'Penjualan'],
    ['label' => 'Sales Order', 'url' => url('sales/so/list')],
    ['label' => $pageTitle],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4 justify-content-center">
<div class="col-lg-7">

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <i class="bi bi-exclamation-triangle me-2"></i><strong>Periksa kembali:</strong>
  <ul class="mb-0 mt-1 ps-3">
    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST"
      action="<?= url($mode === 'create' ? 'sales/so/save' : 'sales/so/update') ?>"
      class="no-double-submit">
  <?= csrf_field() ?>
  <?php if ($mode === 'edit'): ?>
  <input type="hidden" name="so_id" value="<?= $id ?>">
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-header">
      <i class="bi bi-file-earmark-text me-2 text-primary"></i>
      <?= $mode === 'create' ? 'Informasi Sales Order Baru' : 'Edit Sales Order' ?>
    </div>
    <div class="card-body p-4">
      <div class="row g-3">

        <?php if ($mode === 'create'): ?>
        <!-- Customer search -->
        <div class="col-12">
          <label class="form-label">Customer <span class="required">*</span></label>
          <div class="input-group">
            <input type="text" id="customerSearch" class="form-control"
                   placeholder="Ketik nama atau kode customer..."
                   value="<?= e($formData['customer_name_display'] ?? '') ?>"
                   autocomplete="off">
            <button type="button" class="btn btn-outline-secondary" id="btnClearCustomer"
                    style="display:none"><i class="bi bi-x"></i></button>
          </div>
          <input type="hidden" name="customer_id" id="customerId"
                 value="<?= e($form['customer_id']) ?>" required>
          <div id="customerDropdown"
               class="position-absolute bg-white border rounded shadow-sm z-3 mt-1"
               style="width:380px;display:none;max-height:220px;overflow-y:auto"></div>
        </div>
        <?php else: ?>
        <div class="col-12">
          <label class="form-label">Customer</label>
          <input type="text" class="form-control bg-light"
                 value="<?= e($so['customer_name']) ?>" disabled>
        </div>
        <?php endif; ?>

        <div class="col-md-6">
          <label class="form-label">Salesperson</label>
          <select name="salesperson_id" class="form-select">
            <option value="">— Pilih —</option>
            <?php foreach ($salespersons as $sp): ?>
            <option value="<?= $sp['id'] ?>"
              <?= (int)$form['salesperson_id'] === $sp['id'] ? 'selected' : '' ?>>
              <?= e($sp['full_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Catatan</label>
          <textarea name="notes" class="form-control" rows="3"
                    placeholder="Catatan internal atau untuk customer..."><?= e($form['notes']) ?></textarea>
        </div>

      </div>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= $mode === 'edit' ? url('sales/so/detail').'?id='.$id : url('sales/so/list') ?>"
       class="btn btn-secondary">Batal</a>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check-lg me-1"></i>
      <?= $mode === 'create' ? 'Buat Sales Order' : 'Simpan Perubahan' ?>
    </button>
  </div>
</form>

<?php if ($mode === 'create'): ?>
<div class="card mt-3 border-0" style="background:#F8FAFC">
  <div class="card-body p-3 small text-muted">
    <i class="bi bi-lightbulb me-1 text-warning"></i>
    <strong>Tips:</strong> Jika sudah ada Reservasi yang disetujui, gunakan menu
    <a href="<?= url('sales/reservation') ?>">Reservasi Aktif</a> → tombol "Buat SO"
    untuk konversi otomatis.
  </div>
</div>
<?php endif; ?>

</div>
</div>

<?php if ($mode === 'create'):
$extraJs = <<<'JS'
<script>
(function () {
  const BASE = document.querySelector('meta[name="base-url"]')?.content ?? '';
  let csTimer;

  $('#customerSearch').on('input', function () {
    const q = $(this).val().trim();
    clearTimeout(csTimer);
    if (q.length < 2) { $('#customerDropdown').hide(); return; }
    csTimer = setTimeout(() => {
      $.get(BASE + '/master/customer/ajax/search.php', { q }, function (res) {
        if (!res.success || !res.data.length) {
          $('#customerDropdown').html('<div class="p-3 text-muted small">Tidak ada hasil.</div>').show();
          return;
        }
        const html = res.data.map(c =>
          `<div class="px-3 py-2 customer-option"
                style="cursor:pointer;border-bottom:1px solid #f0f0f0"
                data-id="${c.id}" data-name="${c.name}" data-no="${c.customer_code}">
             <strong>${c.name}</strong>
             <span class="text-muted small ms-2">${c.customer_code}</span>
           </div>`
        ).join('');
        $('#customerDropdown').html(html).show();
      }, 'json');
    }, 300);
  });

  $(document).on('click', '.customer-option', function () {
    $('#customerId').val($(this).data('id'));
    $('#customerSearch').val($(this).data('name'));
    $('#btnClearCustomer').show();
    $('#customerDropdown').hide();
  });

  $('#btnClearCustomer').on('click', function () {
    $('#customerId').val('');
    $('#customerSearch').val('');
    $(this).hide();
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#customerSearch,#customerDropdown').length)
      $('#customerDropdown').hide();
  });
})();
</script>
JS;
endif;
require_once __DIR__ . '/../../layout/footer.php';
