<?php
$page_title = 'Delivery Dashboard';
require_once __DIR__ . '/core/header.php';

$scheduled_dir = __DIR__ . '/storage/scheduled';
$archive_dir   = __DIR__ . '/storage/archive';
$state_file    = __DIR__ . '/storage/campaignState.json';

// 1. Get Scheduled Count
$scheduled_files = glob($scheduled_dir . '/campaign_*.json');
$pending_count = count($scheduled_files);

// 2. Get Active Campaign
$active = file_exists($state_file) ? json_decode(file_get_contents($state_file), true) : null;
$is_sending = $active && in_array($active['status'] ?? '', ['queued', 'sending']);
if ($active && ($active['status'] ?? '') === 'scheduled') $pending_count++;

// 3. Aggregate Global Stats (Simplified)
$archived_files = glob($archive_dir . '/campaign_*.json');
$total_delivered = 0;
$global_log = [];

foreach ($archived_files as $f) {
    $d = json_decode(file_get_contents($f), true);
    if ($d) {
        $total_delivered += ($d['sent'] ?? 0);
        // Collect last 5 logs from each for a "recent" overview
        $logs = $d['sent_log'] ?? [];
        foreach (array_slice(array_reverse($logs), 0, 5) as $l) {
            $l['campaign'] = $d['campaign_name'] ?? $d['subject'];
            $l['time'] = $d['updated_at'] ?? '—';
            $global_log[] = $l;
        }
    }
}
if ($active && !in_array($active['status'], ['idle', 'scheduled'])) {
    $total_delivered += ($active['sent'] ?? 0);
}

// Sort global log by time descending
usort($global_log, fn($a, $b) => strcmp($b['time'], $a['time']));
$global_log = array_slice($global_log, 0, 50); // Keep last 50
?>

<section id="tab-logs" class="tab-section active">

  <div style="margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:12px; display:flex; align-items:flex-end; justify-content:space-between;">
    <div>
      <h1 style="font-size:20px;font-weight:800;letter-spacing:-0.5px;color:#0f172a;">Delivery Dashboard</h1>
      <p style="font-size:12px;color:#64748b;font-weight:500;">Queue management and delivery intelligence.</p>
    </div>
    <div style="display:flex; gap:12px;">
       <div style="text-align:right;">
         <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#94a3b8;">Pending</div>
         <div style="font-size:14px; font-weight:800; color:var(--accent);"><?= $pending_count ?></div>
       </div>
       <div style="text-align:right; border-left:1px solid #e2e8f0; padding-left:12px;">
         <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#94a3b8;">Delivered</div>
         <div style="font-size:14px; font-weight:800; color:#10b981;"><?= $total_delivered ?></div>
       </div>
    </div>
  </div>

  <div style="display:grid; grid-template-columns: 1fr 340px; gap:20px; align-items:start;">
    
    <!-- LEFT COLUMN -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        
        <!-- Active Monitor -->
        <div id="activeDashboard" style="<?= $is_sending ? '' : 'display:none;' ?>">
            <div class="card" style="padding:16px; border-radius:12px; border:1px solid #e2e8f0; border-left:4px solid var(--accent); background:#fff;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                    <div>
                        <div id="dashStatus" style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--accent); margin-bottom:2px;">Sending</div>
                        <div id="dashSubject" style="font-size:15px; font-weight:800; color:#0f172a;"><?= htmlspecialchars($active['campaign_name'] ?? '') ?></div>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button onclick="cancelCampaign()" style="background:none; border:1px solid #fecaca; padding:4px 8px; border-radius:6px; color:#ef4444; font-size:11px; font-weight:700; cursor:pointer;">Stop</button>
                        <button onclick="resetCampaign()" style="background:none; border:1px solid #e2e8f0; padding:4px 8px; border-radius:6px; color:#64748b; font-size:11px; font-weight:700; cursor:pointer;">Reset</button>
                    </div>
                </div>
                <div class="prog-bar-wrap" style="height:6px; background:#f1f5f9; border-radius:10px; overflow:hidden; margin-bottom:10px;">
                    <div id="dashBar" class="prog-bar-fill" style="width:<?= $active['total'] > 0 ? ($active['offset']/$active['total']*100) : 0 ?>%; height:100%; background:var(--accent);"></div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:12px; font-weight:700;">
                    <div id="dashCount" style="color:#0f172a;"><?= $active['offset'] ?? 0 ?> / <?= $active['total'] ?? 0 ?></div>
                    <div style="display:flex; gap:12px;">
                        <span style="color:#10b981; display:flex; align-items:center; gap:4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                            <span id="dashSent"><?= $active['sent'] ?? 0 ?></span>
                        </span>
                        <span style="color:#ef4444; display:flex; align-items:center; gap:4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <span id="dashFail"><?= $active['failed'] ?? 0 ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Delivery Log -->
        <div class="card" style="padding:0; border-radius:12px; border:1px solid #e2e8f0; background:#fff; overflow:hidden;">
            <div style="padding:10px 16px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#94a3b8; letter-spacing:1px;">Global Delivery Log</div>
                <div style="font-size:10px; font-weight:700; color:#94a3b8;">Latest 50 events</div>
            </div>
            <div id="resultRows" style="max-height:600px; overflow-y:auto;">
                <?php if (empty($global_log)): ?>
                    <div style="padding:40px; text-align:center; color:#94a3b8; font-size:13px;">No delivery activity yet.</div>
                <?php else: ?>
                    <table style="width:100%; border-collapse:collapse; font-size:12px;">
                        <tbody id="globalLogBody">
                            <?php foreach ($global_log as $l): ?>
                                <tr style="border-bottom:1px solid #f8fafc;">
                                    <td style="padding:10px 16px; width:40px;">
                                        <div style="width:8px; height:8px; border-radius:50%; background:<?= $l['status']==='sent' ? '#10b981' : '#ef4444' ?>;"></div>
                                    </td>
                                    <td style="padding:10px 0;">
                                        <div style="font-weight:700; color:#0f172a;"><?= htmlspecialchars($l['name']) ?></div>
                                        <div style="font-size:11px; color:#94a3b8;"><?= htmlspecialchars($l['email']) ?></div>
                                    </td>
                                    <td style="padding:10px 16px; text-align:right;">
                                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;"><?= htmlspecialchars($l['campaign']) ?></div>
                                        <div style="font-size:10px; color:#94a3b8;"><?= date('H:i', strtotime($l['time'])) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Scheduled Queue -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="card" style="padding:0; border-radius:12px; border:1px solid #e2e8f0; background:#fff; overflow:hidden;">
            <div style="padding:10px 16px; border-bottom:1px solid #f1f5f9; background:#fafafa;">
                <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#94a3b8; letter-spacing:1px;">Upcoming Queue</div>
            </div>
            <div style="max-height:400px; overflow-y:auto;">
                <?php 
                $upcoming = [];
                foreach ($scheduled_files as $f) {
                    $d = json_decode(file_get_contents($f), true);
                    if ($d) $upcoming[] = $d;
                }
                usort($upcoming, fn($a, $b) => ($a['scheduled_at'] ?? 0) <=> ($b['scheduled_at'] ?? 0));
                
                if (empty($upcoming)): ?>
                    <div style="padding:24px; text-align:center; color:#94a3b8; font-size:12px;">No campaigns scheduled.</div>
                <?php else: ?>
                    <?php foreach ($upcoming as $u): ?>
                        <div style="padding:12px 16px; border-bottom:1px solid #f8fafc;">
                            <div style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:2px;"><?= htmlspecialchars($u['campaign_name'] ?? $u['subject']) ?></div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div style="font-size:11px; font-weight:700; color:var(--accent);"><?= date('M j, H:i', $u['scheduled_at']) ?></div>
                                <div style="font-size:11px; color:#94a3b8;"><?= $u['total'] ?> emails</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- System activity (Minimized) -->
        <div class="card" style="padding:0; border-radius:12px; border:1px solid #e2e8f0; background:#fff; overflow:hidden;">
            <div style="padding:10px 16px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#94a3b8; letter-spacing:1px;">Engine Status</div>
                <div style="width:8px; height:8px; border-radius:50%; background:#10b981;"></div>
            </div>
            <div id="systemLogBox" style="max-height:160px; overflow-y:auto; padding:12px; background:#0f172a; color:#94a3b8; font-family:monospace; font-size:10px; line-height:1.4;">
                Polling engine logs...
            </div>
        </div>
    </div>

  </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    initApp(); 
});
</script>

<?php require_once __DIR__ . '/core/footer.php'; ?>
