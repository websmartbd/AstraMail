<?php
$scheduled_dir = __DIR__ . '/storage/scheduled';
if (!is_dir($scheduled_dir)) mkdir($scheduled_dir, 0777, true);

// Handle Delete/Cancel BEFORE any output
if (isset($_GET['cancel'])) {
    $file = $scheduled_dir . '/campaign_' . $_GET['cancel'] . '.json';
    if (file_exists($file)) unlink($file);
    header('Location: scheduled.php');
    exit;
}

$page_title = 'Scheduled Campaigns';
require_once __DIR__ . '/core/header.php';

// Get All Scheduled
$scheduled_campaigns = [];
$files = glob($scheduled_dir . '/campaign_*.json');
foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true);
    if ($data) $scheduled_campaigns[] = $data;
}

// Sort by schedule time
usort($scheduled_campaigns, fn($a, $b) => ($a['scheduled_at'] ?? 0) <=> ($b['scheduled_at'] ?? 0));

// Viewing details of one?
$view_item = null;
if (isset($_GET['view'])) {
    foreach ($scheduled_campaigns as $sc) {
        if ($sc['campaign_id'] === $_GET['view']) {
            $view_item = $sc;
            break;
        }
    }
}
?>

<section id="tab-scheduled" class="tab-section active">
    <div class="header">
      <div style="display:flex; justify-content:space-between; align-items:flex-end;">
        <div>
          <h1><?= $view_item ? 'Planned Campaign' : 'Upcoming Campaigns' ?></h1>
          <p><?= $view_item ? 'Reviewing settings for: ' . htmlspecialchars($view_item['subject']) : 'Manage your future email marketing schedule.' ?></p>
        </div>
        <?php if ($view_item): ?>
          <a href="scheduled.php" class="btn btn-outline" style="font-size:12px; padding:6px 12px;">← Back to Schedule</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($view_item): ?>
      <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:24px; margin-bottom:32px;">
         <div class="card">
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:12px;">Campaign Content</div>
            <h3 style="margin-bottom:8px;"><?= htmlspecialchars($view_item['subject']) ?></h3>
            <div style="padding:16px; background:#f8fafc; border-radius:12px; font-size:13px; color:#475569; max-height:300px; overflow-y:auto; border:1px solid #e2e8f0;">
               <?= $view_item['body'] ?>
            </div>
         </div>
         <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card" style="border-left:4px solid var(--accent);">
               <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:8px;">Launch Time</div>
               <div style="font-weight:800; font-size:18px; color:var(--accent);">
                  <?= date('M j, Y — H:i', $view_item['scheduled_at']) ?>
               </div>
            </div>
            <div class="card">
               <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:8px;">Target Audience</div>
               <div style="font-weight:700;"><?= $view_item['total'] ?> Recipients</div>
               <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Filter: <?= htmlspecialchars($view_item['target']) ?></div>
            </div>
            <a href="?cancel=<?= $view_item['campaign_id'] ?>" onclick="return confirm('Stop and delete this schedule?')" class="btn btn-outline" style="color:var(--danger); border-color:rgba(239, 68, 68, 0.1); width:100%; text-align:center;">Cancel this Schedule</a>
         </div>
      </div>
    <?php endif; ?>

    <?php if (!$view_item): ?>
      <div class="card" style="padding:0; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
          <thead style="background:#f8fafc;">
            <tr>
              <th style="text-align:left; padding:12px 20px; border-bottom:1px solid var(--border);">Campaign Name / Subject</th>
              <th style="text-align:left; padding:12px 20px; border-bottom:1px solid var(--border);">Scheduled Date</th>
              <th style="text-align:left; padding:12px 20px; border-bottom:1px solid var(--border);">Recipients</th>
              <th style="text-align:right; padding:12px 20px; border-bottom:1px solid var(--border);">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($scheduled_campaigns)): ?>
              <tr><td colspan="4" style="padding:40px; text-align:center; color:var(--text-muted);">No campaigns scheduled yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($scheduled_campaigns as $sc): ?>
              <tr>
                <td style="padding:12px 20px; border-bottom:1px solid var(--border);">
                  <div style="font-weight:700;"><?= htmlspecialchars($sc['campaign_name'] ?? $sc['subject']) ?></div>
                  <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($sc['subject']) ?></div>
                </td>
                <td style="padding:12px 20px; border-bottom:1px solid var(--border); font-weight:700; color:var(--accent);">
                  <?= date('M j, H:i', $sc['scheduled_at']) ?>
                </td>
                <td style="padding:12px 20px; border-bottom:1px solid var(--border); font-weight:600;">
                  <?= $sc['total'] ?> 
                </td>
                <td style="padding:12px 20px; border-bottom:1px solid var(--border); text-align:right;">
                  <a href="?view=<?= $sc['campaign_id'] ?>" class="btn btn-outline" style="font-size:11px; padding:4px 8px;">View Plan</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/core/footer.php'; ?>
