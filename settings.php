<?php
$page_title = 'Settings';
require_once __DIR__ . '/core/header.php';
?>

<section id="tab-settings" class="tab-section active">

  <div style="margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px;">
    <h1 style="font-size:20px;font-weight:800;letter-spacing:-0.5px;color:#0f172a;">Settings</h1>
    <p style="font-size:12px;color:#64748b;font-weight:500;">Configure your delivery engine and platform identity.</p>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:20px;align-items:start;">

    <!-- SMTP -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        <div style="font-size:13px;font-weight:800;">Delivery Engine (SMTP)</div>
      </div>

      <div class="form-group">
        <label>SMTP Host</label>
        <input type="text" id="smtp_host" style="padding:9px 12px;font-size:13px;" value="<?= htmlspecialchars($smtp_config['host']) ?>">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group">
          <label>Port</label>
          <input type="number" id="smtp_port" style="padding:9px 12px;font-size:13px;" value="<?= $smtp_config['port'] ?>">
        </div>
        <div class="form-group">
          <label>Encryption</label>
          <select id="smtp_encryption" style="padding:9px 12px;font-size:13px;">
            <option value="ssl"  <?= $smtp_config['encryption'] == 'ssl'  ? 'selected' : '' ?>>SSL</option>
            <option value="tls"  <?= $smtp_config['encryption'] == 'tls'  ? 'selected' : '' ?>>TLS</option>
            <option value="none" <?= $smtp_config['encryption'] == 'none' ? 'selected' : '' ?>>None</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Username</label>
        <input type="text" id="smtp_username" style="padding:9px 12px;font-size:13px;" value="<?= htmlspecialchars($smtp_config['username']) ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>Password</label>
        <input type="password" id="smtp_password" style="padding:9px 12px;font-size:13px;" value="<?= htmlspecialchars($smtp_config['password']) ?>">
      </div>
    </div>

    <!-- Identity & System -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <div style="font-size:13px;font-weight:800;">Platform Identity</div>
      </div>

      <div class="form-group">
        <label>Sender Display Name</label>
        <input type="text" id="from_name" style="padding:9px 12px;font-size:13px;" value="<?= htmlspecialchars($smtp_config['from_name']) ?>">
      </div>
      <div class="form-group" style="margin-bottom:20px;">
        <label>Sender Email Address</label>
        <input type="email" id="from_email" style="padding:9px 12px;font-size:13px;" value="<?= htmlspecialchars($smtp_config['from_email']) ?>">
      </div>

      <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;margin-bottom:10px;">System</div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group" style="margin-bottom:0;">
          <label>Hourly Limit</label>
          <input type="number" id="hourly_limit" style="padding:9px 12px;font-size:13px;" value="<?= $smtp_config['hourly_limit'] ?? 25 ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>Timezone</label>
          <select id="timezone" style="padding:9px 12px;font-size:12px;">
            <?php
            $timezones  = DateTimeZone::listIdentifiers();
            $current_tz = $smtp_config['timezone'] ?? 'UTC';
            foreach ($timezones as $tz) {
              $sel = ($tz === $current_tz) ? 'selected' : '';
              echo "<option value=\"$tz\" $sel>$tz</option>";
            }
            ?>
          </select>
        </div>
      </div>

      <div class="form-group" style="margin-top:16px;">
        <label>Tracking Base URL</label>
        <input type="text" id="app_url" style="padding:9px 12px;font-size:12px;font-family:monospace;" value="<?= htmlspecialchars($smtp_config['app_url']) ?>" placeholder="https://example.com/mail/">
        <div style="margin-top:6px;display:flex;align-items:center;gap:6px;">
          <div style="width:6px;height:6px;background:#10b981;border-radius:50%;"></div>
          <div style="font-size:10px;font-weight:700;color:#64748b;">This URL is used for all tracking pixels and links.</div>
        </div>
      </div>
    </div>

  </div>

  <div style="margin-top:24px;display:flex;justify-content:flex-end;">
    <button class="btn btn-primary" onclick="saveSettings()" style="padding:10px 28px;font-size:13px;font-weight:800;border-radius:8px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:7px;vertical-align:middle;"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Save Configuration
    </button>
  </div>

</section>
<?php require_once __DIR__ . '/core/footer.php'; ?>