<?php
$scheduled_dir = __DIR__ . '/storage/scheduled';
if (!is_dir($scheduled_dir)) mkdir($scheduled_dir, 0777, true);

if (isset($_GET['cancel'])) {
    $file = $scheduled_dir . '/campaign_' . $_GET['cancel'] . '.json';
    if (file_exists($file)) unlink($file);
    header('Location: scheduled.php');
    exit;
}

$page_title = 'Scheduled';
require_once __DIR__ . '/core/header.php';

$scheduled_campaigns = [];
$files = glob($scheduled_dir . '/campaign_*.json');
foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true);
    if ($data) $scheduled_campaigns[] = $data;
}
usort($scheduled_campaigns, fn($a, $b) => ($a['scheduled_at'] ?? 0) <=> ($b['scheduled_at'] ?? 0));

$view_item = null;
if (isset($_GET['view'])) {
    foreach ($scheduled_campaigns as $sc) {
        if ($sc['campaign_id'] === $_GET['view']) { $view_item = $sc; break; }
    }
}
?>

<section id="tab-scheduled" class="tab-section active">

  <div style="margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px;display:flex;align-items:flex-end;justify-content:space-between;">
    <div>
      <h1 style="font-size:20px;font-weight:800;letter-spacing:-0.5px;color:#0f172a;">
        <?= $view_item ? 'Scheduled Campaign' : 'Scheduled' ?>
      </h1>
      <p style="font-size:12px;color:#64748b;font-weight:500;">
        <?= $view_item ? htmlspecialchars($view_item['subject']) : 'Upcoming campaigns queued for delivery.' ?>
      </p>
    </div>
    <?php if ($view_item): ?>
      <a href="scheduled.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#475569;text-decoration:none;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Back
      </a>
    <?php else: ?>
      <div style="font-size:11px;font-weight:700;color:#64748b;"><?= count($scheduled_campaigns) ?> queued</div>
    <?php endif; ?>
  </div>

  <?php if ($view_item): ?>
    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;align-items:start;">
      <!-- Content -->
      <div class="card" style="padding:16px;border-radius:12px;border:1px solid #e2e8f0;">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;margin-bottom:10px;">Email Content</div>
        <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:10px;"><?= htmlspecialchars($view_item['subject']) ?></div>
        <div style="padding:12px;background:#f8fafc;border-radius:8px;font-size:13px;color:#475569;max-height:300px;overflow-y:auto;border:1px solid #f1f5f9;line-height:1.6;">
          <?= $view_item['body'] ?>
        </div>
      </div>
      <!-- Meta -->
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="card" style="padding:16px;border-radius:12px;border:1px solid #e2e8f0;border-left:3px solid var(--accent);">
          <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;margin-bottom:6px;">Launch Time</div>
          <div style="font-size:16px;font-weight:800;color:var(--accent);"><?= date('M j, Y', $view_item['scheduled_at']) ?></div>
          <div style="font-size:13px;font-weight:700;color:#475569;"><?= date('H:i', $view_item['scheduled_at']) ?></div>
        </div>
        <div class="card" style="padding:16px;border-radius:12px;border:1px solid #e2e8f0;">
          <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;margin-bottom:6px;">Recipients</div>
          <div style="font-size:16px;font-weight:800;color:#0f172a;"><?= $view_item['total'] ?></div>
          <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars($view_item['target']) ?></div>
        </div>
        <a href="?cancel=<?= $view_item['campaign_id'] ?>" onclick="return confirm('Cancel and delete this scheduled campaign?')"
          style="display:block;padding:10px;text-align:center;border:1px solid #fecaca;border-radius:8px;font-size:13px;font-weight:700;color:#ef4444;text-decoration:none;">
          Cancel Schedule
        </a>
      </div>
    </div>

  <?php else: ?>
    <div class="card" style="padding:0;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
      <div style="padding:10px 16px;border-bottom:1px solid #f1f5f9;background:#fafafa;">
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;">Queue</div>
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Campaign</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Scheduled</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Recipients</th>
            <th style="text-align:right;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($scheduled_campaigns)): ?>
            <tr><td colspan="4" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">No campaigns scheduled yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($scheduled_campaigns as $sc): ?>
            <tr style="border-bottom:1px solid #f8fafc;">
              <td style="padding:10px 16px;">
                <div style="font-weight:700;color:#0f172a;"><?= htmlspecialchars($sc['campaign_name'] ?? $sc['subject']) ?></div>
                <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($sc['subject']) ?></div>
              </td>
              <td style="padding:10px 16px;">
                <div style="font-weight:700;color:var(--accent);font-size:12px;"><?= date('M j, Y', $sc['scheduled_at']) ?></div>
                <div style="font-size:11px;color:#94a3b8;"><?= date('H:i', $sc['scheduled_at']) ?></div>
              </td>
              <td style="padding:10px 16px;font-weight:700;color:#0f172a;"><?= $sc['total'] ?></td>
              <td style="padding:10px 16px;text-align:right;">
                <a href="?view=<?= $sc['campaign_id'] ?>"
                  style="display:inline-block;padding:5px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;font-weight:700;color:#475569;text-decoration:none;">
                  View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</section>
<?php require_once __DIR__ . '/core/footer.php'; ?>
