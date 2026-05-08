<?php
$page_title = 'New Campaign';
require_once __DIR__ . '/core/header.php';
$contacts_file = __DIR__ . '/storage/contacts.json';
$email_list = file_exists($contacts_file) ? json_decode(file_get_contents($contacts_file), true) : [];
$total_contacts = count($email_list);
?>

<style>
.wizard-wrap { max-width: 720px; margin: 0 auto; }
@media(max-width:900px){ .wizard-wrap { grid-template-columns: 1fr; } }
@media(max-width:600px){ 
  .step-label { display: none; }
  .step-divider { margin: 0 8px; max-width: none; }
  .steps { justify-content: space-between; }
}

/* Step indicator */
.steps { display: flex; align-items: center; gap: 0; margin-bottom: 24px; }
.step-item { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #94a3b8; cursor: pointer; }
.step-item.active { color: var(--accent); }
.step-item.done { color: #10b981; }
.step-num { width: 24px; height: 24px; border-radius: 50%; border: 2px solid currentColor; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; flex-shrink: 0; }
.step-item.done .step-num { background: #10b981; border-color: #10b981; color: #fff; }
.step-item.active .step-num { background: var(--accent); border-color: var(--accent); color: #fff; }
.step-divider { flex: 1; height: 1px; background: #e2e8f0; margin: 0 12px; max-width: 48px; }

/* Step panels */
.step-panel { display: none; }
.step-panel.active { display: block; }

/* Editor */
.wp-editor { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.wp-tabs { display: flex; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.wp-tab { padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; color: #64748b; border-bottom: 2px solid transparent; }
.wp-tab.active { color: var(--accent); border-bottom-color: var(--accent); background: #fff; }
.wp-toolbar { display: flex; align-items: center; gap: 2px; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; background: #fafafa; }
.wp-tool { background: none; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 700; color: #475569; }
.wp-tool:hover, .wp-tool.active { background: #e2e8f0; }
.wp-tool-sep { width: 1px; height: 18px; background: #e2e8f0; margin: 0 4px; }
.wp-visual { min-height: 280px; height: 280px; padding: 14px; font-size: 14px; outline: none; overflow-y: auto; }
.wp-text { display: none; width: 100%; height: 280px; min-height: 280px; padding: 14px; font-size: 12px; font-family: monospace; border: none; outline: none; resize: none; box-sizing: border-box; }

/* Contact list */
.contact-list { max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
.contact-row { display: flex; align-items: center; gap: 10px; padding: 9px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
.contact-row:last-child { border-bottom: none; }
.contact-row:hover { background: #f8fafc; }
.contact-row.selected { background: #eff6ff; }
.tags-container { display: flex; flex-wrap: wrap; gap: 6px; min-height: 36px; padding: 6px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 8px; }
.tag { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; background:#eff6ff; color:var(--accent); border-radius:20px; font-size:11px; font-weight:700; }
.tag-remove { cursor:pointer; opacity:0.6; }
.search-box { width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:12px; margin-bottom:8px; box-sizing:border-box; outline:none; }
.search-box:focus { border-color: var(--accent); }

/* Mode buttons */
.mode-btn { flex:1; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; background: #fff; color: #64748b; transition: all 0.15s; }
.mode-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }

/* Nav buttons */
.step-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; }

/* Live panel */
.live-panel { position: sticky; top: 24px; }
.live-panel .card { padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; }
.panel-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; margin-bottom: 4px; }
.panel-value { font-size: 13px; font-weight: 700; color: #0f172a; word-break: break-all; }
.panel-value.muted { color: #94a3b8; font-style: italic; font-weight: 500; }

/* Progress bar */
.prog-bar-wrap { background: #f1f5f9; height: 6px; border-radius: 10px; overflow: hidden; }
.prog-bar-fill { height: 100%; background: var(--accent); transition: width 0.5s; }

/* Launch btn */
#sendBtn { width:100%; padding: 13px; font-size: 14px; font-weight: 800; border-radius: 8px; margin-top: 16px; letter-spacing: -0.2px; }
</style>

<section id="tab-campaign" class="tab-section active">

  <div style="margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:12px; display:flex; align-items:center; justify-content:space-between;">
    <div>
      <h1 style="font-size:20px;font-weight:800;letter-spacing:-0.5px;color:#0f172a;">New Campaign</h1>
      <p style="font-size:12px;color:#64748b;font-weight:500;">Compose, target, and launch in seconds.</p>
    </div>
    <div style="font-size:11px;font-weight:700;color:#64748b;"><?= $total_contacts ?> contacts ready</div>
  </div>

  <div class="wizard-wrap">

    <!-- LEFT: Wizard -->
    <div>
      <!-- Step Indicator -->
      <div class="steps">
        <div class="step-item active" id="stepTab1" onclick="goStep(1)">
          <div class="step-num">1</div> <span class="step-label">Compose</span>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="stepTab2" onclick="goStep(2)">
          <div class="step-num">2</div> <span class="step-label">Message</span>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="stepTab3" onclick="goStep(3)">
          <div class="step-num">3</div> <span class="step-label">Recipients</span>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="stepTab4" onclick="goStep(4)">
          <div class="step-num">4</div> <span class="step-label">Schedule & Launch</span>
        </div>
      </div>

      <div class="card" style="padding:20px; border-radius:12px; border:1px solid #e2e8f0;">

        <!-- ── STEP 1: Compose ── -->
        <div class="step-panel active" id="step1">
          <div class="form-group">
            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;letter-spacing:0.5px;">Campaign Name</label>
            <input type="text" id="campaign_name" placeholder="e.g. May Newsletter" style="font-size:13px;padding:10px 12px;">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;letter-spacing:0.5px;">Subject Line</label>
            <input type="text" id="subject" placeholder='e.g. Hello {{name}}, check this out!' style="font-size:13px;padding:10px 12px;">
          </div>
          <div class="step-nav">
            <div></div>
            <button class="btn btn-primary" style="padding:9px 24px;font-size:13px;" onclick="goStep(2)">
              Next: Message
              <svg style="margin-left:6px" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>

        <!-- ── STEP 2: Message Body ── -->
        <div class="step-panel" id="step2">
          <div class="form-group" style="margin-bottom:0;">
            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;letter-spacing:0.5px;">Message Body</label>
            <div class="wp-editor">
              <div class="wp-tabs">
                <div class="wp-tab active" id="tabVisual" onclick="switchEditor('visual')">Visual</div>
                <div class="wp-tab" id="tabText" onclick="switchEditor('text')">HTML</div>
              </div>
              <div class="wp-toolbar" id="wpToolbar">
                <button class="wp-tool" id="toolBold" title="Bold" onclick="exec('bold')"><b>B</b></button>
                <button class="wp-tool" id="toolItalic" title="Italic" onclick="exec('italic')"><i>I</i></button>
                <button class="wp-tool" id="toolUnderline" title="Underline" onclick="exec('underline')"><u>U</u></button>
                <div class="wp-tool-sep"></div>
                <button class="wp-tool" onclick="exec('formatBlock','H1')">H1</button>
                <button class="wp-tool" onclick="exec('formatBlock','H2')">H2</button>
                <button class="wp-tool" onclick="exec('formatBlock','H3')">H3</button>
                <div class="wp-tool-sep"></div>
                <button class="wp-tool" title="Bullet List" onclick="exec('insertUnorderedList')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
                </button>
                <button class="wp-tool" title="Numbered List" onclick="exec('insertOrderedList')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
                </button>
                <div class="wp-tool-sep"></div>
                <button class="wp-tool" title="Align Left" onclick="exec('justifyLeft')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
                </button>
                <button class="wp-tool" title="Align Center" onclick="exec('justifyCenter')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                </button>
                <button class="wp-tool" title="Align Right" onclick="exec('justifyRight')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="wp-tool-sep"></div>
                <button class="wp-tool" onclick="exec('createLink')" title="Insert Link">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                </button>
                <button class="wp-tool" onclick="exec('unlink')" title="Remove Link">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18.84 12.25l1.72-1.71a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M5.17 11.75l-1.72 1.71a5 5 0 007.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                </button>
                <div class="wp-tool-sep"></div>
                <input type="color" class="wp-tool" style="width:28px;padding:2px;height:24px;" onchange="exec('foreColor',this.value)" title="Text Color">
              </div>
              <div id="editorVisual" class="wp-visual" contenteditable="true" oninput="syncToText()"></div>
              <textarea id="body" class="wp-text" oninput="syncToVisual()" placeholder="Write HTML here..."></textarea>
            </div>
            <div style="font-size:11px;color:#94a3b8;margin-top:6px;">Use <strong style="color:#475569;">{{name}}</strong> to personalize each email.</div>
          </div>
          <div class="step-nav">
            <button class="btn btn-outline" style="padding:9px 20px;font-size:13px;" onclick="goStep(1)">← Back</button>
            <button class="btn btn-primary" style="padding:9px 24px;font-size:13px;" onclick="goStep(3)">
              Next: Recipients
              <svg style="margin-left:6px" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>

        <!-- ── STEP 3: Recipients ── -->
        <div class="step-panel" id="step3">
          <div class="form-group" style="margin-bottom:16px;">
            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;letter-spacing:0.5px;">Target Audience</label>
            <div style="display:flex;gap:8px;margin-top:6px;">
              <button class="mode-btn active" id="btnAll" onclick="setRecipient('all')">
                All Contacts (<?= $total_contacts ?>)
              </button>
              <button class="mode-btn" id="btnOne" onclick="setRecipient('one')">
                Specific People
              </button>
            </div>
          </div>

          <div id="specificField" style="display:none;">
            <input type="text" id="contactSearch" placeholder="Search contacts..." class="search-box" oninput="filterContacts()">
            <div class="tags-container" id="selectedTags">
              <span style="color:#94a3b8;font-size:11px;font-style:italic;">No one selected yet…</span>
            </div>
            <div class="contact-list" id="multiContactList">
              <?php foreach ($email_list as $c): ?>
                <label class="contact-row" data-search="<?= strtolower(htmlspecialchars($c['name'].' '.$c['email'])) ?>">
                  <input type="checkbox" class="contact-checkbox" data-name="<?= htmlspecialchars($c['name']) ?>" value="<?= htmlspecialchars($c['email']) ?>" onchange="updateSelectedTags()">
                  <div style="flex:1;">
                    <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($c['name']) ?></div>
                    <div style="color:#94a3b8;font-size:11px;"><?= htmlspecialchars($c['email']) ?></div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
            <div style="padding:8px 12px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:11px;font-weight:700;color:var(--accent);" id="selectedCount">0 Selected</span>
              <button onclick="clearAllSelection()" style="background:none;border:none;color:var(--danger);font-size:11px;font-weight:700;cursor:pointer;">Clear All</button>
            </div>
          </div>

          <div class="step-nav">
            <button class="btn btn-outline" style="padding:9px 20px;font-size:13px;" onclick="goStep(2)">← Back</button>
            <button class="btn btn-primary" style="padding:9px 24px;font-size:13px;" onclick="goStep(4)">
              Next: Schedule
              <svg style="margin-left:6px" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>

        <!-- ── STEP 4: Schedule & Launch ── -->
        <div class="step-panel" id="step4">
          <div class="form-group">
            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;letter-spacing:0.5px;">Delivery Time</label>
            <div style="display:flex;gap:8px;margin-top:6px;">
              <button class="mode-btn active" id="btnNow" onclick="setScheduleMode('now')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;vertical-align:middle;"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                Send Now
              </button>
              <button class="mode-btn" id="btnLater" onclick="setScheduleMode('later')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Schedule
              </button>
            </div>
          </div>

          <div id="schedulePicker" style="display:none;">
            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;letter-spacing:0.5px;">Pick Date & Time</label>
            <input type="datetime-local" id="scheduled_at" style="font-size:13px;padding:10px 12px;margin-top:6px;width:100%;box-sizing:border-box;border:1px solid #e2e8f0;border-radius:8px;outline:none;">
          </div>

          <!-- Summary review -->
          <div style="margin-top:16px;padding:14px;background:#f8fafc;border-radius:8px;border:1px solid #f1f5f9;">
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;margin-bottom:10px;">Campaign Summary</div>
            <div style="display:grid;gap:8px;">
              <div style="display:flex;justify-content:space-between;font-size:12px;">
                <span style="color:#64748b;font-weight:600;">Campaign</span>
                <span style="font-weight:700;" id="sum_name">—</span>
              </div>
              <div style="display:flex;justify-content:space-between;font-size:12px;">
                <span style="color:#64748b;font-weight:600;">Subject</span>
                <span style="font-weight:700;max-width:200px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" id="sum_subject">—</span>
              </div>
              <div style="display:flex;justify-content:space-between;font-size:12px;">
                <span style="color:#64748b;font-weight:600;">Recipients</span>
                <span style="font-weight:700;" id="sum_recipients">—</span>
              </div>
            </div>
          </div>

          <button class="btn btn-primary" id="sendBtn" onclick="sendEmails()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;vertical-align:middle;"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
            Launch Campaign
          </button>

          <div class="step-nav" style="margin-top:12px;">
            <button class="btn btn-outline" style="padding:9px 20px;font-size:13px;" onclick="goStep(3)">← Back</button>
            <div></div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Monitoring moved to logs.php -->
</section>

<script>
let currentStep = 1;

function goStep(n) {
  for (let i = 1; i <= 4; i++) {
    const panel = document.getElementById('step' + i);
    if (panel) panel.classList.toggle('active', i === n);
    const tab = document.getElementById('stepTab' + i);
    if (!tab) continue;
    tab.classList.remove('active', 'done');
    if (i < n) tab.classList.add('done');
    else if (i === n) tab.classList.add('active');
  }
  for (let i = 1; i < n; i++) {
    const num = document.getElementById('stepTab' + i);
    if (num) num.querySelector('.step-num').innerHTML =
      '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
  }
  for (let i = n; i <= 4; i++) {
    const num = document.getElementById('stepTab' + i);
    if (num) num.querySelector('.step-num').textContent = i;
  }
  currentStep = n;
  updateSummary();
}

function updateSummary() {
  const name = document.getElementById('campaign_name').value || '—';
  const subj = document.getElementById('subject').value || '—';
  if (document.getElementById('sum_name')) document.getElementById('sum_name').textContent = name;
  if (document.getElementById('sum_subject')) document.getElementById('sum_subject').textContent = subj;
  const mode = recipientMode === 'all' ? '<?= $total_contacts ?> contacts' : (document.querySelectorAll('.contact-checkbox:checked').length + ' selected');
  if (document.getElementById('sum_recipients')) document.getElementById('sum_recipients').textContent = mode;
}
</script>

<?php require_once __DIR__ . '/core/footer.php'; ?>