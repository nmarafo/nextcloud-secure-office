<?php
/** @var \OCP\IL10N $l */
/** @var array $_ */
$policies = $_['policies'];
$diagnostics = $_['diagnostics'];
$recentAudit = $_['recentAudit'];
$totalAudit = $_['totalAudit'];
$availableGroups = $_['availableGroups'] ?? [];
$isDelegatedView = $_['isDelegatedView'] ?? false;

$overallStatus = $diagnostics['overall_status'];
$badgeColor = match ($overallStatus) {
    'COMPLIANT' => '#28a745',
    'PARTIAL' => '#e08a00',
    default => '#dc3545',
};

// Helper inline para renderizar checkboxes de grupos
$renderGroupCheckboxes = function(string $name, array $selectedGroups, array $allGroups) use ($l) {
    ?>
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:6px; margin-bottom:12px;">
        <?php foreach ($allGroups as $g): ?>
            <?php
                $gid = $g['id'];
                $gname = $g['name'];
                $isChecked = in_array($gid, $selectedGroups, true);
            ?>
            <label style="display:inline-flex; align-items:center; gap:6px; background:<?php echo $isChecked ? '#e0f2fe' : '#f1f5f9'; ?>; border:1px solid <?php echo $isChecked ? '#0284c7' : '#cbd5e1'; ?>; padding:4px 10px; border-radius:14px; font-size:0.85em; cursor:pointer; user-select:none; transition:all 0.15s ease;">
                <input type="checkbox" name="<?php p($name); ?>[]" value="<?php p($gid); ?>" <?php if ($isChecked) print_unescaped('checked'); ?> style="margin:0; cursor:pointer;" onchange="this.parentElement.style.background=this.checked?'#e0f2fe':'#f1f5f9'; this.parentElement.style.borderColor=this.checked?'#0284c7':'#cbd5e1';">
                <span><strong><?php p($gname); ?></strong> (<code><?php p($gid); ?></code>)</span>
            </label>
        <?php endforeach; ?>
        <?php if (empty($allGroups)): ?>
            <span style="color:#64748b; font-size:0.85em; font-style:italic;"><?php p($l->t('No user groups have been created in Nextcloud yet.')); ?></span>
        <?php endif; ?>
    </div>
    <?php
};
?>

<div id="secure-office-admin" class="section" style="max-width:1100px; margin:0 auto; padding:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
        <h2>
            <span class="icon-security" style="display:inline-block; vertical-align:middle; margin-right:8px;"></span>
            <?php p($l->t('Nextcloud Secure Office — National Security Scheme (ENS RD 311/2022)')); ?>
        </h2>
        <?php if ($isDelegatedView): ?>
            <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:4px 12px; border-radius:12px; font-size:0.85em; font-weight:600;">
                <?php p($l->t('🏛️ Information Owner Panel (Institution Management)')); ?>
            </span>
        <?php endif; ?>
    </div>

    <p class="settings-hint" style="margin-bottom:20px;">
        <?php p($l->t('Modular management of security policies, forensic watermarks, role/group data loss prevention (DLP), and auditing for both Collabora Online and Nextcloud native files.')); ?>
    </p>

    <!-- Estado General de Cumplimiento -->
    <div style="background:#f8f9fa; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-bottom:24px;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <div>
                <strong style="font-size:1.1em;"><?php p($l->t('Global Compliance Diagnostics:')); ?></strong>
                <span style="display:inline-block; margin-left:10px; padding:4px 12px; border-radius:12px; color:#fff; font-weight:bold; background-color:<?php p($badgeColor); ?>;">
                    <?php p($diagnostics['overall_label']); ?>
                </span>
            </div>
            <div style="color:#666; font-size:0.9em;">
                <?php p($l->t('Evaluated: %s', [$diagnostics['timestamp']])); ?>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin-top:16px;">
            <?php foreach ($diagnostics['measures'] as $m): ?>
                <?php
                    $cardBorder = match ($m['status']) {
                        'ok' => '#28a745',
                        'warning' => '#e08a00',
                        'error' => '#dc3545',
                        default => '#0082c9',
                    };
                    $cardBg = match ($m['status']) {
                        'ok' => '#f0fff4',
                        'warning' => '#fffaf0',
                        'error' => '#fff5f5',
                        default => '#f7fafc',
                    };
                ?>
                <div style="background:<?php p($cardBg); ?>; border-left:4px solid <?php p($cardBorder); ?>; border-radius:4px; padding:10px 14px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <div style="font-weight:bold; font-size:0.95em; margin-bottom:4px; color:#2d3748;">
                        [<?php p($m['code']); ?>] <?php p($m['title']); ?>
                    </div>
                    <div style="font-size:0.85em; color:#4a5568; margin-bottom:4px;">
                        <?php p($m['details']); ?>
                    </div>
                    <?php if ($m['recommendation']): ?>
                        <div style="font-size:0.8em; color:#c53030; font-family:monospace; background:#fff; padding:4px 6px; border-radius:3px; border:1px solid #feb2b2; margin-top:4px;">
                            <?php p($m['recommendation']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Formulario de Configuración de Directivas -->
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin-bottom:24px;">
        <h3 style="margin-top:0; margin-bottom:16px;"><?php p($l->t('Security Policies, Roles, and DLP (Collabora Office & Native Files)')); ?></h3>
        <form id="secure-office-settings-form" onsubmit="return saveSecureOfficeSettings(event)">
            
            <!-- Clasificación ENS Común -->
            <div style="margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #e2e8f0;">
                <label for="sec_classification"><strong><?php p($l->t('Information ENS Classification Level:')); ?></strong></label><br>
                <input type="text" id="sec_classification" name="ens_classification" value="<?php p($policies['ens_classification']); ?>" style="width:100%; max-width:550px; margin-top:4px;" required>
                <br><span class="settings-hint"><?php p($l->t('Corporate label stamped on watermarks and audit metadata (e.g. %s, %s).', ['CONFIDENCIAL (ENS RD 311/2022)', 'DIFUSIÓN LIMITADA'])); ?></span>
            </div>

            <!-- GESTIÓN DELEGADA ENS: Responsable de la Información -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; padding:16px; margin-bottom:20px;">
                <label style="font-size:1.05em; font-weight:bold; color:#166534;">
                    <?php p($l->t('🏛️ Information Owner — Delegated ENS Management ([org.1], [org.2], [mp.ac.3])')); ?>
                </label>
                <p class="settings-hint" style="margin-bottom:8px; color:#14532d;">
                    <?php p($l->t('Allows members of selected roles or groups (e.g. %s, %s) to manage these permission policies and review institutional audit records without requiring IT superadministrator privileges:', ['direccion', 'equipo_directivo'])); ?>
                </p>
                <?php $renderGroupCheckboxes('delegated_admin_groups', $policies['delegated_admin_groups'] ?? [], $availableGroups); ?>
            </div>

            <!-- MÓDULO 1: Collabora Online -->
            <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:16px; margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="sec_collabora_protection_enabled" name="collabora_protection_enabled" value="yes" <?php if ($policies['collabora_protection_enabled']) print_unescaped('checked'); ?> style="transform:scale(1.2);">
                    <label for="sec_collabora_protection_enabled" style="font-size:1.05em; font-weight:bold; color:#1e293b;">
                        <?php p($l->t('Module 1: Interactive Office Protection (Collabora Online / CODE)')); ?>
                    </label>
                </div>
                <p class="settings-hint" style="margin-left:26px; margin-bottom:12px;">
                    <?php p($l->t('Applies dynamic forensic watermarks and DLP controls for downloading, printing, and copying with role/group granularity inside the document editor (.docx, .xlsx, .pptx, PDFs).')); ?>
                </p>

                <div id="collabora-suboptions" style="margin-left:26px; <?php if (!$policies['collabora_protection_enabled']) print_unescaped('opacity:0.6;'); ?>">
                    <!-- Marca de Agua -->
                    <p>
                        <input type="checkbox" id="sec_watermark_enabled" name="watermark_enabled" value="yes" <?php if ($policies['watermark_enabled']) print_unescaped('checked'); ?>>
                        <label for="sec_watermark_enabled"><strong><?php p($l->t('Enable Dynamic Forensic Watermark')); ?></strong> ([mp.info.6])</label>
                    </p>

                    <div style="margin-top:4px; margin-bottom:14px; margin-left:24px;">
                        <label for="sec_watermark_template" class="settings-hint"><?php p($l->t('Watermark template:')); ?></label><br>
                        <input type="text" id="sec_watermark_template" name="watermark_template" value="<?php p($policies['watermark_template']); ?>" style="width:100%; max-width:650px; font-family:monospace; font-size:0.9em; margin-top:2px;">
                        <br><span class="settings-hint"><?php p($l->t('Tokens:')); ?> <code>{classification}</code>, <code>{userDisplayName}</code>, <code>{userId}</code>, <code>{userIp}</code>, <code>{date}</code>.</span>
                    </div>

                    <!-- DLP Exportación / Descarga -->
                    <div style="margin-top:14px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <p style="margin-bottom:4px;">
                            <input type="checkbox" id="sec_dlp_disable_export" name="dlp_disable_export" value="yes" <?php if ($policies['dlp_disable_export']) print_unescaped('checked'); ?>>
                            <label for="sec_dlp_disable_export"><strong><?php p($l->t('Restrict export and download in viewer')); ?></strong> (<code>DisableExport</code>)</label>
                        </p>
                        <div style="margin-left:24px;">
                            <span class="settings-hint"><?php p($l->t('Groups permitted to export/download (administrators are always authorized; if none selected, blocked for all):')); ?></span>
                            <?php $renderGroupCheckboxes('dlp_export_allowed_groups', $policies['dlp_export_allowed_groups'] ?? [], $availableGroups); ?>
                        </div>
                    </div>

                    <!-- DLP Impresión -->
                    <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <p style="margin-bottom:4px;">
                            <input type="checkbox" id="sec_dlp_disable_print" name="dlp_disable_print" value="yes" <?php if ($policies['dlp_disable_print']) print_unescaped('checked'); ?>>
                            <label for="sec_dlp_disable_print"><strong><?php p($l->t('Restrict physical and virtual printing')); ?></strong> (<code>DisablePrint</code>)</label>
                        </p>
                        <div style="margin-left:24px;">
                            <span class="settings-hint"><?php p($l->t('Groups permitted to print:')); ?></span>
                            <?php $renderGroupCheckboxes('dlp_print_allowed_groups', $policies['dlp_print_allowed_groups'] ?? [], $availableGroups); ?>
                        </div>
                    </div>

                    <!-- DLP Portapapeles / Copia -->
                    <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <p style="margin-bottom:4px;">
                            <input type="checkbox" id="sec_dlp_disable_copy" name="dlp_disable_copy" value="yes" <?php if ($policies['dlp_disable_copy']) print_unescaped('checked'); ?>>
                            <label for="sec_dlp_disable_copy"><strong><?php p($l->t('Clipboard isolation / Restrict copying')); ?></strong> (<code>DisableCopy</code>)</label>
                        </p>
                        <div style="margin-left:24px;">
                            <span class="settings-hint"><?php p($l->t('Groups permitted to copy to clipboard:')); ?></span>
                            <?php $renderGroupCheckboxes('dlp_copy_allowed_groups', $policies['dlp_copy_allowed_groups'] ?? [], $availableGroups); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MÓDULO 2: Archivos Nativos -->
            <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:16px; margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="sec_native_protection_enabled" name="native_protection_enabled" value="yes" <?php if ($policies['native_protection_enabled']) print_unescaped('checked'); ?> style="transform:scale(1.2);">
                    <label for="sec_native_protection_enabled" style="font-size:1.05em; font-weight:bold; color:#1e293b;">
                        <?php p($l->t('Module 2: Protection and Traceability of Native Files (Nextcloud Files)')); ?>
                    </label>
                </div>
                <p class="settings-hint" style="margin-left:26px; margin-bottom:12px;">
                    <?php p($l->t('Monitors and controls direct downloads from the Files interface or WebDAV (PDF, images, archives, etc.).')); ?>
                </p>

                <div id="native-suboptions" style="margin-left:26px; <?php if (!$policies['native_protection_enabled']) print_unescaped('opacity:0.6;'); ?>">
                    <p>
                        <input type="checkbox" id="sec_native_audit_enabled" name="native_audit_enabled" value="yes" <?php if ($policies['native_audit_enabled']) print_unescaped('checked'); ?>>
                        <label for="sec_native_audit_enabled"><strong><?php p($l->t('Audit and Traceability of Native Downloads')); ?></strong> ([mp.info.2])</label>
                        <br><span class="settings-hint"><?php p($l->t('Logs every direct download or read of native files to the ENS audit database with user, IP, and timestamp.')); ?></span>
                    </p>

                    <div style="margin-top:14px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <p style="margin-bottom:4px;">
                            <input type="checkbox" id="sec_native_dlp_disable_download" name="native_dlp_disable_download" value="yes" <?php if ($policies['native_dlp_disable_download']) print_unescaped('checked'); ?>>
                            <label for="sec_native_dlp_disable_download"><strong><?php p($l->t('Strict DLP Mode: Restrict direct file downloads')); ?></strong> ([mp.info.6])</label>
                        </p>
                        <div style="margin-left:24px;">
                            <span class="settings-hint"><?php p($l->t('Groups permitted to download native files directly (administrators are always authorized):')); ?></span>
                            <?php $renderGroupCheckboxes('native_dlp_allowed_groups', $policies['native_dlp_allowed_groups'] ?? [], $availableGroups); ?>
                        </div>
                    </div>
                </div>
            </div>

            <p style="margin-top:20px;">
                <button type="submit" class="button primary" id="btn-save-settings"><?php p($l->t('Save security policies')); ?></button>
                <span id="save-status-msg" style="margin-left:12px; font-weight:bold;"></span>
            </p>
        </form>
    </div>

    <!-- Registro de Auditoría ENS -->
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
            <div>
                <h3 style="margin:0;"><?php p($l->t('Traceability and ENS Activity Log ([mp.info.2])')); ?></h3>
                <span class="settings-hint"><?php p($l->t('Total records in database:')); ?> <strong><?php p($totalAudit); ?></strong></span>
            </div>
            <div>
                <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('secure_office.settings_api.export_audit')); ?>" class="button" target="_blank" download>
                    <?php p($l->t('📥 Export audit to CSV')); ?>
                </a>
            </div>
        </div>

        <?php if (empty($recentAudit)): ?>
            <p style="color:#718096; font-style:italic;"><?php p($l->t('No activity recorded yet.')); ?></p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="grid" style="width:100%; border-collapse:collapse; font-size:0.9em;">
                    <thead>
                        <tr style="background:#edf2f7; text-align:left;">
                            <th style="padding:8px 10px;"><?php p($l->t('Date (UTC)')); ?></th>
                            <th style="padding:8px 10px;"><?php p($l->t('Type / Action')); ?></th>
                            <th style="padding:8px 10px;"><?php p($l->t('User')); ?></th>
                            <th style="padding:8px 10px;"><?php p($l->t('Origin IP')); ?></th>
                            <th style="padding:8px 10px;"><?php p($l->t('Item / Document')); ?></th>
                            <th style="padding:8px 10px;"><?php p($l->t('Classification')); ?></th>
                            <th style="padding:8px 10px; text-align:center;"><?php p($l->t('DLP Applied')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAudit as $row): ?>
                            <?php
                                $action = $row['action'] ?? 'COLLABORA_VIEW';
                                $badgeStyle = match ($action) {
                                    'NATIVE_DOWNLOAD' => 'background:#d1fae5; color:#065f46; border:1px solid #a7f3d0;',
                                    'BLOCKED_DOWNLOAD' => 'background:#fee2e2; color:#991b1b; border:1px solid #fecaca;',
                                    'POLICY_CHANGE' => 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;',
                                    default => 'background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;',
                                };
                                $actionLabel = match ($action) {
                                    'NATIVE_DOWNLOAD' => $l->t('⬇️ Native Download'),
                                    'BLOCKED_DOWNLOAD' => $l->t('⛔ Blocked Download'),
                                    'POLICY_CHANGE' => $l->t('⚙️ Policy Change'),
                                    default => $l->t('📄 Collabora Office'),
                                };
                            ?>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:8px 10px; font-family:monospace;"><?php p($row['created_at']); ?></td>
                                <td style="padding:8px 10px;">
                                    <span style="<?php p($badgeStyle); ?> padding:2px 8px; border-radius:10px; font-size:0.8em; font-weight:600; display:inline-block;">
                                        <?php p($actionLabel); ?>
                                    </span>
                                </td>
                                <td style="padding:8px 10px;"><strong><?php p($row['user_id']); ?></strong></td>
                                <td style="padding:8px 10px; font-family:monospace;"><?php p($row['remote_ip']); ?></td>
                                <td style="padding:8px 10px;" title="<?php p($row['file_path']); ?>">
                                    <?php p($row['file_name']); ?>
                                </td>
                                <td style="padding:8px 10px;">
                                    <span style="background:#e2e8f0; padding:2px 8px; border-radius:8px; font-size:0.85em;">
                                        <?php p($row['classification']); ?>
                                    </span>
                                </td>
                                <td style="padding:8px 10px; text-align:center;">
                                    <?php if ($row['dlp_export_disabled']): ?>
                                        <span title="<?php p($l->t('Export / Download Blocked')); ?>" style="cursor:help;">🚫 Export</span>
                                    <?php endif; ?>
                                    <?php if ($row['dlp_copy_disabled']): ?>
                                        <span title="<?php p($l->t('Copy Blocked')); ?>" style="cursor:help; margin-left:4px;">📋 Bloq</span>
                                    <?php endif; ?>
                                    <?php if ($row['dlp_print_disabled']): ?>
                                        <span title="<?php p($l->t('Print Blocked')); ?>" style="cursor:help; margin-left:4px;">🖨️ Bloq</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function saveSecureOfficeSettings(event) {
    event.preventDefault();
    const btn = document.getElementById('btn-save-settings');
    const msg = document.getElementById('save-status-msg');
    btn.disabled = true;
    msg.style.color = '#0082c9';
    msg.innerText = (typeof t === 'function') ? t('secure_office', 'Saving policies...') : 'Saving policies...';

    const getCheckedGroups = (name) => {
        const checkboxes = document.querySelectorAll('input[name="' + name + '[]"]:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    };

    const payload = {
        ens_classification: document.getElementById('sec_classification').value,
        delegated_admin_groups: getCheckedGroups('delegated_admin_groups'),
        collabora_protection_enabled: document.getElementById('sec_collabora_protection_enabled').checked,
        watermark_enabled: document.getElementById('sec_watermark_enabled').checked,
        watermark_template: document.getElementById('sec_watermark_template').value,
        dlp_disable_export: document.getElementById('sec_dlp_disable_export').checked,
        dlp_export_allowed_groups: getCheckedGroups('dlp_export_allowed_groups'),
        dlp_disable_copy: document.getElementById('sec_dlp_disable_copy').checked,
        dlp_copy_allowed_groups: getCheckedGroups('dlp_copy_allowed_groups'),
        dlp_disable_print: document.getElementById('sec_dlp_disable_print').checked,
        dlp_print_allowed_groups: getCheckedGroups('dlp_print_allowed_groups'),
        native_protection_enabled: document.getElementById('sec_native_protection_enabled').checked,
        native_audit_enabled: document.getElementById('sec_native_audit_enabled').checked,
        native_dlp_disable_download: document.getElementById('sec_native_dlp_disable_download').checked,
        native_dlp_allowed_groups: getCheckedGroups('native_dlp_allowed_groups')
    };

    fetch(OC.generateUrl('/apps/secure_office/api/v1/settings'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'requesttoken': OC.requestToken
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data && data.status === 'success') {
            msg.style.color = '#28a745';
            msg.innerText = '✓ ' + data.message;
            setTimeout(() => { msg.innerText = ''; }, 4000);
        } else {
            msg.style.color = '#dc3545';
            msg.innerText = (data && data.message) ? data.message : ((typeof t === 'function') ? t('secure_office', 'Error saving configuration.') : 'Error saving configuration.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        msg.style.color = '#dc3545';
        msg.innerText = (typeof t === 'function') ? t('secure_office', 'Server connection error.') : 'Server connection error.';
        console.error(err);
    });

    return false;
}
</script>