<?php
$page_title = 'System Configuration';
require_once __DIR__ . '/core/header.php';
?>

<section id="tab-settings" class="tab-section active">
  <div class="header">
    <h1>Settings</h1>
    <p>Configure your SMTP credentials.</p>
  </div>
  <div class="card" style="max-width:600px;">
    <div class="form-group">
      <label>SMTP Host</label>
      <input type="text" id="smtp_host" value="<?= htmlspecialchars($smtp_config['host']) ?>">
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
      <div class="form-group">
        <label>Port</label>
        <input type="number" id="smtp_port" value="<?= $smtp_config['port'] ?>">
      </div>
      <div class="form-group">
        <label>Encryption</label>
        <select id="smtp_encryption">
          <option value="ssl" <?= $smtp_config['encryption'] == 'ssl' ? 'selected' : '' ?>>SSL</option>
          <option value="tls" <?= $smtp_config['encryption'] == 'tls' ? 'selected' : '' ?>>TLS</option>
          <option value="none" <?= $smtp_config['encryption'] == 'none' ? 'selected' : '' ?>>None</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Username</label>
      <input type="text" id="smtp_username" value="<?= htmlspecialchars($smtp_config['username']) ?>">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" id="smtp_password" value="<?= htmlspecialchars($smtp_config['password']) ?>">
    </div>
    <div style="margin:24px 0; border-top:1px solid var(--border); padding-top:24px;">
      <div class="form-group">
        <label>System Timezone</label>
        <select id="timezone">
          <?php
          $timezones = DateTimeZone::listIdentifiers();
          $current_tz = $smtp_config['timezone'] ?? 'UTC';
          foreach ($timezones as $tz) {
              $selected = ($tz === $current_tz) ? 'selected' : '';
              echo "<option value=\"$tz\" $selected>$tz</option>";
          }
          ?>
        </select>
      </div>

      <div class="form-group">
        <label>Hourly Send Limit (Emails per hour)</label>
        <input type="number" id="hourly_limit" value="<?= $smtp_config['hourly_limit'] ?? 25 ?>" min="1" max="500">
        <p style="font-size:11px; color:var(--text-muted); margin-top:4px;">Stay under your host's limit (e.g., 25 for most shared hosts).</p>
      </div>
      
      <div class="form-group">
        <label>Sender Name</label>
        <input type="text" id="from_name" value="<?= htmlspecialchars($smtp_config['from_name']) ?>">
      </div>
      <div class="form-group">
        <label>Sender Email</label>
        <input type="email" id="from_email" value="<?= htmlspecialchars($smtp_config['from_email']) ?>">
      </div>
    </div>
    <button class="btn btn-primary" style="width:100%; height:50px;" onclick="saveSettings()">Save
      Configuration</button>
  </div>
</section>

<?php require_once __DIR__ . '/core/footer.php'; ?>