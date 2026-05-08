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
    <div style="font-size:11px;font-weight:700;color:#64748b;"><?= $total ?> total</div>
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
      <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:0.8px;">Audience</div>
      <div style="font-size:11px;font-weight:700;color:#64748b;"><?= $total ?> contacts</div>
    </div>
    <div style="max-height:520px;overflow-y:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead style="background:#fafafa;position:sticky;top:0;">
          <tr>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">#</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Name</th>
            <th style="text-align:left;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Email</th>
            <th style="text-align:right;padding:9px 16px;font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($email_list)): ?>
            <tr><td colspan="4" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">No contacts yet. Add your first one above.</td></tr>
          <?php endif; ?>
          <?php foreach ($email_list as $index => $c): ?>
            <tr style="border-bottom:1px solid #f8fafc;">
              <td style="padding:9px 16px;color:#94a3b8;font-size:11px;font-weight:700;"><?= $index + 1 ?></td>
              <td style="padding:9px 16px;font-weight:700;color:#0f172a;"><?= htmlspecialchars($c['name']) ?></td>
              <td style="padding:9px 16px;color:#64748b;font-size:12px;"><?= htmlspecialchars($c['email']) ?></td>
              <td style="padding:9px 16px;text-align:right;">
                <div style="display:inline-flex;gap:6px;">
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