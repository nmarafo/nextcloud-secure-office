<div id="app">
    <div id="app-content" style="padding: 30px; max-width: 850px; margin: 0 auto;">
        <div class="card" style="padding: 28px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); background: var(--color-main-background); border: 1px solid var(--color-border);">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                <span class="icon-security" style="font-size:2em; display:inline-block;"></span>
                <h1 style="margin:0; font-size:1.6em; color: var(--color-primary-element);"><?php p($_['appName']); ?></h1>
            </div>
            
            <p style="font-size: 1.05em; line-height: 1.6; color: var(--color-text-maxcontrast);">
                <?php p($_['message']); ?>
            </p>

            <div style="background:var(--color-background-hover); border-radius:6px; padding:16px; margin:20px 0;">
                <h3 style="margin-top:0; margin-bottom:10px; font-size:1.1em;">Directivas de Seguridad aplicadas a su perfil:</h3>
                <ul style="list-style:none; padding:0; margin:0; line-height:2;">
                    <li>
                        <strong>Clasificación ENS:</strong> 
                        <span style="background:var(--color-primary-element-light); padding:2px 8px; border-radius:10px; font-size:0.9em;">
                            <?php p($_['policies']['ens_classification']); ?>
                        </span>
                    </li>
                    <li>
                        <strong>Permiso de descarga directa:</strong>
                        <?php if ($_['userCanDownloadNative']): ?>
                            <span style="color:#16a34a; font-weight:600;">✓ Autorizado para su rol</span>
                        <?php else: ?>
                            <span style="color:#dc2626; font-weight:600;">⛔ Restringido por política ENS [mp.info.6] (solo visualización controlada)</span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong>Permiso de impresión en visor:</strong>
                        <?php if ($_['userCanPrint']): ?>
                            <span style="color:#16a34a; font-weight:600;">✓ Autorizado</span>
                        <?php else: ?>
                            <span style="color:#dc2626; font-weight:600;">⛔ Restringido por política ENS [mp.info.6]</span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong>Permiso de exportación en visor:</strong>
                        <?php if ($_['userCanExport']): ?>
                            <span style="color:#16a34a; font-weight:600;">✓ Autorizado</span>
                        <?php else: ?>
                            <span style="color:#dc2626; font-weight:600;">⛔ Restringido por política ENS [mp.info.6]</span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong>Marca de agua forense:</strong>
                        <span style="color:#0284c7; font-weight:600;">Activa en visor de documentos con su usuario e IP</span>
                    </li>
                </ul>
            </div>

            <p class="settings-hint">
                Las políticas de acceso y extracción de documentos son gestionadas y autorizadas por la Dirección del centro / Responsable de la Información conforme al Real Decreto 311/2022 (ENS).
            </p>
        </div>
    </div>
</div>
