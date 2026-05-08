<?php
$page_title = 'Reports';
require_once __DIR__ . '/core/header.php';

$state_file  = __DIR__ . '/storage/campaignState.json';
$archive_dir = __DIR__ . '/storage/archive';
if (!is_dir($archive_dir)) mkdir($archive_dir, 0777, true);

$last_campaign = file_exists($state_file) ? json_decode(file_get_contents($state_file), true) : null;

$view_report = null;
if (isset($_GET['view_report'])) {
  $cid = $_GET['view_report'];
  $rf  = $archive_dir . '/campaign_' . $cid . '.json';
  if (file_exists($rf)) $view_report = json_decode(file_get_contents($rf), true);
  if ($last_campaign && ($last_campaign['campaign_id'] ?? '') === $cid) $view_report = $last_campaign;
}

$archived_reports = [];
foreach (glob($archive_dir . '/campaign_*.json') as $file) {
  $d = json_decode(file_get_contents($file), true);
  if ($d) $archived_reports[] = $d;
}
usort($archived_reports, fn($a, $b) => ($b['updated_at'] ?? '') <=> ($a['updated_at'] ?? ''));

$total_campaigns_count = count($archived_reports);
$global_sent = $global_failed = $global_opens = $global_clicks = 0;
foreach ($archived_reports as $r) {
  $global_sent   += ($r['sent']   ?? 0);
  $global_failed += ($r['failed'] ?? 0);
  $global_opens  += ($r['opens']  ?? 0);
  $global_clicks += ($r['clicks'] ?? 0);
}
// Prepare the full list of campaigns for the table (Active + Archived)
$all_campaigns = $archived_reports;
if ($last_campaign) {
    $is_archived = false;
    foreach ($archived_reports as $ar) {
        if (($ar['campaign_id'] ?? '') === ($last_campaign['campaign_id'] ?? '')) { $is_archived = true; break; }
    }
    if (!$is_archived) {
        array_unshift($all_campaigns, $last_campaign);
    }
}

$rts = $view_report ?: ['sent' => $global_sent, 'failed' => $global_failed, 'opens' => $global_opens, 'clicks' => $global_clicks];
?>

<section id="tab-reports" class="tab-section active">

  <!-- Header -->
  <div style="margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px;display:flex;align-items:flex-end;justify-content:space-between;">
    <div>
      <h1 style="font-size:20px;font-weight:800;letter-spacing:-0.5px;color:#0f172a;">Reports</h1>
      <p style="font-size:12px;color:#64748b;font-weight:500;">
        <?= $view_report ? htmlspecialchars($view_report['campaign_name'] ?? $view_report['subject']) : 'Platform performance overview' ?>
      </p>
    </div>
    <?php if ($view_report): ?>
      <a href="reports.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#475569;text-decoration:none;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Back
      </a>
    <?php else: ?>
      <div style="font-size:11px;font-weight:700;color:#64748b;"><?= $total_campaigns_count ?> campaigns</div>
    <?php endif; ?>
  </div>

  <!-- Stat Cards -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px;">
    <?php
    $open_rate = $rts['sent'] > 0 ? round((($rts['opens'] ?? 0) / $rts['sent']) * 100, 1) : 0;
    $click_rate = $rts['sent'] > 0 ? round((($rts['clicks'] ?? 0) / $rts['sent']) * 100, 1) : 0;
    
    $stats = [
      ['label'=>'Sent',    'value'=>$rts['sent'],         'color'=>'#10b981', 'sub' => 'Total Emails', 'icon' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>'],
      ['label'=>'Opens',   'value'=>$rts['opens'] ?? 0,   'color'=>'#2563eb', 'sub' => $open_rate . '% Rate', 'icon' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>'],
      ['label'=>'Clicks',  'value'=>$rts['clicks'] ?? 0,  'color'=>'#8b5cf6', 'sub' => $click_rate . '% Rate', 'icon' => '<path d="m3 10 7-7 7 7"/><path d="m10 3v18"/>'],
      ['label'=>'Bounced', 'value'=>$rts['failed'] ?? 0,  'color'=>'#ef4444', 'sub' => 'Failed Delivery', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'],
    ];
    foreach ($stats as $s): ?>
    <div style="padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;position:relative;overflow:hidden;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;"><?= $s['label'] ?></div>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="<?= $s['color'] ?>33" stroke-width="3" style="position:absolute;right:10px;top:10px;opacity:0.5;"><?= $s['icon'] ?></svg>
      </div>
      <div style="font-size:22px;font-weight:800;color:<?= $s['color'] ?>;"><?= $s['value'] ?></div>
      <div style="font-size:10px;font-weight:700;color:#64748b;margin-top:2px;"><?= $s['sub'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($view_report): ?>
    <!-- Link Performance -->
    <?php
    $links = [];
    foreach ($rts['click_log'] ?? [] as $cl) {
      $url = $cl['url'] ?? '';
      if (!$url) continue;
      if (!isset($links[$url])) $links[$url] = ['clicks' => 0, 'unique' => []];
      $links[$url]['clicks']++;
      if (!in_array($cl['email'], $links[$url]['unique'])) $links[$url]['unique'][] = $cl['email'];
    }
    if (!empty($links)): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:20px;">
      <div style="padding:10px 16px;border-bottom:1px solid #f1f5f9;background:#fafafa;">
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;">Link Performance</div>
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
          <tr style="background:#fafafa;">
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">URL</th>
            <th style="text-align:center;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Total Clicks</th>
            <th style="text-align:center;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Unique Clicks</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($links as $url => $data): ?>
          <tr style="border-bottom:1px solid #f8fafc;">
            <td style="padding:10px 16px;">
              <a href="<?= htmlspecialchars($url) ?>" target="_blank" style="color:var(--accent);text-decoration:none;font-weight:600;word-break:break-all;">
                <?= htmlspecialchars($url) ?>
              </a>
            </td>
            <td style="padding:10px 16px;text-align:center;font-weight:700;color:#0f172a;"><?= $data['clicks'] ?></td>
            <td style="padding:10px 16px;text-align:center;font-weight:700;color:#0f172a;"><?= count($data['unique']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- Recipient Log -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
      <div style="padding:10px 16px;border-bottom:1px solid #f1f5f9;background:#fafafa;display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;">Recipient Log</div>
        <div style="font-size:11px;font-weight:700;color:#64748b;">Total: <?= $rts['total'] ?? 0 ?></div>
      </div>
      <div style="max-height:420px;overflow-y:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <tbody>
            <?php foreach (array_reverse($rts['sent_log'] ?? []) as $log):
              $opened  = in_array($log['email'], $rts['open_log']  ?? []);
              $clicked = false;
              foreach ($rts['click_log'] ?? [] as $cl) { if ($cl['email'] === $log['email']) { $clicked = true; break; } }
            ?>
              <tr style="border-bottom:1px solid #f8fafc;">
                <td style="padding:9px 16px;">
                  <span style="font-weight:700;color:#0f172a;"><?= htmlspecialchars($log['name']) ?></span>
                  <span style="color:#94a3b8;margin-left:8px;font-size:11px;"><?= htmlspecialchars($log['email']) ?></span>
                </td>
                <td style="padding:9px 16px;text-align:right;">
                  <div style="display:inline-flex;align-items:center;gap:6px;">
                    <?php if ($opened):  ?><span class="stat-pill open"  style="padding:2px 6px;font-size:9px;">Open</span><?php endif; ?>
                    <?php if ($clicked): ?><span class="stat-pill click" style="padding:2px 6px;font-size:9px;">Click</span><?php endif; ?>
                    <span style="font-weight:700;font-size:11px;color:<?= $log['status'] === 'sent' ? '#10b981' : '#ef4444' ?>;">
                      <?= $log['status'] === 'sent' ? 'Sent' : 'Failed' ?>
                    </span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php else: ?>
    <!-- Campaign List -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
      <div style="padding:10px 16px;border-bottom:1px solid #f1f5f9;background:#fafafa;">
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;">Past Campaigns</div>
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
          <tr>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Campaign</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Date</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Stats</th>
            <th style="text-align:right;padding:9px 16px;border-bottom:1px solid #f1f5f9;"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($all_campaigns)): ?>
            <tr><td colspan="4" style="padding:48px;text-align:center;color:#94a3b8;">No campaigns yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($all_campaigns as $r): 
             $is_active = $last_campaign && ($r['campaign_id'] ?? '') === ($last_campaign['campaign_id'] ?? '');
          ?>
            <tr style="border-bottom:1px solid #f8fafc;">
              <td style="padding:10px 16px;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="font-weight:700;color:#0f172a;"><?= htmlspecialchars($r['campaign_name'] ?? $r['subject']) ?></div>
                  <?php if ($is_active): ?>
                    <span style="background:#eff6ff;color:var(--accent);font-size:9px;font-weight:800;padding:2px 6px;border-radius:4px;text-transform:uppercase;">Live</span>
                  <?php endif; ?>
                </div>
                <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($r['subject']) ?></div>
              </td>
              <td style="padding:10px 16px;font-size:11px;color:#64748b;font-weight:600;"><?= date('M j, Y', strtotime($r['updated_at'] ?? 'now')) ?></td>
              <td style="padding:10px 16px;">
                <div style="display:inline-flex;gap:4px;">
                  <span class="stat-pill sent"  style="padding:2px 6px;font-size:10px;display:inline-flex;align-items:center;gap:4px;font-weight:700;">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                    <?= $r['sent'] ?>
                  </span>
                  <span class="stat-pill open"  style="padding:2px 6px;font-size:10px;display:inline-flex;align-items:center;gap:4px;font-weight:700;">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <?= $r['opens'] ?? 0 ?>
                  </span>
                  <span class="stat-pill click" style="padding:2px 6px;font-size:10px;display:inline-flex;align-items:center;gap:4px;font-weight:700;">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m3 10 7-7 7 7"/><path d="m10 3v18"/></svg>
                    <?= $r['clicks'] ?? 0 ?>
                  </span>
                </div>
              </td>
              <td style="padding:10px 16px;text-align:right;">
                <a href="?view_report=<?= $r['campaign_id'] ?>"
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