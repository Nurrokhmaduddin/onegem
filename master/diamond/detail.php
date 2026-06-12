<?php
/**
 * master/diamond/detail.php
 * Detail lengkap berlian: spesifikasi, sertifikat, histori status, aksi
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_auth(); require_permission('DIAMOND_VIEW');

$diamondId = (int)get_param('id');
$diamond   = DiamondRepository::findById($diamondId);
if (!$diamond) { flash_set('error','Data berlian tidak ditemukan.'); redirect(url('master/diamond')); }

$cert      = DiamondRepository::getCertificate($diamondId);
$histories = DiamondRepository::getStateHistories($diamondId);
$rate      = DiamondRepository::getActiveRate();

$statusConfig = [
    'registered' => ['label'=>'Terdaftar',    'color'=>'secondary','icon'=>'bi-hourglass'],
    'available'  => ['label'=>'Tersedia',      'color'=>'success',  'icon'=>'bi-check-circle-fill'],
    'reserved'   => ['label'=>'Direservasi',   'color'=>'warning',  'icon'=>'bi-lock-fill'],
    'sold'       => ['label'=>'Terjual',       'color'=>'primary',  'icon'=>'bi-bag-check-fill'],
    'returned'   => ['label'=>'Diretur',       'color'=>'danger',   'icon'=>'bi-arrow-return-left'],
    'in_repair'  => ['label'=>'Reparasi',      'color'=>'purple',   'icon'=>'bi-tools'],
    'retired'    => ['label'=>'Nonaktif',      'color'=>'dark',     'icon'=>'bi-x-circle-fill'],
];
$acqLabels = [
    'consignment'         => 'Konsinyasi (Titipan)',
    'purchase_returnable' => 'Pembelian (Bisa Retur)',
    'purchase_final'      => 'Pembelian Putus',
];

$sc      = $statusConfig[$diamond['status']] ?? $statusConfig['registered'];
$sellIdr = round((float)$diamond['selling_price_usd'] * $rate);
$costIdr = round((float)$diamond['cost_price_usd']   * $rate);
$margin  = DiamondService::calcMargin((float)$diamond['cost_price_usd'], (float)$diamond['selling_price_usd']);

$pageTitle   = 'Detail Berlian — ' . $diamond['internal_code'];
$breadcrumbs = [
    ['label'=>'Master Data'],
    ['label'=>'Berlian','url'=>url('master/diamond')],
    ['label'=>e($diamond['internal_code'])],
];
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="row g-4">

  <!-- ── Kolom kiri: info utama ───────────────────────────────────────── -->
  <div class="col-lg-4">

    <!-- Status card -->
    <div class="card mb-3">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:52px;height:52px;border-radius:12px;background:var(--bs-<?=$sc['color']?>-bg-subtle,#F3F4F6);
            color:var(--bs-<?=$sc['color']?>-text-emphasis,#374151);
            display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
            <i class="bi <?=$sc['icon']?>"></i>
          </div>
          <div>
            <div class="font-mono fw-700 fs-5 text-primary"><?= e($diamond['internal_code']) ?></div>
            <span class="badge bg-<?=$sc['color']?>-subtle text-<?=$sc['color']?> border border-<?=$sc['color']?>-subtle">
              <?= e($sc['label']) ?>
            </span>
          </div>
        </div>

        <?php if ($diamond['factory_barcode']): ?>
        <div class="bg-light rounded p-2 mb-3 font-mono small text-muted">
          <i class="bi bi-upc me-1"></i><?= e($diamond['factory_barcode']) ?>
        </div>
        <?php endif; ?>

        <!-- Aksi berdasarkan status -->
        <div class="d-flex flex-column gap-2">
          <?php if ($diamond['status'] === 'registered' && can('DIAMOND_EDIT')): ?>
            <button type="button" class="btn btn-success btn-sm btn-activate"
              data-id="<?= $diamondId ?>" data-code="<?= e($diamond['internal_code']) ?>">
              <i class="bi bi-check-circle me-1"></i>Terima & Aktifkan
            </button>
          <?php endif; ?>
          <?php if (!in_array($diamond['status'],['sold','retired']) && can('DIAMOND_EDIT')): ?>
            <a href="<?= url('master/diamond/edit') ?>?id=<?= $diamondId ?>"
               class="btn btn-outline-primary btn-sm">
              <i class="bi bi-pencil me-1"></i>Edit Data
            </a>
          <?php endif; ?>
          <?php if (in_array($diamond['status'],['available','returned']) && can('DIAMOND_EDIT')): ?>
            <button type="button" class="btn btn-outline-danger btn-sm btn-retire"
              data-id="<?= $diamondId ?>">
              <i class="bi bi-x-circle me-1"></i>Nonaktifkan
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Harga -->
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-price-tag-fill me-2"></i>Harga</div>
      <div class="card-body p-3">
        <div class="border-bottom pb-2 mb-2">
          <div class="small text-muted">Harga Jual</div>
          <div class="fw-700 fs-5 text-primary">$<?= number_format((float)$diamond['selling_price_usd'],2) ?></div>
          <div class="small text-muted">Rp <?= number_format($sellIdr,0,',','.') ?></div>
        </div>
        <div class="border-bottom pb-2 mb-2">
          <div class="small text-muted">Harga Pokok (HPP)</div>
          <div class="fw-600">$<?= number_format((float)$diamond['cost_price_usd'],2) ?></div>
          <div class="small text-muted">Rp <?= number_format($costIdr,0,',','.') ?></div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="small text-muted">Margin</span>
          <strong style="color:<?= $margin>=20?'#15803D':($margin>=10?'#B45309':'#DC2626') ?>">
            <?= $margin ?>%
          </strong>
        </div>
        <div class="text-muted mt-2" style="font-size:11px">
          Kurs: Rp <?= number_format($rate,0,',','.') ?> / USD
        </div>
      </div>
    </div>

    <!-- Sertifikat -->
    <?php if ($cert): ?>
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-award me-2"></i>Sertifikat</div>
      <div class="card-body p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
            <?= e($cert['cert_type']) ?>
          </span>
          <span class="font-mono fw-600"><?= e($cert['cert_number']) ?></span>
        </div>
        <?php if ($cert['issuer']): ?>
          <div class="small text-muted"><i class="bi bi-building me-1"></i><?= e($cert['issuer']) ?></div>
        <?php endif; ?>
        <?php if ($cert['issue_date']): ?>
          <div class="small text-muted"><i class="bi bi-calendar me-1"></i><?= format_date($cert['issue_date']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- ── Kolom kanan: spesifikasi + riwayat ──────────────────────────── -->
  <div class="col-lg-8">

    <!-- Tab -->
    <div class="card">
      <div class="card-header p-0">
        <ul class="nav nav-tabs border-0 px-3 pt-2">
          <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tabSpek">
              <i class="bi bi-gem me-1"></i>Spesifikasi
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tabHistory">
              <i class="bi bi-clock-history me-1"></i>Riwayat Status
            </a>
          </li>
        </ul>
      </div>
      <div class="tab-content">

        <!-- Tab Spesifikasi -->
        <div class="tab-pane fade show active p-4" id="tabSpek">
          <div class="row g-3 mb-4">
            <div class="col-12">
              <div class="fw-600 small text-muted text-uppercase border-bottom pb-2 mb-3">
                4Cs — Spesifikasi Berlian
              </div>
            </div>
            <?php
            $specs = [
                ['Bentuk',        $diamond['shape_name']],
                ['Berat Karat',   $diamond['carat_weight'] ? $diamond['carat_weight'].' ct' : null],
                ['Warna (Color)', $diamond['color_grade']],
                ['Kejernihan',    $diamond['clarity_grade']],
                ['Potongan (Cut)',$diamond['cut_grade']],
                ['Polish',        $diamond['polish']],
                ['Symmetry',      $diamond['symmetry']],
                ['Fluorescence',  $diamond['fluorescence']],
                ['Measurements',  $diamond['measurements']],
                ['Table %',       $diamond['table_percent'] ? $diamond['table_percent'].'%' : null],
                ['Depth %',       $diamond['depth_percent'] ? $diamond['depth_percent'].'%' : null],
                ['Jumlah Batu',   $diamond['stone_count']],
            ];
            foreach ($specs as $spec):
                if (!$spec[1]) continue;
            ?>
            <div class="col-md-4 col-6">
              <div class="bg-light rounded p-3 h-100">
                <div class="text-muted small text-uppercase" style="font-size:10px;letter-spacing:.04em">
                  <?= e($spec[0]) ?>
                </div>
                <div class="fw-600 mt-1"><?= e($spec[1]) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if ($diamond['metal_type'] || $diamond['karat']): ?>
          <div class="row g-3 mb-4">
            <div class="col-12">
              <div class="fw-600 small text-muted text-uppercase border-bottom pb-2 mb-3">Logam / Setting</div>
            </div>
            <?php
            $metals = [
                ['Jenis Logam', $diamond['metal_type']],
                ['Berat Logam', $diamond['metal_weight_gr'] ? $diamond['metal_weight_gr'].' gr' : null],
                ['Karat Emas',  $diamond['karat'] ? $diamond['karat'].'K' : null],
            ];
            foreach ($metals as $m): if (!$m[1]) continue; ?>
            <div class="col-md-4 col-6">
              <div class="bg-light rounded p-3">
                <div class="text-muted small text-uppercase" style="font-size:10px"><?= e($m[0]) ?></div>
                <div class="fw-600 mt-1"><?= e($m[1]) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-12">
              <div class="fw-600 small text-muted text-uppercase border-bottom pb-2 mb-3">Informasi Perolehan</div>
            </div>
            <?php
            $info = [
                ['Supplier',         $diamond['supplier_name']],
                ['Jenis Perolehan',  $acqLabels[$diamond['acquisition_type']] ?? $diamond['acquisition_type']],
                ['Tanggal Masuk',    format_date($diamond['acquired_at'])],
                ['Lokasi Saat Ini',  ($diamond['warehouse_name']??'').' — '.($diamond['branch_name']??'')],
            ];
            foreach ($info as $inf): ?>
            <div class="col-md-6">
              <div class="d-flex gap-2">
                <div class="text-muted small" style="min-width:130px"><?= e($inf[0]) ?></div>
                <div class="fw-500 small"><?= e($inf[1]??'—') ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if ($diamond['notes']): ?>
          <div class="mt-4 p-3 bg-light rounded">
            <div class="small text-muted mb-1"><i class="bi bi-sticky-note me-1"></i>Catatan</div>
            <div style="font-size:13px"><?= nl2br(e($diamond['notes'])) ?></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Tab Riwayat Status -->
        <div class="tab-pane fade p-4" id="tabHistory">
          <?php if (empty($histories)): ?>
            <div class="table-empty" style="padding:32px">
              <i class="bi bi-clock-history text-muted"></i>
              <p class="small">Belum ada riwayat perubahan status.</p>
            </div>
          <?php else: ?>
          <div class="timeline">
            <?php foreach ($histories as $h): ?>
            <div class="d-flex gap-3 mb-4">
              <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;margin-top:2px;
                background:#EFF6FF;color:#185FA5;display:flex;align-items:center;justify-content:center;font-size:14px">
                <i class="bi bi-arrow-right-circle-fill"></i>
              </div>
              <div>
                <div class="fw-600 small font-mono"><?= e($h['event_name']) ?></div>
                <div class="d-flex align-items-center gap-2 my-1">
                  <?php if ($h['from_status']): ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:11px">
                      <?= e($statusConfig[$h['from_status']]['label'] ?? $h['from_status']) ?>
                    </span>
                    <i class="bi bi-arrow-right text-muted small"></i>
                  <?php endif; ?>
                  <?php $ts=$statusConfig[$h['to_status']]??['label'=>$h['to_status'],'color'=>'secondary']; ?>
                  <span class="badge bg-<?=$ts['color']?>-subtle text-<?=$ts['color']?> border border-<?=$ts['color']?>-subtle" style="font-size:11px">
                    <?= e($ts['label']) ?>
                  </span>
                </div>
                <div class="text-muted" style="font-size:12px">
                  <?php if ($h['actor_name']): ?><i class="bi bi-person me-1"></i><?= e($h['actor_name']) ?>&nbsp;&bull;&nbsp;<?php endif; ?>
                  <i class="bi bi-clock me-1"></i><?= format_datetime($h['changed_at']) ?>
                </div>
                <?php if ($h['notes']): ?>
                  <div class="text-muted small fst-italic mt-1"><?= e($h['notes']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
<script>
$(function () {
  // Aktivasi
  $(document).on('click', '.btn-activate', function () {
    const id   = $(this).data('id');
    const code = $(this).data('code');
    if (!confirm('Aktifkan berlian ' + code + '?\\nStatus akan berubah menjadi Tersedia.')) return;
    erpAjax({
      url  : window.BASE_URL + '/master/diamond/activate',
      data : { diamond_id: id, csrf_token: \$('meta[name="csrf-token"]').attr('content') },
      onSuccess: function (res) { erpToast('success', res.message); setTimeout(()=>location.reload(),800); },
    });
  });

  // Nonaktifkan (retire)
  $(document).on('click', '.btn-retire', function () {
    const id = $(this).data('id');
    const reason = prompt('Alasan penonaktifan berlian ini:');
    if (reason === null) return; // dibatalkan
    erpAjax({
      url  : window.BASE_URL + '/master/diamond/retire',
      data : { diamond_id: id, reason: reason, csrf_token: \$('meta[name="csrf-token"]').attr('content') },
      onSuccess: function (res) { erpToast('success', res.message); setTimeout(()=>location.reload(),800); },
    });
  });
});
</script>
JS;
require_once __DIR__ . '/../../layout/footer.php';
