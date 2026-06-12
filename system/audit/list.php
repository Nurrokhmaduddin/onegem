<?php
/**
 * system/audit/list.php
 * Halaman Audit Trail — viewer log aktivitas sistem
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../shared/helper/functions.php';
require_once __DIR__ . '/../../shared/middleware/auth.php';
require_once __DIR__ . '/../../shared/middleware/audit.php';

require_auth();
require_permission('AUDIT_VIEW');

// Filter parameters
$module    = get_param('module');
$action    = get_param('action');
$userId    = (int) get_param('user_id', 0);
$dateFrom  = get_param('date_from');
$dateTo    = get_param('date_to');
$search    = get_param('search');
$page      = max(1, (int) get_param('page', 1));
$perPage   = 25;
$offset    = pagination_offset($page, $perPage);

// Build query
$where  = ['1=1'];
$params = [];

if ($module)   { $where[] = 'a.module = ?';  $params[] = $module; }
if ($action)   { $where[] = 'a.action = ?';  $params[] = $action; }
if ($userId)   { $where[] = 'a.user_id = ?'; $params[] = $userId; }
if ($dateFrom) { $where[] = 'DATE(a.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(a.created_at) <= ?'; $params[] = $dateTo; }
if ($search)   {
    $like = '%' . sanitize_like($search) . '%';
    $where[] = '(a.description LIKE ? OR a.document_no LIKE ? OR a.username LIKE ?)';
    $params  = array_merge($params, [$like, $like, $like]);
}

$whereStr = implode(' AND ', $where);

$total = Database::fetchOne(
    "SELECT COUNT(*) n FROM audit_logs a WHERE {$whereStr}", $params
)['n'] ?? 0;

$logs = Database::fetchAll(
    "SELECT a.*, u.full_name
       FROM audit_logs a
       LEFT JOIN users u ON u.id = a.user_id
      WHERE {$whereStr}
      ORDER BY a.created_at DESC
      LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

$pagData = pagination_data($total, $page, $perPage);

// Untuk filter dropdown
$modules = Database::fetchAll("SELECT DISTINCT module FROM audit_logs ORDER BY module");
$actions = Database::fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action");

// Warna action badge
$actionColors = [
    'LOGIN'        => 'success', 'LOGOUT'       => 'secondary',
    'LOGIN_FAILED' => 'danger',  'CREATE'        => 'primary',
    'UPDATE'       => 'warning', 'DELETE'        => 'danger',
    'APPROVE'      => 'success', 'REJECT'        => 'danger',
    'POST'         => 'info',    'REVERSE'       => 'warning',
];

$pageTitle   = 'Audit Trail';
$breadcrumbs = [['label'=>'Sistem'],['label'=>'Audit Trail']];
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Filter card -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-sm-6 col-md-3">
        <label class="form-label mb-1 small">Cari</label>
        <input type="text" name="search" class="form-control form-control-sm"
          value="<?= e($search) ?>" placeholder="Deskripsi, no. dokumen, user...">
      </div>
      <div class="col-sm-6 col-md-2">
        <label class="form-label mb-1 small">Modul</label>
        <select name="module" class="form-select form-select-sm">
          <option value="">Semua Modul</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?= e($m['module']) ?>" <?= $module === $m['module'] ? 'selected' : '' ?>>
              <?= e($m['module']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-md-2">
        <label class="form-label mb-1 small">Aksi</label>
        <select name="action" class="form-select form-select-sm">
          <option value="">Semua Aksi</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?= e($a['action']) ?>" <?= $action === $a['action'] ? 'selected' : '' ?>>
              <?= e($a['action']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-md-2">
        <label class="form-label mb-1 small">Dari Tanggal</label>
        <input type="date" name="date_from" class="form-control form-control-sm"
          value="<?= e($dateFrom) ?>">
      </div>
      <div class="col-sm-6 col-md-2">
        <label class="form-label mb-1 small">Sampai Tanggal</label>
        <input type="date" name="date_to" class="form-control form-control-sm"
          value="<?= e($dateTo) ?>">
      </div>
      <div class="col-sm-6 col-md-1">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Tabel -->
<div class="table-card">
  <div class="table-toolbar">
    <small class="text-muted">
      <?= number_format($total) ?> entri ditemukan
      <?php if ($module || $action || $dateFrom || $search): ?>
        &mdash; <a href="<?= url('system/audit') ?>" class="text-danger">Reset filter</a>
      <?php endif; ?>
    </small>
  </div>

  <?php if (empty($logs)): ?>
    <div class="table-empty">
      <i class="bi bi-journal-text"></i>
      <p>Tidak ada log ditemukan.</p>
    </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px">
      <thead>
        <tr>
          <th>Waktu</th>
          <th>Pengguna</th>
          <th>Modul</th>
          <th>Aksi</th>
          <th>No. Dokumen</th>
          <th>IP Address</th>
          <th>Keterangan</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td class="text-muted small" style="white-space:nowrap">
            <?= format_datetime($log['created_at']) ?>
          </td>
          <td>
            <div class="fw-500"><?= e($log['full_name'] ?? '—') ?></div>
            <div class="font-mono text-muted" style="font-size:11px">@<?= e($log['username'] ?? 'sistem') ?></div>
          </td>
          <td>
            <span class="badge bg-light text-dark border" style="font-size:11px">
              <?= e($log['module']) ?>
            </span>
          </td>
          <td>
            <?php $color = $actionColors[$log['action']] ?? 'secondary'; ?>
            <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> border border-<?= $color ?>-subtle" style="font-size:11px">
              <?= e($log['action']) ?>
            </span>
          </td>
          <td class="font-mono small"><?= $log['document_no'] ? e($log['document_no']) : '—' ?></td>
          <td class="font-mono small text-muted"><?= e($log['ip_address'] ?? '—') ?></td>
          <td class="small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= e($log['description'] ?? '—') ?>
          </td>
          <td>
            <?php if ($log['before_value'] || $log['after_value']): ?>
              <button type="button" class="btn btn-icon btn-outline-secondary btn-sm btn-show-diff"
                data-before="<?= e($log['before_value'] ?? '{}') ?>"
                data-after="<?= e($log['after_value'] ?? '{}') ?>"
                title="Lihat perubahan data">
                <i class="bi bi-code-slash"></i>
              </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pagData['total_pages'] > 1): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
    <small class="text-muted">
      Halaman <?= $pagData['current'] ?> dari <?= $pagData['total_pages'] ?>
      (<?= number_format($total) ?> entri)
    </small>
    <nav>
      <ul class="pagination mb-0 pagination-sm">
        <li class="page-item <?= !$pagData['has_prev'] ? 'disabled' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>
        <?php for ($p = max(1,$page-2); $p <= min($pagData['total_pages'],$page+2); $p++): ?>
          <li class="page-item <?= $p===$page?'active':'' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= !$pagData['has_next'] ? 'disabled' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal: diff before/after -->
<div class="modal fade" id="modalDiff" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-code-slash me-2"></i>Perubahan Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label small fw-600 text-danger">Sebelum</label>
            <pre id="diffBefore" class="bg-light border rounded p-3"
              style="font-size:12px;max-height:300px;overflow:auto"></pre>
          </div>
          <div class="col-6">
            <label class="form-label small fw-600 text-success">Sesudah</label>
            <pre id="diffAfter" class="bg-light border rounded p-3"
              style="font-size:12px;max-height:300px;overflow:auto"></pre>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
$(function () {
  $(document).on('click', '.btn-show-diff', function () {
    const before = $(this).data('before');
    const after  = $(this).data('after');
    try {
      $('#diffBefore').text(JSON.stringify(JSON.parse(before), null, 2));
      $('#diffAfter').text(JSON.stringify(JSON.parse(after), null, 2));
    } catch (e) {
      $('#diffBefore').text(before || '—');
      $('#diffAfter').text(after || '—');
    }
    new bootstrap.Modal(document.getElementById('modalDiff')).show();
  });
});
</script>
JS;

require_once __DIR__ . '/../../layout/footer.php';
