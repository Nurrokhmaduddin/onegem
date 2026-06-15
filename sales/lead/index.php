<?php
/**
 * sales/lead/index.php
 * Kanban / Pipeline view per status lead
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
require_permission('LEAD_VIEW');

// Ambil semua lead aktif (bukan converted/lost) untuk kanban, max 200
$pipeline = [];
$stages   = ['new', 'contacted', 'qualified', 'quoted'];
foreach ($stages as $stage) {
    $pipeline[$stage] = LeadRepository::getList(
        search: '',
        status: $stage,
        assignedTo: 0,
        sortBy: 'l.updated_at',
        sortDir: 'DESC',
        limit: 50,
        offset: 0
    );
}

$statusConfig = [
    'new'       => ['label' => 'Baru',               'color' => 'secondary', 'icon' => 'bi-person-plus',    'bg' => '#F9FAFB'],
    'contacted' => ['label' => 'Dihubungi',           'color' => 'info',      'icon' => 'bi-telephone',      'bg' => '#F0F9FF'],
    'qualified' => ['label' => 'Qualified',           'color' => 'primary',   'icon' => 'bi-person-check',   'bg' => '#EFF6FF'],
    'quoted'    => ['label' => 'Penawaran Terkirim',  'color' => 'warning',   'icon' => 'bi-file-earmark-text', 'bg' => '#FFFBEB'],
];

$counts = [];
foreach (array_keys($statusConfig) + ['converted','lost'] as $s) {
    $row = Database::fetchOne(
        "SELECT COUNT(*) AS n FROM leads WHERE status = ? AND deleted_at IS NULL", [$s]
    );
    $counts[$s] = (int)($row['n'] ?? 0);
}

$pageTitle   = 'Pipeline Lead';
$breadcrumbs = [['label' => 'Penjualan'], ['label' => 'Lead']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Top bar -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="mb-0 fw-700"><i class="bi bi-kanban me-2 text-primary"></i>Pipeline Lead</h5>
    <div class="text-muted small mt-1">
      Total aktif: <strong><?= array_sum(array_intersect_key($counts, $statusConfig)) ?></strong> lead
      &nbsp;·&nbsp; Konversi: <strong><?= $counts['converted'] ?></strong>
      &nbsp;·&nbsp; Tidak jadi: <strong><?= $counts['lost'] ?></strong>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('sales/lead/list') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-list-ul me-1"></i>Tampilan List
    </a>
    <?php if (can('LEAD_CREATE')): ?>
    <a href="<?= url('sales/lead/create') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Buat Lead
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Kanban Board -->
<div class="kanban-board d-flex gap-3 pb-3" style="overflow-x:auto;align-items:flex-start">
  <?php foreach ($stages as $stage):
    $cfg   = $statusConfig[$stage];
    $cards = $pipeline[$stage];
  ?>
  <div class="kanban-column flex-shrink-0" style="width:280px">
    <!-- Column header -->
    <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-top mb-0"
         style="background:<?= $cfg['bg'] ?>;border:1px solid #E5E7EB;border-bottom:none">
      <div class="d-flex align-items-center gap-2">
        <i class="<?= $cfg['icon'] ?> text-<?= $cfg['color'] ?>"></i>
        <span class="fw-600 small"><?= $cfg['label'] ?></span>
      </div>
      <span class="badge bg-<?= $cfg['color'] ?>-subtle text-<?= $cfg['color'] ?> border border-<?= $cfg['color'] ?>-subtle">
        <?= $counts[$stage] ?>
      </span>
    </div>

    <!-- Cards -->
    <div class="kanban-cards" style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:0 0 8px 8px;min-height:200px;padding:8px">
      <?php if (empty($cards)): ?>
      <div class="text-center py-4 text-muted small">
        <i class="bi bi-inbox opacity-25 d-block fs-4 mb-1"></i>
        Tidak ada lead
      </div>
      <?php endif; ?>
      <?php foreach ($cards as $lead): ?>
      <a href="<?= url('sales/lead/detail') ?>?id=<?= $lead['id'] ?>"
         class="text-decoration-none d-block mb-2">
        <div class="card card-kanban border-0 shadow-sm p-3"
             style="border-radius:8px;transition:box-shadow .15s">
          <div class="d-flex align-items-start gap-2">
            <!-- Avatar -->
            <div class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center fw-700 text-primary"
                 style="width:34px;height:34px;background:#EFF6FF;font-size:13px">
              <?= e(name_initials($lead['name'])) ?>
            </div>
            <div class="flex-fill min-w-0">
              <div class="fw-600 text-dark lh-sm text-truncate" style="font-size:13px">
                <?= e($lead['name']) ?>
              </div>
              <?php if ($lead['company']): ?>
              <div class="text-muted small text-truncate" style="font-size:11px">
                <?= e($lead['company']) ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mt-2 d-flex flex-wrap gap-1">
            <?php if ($lead['source']): ?>
            <span class="badge bg-light text-dark border" style="font-size:10px">
              <?= e($lead['source']) ?>
            </span>
            <?php endif; ?>
            <?php if ($lead['interest_carat']): ?>
            <span class="badge bg-primary-subtle text-primary" style="font-size:10px">
              <?= e($lead['interest_carat']) ?>ct
            </span>
            <?php endif; ?>
          </div>

          <div class="d-flex align-items-center justify-content-between mt-2">
            <span class="text-muted" style="font-size:11px">
              <i class="bi bi-person-fill me-1"></i>
              <?= e($lead['salesperson_name'] ?? '—') ?>
            </span>
            <span class="text-muted" style="font-size:11px">
              <?= time_ago($lead['updated_at']) ?>
            </span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>

      <!-- Show more jika > 50 -->
      <?php if ($counts[$stage] > 50): ?>
      <a href="<?= url('sales/lead/list') ?>?status=<?= $stage ?>"
         class="btn btn-sm btn-outline-secondary w-100 mt-1" style="font-size:12px">
        +<?= $counts[$stage] - count($cards) ?> lead lainnya
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Kolom Converted (summary only) -->
  <div class="kanban-column flex-shrink-0" style="width:220px;opacity:.7">
    <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-top"
         style="background:#F0FDF4;border:1px solid #E5E7EB;border-bottom:none">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check2-circle text-success"></i>
        <span class="fw-600 small text-success">Konversi</span>
      </div>
      <span class="badge bg-success-subtle text-success border border-success-subtle">
        <?= $counts['converted'] ?>
      </span>
    </div>
    <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:0 0 8px 8px;padding:12px;text-align:center">
      <i class="bi bi-trophy text-success fs-3 d-block mb-2 opacity-50"></i>
      <div class="small text-muted">
        <strong class="text-success"><?= $counts['converted'] ?></strong> lead berhasil<br>dikonversi ke Customer
      </div>
      <a href="<?= url('sales/lead/list') ?>?status=converted"
         class="btn btn-sm btn-outline-success mt-2 w-100" style="font-size:12px">
        Lihat Semua
      </a>
    </div>
  </div>
</div>

<?php
$extraCss = <<<'CSS'
<style>
.card-kanban:hover { box-shadow: 0 4px 12px rgba(0,0,0,.12) !important; }
.kanban-board::-webkit-scrollbar { height: 6px; }
.kanban-board::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }
</style>
CSS;
require_once __DIR__ . '/../../layout/footer.php';
