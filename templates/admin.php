<?php
/** @var array $_ */
$policies = $_['policies'];
$diagnostics = $_['diagnostics'];
$recentAudit = $_['recentAudit'];
$totalAudit = $_['totalAudit'];

$overallStatus = $diagnostics['overall_status'];
$badgeColor = match ($overallStatus) {
    'COMPLIANT' => '#28a745',
    'PARTIAL' => '#e08a00',
    default => '#dc3545',
};
?>

<div id="secure-office-admin" class="section">
    <h2>
        <span class="icon-security" style="display:inline-block; vertical-align:middle; margin-right:8px;"></span>
        Nextcloud Secure Office &mdash; Esquema Nacional de Seguridad (ENS RD 311/2022)
    </h2>
    <p class="settings-hint">
        Gestión de directivas de seguridad, marcas de agua forenses, prevención de fuga de información (DLP) y auditoría de accesos a documentos colaborativos.
    </p>

    <!-- Estado General de Cumplimiento -->
    <div style="background:#f8f9fa; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-bottom:24px;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <div>
                <strong style="font-size:1.1em;">Diagnóstico Global de Cumplimiento:</strong>
                <span style="display:inline-block; margin-left:10px; padding:4px 12px; border-radius:12px; color:#fff; font-weight:bold; background-color:<?php p($badgeColor); ?>;">
                    <?php p($diagnostics['overall_label']); ?>
                </span>
            </div>
            <div style="color:#666; font-size:0.9em;">
                Evaluado: <?php p($diagnostics['timestamp']); ?>
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
        <h3 style="margin-top:0; margin-bottom:16px;">Directivas de Seguridad y DLP (Collabora Online)</h3>

        <form id="secure-office-settings-form" onsubmit="return saveSecureOfficeSettings(event)">
            <p>
                <label for="sec_classification"><strong>Nivel de Clasificación ENS de la Información:</strong></label><br>
                <input type="text" id="sec_classification" name="ens_classification" value="<?php p($policies['ens_classification']); ?>" style="width:100%; max-width:550px; margin-top:4px;" required>
                <br><span class="settings-hint">Etiqueta mostrada en la cabecera forense y metadatos de auditoría (ej. <em>CONFIDENCIAL (ENS RD 311/2022)</em>, <em>DIFUSIÓN LIMITADA</em>).</span>
            </p>

            <p style="margin-top:16px;">
                <input type="checkbox" id="sec_watermark_enabled" name="watermark_enabled" value="yes" <?php if ($policies['watermark_enabled']) print_unescaped('checked'); ?>>
                <label for="sec_watermark_enabled"><strong>Habilitar Marca de Agua Forense Dinámica</strong> (Medida [mp.info.6])</label>
                <br><span class="settings-hint">Inserta marcas de agua translucidas en todas las páginas durante la edición/visualización en Collabora Online.</span>
            </p>

            <p style="margin-top:12px; margin-left:24px;">
                <label for="sec_watermark_template">Plantilla de la marca de agua:</label><br>
                <input type="text" id="sec_watermark_template" name="watermark_template" value="<?php p($policies['watermark_template']); ?>" style="width:100%; max-width:650px; font-family:monospace; font-size:0.9em; margin-top:4px;">
                <br><span class="settings-hint">Tokens dinámicos: <code>{classification}</code>, <code>{userDisplayName}</code>, <code>{userId}</code>, <code>{userIp}</code>, <code>{date}</code>.</span>
            </p>

            <h4 style="margin-top:20px; margin-bottom:8px;">Controles DLP (Data Loss Prevention):</h4>

            <p>
                <input type="checkbox" id="sec_dlp_disable_export" name="dlp_disable_export" value="yes" <?php if ($policies['dlp_disable_export']) print_unescaped('checked'); ?>>
                <label for="sec_dlp_disable_export"><strong>Bloquear descarga y exportación de archivos</strong> (<code>DisableExport</code>)</label>
                <br><span class="settings-hint">Impide que los usuarios descarguen copias descifradas en PDF, DOCX o formatos locales desde el visor ofimático.</span>
            </p>

            <p>
                <input type="checkbox" id="sec_dlp_disable_copy" name="dlp_disable_copy" value="yes" <?php if ($policies['dlp_disable_copy']) print_unescaped('checked'); ?>>
                <label for="sec_dlp_disable_copy"><strong>Aislamiento de portapapeles</strong> (<code>DisableCopy</code>)</label>
                <br><span class="settings-hint">Impide la copia de texto del documento al portapapeles del sistema operativo fuera de la sesión colaborativa.</span>
            </p>

            <p>
                <input type="checkbox" id="sec_dlp_disable_print" name="dlp_disable_print" value="yes" <?php if ($policies['dlp_disable_print']) print_unescaped('checked'); ?>>
                <label for="sec_dlp_disable_print"><strong>Bloquear impresión</strong> (<code>DisablePrint</code>)</label>
                <br><span class="settings-hint">Inhabilita la opción de imprimir físicamente o virtualmente los documentos clasificados.</span>
            </p>

            <p style="margin-top:20px;">
                <button type="submit" class="button primary" id="btn-save-settings">Guardar directivas de seguridad</button>
                <span id="save-status-msg" style="margin-left:12px; font-weight:bold;"></span>
            </p>
        </form>
    </div>

    <!-- Registro de Auditoría ENS -->
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
            <div>
                <h3 style="margin:0;">Trazabilidad y Registro de Actividad ENS ([mp.info.2])</h3>
                <span class="settings-hint">Total de accesos registrados en base de datos: <strong><?php p($totalAudit); ?></strong></span>
            </div>
            <div>
                <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('secure_office.settings_api.export_audit')); ?>" class="button" target="_blank" download>
                    📥 Exportar auditoría a CSV
                </a>
            </div>
        </div>

        <?php if (empty($recentAudit)): ?>
            <p style="color:#718096; font-style:italic;">No hay accesos a documentos registrados aún.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="grid" style="width:100%; border-collapse:collapse; font-size:0.9em;">
                    <thead>
                        <tr style="background:#edf2f7; text-align:left;">
                            <th style="padding:8px 10px;">Fecha (UTC)</th>
                            <th style="padding:8px 10px;">Usuario</th>
                            <th style="padding:8px 10px;">IP Origen</th>
                            <th style="padding:8px 10px;">Documento</th>
                            <th style="padding:8px 10px;">Clasificación</th>
                            <th style="padding:8px 10px; text-align:center;">DLP Aplicado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAudit as $row): ?>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:8px 10px; font-family:monospace;"><?php p($row['created_at']); ?></td>
                                <td style="padding:8px 10px;"><strong><?php p($row['user_id']); ?></strong></td>
                                <td style="padding:8px 10px; font-family:monospace;"><?php p($row['remote_ip']); ?></td>
                                <td style="padding:8px 10px;" title="<?php p($row['file_path']); ?>">
                                    📄 <?php p($row['file_name']); ?>
                                </td>
                                <td style="padding:8px 10px;">
                                    <span style="background:#e2e8f0; padding:2px 8px; border-radius:8px; font-size:0.85em;">
                                        <?php p($row['classification']); ?>
                                    </span>
                                </td>
                                <td style="padding:8px 10px; text-align:center;">
                                    <?php if ($row['dlp_export_disabled']): ?>
                                        <span title="Exportación Bloqueada" style="cursor:help;">🚫 Export</span>
                                    <?php endif; ?>
                                    <?php if ($row['dlp_copy_disabled']): ?>
                                        <span title="Copiado Bloqueado" style="cursor:help; margin-left:4px;">📋 Bloq</span>
                                    <?php endif; ?>
                                    <?php if ($row['dlp_print_disabled']): ?>
                                        <span title="Impresión Bloqueada" style="cursor:help; margin-left:4px;">🖨️ Bloq</span>
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
    msg.innerText = 'Guardando directivas...';

    const payload = {
        ens_classification: document.getElementById('sec_classification').value,
        watermark_enabled: document.getElementById('sec_watermark_enabled').checked,
        watermark_template: document.getElementById('sec_watermark_template').value,
        dlp_disable_export: document.getElementById('sec_dlp_disable_export').checked,
        dlp_disable_copy: document.getElementById('sec_dlp_disable_copy').checked,
        dlp_disable_print: document.getElementById('sec_dlp_disable_print').checked
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
            msg.innerText = 'Error al guardar configuración.';
        }
    })
    .catch(err => {
        btn.disabled = false;
        msg.style.color = '#dc3545';
        msg.innerText = 'Error de conexión con el servidor.';
        console.error(err);
    });

    return false;
}
</script>