<?php
/** @var \OCP\IL10N $l */
/** @var array $_ */
$policies = $_['policies'];
$diagnostics = $_['diagnostics'];
$recentAudit = $_['recentAudit'];
$totalAudit = $_['totalAudit'];
$availableGroups = $_['availableGroups'] ?? [];
$fileRules = $_['fileRules'] ?? [];
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
<style>
    #content.app-secure_office, #content {
        overflow-y: auto !important;
        position: relative !important;
        height: auto !important;
        min-height: calc(100vh - 50px) !important;
    }
    body, html {
        overflow-y: auto !important;
    }
</style>

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

    <!-- Panel de Configuración Modular -->
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin-bottom:24px;">
        <h3 style="margin-top:0; margin-bottom:16px; border-bottom:1px solid #edf2f7; padding-bottom:8px;">
            <?php p($l->t('Security and DLP Policy Configuration')); ?>
        </h3>

        <form id="secure-office-settings-form" onsubmit="return saveSecureOfficeSettings(event);">
            <!-- Clasificación Global ENS -->
            <div style="margin-bottom:20px; background:#f8fafc; border-left:4px solid #0284c7; padding:12px 16px; border-radius:0 6px 6px 0;">
                <label for="sec_classification" style="font-weight:bold; display:block; margin-bottom:4px;">
                    <?php p($l->t('Institutional ENS Classification:')); ?>
                </label>
                <input type="text" id="sec_classification" name="ens_classification" value="<?php p($policies['ens_classification']); ?>" style="width:100%; max-width:450px;" required>
                <span class="settings-hint" style="display:block; margin-top:4px;">
                    <?php p($l->t('Text displayed on the forensic watermark and compliance logs (e.g. "CONFIDENTIAL (ENS RD 311/2022)").')); ?>
                </span>
            </div>

            <!-- Gestión Delegada (Responsable de la Información) -->
            <div style="margin-bottom:24px; background:#f0fdf4; border-left:4px solid #16a34a; padding:12px 16px; border-radius:0 6px 6px 0;">
                <div style="font-weight:bold; margin-bottom:4px; color:#166534;">
                    🏛️ <?php p($l->t('Delegated Governance — Information Owner ([org.1], [org.2])')); ?>
                </div>
                <span class="settings-hint" style="display:block; margin-bottom:8px;">
                    <?php p($l->t('Select user groups that represent the Information Owner (e.g. School Direction, Department Heads). Members can manage these DLP policies without needing full IT superadmin privileges:')); ?>
                </span>
                <?php $renderGroupCheckboxes('delegated_admin_groups', $policies['delegated_admin_groups'] ?? [], $availableGroups); ?>
            </div>

            <!-- Módulo 1: Collabora Online -->
            <div style="margin-bottom:24px; border:1px solid #e2e8f0; border-radius:6px; padding:16px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                    <input type="checkbox" id="sec_collabora_protection_enabled" name="collabora_protection_enabled" value="yes" <?php if ($policies['collabora_protection_enabled']) print_unescaped('checked'); ?> onchange="document.getElementById('collabora-suboptions').style.opacity = this.checked ? '1' : '0.4';">
                    <label for="sec_collabora_protection_enabled" style="font-size:1.05em; font-weight:bold; color:#1e293b;">
                        <?php p($l->t('Module 1: Protection in Collaborative Office (Collabora Online / CODE)')); ?>
                    </label>
                </div>
                <p class="settings-hint" style="margin-left:26px; margin-bottom:12px;">
                    <?php p($l->t('Enforces forensic watermarking and role-based exfiltration control during office editing sessions.')); ?>
                </p>

                <div id="collabora-suboptions" style="margin-left:26px; <?php if (!$policies['collabora_protection_enabled']) print_unescaped('opacity:0.4;'); ?>">
                    <!-- Marca de Agua Forense -->
                    <div style="margin-bottom:14px;">
                        <input type="checkbox" id="sec_watermark_enabled" name="watermark_enabled" value="yes" <?php if ($policies['watermark_enabled']) print_unescaped('checked'); ?>>
                        <label for="sec_watermark_enabled"><strong><?php p($l->t('Dynamic Forensic Watermark')); ?></strong> ([mp.info.6])</label>
                        <br>
                        <span class="settings-hint"><?php p($l->t('Superimposes indelible user identity and IP information diagonally across the document canvas.')); ?></span>
                        <div style="margin-top:6px;">
                            <input type="text" id="sec_watermark_template" name="watermark_template" value="<?php p($policies['watermark_template']); ?>" style="width:100%; max-width:600px; font-family:monospace; font-size:0.85em;">
                            <span class="settings-hint" style="display:block; font-size:0.8em; margin-top:2px;">
                                <?php p($l->t('Available tokens: {classification}, {userId}, {userDisplayName}, {userIp}, {date}')); ?>
                            </span>
                        </div>
                    </div>

                    <!-- DLP: Restricción de Descarga y Exportación -->
                    <div style="margin-bottom:14px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <input type="checkbox" id="sec_dlp_disable_export" name="dlp_disable_export" value="yes" <?php if ($policies['dlp_disable_export']) print_unescaped('checked'); ?>>
                        <label for="sec_dlp_disable_export"><strong><?php p($l->t('DLP: Prohibit Document Export and Download')); ?></strong> ([mp.info.6])</label>
                        <br>
                        <span class="settings-hint"><?php p($l->t('Removes "Download as", "Export to PDF", and file saving actions in the editor for unauthorized groups.')); ?></span>
                        
                        <div style="margin-top:6px; margin-left:20px;">
                            <span class="settings-hint"><?php p($l->t('Groups permitted to export/download documents:')); ?></span>
                            <?php $renderGroupCheckboxes('dlp_export_allowed_groups', $policies['dlp_export_allowed_groups'] ?? [], $availableGroups); ?>
                        </div>
                    </div>

                    <!-- DLP: Restricción de Copiado al Portapapeles -->
                    <div style="margin-bottom:14px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <input type="checkbox" id="sec_dlp_disable_copy" name="dlp_disable_copy" value="yes" <?php if ($policies['dlp_disable_copy']) print_unescaped('checked'); ?>>
                        <label for="sec_dlp_disable_copy"><strong><?php p($l->t('DLP: Prohibit Clipboard Copy/Paste Outside Editor')); ?></strong> ([mp.info.6])</label>
                        <br>
                        <span class="settings-hint"><?php p($l->t('Prevents copying sensitive document content out of the office canvas to local clipboards.')); ?></span>

                        <div style="margin-top:6px; margin-left:20px;">
                            <span class="settings-hint"><?php p($l->t('Groups permitted to copy text from documents:')); ?></span>
                            <?php $renderGroupCheckboxes('dlp_copy_allowed_groups', $policies['dlp_copy_allowed_groups'] ?? [], $availableGroups); ?>
                        </div>
                    </div>

                    <!-- DLP: Restricción de Impresión -->
                    <div style="margin-bottom:6px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <input type="checkbox" id="sec_dlp_disable_print" name="dlp_disable_print" value="yes" <?php if ($policies['dlp_disable_print']) print_unescaped('checked'); ?>>
                        <label for="sec_dlp_disable_print"><strong><?php p($l->t('DLP: Prohibit Printing')); ?></strong> ([mp.info.6])</label>
                        <br>
                        <span class="settings-hint"><?php p($l->t('Disables physical printing and print-to-PDF functions within the office suite for unauthorized groups.')); ?></span>

                        <div style="margin-top:6px; margin-left:20px;">
                            <span class="settings-hint"><?php p($l->t('Groups permitted to print documents:')); ?></span>
                            <?php $renderGroupCheckboxes('dlp_print_allowed_groups', $policies['dlp_print_allowed_groups'] ?? [], $availableGroups); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Módulo 2: Archivos Nativos -->
            <div style="margin-bottom:20px; border:1px solid #e2e8f0; border-radius:6px; padding:16px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                    <input type="checkbox" id="sec_native_protection_enabled" name="native_protection_enabled" value="yes" <?php if ($policies['native_protection_enabled']) print_unescaped('checked'); ?> onchange="document.getElementById('native-suboptions').style.opacity = this.checked ? '1' : '0.4';">
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

    <!-- Reglas Granulares por Archivo y Combinación Archivo x Usuario -->
    <div id="sec-granular-rules-section" style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:12px;">
            <div>
                <h3 style="margin:0; font-size:1.2em; display:flex; align-items:center; gap:8px;">
                    <span>🛡️</span>
                    <span><?php p($l->t('Granular Permissions by File and File × User Matrix ([mp.info.6])')); ?></span>
                </h3>
                <span class="settings-hint">
                    <?php p($l->t('Allows defining custom DLP rules (export, print, copy, download) for specific files, or establishing direct exceptions for particular users/groups. These rules take precedence over general center policies.')); ?>
                </span>
            </div>
            <div>
                <span style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:4px 10px; border-radius:12px; font-size:0.85em; font-weight:600;">
                    <?php p($l->t('Rules in effect:')); ?> <strong id="file-rules-count"><?php echo count($fileRules); ?></strong>
                </span>
            </div>
        </div>

        <!-- Formulario de Creación / Asignación de Regla -->
        <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:16px; margin-bottom:20px;">
            <h4 style="margin-top:0; margin-bottom:12px; font-size:1em; color:#1e293b;">
                <?php p($l->t('➕ Define or update rule for specific file:')); ?>
            </h4>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin-bottom:12px;">
                <!-- Selección de Archivo -->
                <div>
                    <label for="sec_rule_file_search" style="display:block; font-weight:600; font-size:0.85em; margin-bottom:4px;">
                        <?php p($l->t('Search file or enter ID:')); ?>
                    </label>
                    <div style="position:relative;">
                        <input type="text" id="sec_rule_file_search" placeholder="<?php p($l->t('Type file name to search...')); ?>" style="width:100%; box-sizing:border-box;">
                        <div id="sec_file_search_results" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #0284c7; border-radius:4px; max-height:180px; overflow-y:auto; z-index:100; box-shadow:0 4px 6px rgba(0,0,0,0.1);"></div>
                    </div>
                    <div style="display:flex; gap:6px; margin-top:4px;">
                        <input type="number" id="sec_rule_file_id" placeholder="ID" style="width:75px;" required>
                        <input type="text" id="sec_rule_file_name" placeholder="<?php p($l->t('File Name')); ?>" style="flex:1;" readonly>
                        <input type="hidden" id="sec_rule_file_path">
                    </div>
                </div>

                <!-- Selección de Destinatario / Ámbito -->
                <div>
                    <label for="sec_rule_target_type" style="display:block; font-weight:600; font-size:0.85em; margin-bottom:4px;">
                        <?php p($l->t('Recipient / Scope:')); ?>
                    </label>
                    <select id="sec_rule_target_type" style="width:100%;">
                        <option value="user"><?php p($l->t('👤 Specific User')); ?></option>
                        <option value="group"><?php p($l->t('👥 Specific Group')); ?></option>
                        <option value="all"><?php p($l->t('🌐 All Users on this file (*)')); ?></option>
                    </select>

                    <div id="sec_target_user_box" style="margin-top:4px;">
                        <input type="text" id="sec_rule_user_id" placeholder="<?php p($l->t('User UID (e.g. jdoe, teacher1)')); ?>" style="width:100%; box-sizing:border-box;">
                    </div>
                    <div id="sec_target_group_box" style="margin-top:4px; display:none;">
                        <select id="sec_rule_group_id" style="width:100%;">
                            <?php foreach ($availableGroups as $grp): ?>
                                <option value="<?php p($grp['id']); ?>"><?php p($grp['name']); ?> (<?php p($grp['id']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Clasificación ENS específica (opcional) -->
                <div>
                    <label for="sec_rule_classification" style="display:block; font-weight:600; font-size:0.85em; margin-bottom:4px;">
                        <?php p($l->t('Specific Classification (optional):')); ?>
                    </label>
                    <input type="text" id="sec_rule_classification" placeholder="<?php p($l->t('e.g. CONFIDENCIAL - EVALUACIONES')); ?>" style="width:100%; box-sizing:border-box;">
                    <span class="settings-hint" style="font-size:0.75em;"><?php p($l->t('Leave empty to inherit general classification')); ?></span>
                </div>
            </div>

            <!-- Directivas DLP Granulares -->
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:4px; padding:10px 12px; margin-bottom:12px;">
                <div style="font-size:0.85em; font-weight:600; color:#334155; margin-bottom:8px;">
                    <?php p($l->t('DLP Directives for this combination:')); ?>
                </div>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap:10px;">
                    <div>
                        <label style="font-size:0.8em; font-weight:500; display:block; margin-bottom:2px;">
                            <?php p($l->t('Export / Download Collabora:')); ?>
                        </label>
                        <select id="sec_rule_dlp_export" style="width:100%; font-size:0.85em;">
                            <option value="0"><?php p($l->t('0 - Inherit policy')); ?></option>
                            <option value="1"><?php p($l->t('✓ Allow (+1)')); ?></option>
                            <option value="-1"><?php p($l->t('⛔ Block (-1)')); ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:0.8em; font-weight:500; display:block; margin-bottom:2px;">
                            <?php p($l->t('Print:')); ?>
                        </label>
                        <select id="sec_rule_dlp_print" style="width:100%; font-size:0.85em;">
                            <option value="0"><?php p($l->t('0 - Inherit policy')); ?></option>
                            <option value="1"><?php p($l->t('✓ Allow (+1)')); ?></option>
                            <option value="-1"><?php p($l->t('⛔ Block (-1)')); ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:0.8em; font-weight:500; display:block; margin-bottom:2px;">
                            <?php p($l->t('Copy to Clipboard:')); ?>
                        </label>
                        <select id="sec_rule_dlp_copy" style="width:100%; font-size:0.85em;">
                            <option value="0"><?php p($l->t('0 - Inherit policy')); ?></option>
                            <option value="1"><?php p($l->t('✓ Allow (+1)')); ?></option>
                            <option value="-1"><?php p($l->t('⛔ Block (-1)')); ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:0.8em; font-weight:500; display:block; margin-bottom:2px;">
                            <?php p($l->t('Native Download (Files):')); ?>
                        </label>
                        <select id="sec_rule_dlp_download" style="width:100%; font-size:0.85em;">
                            <option value="0"><?php p($l->t('0 - Inherit policy')); ?></option>
                            <option value="1"><?php p($l->t('✓ Allow (+1)')); ?></option>
                            <option value="-1"><?php p($l->t('⛔ Block (-1)')); ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" class="button primary" id="btn-save-file-rule" onclick="saveGranularFileRule()">
                    <?php p($l->t('💾 Save File Rule')); ?>
                </button>
                <span id="file-rule-status-msg" style="font-size:0.85em; font-weight:bold;"></span>
            </div>
        </div>

        <!-- Tabla de Reglas Configuradas -->
        <div style="overflow-x:auto;">
            <table class="grid" style="width:100%; border-collapse:collapse; font-size:0.88em;" id="table-file-rules">
                <thead>
                    <tr style="background:#f1f5f9; text-align:left;">
                        <th style="padding:8px 10px;"><?php p($l->t('File ID & Name')); ?></th>
                        <th style="padding:8px 10px;"><?php p($l->t('Scope / Recipient')); ?></th>
                        <th style="padding:8px 10px; text-align:center;"><?php p($l->t('Export')); ?></th>
                        <th style="padding:8px 10px; text-align:center;"><?php p($l->t('Print')); ?></th>
                        <th style="padding:8px 10px; text-align:center;"><?php p($l->t('Copy')); ?></th>
                        <th style="padding:8px 10px; text-align:center;"><?php p($l->t('Download')); ?></th>
                        <th style="padding:8px 10px;"><?php p($l->t('Classification')); ?></th>
                        <th style="padding:8px 10px; text-align:center;"><?php p($l->t('Actions')); ?></th>
                    </tr>
                </thead>
                <tbody id="file-rules-tbody">
                    <?php if (empty($fileRules)): ?>
                        <tr id="no-file-rules-row">
                            <td colspan="8" style="padding:12px; text-align:center; color:#64748b; font-style:italic;">
                                <?php p($l->t('No specific file or user rules configured yet. Global policies apply.')); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fileRules as $r): ?>
                            <?php
                                $rTargetType = $r['target_type'];
                                $rTargetId = $r['target_id'];
                                $badgeTarget = match ($rTargetType) {
                                    'user' => '👤 ' . $rTargetId,
                                    'group' => '👥 ' . $rTargetId,
                                    default => '🌐 ' . $l->t('All (*)'),
                                };
                                $renderPermBadge = function($val) use ($l) {
                                    return match ((int)$val) {
                                        1 => '<span style="color:#16a34a; font-weight:bold;">✓ ' . $l->t('Allow') . '</span>',
                                        -1 => '<span style="color:#dc2626; font-weight:bold;">⛔ ' . $l->t('Block') . '</span>',
                                        default => '<span style="color:#64748b;">' . $l->t('Inherit') . '</span>',
                                    };
                                };
                            ?>
                            <tr style="border-bottom:1px solid #e2e8f0;" id="file-rule-row-<?php p($r['id']); ?>">
                                <td style="padding:8px 10px;">
                                    <strong>#<?php p($r['file_id']); ?></strong> <?php p($r['file_name'] ?? 'document'); ?>
                                </td>
                                <td style="padding:8px 10px;">
                                    <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; font-size:0.85em; font-weight:600;">
                                        <?php p($badgeTarget); ?>
                                    </span>
                                </td>
                                <td style="padding:8px 10px; text-align:center;"><?php print_unescaped($renderPermBadge($r['dlp_export'])); ?></td>
                                <td style="padding:8px 10px; text-align:center;"><?php print_unescaped($renderPermBadge($r['dlp_print'])); ?></td>
                                <td style="padding:8px 10px; text-align:center;"><?php print_unescaped($renderPermBadge($r['dlp_copy'])); ?></td>
                                <td style="padding:8px 10px; text-align:center;"><?php print_unescaped($renderPermBadge($r['dlp_download'])); ?></td>
                                <td style="padding:8px 10px;">
                                    <span style="font-size:0.85em; color:#475569;">
                                        <?php p($r['classification'] ?: $l->t('(Inherited)')); ?>
                                    </span>
                                </td>
                                <td style="padding:8px 10px; text-align:center;">
                                    <button type="button" class="button" style="color:#dc2626; padding:2px 6px; font-size:0.8em;" onclick="deleteGranularFileRule(<?php p($r['id']); ?>)">
                                        🗑️ <?php p($l->t('Delete')); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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

<?php
// Note: Client logic is cleanly registered via Util::addScript('secure_office', 'admin') for strict CSP compliance.
?>