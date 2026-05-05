<?php
$page_title = 'Campaign Analytics';
require_once __DIR__ . '/core/header.php';

// Load Last Campaign State for Reports
$state_file = __DIR__ . '/storage/campaignState.json';
$last_campaign = file_exists($state_file) ? json_decode(file_get_contents($state_file), true) : null;

// Archive Directory
$archive_dir = __DIR__ . '/storage/archive';
if (!is_dir($archive_dir))
  mkdir($archive_dir, 0777, true);

// Handle Viewing Archived Report
$view_report = null;
if (isset($_GET['view_report'])) {
  $report_file = $archive_dir . '/campaign_' . $_GET['view_report'] . '.json';
  if (file_exists($report_file)) {
    $view_report = json_decode(file_get_contents($report_file), true);
  }
}

// Get All Archived Reports
$archived_reports = [];
$files = glob($archive_dir . '/campaign_*.json');
foreach ($files as $file) {
  $data = json_decode(file_get_contents($file), true);
  if ($data)
    $archived_reports[] = $data;
}
// Sort by date (newest first)
usort($archived_reports, fn($a, $b) => ($b['updated_at'] ?? '') <=> ($a['updated_at'] ?? ''));
?>

<section id="tab-reports" class="tab-section active">
  <div class="header">
    <div style="display:flex; justify-content:space-between; align-items:flex-end;">
      <div>
        <h1><?= $view_report ? 'Campaign Details' : 'Campaign History' ?></h1>
        <p>
          <?= $view_report ? 'Viewing results for: ' . htmlspecialchars($view_report['subject']) : 'Manage and analyze your past email campaigns.' ?>
        </p>
      </div>
      <?php if ($view_report): ?>
        <a href="reports.php" class="btn btn-outline" style="font-size:12px; padding:6px 12px;">← Back to History</a>
      <?php endif; ?>
    </div>
  </div>

  <?php
  $report_to_show = $view_report ?: $last_campaign;

  // Calculate Unique Campaigns Count
  $archived_ids = array_map(fn($r) => $r['campaign_id'] ?? '', $archived_reports);
  $total_campaigns_count = count($archived_reports);

  // Add active campaign ONLY if it's not already in the archive
  if ($last_campaign && !in_array($last_campaign['campaign_id'] ?? '', $archived_ids)) {
    $total_campaigns_count++;
  }

  if ($report_to_show):
    ?>
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:20px; margin-bottom:24px;">
      <div class="card" style="padding:16px;">
        <div
          style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:8px;">
          <?= $view_report ? 'Subject' : 'Total Campaigns' ?>
        </div>
        <div style="font-weight:700; font-size:<?= $view_report ? '14px' : '28px' ?>;">
          <?= $view_report ? htmlspecialchars($report_to_show['subject']) : $total_campaigns_count ?>
        </div>
      </div>
      <div class="card" style="padding:16px;">
        <div
          style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:8px;">
          Delivered</div>
        <div style="font-weight:700; font-size:24px; color:var(--success);"><?= $report_to_show['sent'] ?></div>
      </div>
      <div class="card" style="padding:16px;">
        <div
          style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:8px;">
          Bounced</div>
        <div style="font-weight:700; font-size:24px; color:var(--danger);"><?= $report_to_show['failed'] ?></div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($view_report): ?>
    <div class="card" style="padding:0; overflow:hidden; margin-bottom:32px;">
      <div
        style="padding:16px 24px; background:#f8fafc; border-bottom:1px solid var(--border); font-weight:700; font-size:14px; display:flex; justify-content:space-between;">
        <span>Detailed Delivery Log</span>
        <span style="color:var(--text-muted); font-size:12px;">Total: <?= $report_to_show['total'] ?></span>
      </div>
      <div style="max-height:400px; overflow-y:auto;">
        <div class="log-wrap" style="border:none;">
          <?php foreach (array_reverse($report_to_show['sent_log'] ?? []) as $log): ?>
            <div class="log-item">
              <div class="status-dot"
                style="background:<?= $log['status'] === 'sent' ? 'var(--success)' : 'var(--danger)' ?>"></div>
              <div style="flex:1;"><b><?= htmlspecialchars($log['name']) ?></b> <span
                  style="color:var(--text-muted);"><?= htmlspecialchars($log['email']) ?></span></div>
              <div
                style="font-size:11px; font-weight:700; color:<?= $log['status'] === 'sent' ? 'var(--success)' : 'var(--danger)' ?>">
                <?= $log['status'] === 'sent' ? 'Delivered' : 'Failed' ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$view_report): ?>
    <div class="header" style="margin-top:40px;">
      <h2>Past Campaigns</h2>
    </div>
    <div class="card" style="padding:0; overflow:hidden;">
      <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead style="background:#f8fafc;">
          <tr>
            <th style="text-align:left; padding:12px 20px; border-bottom:1px solid var(--border);">Campaign Subject</th>
            <th style="text-align:left; padding:12px 20px; border-bottom:1px solid var(--border);">Date</th>
            <th style="text-align:left; padding:12px 20px; border-bottom:1px solid var(--border);">Stats</th>
            <th style="text-align:right; padding:12px 20px; border-bottom:1px solid var(--border);">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($archived_reports)): ?>
            <tr>
              <td colspan="4" style="padding:40px; text-align:center; color:var(--text-muted);">No archived campaigns found.
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($archived_reports as $r): ?>
            <tr>
              <td style="padding:12px 20px; border-bottom:1px solid var(--border);">
                <div style="font-weight:700;"><?= htmlspecialchars($r['campaign_name'] ?? $r['subject']) ?></div>
                <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($r['subject']) ?></div>
              </td>
              <td style="padding:12px 20px; border-bottom:1px solid var(--border); color:var(--text-muted);">
                <?= date('M j, Y H:i', strtotime($r['updated_at'] ?? 'now')) ?>
              </td>
              <td style="padding:12px 20px; border-bottom:1px solid var(--border);">
                <span style="color:var(--success); font-weight:700;"><?= $r['sent'] ?></span> /
                <span style="color:var(--danger); font-weight:700;"><?= $r['failed'] ?></span>
              </td>
              <td style="padding:12px 20px; border-bottom:1px solid var(--border); text-align:right;">
                <a href="?view_report=<?= $r['campaign_id'] ?>" class="btn btn-outline"
                  style="font-size:11px; padding:4px 8px;">View Details</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/core/footer.php'; ?>