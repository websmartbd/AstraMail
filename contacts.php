<?php
$page_title = 'Audience Management';
require_once __DIR__ . '/core/header.php';

// Load Contacts
$contacts_file = __DIR__ . '/storage/contacts.json';
$email_list = file_exists($contacts_file) ? json_decode(file_get_contents($contacts_file), true) : [];
?>

<section id="tab-contacts" class="tab-section active">
  <div class="header">
    <h1>Contacts</h1>
    <p>Manage your audience list.</p>
  </div>

  <div class="card">
    <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px;">
      <input type="text" id="newContactName" placeholder="Name">
      <input type="email" id="newContactEmail" placeholder="Email">
      <button class="btn btn-primary" onclick="addContact()">Add</button>
    </div>
  </div>

  <div class="card" style="padding:0; overflow:hidden;">
    <div style="max-height:500px; overflow-y:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead style="background:#f8fafc; position:sticky; top:0;">
          <tr>
            <th style="text-align:left; padding:12px 20px; border-bottom:1px solid var(--border);">Name</th>
            <th style="text-align:left; padding:12px 20px; border-bottom:1px solid var(--border);">Email</th>
            <th style="text-align:right; padding:12px 20px; border-bottom:1px solid var(--border);">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($email_list)): ?>
            <tr>
              <td colspan="3" style="padding:40px; text-align:center; color:var(--text-muted);">No contacts found.</td>
            </tr>
          <?php endif; ?>
          <?php foreach ($email_list as $index => $c): ?>
            <tr>
              <td style="padding:12px 20px; border-bottom:1px solid var(--border); font-weight:600;">
                <?= htmlspecialchars($c['name']) ?>
              </td>
              <td style="padding:12px 20px; border-bottom:1px solid var(--border); color:var(--text-muted);">
                <?= htmlspecialchars($c['email']) ?>
              </td>
              <td style="padding:12px 20px; border-bottom:1px solid var(--border); text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:8px;">
                  <button class="btn btn-outline" style="padding:6px; min-width:32px;"
                    onclick="editContact(<?= $index ?>)" title="Edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" />
                    </svg>
                  </button>
                  <button class="btn btn-outline"
                    style="padding:6px; min-width:32px; color:var(--danger); border-color:rgba(239, 68, 68, 0.1);"
                    onclick="deleteContact(<?= $index ?>)" title="Delete">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <path d="M3 6h18" />
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                      <line x1="10" y1="11" x2="10" y2="17" />
                      <line x1="14" y1="11" x2="14" y2="17" />
                    </svg>
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