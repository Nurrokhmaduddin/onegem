<?php
/**
 * sales/reservation/form.php — Form create reservasi baru
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
require_permission('RESERVATION_CREATE');

$errors   = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

// Pre-fill dari customer_id
$preCustomerId = (int) get_param('customer_id', 0);
$preCustomer   = $preCustomerId
    ? Database::fetchOne("SELECT id, name, customer_code FROM customers WHERE id = ? AND deleted_at IS NULL", [$preCustomerId])
    : null;

// Ambil salesperson list
$salespersons = Database::fetchAll(
    "SELECT u.id, u.full_name FROM users u
       JOIN roles r ON r.id = u.role_id
      WHERE u.is_active = 1 AND r.role_code IN ('SALES','MANAGER','OWNER')
      ORDER BY u.full_name ASC"
);

$maxExpiry = date('Y-m-d', strtotime('+' . ReservationService::MAX_DAYS . ' days'));

$form = array_merge([
    'customer_id'    => $preCustomerId ?: '',
    'salesperson_id' => $_SESSION['user_id'] ?? '',
    'expiry_date'    => $maxExpiry,
    'notes'          => '',
], $formData);

$pageTitle   = 'Buat Reservasi Baru';
$breadcrumbs = [
    ['label' => 'Penjualan'],
    ['label' => 'Reservasi', 'url' => url('sales/reservation')],
    ['label' => $pageTitle],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">
<div class="col-lg-8">

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <i class="bi bi-exclamation-triangle me-2"></i><strong>Periksa kembali:</strong>
  <ul class="mb-0 mt-1 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= url('sales/reservation/save') ?>" id="rsvForm" class="no-double-submit">
  <?= csrf_field() ?>

  <!-- Informasi Customer -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-person me-2 text-primary"></i>Customer</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Customer <span class="required">*</span></label>
          <div class="input-group">
            <input type="text" id="customerSearch" class="form-control"
                   placeholder="Ketik nama atau kode customer..."
                   value="<?= e($preCustomer['name'] ?? ($formData['customer_name_display'] ?? '')) ?>"
                   autocomplete="off">
            <button type="button" class="btn btn-outline-secondary" id="btnClearCustomer"
                    <?= empty($form['customer_id']) ? 'style="display:none"' : '' ?>>
              <i class="bi bi-x"></i>
            </button>
          </div>
          <input type="hidden" name="customer_id" id="customerId" value="<?= e($form['customer_id']) ?>" required>
          <div id="customerDropdown" class="position-absolute bg-white border rounded shadow-sm mt-1 z-3" style="width:400px;display:none;max-height:240px;overflow-y:auto"></div>
          <?php if ($preCustomer): ?>
          <div class="mt-1 small text-muted">
            <i class="bi bi-check-circle text-success me-1"></i>
            <?= e($preCustomer['customer_code']) ?> — <?= e($preCustomer['name']) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Salesperson</label>
          <select name="salesperson_id" class="form-select">
            <option value="">— Pilih Salesperson —</option>
            <?php foreach ($salespersons as $sp): ?>
            <option value="<?= $sp['id'] ?>" <?= (int)$form['salesperson_id'] === $sp['id'] ? 'selected' : '' ?>>
              <?= e($sp['full_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">
            Tanggal Kedaluwarsa
            <span class="required">*</span>
            <span class="text-muted small">(maks <?= ReservationService::MAX_DAYS ?> hari)</span>
          </label>
          <input type="date" name="expiry_date" class="form-control"
                 value="<?= e($form['expiry_date']) ?>"
                 min="<?= date('Y-m-d') ?>" max="<?= $maxExpiry ?>" required>
        </div>
      </div>
    </div>
  </div>

  <!-- Pilih Berlian -->
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
      <span><i class="bi bi-gem me-2 text-primary"></i>Pilih Berlian</span>
      <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddDiamond">
        <i class="bi bi-plus-lg me-1"></i>Tambah Berlian
      </button>
    </div>
    <div class="card-body p-0">
      <div id="selectedDiamonds">
        <div class="text-center py-4 text-muted" id="emptyDiamondMsg">
          <i class="bi bi-gem opacity-25 fs-2 d-block mb-2"></i>
          Belum ada berlian dipilih. Klik "Tambah Berlian" untuk mencari.
        </div>
      </div>

      <!-- Totals -->
      <div class="border-top p-3 bg-light" id="totalRow" style="display:none">
        <div class="d-flex justify-content-end">
          <div class="text-end">
            <div class="small text-muted">Total (USD)</div>
            <div class="fw-700 fs-5 text-primary" id="grandTotalUsd">$0</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Catatan -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-sticky me-2 text-primary"></i>Catatan</div>
    <div class="card-body p-4">
      <textarea name="notes" class="form-control" rows="3"
                placeholder="Catatan untuk customer atau internal..."><?= e($form['notes']) ?></textarea>
    </div>
  </div>

  <!-- Hidden diamond ids -->
  <div id="hiddenDiamondInputs"></div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('sales/reservation') ?>" class="btn btn-secondary">Batal</a>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-bookmark-check me-1"></i>Buat Reservasi
    </button>
  </div>
</form>
</div>

<!-- Sidebar info -->
<div class="col-lg-4">
  <div class="card border-0" style="background:#F8FAFC">
    <div class="card-body p-4">
      <h6 class="fw-600 mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Panduan</h6>
      <ul class="small text-muted mb-0 ps-3">
        <li class="mb-2">Reservasi mengunci status berlian menjadi <strong>Reserved</strong>.</li>
        <li class="mb-2">Berlian yang sudah Reserved tidak dapat dijual ke customer lain.</li>
        <li class="mb-2">Reservasi berlaku maksimal <strong><?= ReservationService::MAX_DAYS ?> hari</strong> (R001).</li>
        <li class="mb-2">Setelah kedaluwarsa, lock berlian dilepas otomatis oleh sistem.</li>
        <li>Setelah deal, konversi reservasi menjadi <strong>Sales Order</strong>.</li>
      </ul>
    </div>
  </div>
</div>
</div>

<!-- Modal Cari Berlian -->
<div class="modal fade" id="modalSearchDiamond" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-gem me-2 text-primary"></i>Cari Berlian Available</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" id="diamondSearchInput" class="form-control"
                 placeholder="Cari SKU, warna, clarity, nomor sertifikat...">
        </div>
        <div id="diamondResults" style="min-height:200px">
          <div class="text-center py-5 text-muted">
            <i class="bi bi-search fs-2 d-block mb-2 opacity-25"></i>
            Ketik untuk mencari berlian available.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
(function () {
  const selectedIds = new Set();
  const ajaxUrl     = document.querySelector('meta[name="base-url"]')?.content ?? '';

  /* ---------- Customer Search ---------- */
  let csTimer;
  $('#customerSearch').on('input', function () {
    const q = $(this).val().trim();
    clearTimeout(csTimer);
    if (q.length < 2) { $('#customerDropdown').hide(); return; }
    csTimer = setTimeout(() => {
      $.get(ajaxUrl + '/master/customer/ajax/search.php', { q }, function (res) {
        if (!res.success || !res.data.length) {
          $('#customerDropdown').html('<div class="p-3 text-muted small">Tidak ada hasil.</div>').show();
          return;
        }
        const html = res.data.map(c =>
          `<div class="px-3 py-2 customer-option" style="cursor:pointer;border-bottom:1px solid #f0f0f0"
                data-id="${c.id}" data-name="${c.name}" data-no="${c.customer_code}">
             <strong>${c.name}</strong> <span class="text-muted small">${c.customer_code}</span>
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
    $('#customerDropdown').hide();
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#customerSearch, #customerDropdown').length)
      $('#customerDropdown').hide();
  });

  /* ---------- Diamond Search ---------- */
  let dsTimer;
  $('#btnAddDiamond').on('click', function () {
    $('#diamondSearchInput').val('');
    $('#diamondResults').html(
      '<div class="text-center py-5 text-muted"><i class="bi bi-search fs-2 d-block mb-2 opacity-25"></i>Ketik untuk mencari berlian available.</div>'
    );
    new bootstrap.Modal(document.getElementById('modalSearchDiamond')).show();
    setTimeout(() => $('#diamondSearchInput').focus(), 300);
  });

  $('#diamondSearchInput').on('input', function () {
    const q = $(this).val().trim();
    clearTimeout(dsTimer);
    dsTimer = setTimeout(() => searchDiamonds(q), 300);
  });

  function searchDiamonds(q) {
    const exclude = [...selectedIds].join(',');
    $.get(ajaxUrl + '/sales/quotation/ajax/search_diamond.php', { q, exclude }, function (res) {
      if (!res.success || !res.data.length) {
        $('#diamondResults').html('<div class="text-center py-4 text-muted">Tidak ada berlian available.</div>');
        return;
      }
      const rows = res.data.map(d => `
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom hover-bg">
          <div>
            <span class="fw-600 font-mono">${d.sku}</span>
            <span class="ms-2 text-muted small">${d.carat}ct · ${d.color} · ${d.clarity} · ${d.cut}</span>
            ${d.certificate_no ? `<span class="badge bg-light text-dark border ms-2" style="font-size:10px">${d.lab} ${d.certificate_no}</span>` : ''}
          </div>
          <div class="d-flex align-items-center gap-3">
            <span class="fw-600 text-primary">$${parseFloat(d.price_usd).toLocaleString()}</span>
            <button type="button" class="btn btn-sm btn-primary btn-select-diamond"
                    data-id="${d.id}" data-sku="${d.sku}" data-carat="${d.carat}"
                    data-color="${d.color}" data-clarity="${d.clarity}" data-cut="${d.cut}"
                    data-price="${d.price_usd}" data-cert="${d.certificate_no ?? ''}" data-lab="${d.lab ?? ''}">
              Pilih
            </button>
          </div>
        </div>
      `).join('');
      $('#diamondResults').html(rows);
    }, 'json');
  }

  $(document).on('click', '.btn-select-diamond', function () {
    const d = $(this).data();
    if (selectedIds.has(d.id)) return;
    addDiamondRow(d);
    selectedIds.add(d.id);
    $(this).closest('div.d-flex').find('.btn-select-diamond').prop('disabled', true).text('Dipilih');
    searchDiamonds($('#diamondSearchInput').val());
  });

  function addDiamondRow(d) {
    $('#emptyDiamondMsg').hide();
    $('#totalRow').show();
    const row = $(`
      <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom" data-diamond-id="${d.id}">
        <div>
          <span class="fw-600 font-mono">${d.sku}</span>
          <span class="ms-2 text-muted small">${d.carat}ct · ${d.color} · ${d.clarity} · ${d.cut}</span>
          ${d.cert ? `<div class="small text-muted mt-1">${d.lab} ${d.cert}</div>` : ''}
        </div>
        <div class="d-flex align-items-center gap-3">
          <span class="fw-600 text-primary" data-price="${d.price}">$${parseFloat(d.price).toLocaleString()}</span>
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-diamond"
                  data-id="${d.id}"><i class="bi bi-x"></i></button>
        </div>
      </div>
    `);
    $('#selectedDiamonds').append(row);
    $('<input>').attr({ type: 'hidden', name: 'diamond_ids[]', value: d.id }).appendTo('#hiddenDiamondInputs');
    updateTotal();
  }

  $(document).on('click', '.btn-remove-diamond', function () {
    const id = $(this).data('id');
    selectedIds.delete(id);
    $(this).closest('[data-diamond-id]').remove();
    $(`#hiddenDiamondInputs input[value="${id}"]`).remove();
    if (selectedIds.size === 0) { $('#emptyDiamondMsg').show(); $('#totalRow').hide(); }
    updateTotal();
  });

  function updateTotal() {
    let total = 0;
    $('#selectedDiamonds [data-price]').each(function () {
      total += parseFloat($(this).data('price')) || 0;
    });
    $('#grandTotalUsd').text('$' + total.toLocaleString(undefined, { minimumFractionDigits: 0 }));
  }
})();
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
