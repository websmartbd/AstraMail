let currentLogData = [];
let pollTimer = null;
let recipientMode = 'all';
let scheduleMode = 'now';

let modalResolve = null;
let savedSelection = null;

function saveSelection() {
  const sel = window.getSelection();
  if (sel.getRangeAt && sel.rangeCount) {
    savedSelection = sel.getRangeAt(0);
  }
}

function restoreSelection() {
  if (savedSelection) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedSelection);
  }
}

function esc(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function showModal(title, body, isConfirm = false, isPrompt = false, defaultValue = '') {
  return new Promise((resolve) => {
    const overlay = document.getElementById('customModal');
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = body;
    
    const inputWrap = document.getElementById('modalInputWrap');
    const input = document.getElementById('modalInput');
    inputWrap.style.display = isPrompt ? 'block' : 'none';
    if(isPrompt) input.value = defaultValue;

    const btnCancel = document.getElementById('modalBtnCancel');
    btnCancel.style.display = (isConfirm || isPrompt) ? 'block' : 'none';
    
    overlay.classList.add('open');
    modalResolve = resolve;

    const close = (val) => {
      overlay.classList.remove('open');
      setTimeout(() => resolve(val), 300);
    };

    document.getElementById('modalBtnOk').onclick = () => close(isPrompt ? input.value : true);
    btnCancel.onclick = () => close(null);
  });
}

// Global alert/confirm overrides for convenience
window.niceAlert = (title, msg) => showModal(title, msg);
window.niceConfirm = (title, msg) => showModal(title, msg, true);
window.nicePrompt = (title, msg, def) => showModal(title, msg, false, true, def);

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('open');
}

function setRecipient(mode) {
  recipientMode = mode;
  document.getElementById('btnAll').classList.toggle('active', mode === 'all');
  document.getElementById('btnOne').classList.toggle('active', mode === 'one');
  document.getElementById('specificField').style.display = mode === 'one' ? 'block' : 'none';
}

function setScheduleMode(mode) {
  scheduleMode = mode;
  document.getElementById('btnNow').classList.toggle('active', mode === 'now');
  document.getElementById('btnLater').classList.toggle('active', mode === 'later');
  document.getElementById('schedulePicker').style.display = mode === 'later' ? 'block' : 'none';
}

function switchEditor(mode) {
  const visual = document.getElementById('editorVisual');
  const text = document.getElementById('body');
  const toolbar = document.getElementById('wpToolbar');

  document.getElementById('tabVisual').classList.toggle('active', mode === 'visual');
  document.getElementById('tabText').classList.toggle('active', mode === 'text');
  
  if (mode === 'visual') {
    visual.innerHTML = text.value;
    visual.style.display = 'block';
    toolbar.style.display = 'flex';
    text.style.display = 'none';
    visual.focus();
  } else {
    text.value = visual.innerHTML;
    visual.style.display = 'none';
    toolbar.style.display = 'none';
    text.style.display = 'block';
    text.focus();
  }
}

async function syncBounces() {
  const btn = document.getElementById('syncBtn');
  const originalHtml = btn.innerHTML;
  btn.innerHTML = 'Syncing...';
  btn.disabled = true;

  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'sync_bounces');

  try {
    const res = await fetch('send.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'success') {
      window.location.reload();
    } else {
      alert('Sync failed. Please check IMAP settings.');
      btn.innerHTML = originalHtml;
      btn.disabled = false;
    }
  } catch(e) {
    btn.innerHTML = originalHtml;
    btn.disabled = false;
  }
}

async function exec(command, value = null) {
  if (command === 'createLink' && value === null) {
    saveSelection();
    
    // Try to get existing URL if selection is inside a link
    let existingUrl = 'https://';
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
      const container = sel.getRangeAt(0).startContainer;
      const parentLink = container.parentElement.closest('a');
      if (parentLink) existingUrl = parentLink.getAttribute('href');
    }

    value = await nicePrompt('Insert/Edit Link', 'Enter the full URL:', existingUrl);
    restoreSelection();
    if (!value) return;
  }
  document.getElementById('editorVisual').focus();
  document.execCommand(command, false, value);
  syncToText();
  updateToolbarState();
}

function updateToolbarState() {
  const tools = {
    'bold': 'toolBold',
    'italic': 'toolItalic',
    'underline': 'toolUnderline'
  };
  for (let cmd in tools) {
    const active = document.queryCommandState(cmd);
    if(document.getElementById(tools[cmd])) {
        document.getElementById(tools[cmd]).classList.toggle('active', active);
    }
  }
}

function syncToVisual() {
  document.getElementById('editorVisual').innerHTML = document.getElementById('body').value;
}

function syncToText() {
  document.getElementById('body').value = document.getElementById('editorVisual').innerHTML;
}

// Ensure paste is clean and state is updated
document.addEventListener('DOMContentLoaded', () => {
  const visual = document.getElementById('editorVisual');
  if(visual) {
    visual.addEventListener('paste', (e) => {
      e.preventDefault();
      const text = (e.originalEvent || e).clipboardData.getData('text/plain');
      document.execCommand('insertText', false, text);
    });

    // Detect cursor position for toolbar state
    ['keyup', 'click', 'mouseup'].forEach(ev => {
        visual.addEventListener(ev, updateToolbarState);
    });
  }
});

function filterContacts() {
  const query = document.getElementById('contactSearch').value.toLowerCase();
  document.querySelectorAll('.contact-row').forEach(row => {
    row.style.display = row.getAttribute('data-search').includes(query) ? 'flex' : 'none';
  });
}

function updateSelectedTags() {
  const container = document.getElementById('selectedTags');
  const checkboxes = document.querySelectorAll('.contact-checkbox');
  const checked = document.querySelectorAll('.contact-checkbox:checked');
  
  // Update row highlighting
  checkboxes.forEach(cb => {
    cb.closest('.contact-row').classList.toggle('selected', cb.checked);
  });

  if (checked.length === 0) {
    container.innerHTML = '<span style="color:var(--text-muted); font-size:11px; font-style:italic;">No one selected yet...</span>';
    document.getElementById('selectedCount').textContent = '0 Selected';
    return;
  }

  container.innerHTML = Array.from(checked).map(cb => `
    <div class="tag">
      ${esc(cb.getAttribute('data-name'))}
      <span class="tag-remove" onclick="removeTag('${esc(cb.value)}')">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </span>
    </div>
  `).join('');

  document.getElementById('selectedCount').textContent = checked.length + ' Selected';
}

function removeTag(email) {
  const cb = Array.from(document.querySelectorAll('.contact-checkbox')).find(c => c.value === email);
  if (cb) {
    cb.checked = false;
    updateSelectedTags();
  }
}

function clearAllSelection() {
  document.querySelectorAll('.contact-checkbox').forEach(cb => cb.checked = false);
  updateSelectedTags();
}

async function sendEmails() {
  const subject = document.getElementById('subject').value.trim();
  const body    = document.getElementById('body').value.trim();
  if (!subject || !body) return niceAlert('Oops!', 'Please enter a subject and message.');

  let target = 'all';
  if (recipientMode === 'one') {
    const selected = Array.from(document.querySelectorAll('.contact-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) return niceAlert('Recipient Required', 'Please select at least one person.');
    target = selected.join(',');
  }

  let schedule = '';
  if (scheduleMode === 'later') {
    schedule = document.getElementById('scheduled_at').value;
    if (!schedule) return niceAlert('Time Required', 'Please select a date and time for scheduling.');
  }

  const total = target === 'all' ? TOTAL_CONTACTS : target.split(',').length;
  const confirmMsg = schedule ? `Schedule this campaign for ${new Date(schedule).toLocaleString()}?` : `Ready to launch this campaign to ${total} contacts?`;
  
  if (!(await niceConfirm('Confirm Launch', confirmMsg))) return;

  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'queue');
  form.append('name', document.getElementById('campaign_name').value.trim() || subject);
  form.append('subject', subject);
  form.append('body', body);
  form.append('target', target);
  form.append('scheduled_at', schedule);

  const btn = document.getElementById('sendBtn');
  btn.disabled = true;
  btn.textContent = 'Processing...';

  try {
    const res = await fetch('send.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'scheduled') {
      niceAlert('Success', 'Campaign scheduled successfully!');
      location.href = 'scheduled.php';
    } else if (data.status === 'queued') {
      location.href = 'logs.php';
    } else {
      niceAlert('Error', data.message);
      btn.disabled = false;
      btn.textContent = '🚀 Launch Campaign Now';
    }
  } catch(e) { 
    btn.disabled = false; 
    btn.textContent = '🚀 Launch Campaign Now';
  }
}

function startPolling() {
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = setInterval(pollStatus, 5000);
  pollStatus();
}

async function pollStatus() {
  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'status');
  try {
    const res = await fetch('send.php', { method: 'POST', body: form });
    const data = await res.json();
    renderStatus(data);
    
    // Also fetch system log if we are on the logs page
    if (document.getElementById('systemLogBox')) {
        fetchSystemLog();
    }
  } catch(e) {}
}

async function fetchSystemLog() {
  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'get_system_log');
  try {
    const res = await fetch('send.php', { method: 'POST', body: form });
    const data = await res.json();
    const box = document.getElementById('systemLogBox');
    if (data.status === 'success') {
      box.textContent = data.log;
      box.scrollTop = box.scrollHeight; // Auto scroll to bottom
    }
  } catch(e) {}
}

async function clearSystemLog() {
  if (!(await niceConfirm('Clear System Log', 'Are you sure you want to empty the activity log?'))) return;
  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'clear_system_log');
  await fetch('send.php', { method: 'POST', body: form });
  fetchSystemLog();
}

function renderStatus(data) {
  if (!data || data.status === 'idle') {
    if (document.getElementById('noActiveCampaign')) document.getElementById('noActiveCampaign').style.display = 'block';
    if (document.getElementById('activeDashboard')) document.getElementById('activeDashboard').style.display = 'none';
    if (document.getElementById('logCountInfo')) document.getElementById('logCountInfo').textContent = 'System Idle';
    return;
  }
  
  if (document.getElementById('noActiveCampaign')) document.getElementById('noActiveCampaign').style.display = 'none';
  if (document.getElementById('activeDashboard')) document.getElementById('activeDashboard').style.display = 'block';
  
  const { status, subject, sent = 0, failed = 0, total = 0, offset = 0, sent_log = [], scheduled_at } = data;
  
  document.getElementById('activeDashboard').style.display = 'block';
  if (document.getElementById('dashSubject')) document.getElementById('dashSubject').textContent = subject || 'No active campaign';
  if (document.getElementById('dashStatus')) document.getElementById('dashStatus').textContent = status.toUpperCase();
  if (document.getElementById('logCountInfo')) document.getElementById('logCountInfo').textContent = status.toUpperCase();
  
  // Update Global Stats if on logs.php
  if (data.pending_count !== undefined && document.querySelector('[style*="color:var(--accent)"][style*="font-size:14px"]')) {
      document.querySelector('[style*="color:var(--accent)"][style*="font-size:14px"]').textContent = data.pending_count;
  }
  if (data.total_delivered !== undefined && document.querySelector('[style*="color:#10b981"][style*="font-size:14px"]')) {
      document.querySelector('[style*="color:#10b981"][style*="font-size:14px"]').textContent = data.total_delivered;
  }

  if (['queued', 'sending', 'done', 'cancelled'].includes(status)) {
    document.getElementById('dashProgressWrap').style.display = 'block';
    document.getElementById('dashScheduleWrap').style.display = 'none';
    
    const pct = total > 0 ? Math.round((offset / total) * 100) : 0;
    document.getElementById('dashBar').style.width = pct + '%';
    document.getElementById('dashCount').textContent = offset + ' / ' + total;
    document.getElementById('dashSent').textContent = sent;
    document.getElementById('dashFail').textContent = failed;
  } else if (status === 'scheduled') {
    document.getElementById('dashProgressWrap').style.display = 'none';
    document.getElementById('dashScheduleWrap').style.display = 'block';
    document.getElementById('dashTime').textContent = new Date(scheduled_at * 1000).toLocaleString();
  }

  if (sent_log.length > 0) {
    const box = document.getElementById('resultRows');
    if(box) {
        box.style.display = 'block';
        const isGlobalTable = !!document.getElementById('globalLogBody');
        
        if (isGlobalTable) {
            // Prepend new active logs to the global table
            // For simplicity, we'll just show the last 20 active logs at the top
            const activeLogsHtml = sent_log.slice(-20).reverse().map(r => {
                const currentStatus = (data.contact_status && data.contact_status[r.email]) || r.status;
                return `
                <tr style="border-bottom:1px solid #f8fafc; background: #fdf2f200;">
                    <td style="padding:10px 16px; width:40px;">
                        <div style="width:8px; height:8px; border-radius:50%; background:${currentStatus === 'sent' ? '#10b981' : '#ef4444'};"></div>
                    </td>
                    <td style="padding:10px 0;">
                        <div style="font-weight:700; color:#0f172a;">${esc(r.name)}</div>
                        <div style="font-size:11px; color:#94a3b8;">${esc(r.email)}</div>
                    </td>
                    <td style="padding:10px 16px; text-align:right;">
                        <div style="font-size:10px; font-weight:800; color:var(--accent); text-transform:uppercase;">ACTIVE</div>
                        <div style="font-size:10px; color:#94a3b8;">Just now</div>
                    </td>
                </tr>
            `}).join('');
            
            const tbody = document.getElementById('globalLogBody');
            // If we want a true global log, we'd need to merge, but for now let's just update the top
            // To keep it simple and responsive:
            if (status === 'sending') {
                // If sending, we show active logs. When done, the page reload will show the archived ones.
                tbody.innerHTML = activeLogsHtml;
            }
        } else {
            box.innerHTML = sent_log.slice().reverse().map(r => `
                <div class="log-item">
                  <div class="status-dot" style="background:${r.status === 'sent' ? 'var(--success)' : 'var(--danger)'}"></div>
                  <div style="flex:1;"><b>${esc(r.name)}</b> <span style="color:var(--text-muted);">${esc(r.email)}</span></div>
                </div>
              `).join('');
        }
    }
  }

  if (status === 'done' || status === 'cancelled') {
    clearInterval(pollTimer);
    document.getElementById('sendBtn').disabled = false;
    document.getElementById('sendBtn').textContent = '🚀 Launch Campaign';
  }
}

async function resetCampaign() {
    if (!(await niceConfirm('Reset Dashboard', 'This will hide the current status from the dashboard. The report will still be available in the Reports tab. Continue?'))) return;
    const form = new FormData();
    form.append('_token', API_TOKEN);
    form.append('action', 'reset');
    await fetch('send.php', { method: 'POST', body: form });
    location.reload();
}

async function addContact() {
  const name = document.getElementById('newContactName').value.trim();
  const email = document.getElementById('newContactEmail').value.trim();
  if (!name || !email) return;
  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'add_contact');
  form.append('name', name);
  form.append('email', email);
  const res = await fetch('send.php', { method: 'POST', body: form });
  const data = await res.json();
  if (data.status === 'success') location.reload();
}

async function editContact(el, index) {
    const oldName = el.dataset.name;
    const oldEmail = el.dataset.email;
    
    // Custom Multi-Field Edit
    const html = `
        <div style="text-align:left;">
            <div class="form-group" style="margin-bottom:12px;">
                <label style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; margin-bottom:6px; display:block;">Name</label>
                <input type="text" id="editName" value="${esc(oldName)}" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; outline:none;">
            </div>
            <div class="form-group">
                <label style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; margin-bottom:6px; display:block;">Email</label>
                <input type="email" id="editEmail" value="${esc(oldEmail)}" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; outline:none;">
            </div>
        </div>
    `;

    const confirmed = await niceConfirm('Update Contact', html);
    if (!confirmed) return;

    const newName = document.getElementById('editName').value.trim();
    const newEmail = document.getElementById('editEmail').value.trim();

    if (!newName || !newEmail) return niceAlert('Error', 'Both name and email are required.');

    const form = new FormData();
    form.append('_token', API_TOKEN);
    form.append('action', 'edit_contact');
    form.append('index', index);
    form.append('old_email', oldEmail); // Fix #4: Identity verification
    form.append('name', newName);
    form.append('email', newEmail);
    
    const res = await fetch('send.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'success') {
        location.reload();
    } else {
        niceAlert('Error', data.message || 'Failed to update contact.');
    }
}

async function reactivateContact(el, index) {
  const old_email = el.dataset.email; // Fix #4: Pass email for server-side verification
  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'reactivate_contact');
  form.append('index', index);
  form.append('old_email', old_email);

  await fetch('send.php', { method: 'POST', body: form });
  window.location.reload();
}

async function deleteContact(el, index) {
    if (!(await niceConfirm('Delete Contact', 'Are you sure you want to remove this contact?'))) return;
    const old_email = el.dataset.email; // Fix #4: Pass email for server-side verification
    const form = new FormData();
    form.append('_token', API_TOKEN);
    form.append('action', 'delete_contact');
    form.append('index', index);
    form.append('old_email', old_email);
    
    const res = await fetch('send.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'success') {
        location.reload();
    } else {
        niceAlert('Error', data.message || 'Failed to delete contact.');
    }
}

function filterContacts() {
    const input = document.getElementById('contactSearch');
    if (!input) return;
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

async function saveSettings() {
  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'save_settings');
  form.append('host', document.getElementById('smtp_host').value);
  form.append('port', document.getElementById('smtp_port').value);
  form.append('username', document.getElementById('smtp_username').value);
  form.append('password', document.getElementById('smtp_password').value);
  form.append('encryption', document.getElementById('smtp_encryption').value);
  form.append('from_name', document.getElementById('from_name').value);
  form.append('from_email', document.getElementById('from_email').value);
  form.append('hourly_limit', document.getElementById('hourly_limit').value);
  form.append('timezone', document.getElementById('timezone').value);
  form.append('app_url', document.getElementById('app_url').value);
  
  // IMAP
  form.append('imap_host', document.getElementById('imap_host').value);
  form.append('imap_port', document.getElementById('imap_port').value);
  form.append('imap_username', document.getElementById('imap_username').value);
  form.append('imap_password', document.getElementById('imap_password').value);
  
  const res = await fetch('send.php', { method: 'POST', body: form });
  const data = await res.json();
  if (data.status === 'success') { 
    niceAlert('Success', 'Settings Saved Successfully');
    setTimeout(() => location.reload(), 1500); 
  }
}

async function syncBounces() {
  const btn = document.getElementById('syncBtn');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<svg class="spin" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 12c0-4.4 3.6-8 8-8 3.3 0 6.2 2 7.4 4.9M22 12c0 4.4-3.6 8-8 8-3.3 0-6.2-2-7.4-4.9"/></svg> Syncing...';

  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'sync_bounces');

  try {
    const res = await fetch('send.php', { method: 'POST', body: form });
    if (!res.ok) throw new Error('Server returned ' + res.status);
    
    const data = await res.json();
    if (data.status === 'success') {
      location.reload();
    } else {
      niceAlert('Sync Error', data.message || 'The IMAP server could not be reached.');
    }
  } catch (e) {
    console.error(e);
    niceAlert('Error', 'The request timed out or the server is busy. Please try again in a moment.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
}

async function cancelCampaign() {
  if (!confirm('Cancel?')) return;
  const form = new FormData();
  form.append('_token', API_TOKEN);
  form.append('action', 'cancel');
  await fetch('send.php', { method: 'POST', body: form });
  location.reload();
}

function initApp() {
    const form = new FormData();
    form.append('_token', API_TOKEN);
    form.append('action', 'status');
    fetch('send.php', { method: 'POST', body: form })
    .then(res => res.json())
    .then(data => {
        if (data.status && ['queued', 'sending'].includes(data.status)) {
            if(document.getElementById('resultsBox')) document.getElementById('resultsBox').style.display = 'block';
            startPolling();
        }
    })
    .catch(e => {});
}
