<?php
$page_title = 'Contacts';
require_once __DIR__ . '/core/header.php';
$contacts_file = __DIR__ . '/storage/contacts.json';
$email_list = file_exists($contacts_file) ? json_decode(file_get_contents($contacts_file), true) : [];
$total = count($email_list);
?>

<section id="tab-contacts" class="tab-section active">

  <div style="margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:12px; display:flex; align-items:flex-end; justify-content:space-between;">
    <div>
      <h1 style="font-size:20px;font-weight:800;letter-spacing:-0.5px;color:#0f172a;">Contacts</h1>
      <p style="font-size:12px;color:#64748b;font-weight:500;">Manage your audience list.</p>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
      <button onclick="syncBounces()" id="syncBtn" style="font-size:11px;font-weight:800;color:var(--accent);text-decoration:none;border:1px solid #e2e8f0;padding:6px 12px;border-radius:6px;background:#fff;cursor:pointer;display:flex;align-items:center;gap:6px;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 12c0-4.4 3.6-8 8-8 3.3 0 6.2 2 7.4 4.9M22 12c0 4.4-3.6 8-8 8-3.3 0-6.2-2-7.4-4.9"/></svg>
        Sync Bounces
      </button>
      <div style="font-size:11px;font-weight:700;color:#64748b;"><?= $total ?> total</div>
    </div>
  </div>

  <!-- Add Contact -->
  <div class="card" style="padding:16px;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:16px;">
    <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;margin-bottom:10px;">Add New Contact</div>
    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;">
      <input type="text" id="newContactName" placeholder="Full name" style="padding:9px 12px;font-size:13px;border:1px solid #e2e8f0;border-radius:8px;outline:none;">
      <input type="email" id="newContactEmail" placeholder="email@example.com" style="padding:9px 12px;font-size:13px;border:1px solid #e2e8f0;border-radius:8px;outline:none;">
      <button class="btn btn-primary" onclick="addContact()" style="padding:9px 20px;font-size:13px;border-radius:8px;white-space:nowrap;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px;vertical-align:middle;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add
      </button>
    </div>
  </div>

  <!-- Contact Table -->
  <div class="card" style="padding:0;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
    <div style="padding:10px 16px;border-bottom:1px solid #f1f5f9;background:#fafafa;display:flex;justify-content:space-between;align-items:center;">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;">Audience</div>
        <div style="position:relative;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="contactSearch" onkeyup="filterContacts()" placeholder="Search contacts..." 
            style="padding:6px 10px 6px 30px;font-size:12px;border:1px solid #e2e8f0;border-radius:6px;outline:none;width:200px;transition:all 0.2s;">
        </div>
      </div>
      <div style="font-size:11px;font-weight:700;color:#64748b;"><?= $total ?> contacts</div>
    </div>
    <div style="max-height:520px;overflow-y:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead style="background:#fafafa;position:sticky;top:0;">
          <tr>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">#</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Name</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Email</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Status</th>
            <th style="text-align:right;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($email_list)): ?>
            <tr><td colspan="5" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">No contacts yet. Add your first one above.</td></tr>
          <?php endif; ?>
          <?php foreach ($email_list as $index => $c): 
             $status = $c['status'] ?? 'active';
          ?>
            <tr style="border-bottom:1px solid #f8fafc;">
              <td style="padding:9px 16px;color:#94a3b8;font-size:11px;font-weight:700;"><?= $index + 1 ?></td>
              <td style="padding:9px 16px;font-weight:700;color:#0f172a;"><?= htmlspecialchars($c['name']) ?></td>
              <td style="padding:9px 16px;color:#64748b;font-size:12px;"><?= htmlspecialchars($c['email']) ?></td>
              <td style="padding:9px 16px;">
                <?php if ($status === 'bounced'): ?>
                  <span style="background:#fef2f2;color:#ef4444;font-size:9px;font-weight:800;padding:2px 6px;border-radius:4px;text-transform:uppercase;display:inline-flex;align-items:center;gap:4px;">
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    Bounced
                  </span>
                <?php else: ?>
                  <span style="background:#f0fdf4;color:#10b981;font-size:9px;font-weight:800;padding:2px 6px;border-radius:4px;text-transform:uppercase;display:inline-flex;align-items:center;gap:4px;">
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Active
                  </span>
                <?php endif; ?>
              </td>
              <td style="padding:9px 16px;text-align:right;">
                <div style="display:inline-flex;gap:6px;">
                  <?php if ($status === 'bounced'): ?>
                    <button onclick="reactivateContact(<?= $index ?>)" title="Reactivate"
                      style="background:none;border:1px solid #10b981;padding:5px 8px;border-radius:6px;cursor:pointer;color:#10b981;">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg>
                    </button>
                  <?php endif; ?>
                  <button onclick="editContact(<?= $index ?>)" title="Edit"
                    style="background:none;border:1px solid #e2e8f0;padding:5px 8px;border-radius:6px;cursor:pointer;color:#475569;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                  </button>
                  <button onclick="deleteContact(<?= $index ?>)" title="Delete"
                    style="background:none;border:1px solid #fecaca;padding:5px 8px;border-radius:6px;cursor:pointer;color:#ef4444;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</section>
<?php require_once __DIR__ . '/core/footer.php'; ?>