<?php
$page_title = 'Campaign Dashboard';
require_once __DIR__ . '/core/header.php';

// Load Contacts for the multi-select
$contacts_file = __DIR__ . '/storage/contacts.json';
$email_list = file_exists($contacts_file) ? json_decode(file_get_contents($contacts_file), true) : [];
$total_contacts = count($email_list);
?>

<section id="tab-campaign" class="tab-section active">
  <div class="header">
    <h1>Campaigns</h1>
    <p>Launch now or schedule your message for later.</p>
  </div>

  <!-- Active Status Dashboard -->
  <div id="activeDashboard" class="card"
    style="display:none; border-left: 4px solid var(--accent); margin-bottom: 32px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
      <div>
        <div id="dashStatus"
          style="text-transform:uppercase; font-size:11px; font-weight:800; color:var(--accent); letter-spacing:1px; margin-bottom:4px;">
          Status: Idle</div>
        <h3 id="dashSubject" style="font-size:18px; font-weight:800;">No active campaign</h3>
      </div>
      <div style="display:flex; gap:8px;">
        <button onclick="cancelCampaign()" class="btn btn-outline"
          style="padding:6px 12px; font-size:11px; color:var(--danger); border-color:rgba(239, 68, 68, 0.1);">Stop</button>
        <button onclick="resetCampaign()" class="btn btn-outline"
          style="padding:6px 12px; font-size:11px;">Reset</button>
      </div>
    </div>

    <div id="dashProgressWrap" style="display:none;">
      <div style="background:#f1f5f9; height:8px; border-radius:10px; overflow:hidden; margin-bottom:12px;">
        <div id="dashBar" style="width:0%; height:100%; background:var(--accent); transition:width 0.5s;"></div>
      </div>
      <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:700;">
        <span id="dashCount">0 / 0</span>
        <div style="display:flex; gap:12px;">
          <span style="color:var(--success);">✓ <span id="dashSent">0</span></span>
          <span style="color:var(--danger);">✗ <span id="dashFail">0</span></span>
        </div>
      </div>
    </div>

    <div id="dashScheduleWrap" style="display:none; color:var(--text-muted); font-size:13px; font-weight:600;">
      ⏳ Scheduled for: <span id="dashTime" style="color:var(--text);">--</span>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">
    <div>
      <div class="card">
        <!-- Campaign Name -->
        <div class="form-group">
          <label>Campaign Name (for your records)</label>
          <input type="text" id="campaign_name" placeholder="e.g. June Newsletter 2025">
        </div>

        <!-- Subject -->
        <div class="form-group">
          <label>Email Subject</label>
          <input type="text" id="subject" placeholder="e.g. Hello {{name}}!">
        </div>

        <!-- WP Style Editor -->
        <div class="form-group">
          <label>Message Content</label>
          <div class="wp-editor">
            <div class="wp-tabs">
              <div class="wp-tab active" id="tabVisual" onclick="switchEditor('visual')">Visual</div>
              <div class="wp-tab" id="tabText" onclick="switchEditor('text')">Text (HTML)</div>
            </div>

            <div class="wp-toolbar" id="wpToolbar">
              <button class="wp-tool" id="toolBold" title="Bold" onclick="exec('bold')"><b>B</b></button>
              <button class="wp-tool" id="toolItalic" title="Italic" onclick="exec('italic')"><i>I</i></button>
              <button class="wp-tool" id="toolUnderline" title="Underline" onclick="exec('underline')"><u>U</u></button>
              <div class="wp-tool-sep"></div>
              <button class="wp-tool" onclick="exec('formatBlock', 'H1')">H1</button>
              <button class="wp-tool" onclick="exec('formatBlock', 'H2')">H2</button>
              <button class="wp-tool" onclick="exec('formatBlock', 'H3')">H3</button>
              <div class="wp-tool-sep"></div>
              <button class="wp-tool" onclick="exec('insertUnorderedList')">• List</button>
              <button class="wp-tool" onclick="exec('insertOrderedList')">1. List</button>
              <div class="wp-tool-sep"></div>
              <button class="wp-tool" onclick="exec('justifyLeft')">L</button>
              <button class="wp-tool" onclick="exec('justifyCenter')">C</button>
              <button class="wp-tool" onclick="exec('justifyRight')">R</button>
              <div class="wp-tool-sep"></div>
              <button class="wp-tool" onclick="exec('createLink')" title="Insert Link">Link</button>
              <button class="wp-tool" onclick="exec('unlink')" title="Remove Link"><s>Link</s></button>
              <input type="color" class="wp-tool" style="width:40px; padding:2px;"
                onchange="exec('foreColor', this.value)" title="Text Color">
            </div>

            <div id="editorVisual" class="wp-visual" contenteditable="true" oninput="syncToText()"></div>
            <textarea id="body" class="wp-text" oninput="syncToVisual()"
              placeholder="Enter your message or HTML code here..."></textarea>
          </div>
          <p style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">Use <strong>{{name}}</strong> to
            personalize for each recipient.</p>
        </div>

        <div class="form-group">
          <label>Recipient Mode</label>
          <div style="display:flex; gap:8px;">
            <button class="btn btn-outline active" id="btnAll" onclick="setRecipient('all')" style="flex:1;">All
              Contacts (<?= $total_contacts ?>)</button>
            <button class="btn btn-outline" id="btnOne" onclick="setRecipient('one')" style="flex:1;">Specific
              List</button>
          </div>

          <div id="specificField" style="display:none; margin-top:12px;" class="multi-select-container">
            <div class="tags-container" id="selectedTags">
              <span style="color:var(--text-muted); font-size:11px; font-style:italic;">No one selected yet...</span>
            </div>
            <input type="text" id="contactSearch" placeholder="Search people..." class="search-box"
              oninput="filterContacts()">
            <div class="contact-list" id="multiContactList">
              <?php foreach ($email_list as $c): ?>
                <label class="contact-row"
                  data-search="<?= strtolower(htmlspecialchars($c['name'] . ' ' . $c['email'])) ?>">
                  <input type="checkbox" class="contact-checkbox" data-name="<?= htmlspecialchars($c['name']) ?>"
                    value="<?= htmlspecialchars($c['email']) ?>" onchange="updateSelectedTags()">
                  <div style="flex:1;">
                    <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars($c['name']) ?></div>
                    <div style="color:var(--text-muted); font-size:11px;"><?= htmlspecialchars($c['email']) ?></div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
            <div
              style="padding:10px; border-top:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
              <span style="font-size:11px; font-weight:700; color:var(--accent);" id="selectedCount">0 Selected</span>
              <button onclick="clearAllSelection()"
                style="background:none; border:none; color:var(--danger); font-size:11px; font-weight:700; cursor:pointer;">Clear
                All</button>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Delivery Schedule</label>
          <div style="display:flex; gap:8px; margin-bottom:12px;">
            <button class="btn btn-outline active" id="btnNow" onclick="setScheduleMode('now')" style="flex:1;">Send
              Now</button>
            <button class="btn btn-outline" id="btnLater" onclick="setScheduleMode('later')"
              style="flex:1;">Schedule</button>
          </div>
          <div id="schedulePicker" style="display:none;">
            <input type="datetime-local" id="scheduled_at" style="font-size:13px; margin-bottom:10px;">
          </div>
        </div>

        <button class="btn btn-primary" id="sendBtn" onclick="sendEmails()" style="width:100%; height: 50px;">🚀 Launch
          Campaign Now</button>
      </div>

      <!-- Progress Dashboard -->
      <div class="card" id="resultsBox" style="display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h3 id="reportTitle" style="font-size:16px;">Status: Sending</h3>
          <button onclick="cancelCampaign()"
            style="color:var(--danger); border:none; background:none; font-weight:700; font-size:12px; cursor:pointer;">Stop</button>
        </div>
        <div style="background:#f1f5f9; height:8px; border-radius:10px; overflow:hidden; margin-bottom:12px;">
          <div id="progressBar" style="width:0%; height:100%; background:var(--accent); transition:width 0.5s;"></div>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:20px;">
          <span id="progressCount">0 / 0</span>
          <div id="resultSummary"></div>
        </div>
        <div class="log-wrap" id="resultRows"></div>
      </div>
    </div>
</section>

<?php require_once __DIR__ . '/core/footer.php'; ?>