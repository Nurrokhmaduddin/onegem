<?php
/**
 * sales/quotation/form.php — Form create quotation
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/customer/repository.php';
require_once __DIR__ . '/../../sales/lead/repository.php';

require_auth();
require_permission('QUOTATION_CREATE');

$errors    = $_SESSION['form_errors'] ?? [];
$formData  = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

// Pre-fill dari lead_id atau customer_id jika ada
$preLeadId     = (int)get_param('lead_id', 0);
$preCustomerId = (int)get_param('customer_id', 0);
$preLead       = $preLeadId     ? Database::fetchOne("SELECT * FROM leads WHERE id=?",     [$preLeadId])    : null;
$preCustomer   = $preCustomerId ? Database::fetchOne("SELECT * FROM customers WHERE id=?", [$preCustomerId]) : null;

$salespersons = LeadRepository::getSalespersons();
$rate         = (float)(Database::fetchOne(
    "SELECT rate_to_idr FROM currencies WHERE code='USD' AND is_active=1
       AND effective_date<=CURDATE() ORDER BY effective_date DESC LIMIT 1"
)['rate_to_idr'] ?? 16000);

$form = array_merge([
    'customer_id'    => $preCustomerId ?: '',
    'lead_id'        => $preLeadId     ?: '',
    'salesperson_id' => $_SESSION['user_id'] ?? '',
    'quotation_date' => date('Y-m-d'),
    'valid_until'    => date('Y-m-d', strtotime('+14 days')),
    'notes'          => '',
    'internal_notes' => '',
], $formData);

$pageTitle   = 'Buat Quotation Baru';
$breadcrumbs = [
    ['label'=>'Penjualan'],
    ['label'=>'Quotation','url'=>url('sales/quotation')],
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

<form method="POST" action="<?= url('sales/quotation/save') ?>" id="quoForm" class="no-double-submit">
  <?= csrf_field() ?>

  <!-- Informasi dasar -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-person me-2 text-primary"></i>Informasi Pembeli</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <!-- Toggle: Customer atau Lead -->
        <div class="col-12">
          <div class="d-flex gap-3 mb-2">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="buyer_type"
                id="buyerCustomer" value="customer"
                <?= empty($form['lead_id']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="buyerCustomer">Customer Terdaftar</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="buyer_type"
                id="buyerLead" value="lead"
                <?= !empty($form['lead_id']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="buyerLead">Lead / Prospek</label>
            </div>
          </div>
        </div>

        <!-- Customer selector -->
        <div class="col-12" id="customerBlock"
             style="<?= !empty($form['lead_id']) ? 'display:none' : '' ?>">
          <label class="form-label">Customer <span class="required">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" id="customerSearch"
              class="form-control"
              placeholder="Ketik nama / kode customer..."
              value="<?= $preCustomer ? e($preCustomer['name'].' ('.$preCustomer['customer_code'].')') : '' ?>"
              autocomplete="off">
            <input type="hidden" name="customer_id" id="customerIdInput"
              value="<?= e($form['customer_id']) ?>">
          </div>
          <div id="customerDropdown" class="list-group position-absolute shadow-sm" style="z-index:100;display:none;max-width:500px"></div>
        </div>

        <!-- Lead selector -->
        <div class="col-12" id="leadBlock"
             style="<?= empty($form['lead_id']) ? 'display:none' : '' ?>">
          <label class="form-label">Lead / Prospek <span class="required">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-plus"></i></span>
            <input type="text" id="leadSearch"
              class="form-control"
              placeholder="Ketik nama lead..."
              value="<?= $preLead ? e($preLead['name'].' ('.$preLead['lead_code'].')') : '' ?>"
              autocomplete="off">
            <input type="hidden" name="lead_id" id="leadIdInput"
              value="<?= e($form['lead_id']) ?>">
          </div>
          <div id="leadDropdown" class="list-group position-absolute shadow-sm" style="z-index:100;display:none;max-width:500px"></div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Salesperson</label>
          <select name="salesperson_id" class="form-select">
            <option value="">-- Pilih --</option>
            <?php foreach ($salespersons as $s): ?>
              <option value="<?= $s['id'] ?>"
                <?= $form['salesperson_id']==$s['id']?'selected':'' ?>>
                <?= e($s['full_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tanggal Quotation <span class="required">*</span></label>
          <input type="date" name="quotation_date" class="form-control"
            value="<?= e($form['quotation_date']) ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Berlaku Sampai</label>
          <input type="date" name="valid_until" class="form-control"
            value="<?= e($form['valid_until']) ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Pilih Berlian -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-gem me-2 text-primary"></i>Pilih Berlian</span>
      <span class="text-muted small">Kurs: Rp <?= number_format($rate,0,',','.') ?> / USD</span>
    </div>
    <div class="card-body p-4">
      <!-- Search berlian -->
      <div class="input-group mb-3">
        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
        <input type="text" id="diamondSearch"
          class="form-control"
          placeholder="Scan barcode atau ketik kode / spesifikasi berlian..."
          autocomplete="off">
      </div>
      <div id="diamondSearchResults" class="mb-3" style="max-height:280px;overflow-y:auto"></div>

      <!-- Item terpilih -->
      <div id="selectedItems">
        <p class="text-muted small text-center py-3" id="noItemMsg">
          <i class="bi bi-info-circle me-1"></i>Belum ada berlian dipilih.
        </p>
      </div>
    </div>
  </div>

  <!-- Catatan -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-sticky-note me-2 text-primary"></i>Catatan</div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Catatan untuk Customer</label>
          <textarea name="notes" class="form-control" rows="2"
            placeholder="Catatan yang akan tampil di dokumen quotation..."><?= e($form['notes']) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Catatan Internal</label>
          <textarea name="internal_notes" class="form-control" rows="2"
            placeholder="Catatan internal (tidak tampil ke customer)..."><?= e($form['internal_notes']) ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden: item list akan diisi JS -->
  <div id="hiddenItems"></div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('sales/quotation') ?>" class="btn btn-secondary">
      <i class="bi bi-x me-1"></i>Batal
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check-lg me-1"></i>Simpan Quotation
    </button>
  </div>
</form>
</div>

<!-- Sidebar: ringkasan harga -->
<div class="col-lg-4">
  <div class="card sticky-top" style="top:80px">
    <div class="card-header"><i class="bi bi-calculator me-2"></i>Ringkasan Harga</div>
    <div class="card-body p-3">
      <div id="pricingSummary">
        <p class="text-muted small text-center">Pilih berlian untuk melihat total.</p>
      </div>
    </div>
  </div>
</div>
</div>

<?php
$baseUrl = BASE_URL;
$extraJs = <<<JS
<script>
const RATE = {$rate};
const BASE = '{$baseUrl}';

// ── State: item terpilih ──────────────────────────────────────────────
let selectedDiamonds = {};  // {diamond_id: {data, discountPct}}

// ── Customer search ───────────────────────────────────────────────────
let customerTimer;
\$('#customerSearch').on('input', function () {
  clearTimeout(customerTimer);
  const q = \$(this).val().trim();
  if (q.length < 2) { \$('#customerDropdown').hide(); return; }
  customerTimer = setTimeout(function () {
    \$.get(BASE + '/ajax/customer/search', {q}, function (res) {
      if (!res.success || !res.data.length) { \$('#customerDropdown').hide(); return; }
      let html = '';
      res.data.forEach(c => {
        html += \`<a href="#" class="list-group-item list-group-item-action py-2 px-3 customer-pick"
          data-id="\${c.id}" data-text="\${c.text}" style="font-size:13px">
          \${c.text}
          <span class="badge bg-\${c.tier==='vvip'?'warning':c.tier==='vip'?'primary':'secondary'}-subtle text-muted border ms-1">\${c.tier.toUpperCase()}</span>
        </a>\`;
      });
      \$('#customerDropdown').html(html).show();
    }, 'json');
  }, 400);
});
\$(document).on('click', '.customer-pick', function (e) {
  e.preventDefault();
  \$('#customerSearch').val(\$(this).data('text'));
  \$('#customerIdInput').val(\$(this).data('id'));
  \$('#customerDropdown').hide();
});

// ── Lead search ───────────────────────────────────────────────────────
let leadTimer;
\$('#leadSearch').on('input', function () {
  clearTimeout(leadTimer);
  const q = \$(this).val().trim();
  if (q.length < 2) { \$('#leadDropdown').hide(); return; }
  leadTimer = setTimeout(function () {
    \$.getJSON(BASE + '/ajax/lead/search', {q}, function (res) {
      if (!res.success || !res.data.length) { \$('#leadDropdown').hide(); return; }
      let html = '';
      res.data.forEach(l => {
        html += \`<a href="#" class="list-group-item list-group-item-action py-2 px-3 lead-pick"
          data-id="\${l.id}" data-text="\${l.text}" style="font-size:13px">\${l.text}</a>\`;
      });
      \$('#leadDropdown').html(html).show();
    });
  }, 400);
});
\$(document).on('click', '.lead-pick', function (e) {
  e.preventDefault();
  \$('#leadSearch').val(\$(this).data('text'));
  \$('#leadIdInput').val(\$(this).data('id'));
  \$('#leadDropdown').hide();
});

// ── Toggle buyer type ──────────────────────────────────────────────────
\$('input[name=buyer_type]').on('change', function () {
  if (\$(this).val() === 'customer') {
    \$('#customerBlock').show(); \$('#leadBlock').hide();
    \$('#leadIdInput').val('');
  } else {
    \$('#customerBlock').hide(); \$('#leadBlock').show();
    \$('#customerIdInput').val('');
  }
});

// ── Diamond search ─────────────────────────────────────────────────────
let diamondTimer;
\$('#diamondSearch').on('input', function () {
  clearTimeout(diamondTimer);
  const q = \$(this).val().trim();
  \$('#diamondSearchResults').html('');
  if (q.length < 2) return;
  diamondTimer = setTimeout(function () {
    \$.getJSON(BASE + '/ajax/diamond/search', {q, status: 'available'}, function (res) {
      if (!res.success || !res.data.length) {
        \$('#diamondSearchResults').html('<p class="text-muted small p-2">Tidak ada berlian ditemukan.</p>');
        return;
      }
      let html = '<div class="list-group">';
      res.data.forEach(d => {
        const alreadyAdded = selectedDiamonds[d.id] !== undefined;
        html += \`<div class="list-group-item d-flex justify-content-between align-items-center py-2" style="font-size:13px">
          <div>
            <span class="font-mono fw-600">\${d.internal_code}</span>
            <span class="badge bg-light text-dark border ms-1">\${d.carat_weight}ct</span>
            <span class="badge bg-light text-dark border ms-1">\${d.color_grade}</span>
            <span class="badge bg-light text-dark border ms-1">\${d.clarity_grade}</span>
            <div class="small text-muted">\$\${parseFloat(d.selling_price_usd).toFixed(2)} &bull; \${d.warehouse_name||''}</div>
          </div>
          <button type="button" class="btn btn-sm \${alreadyAdded?'btn-outline-secondary disabled':'btn-outline-primary'} btn-add-diamond"
            data-id="\${d.id}"
            data-code="\${d.internal_code}"
            data-carat="\${d.carat_weight}"
            data-color="\${d.color_grade}"
            data-clarity="\${d.clarity_grade}"
            data-price="\${d.selling_price_usd}"
            \${alreadyAdded?'disabled':''}>
            \${alreadyAdded?'<i class=\\"bi bi-check\\"></i> Ditambahkan':'<i class=\\"bi bi-plus\\"></i> Tambah'}
          </button>
        </div>\`;
      });
      html += '</div>';
      \$('#diamondSearchResults').html(html);
    });
  }, 400);
});

// ── Tambah item ────────────────────────────────────────────────────────
\$(document).on('click', '.btn-add-diamond', function () {
  if (\$(this).prop('disabled')) return;
  const d = {
    id      : \$(this).data('id'),
    code    : \$(this).data('code'),
    carat   : \$(this).data('carat'),
    color   : \$(this).data('color'),
    clarity : \$(this).data('clarity'),
    price   : parseFloat(\$(this).data('price')),
  };
  selectedDiamonds[d.id] = { data: d, discountPct: 0 };
  renderItems();
  updatePricing();
  \$(this).html('<i class="bi bi-check"></i> Ditambahkan').addClass('btn-outline-secondary disabled').prop('disabled',true);
});

// ── Hapus item ─────────────────────────────────────────────────────────
\$(document).on('click', '.btn-remove-item', function () {
  const id = \$(this).data('id');
  delete selectedDiamonds[id];
  renderItems();
  updatePricing();
  // Re-enable di hasil search
  \$(`.btn-add-diamond[data-id="\${id}"]`).html('<i class="bi bi-plus"></i> Tambah')
    .removeClass('btn-outline-secondary disabled').prop('disabled',false);
});

// ── Update diskon ──────────────────────────────────────────────────────
\$(document).on('input', '.item-discount', function () {
  const id  = \$(this).data('id');
  const pct = Math.min(100, Math.max(0, parseFloat(\$(this).val())||0));
  if (selectedDiamonds[id]) selectedDiamonds[id].discountPct = pct;
  updatePricing();
  updateHiddenInputs();
});

function renderItems() {
  const ids = Object.keys(selectedDiamonds);
  if (ids.length === 0) {
    \$('#selectedItems').html('<p class="text-muted small text-center py-3" id="noItemMsg"><i class="bi bi-info-circle me-1"></i>Belum ada berlian dipilih.</p>');
    return;
  }
  let html = '<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:13px"><thead><tr><th>Berlian</th><th class="text-end">Harga (USD)</th><th style="width:100px">Diskon (%)</th><th class="text-end">Final (USD)</th><th></th></tr></thead><tbody>';
  ids.forEach(id => {
    const { data: d, discountPct } = selectedDiamonds[id];
    const discUsd   = d.price * discountPct / 100;
    const finalUsd  = d.price - discUsd;
    const finalIdr  = finalUsd * RATE;
    html += \`<tr>
      <td>
        <div class="font-mono fw-600">\${d.code}</div>
        <div class="text-muted" style="font-size:11px">\${d.carat}ct \${d.color} \${d.clarity}</div>
      </td>
      <td class="text-end">\$\${d.price.toFixed(2)}</td>
      <td>
        <input type="number" class="form-control form-control-sm item-discount"
          data-id="\${id}" value="\${discountPct}" min="0" max="100" step="0.1">
      </td>
      <td class="text-end">
        <div class="fw-600">\$\${finalUsd.toFixed(2)}</div>
        <div class="text-muted" style="font-size:11px">Rp \${formatIDR(finalIdr).replace('Rp ','')}</div>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-icon btn-outline-danger btn-sm btn-remove-item" data-id="\${id}">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>\`;
  });
  html += '</tbody></table></div>';
  \$('#selectedItems').html(html);
  updateHiddenInputs();
}

function updatePricing() {
  const ids = Object.keys(selectedDiamonds);
  if (ids.length === 0) {
    \$('#pricingSummary').html('<p class="text-muted small text-center">Pilih berlian untuk melihat total.</p>');
    return;
  }
  let subtotal=0, discount=0;
  ids.forEach(id => {
    const {data:d, discountPct} = selectedDiamonds[id];
    subtotal += d.price;
    discount += d.price * discountPct / 100;
  });
  const total    = subtotal - discount;
  const totalIdr = total * RATE;
  \$('#pricingSummary').html(\`
    <div class="d-flex justify-content-between mb-2 small"><span>Subtotal (<?= count([]) ?> item)</span><span>+\${ids.length} item</span></div>
    <div class="d-flex justify-content-between mb-1 small"><span>Subtotal USD</span><span>\$\${subtotal.toFixed(2)}</span></div>
    <div class="d-flex justify-content-between mb-1 small text-danger"><span>Diskon</span><span>-\$\${discount.toFixed(2)}</span></div>
    <hr class="my-2">
    <div class="d-flex justify-content-between fw-700"><span>Total USD</span><span class="text-primary">\$\${total.toFixed(2)}</span></div>
    <div class="d-flex justify-content-between small text-muted"><span>Total IDR</span><span>Rp \${Math.round(totalIdr).toLocaleString('id-ID')}</span></div>
    <div class="mt-2 text-muted" style="font-size:11px">Kurs: Rp \${RATE.toLocaleString('id-ID')} / USD</div>
  \`);
}

function updateHiddenInputs() {
  let html = '';
  Object.keys(selectedDiamonds).forEach((id, idx) => {
    const {discountPct} = selectedDiamonds[id];
    html += \`<input type="hidden" name="diamond_ids[]" value="\${id}">\`;
    html += \`<input type="hidden" name="discount_pct[\${id}]" value="\${discountPct}">\`;
  });
  \$('#hiddenItems').html(html);
}

// ── Close dropdowns on outside click ─────────────────────────────────
\$(document).on('click', function (e) {
  if (!\$(e.target).closest('#customerSearch, #customerDropdown').length)
    \$('#customerDropdown').hide();
  if (!\$(e.target).closest('#leadSearch, #leadDropdown').length)
    \$('#leadDropdown').hide();
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
