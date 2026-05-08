<?php
/**
 * AstraMail — System Compatibility Check
 */
$page_title = 'System Check';
require_once __DIR__ . '/core/header.php';

function check_ext($name) {
    return extension_loaded($name) 
        ? '<span style="color:#10b981;font-weight:800;">✓ Installed</span>' 
        : '<span style="color:#ef4444;font-weight:800;">✗ Missing</span>';
}

function check_write($path) {
    $full_path = __DIR__ . '/' . $path;
    if (!file_exists($full_path)) {
        return '<span style="color:#f59e0b;font-weight:800;">! Not Found</span>';
    }
    return is_writable($full_path) 
        ? '<span style="color:#10b981;font-weight:800;">✓ Writable</span>' 
        : '<span style="color:#ef4444;font-weight:800;">✗ No Permission</span>';
}
?>

<section class="tab-section active">
    <div style="margin-bottom:24px; border-bottom:1px solid var(--border); padding-bottom:12px;">
        <h1 style="font-size:20px;font-weight:800;letter-spacing:-0.5px;color:#0f172a;">System Diagnostics</h1>
        <p style="font-size:12px;color:#64748b;font-weight:500;">Verify your server environment compatibility.</p>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        
        <!-- PHP & Extensions -->
        <div class="card" style="padding:20px; border-radius:12px; border:1px solid #e2e8f0; background:#fff;">
            <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#94a3b8; letter-spacing:1px; margin-bottom:16px;">Core Environment</div>
            
            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="font-size:13px; font-weight:700; color:#475569;">PHP Version</div>
                <div style="font-size:13px; font-weight:800; color:<?= version_compare(PHP_VERSION, '7.4.0', '>=') ? '#10b981' : '#ef4444' ?>;"><?= PHP_VERSION ?></div>
            </div>

            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="font-size:13px; font-weight:700; color:#475569;">JSON Support</div>
                <div style="font-size:13px;"><?= check_ext('json') ?></div>
            </div>

            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="font-size:13px; font-weight:700; color:#475569;">OpenSSL (SMTP)</div>
                <div style="font-size:13px;"><?= check_ext('openssl') ?></div>
            </div>

            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="font-size:13px; font-weight:700; color:#475569;">IMAP (Bounce Engine)</div>
                <div style="font-size:13px;"><?= check_ext('imap') ?></div>
            </div>

            <div style="display:flex; justify-content:space-between; padding:10px 0;">
                <div style="font-size:13px; font-weight:700; color:#475569;">MBString (Encoding)</div>
                <div style="font-size:13px;"><?= check_ext('mbstring') ?></div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="card" style="padding:20px; border-radius:12px; border:1px solid #e2e8f0; background:#fff;">
            <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#94a3b8; letter-spacing:1px; margin-bottom:16px;">Storage Permissions</div>
            
            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="font-size:13px; font-weight:700; color:#475569;">/storage Directory</div>
                <div style="font-size:13px;"><?= check_write('storage') ?></div>
            </div>

            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="font-size:13px; font-weight:700; color:#475569;">/storage/archive</div>
                <div style="font-size:13px;"><?= check_write('storage/archive') ?></div>
            </div>

            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="font-size:13px; font-weight:700; color:#475569;">contacts.json</div>
                <div style="font-size:13px;"><?= check_write('storage/contacts.json') ?></div>
            </div>

            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="font-size:13px; font-weight:700; color:#475569;">settings.json</div>
                <div style="font-size:13px;"><?= check_write('storage/settings.json') ?></div>
            </div>

            <div style="display:flex; justify-content:space-between; padding:10px 0;">
                <div style="font-size:13px; font-weight:700; color:#475569;">cron.log</div>
                <div style="font-size:13px;"><?= check_write('cron.log') ?></div>
            </div>
        </div>
    </div>

    <div style="margin-top:20px; padding:16px; background:#eff6ff; border-radius:12px; border:1px solid #dbeafe; color:#1e40af; font-size:13px; line-height:1.5;">
        <strong>💡 Recommendation:</strong> If any item is red, please contact your hosting provider to enable the extension or adjust the folder permissions (set them to 755 or 777).
    </div>

</section>

<?php require_once __DIR__ . '/core/footer.php'; ?>
