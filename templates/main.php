<?php
/** @var \OCP\IL10N $l */
/** @var array $_ */
?>
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
                <h3 style="margin-top:0; margin-bottom:10px; font-size:1.1em;"><?php p($l->t('Security policies applied to your profile:')); ?></h3>
                <ul style="list-style:none; padding:0; margin:0; line-height:2;">
                    <li>
                        <strong><?php p($l->t('ENS Classification:')); ?></strong> 
                        <span style="background:var(--color-primary-element-light); padding:2px 8px; border-radius:10px; font-size:0.9em;">
                            <?php p($_['policies']['ens_classification']); ?>
                        </span>
                    </li>
                    <li>
                        <strong><?php p($l->t('Direct download permission:')); ?></strong>
                        <?php if ($_['userCanDownloadNative']): ?>
                            <span style="color:#16a34a; font-weight:600;"><?php p($l->t('✓ Authorized for your role')); ?></span>
                        <?php else: ?>
                            <span style="color:#dc2626; font-weight:600;"><?php p($l->t('⛔ Restricted by ENS policy [mp.info.6] (controlled preview only)')); ?></span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?php p($l->t('Viewer print permission:')); ?></strong>
                        <?php if ($_['userCanPrint']): ?>
                            <span style="color:#16a34a; font-weight:600;"><?php p($l->t('✓ Authorized')); ?></span>
                        <?php else: ?>
                            <span style="color:#dc2626; font-weight:600;"><?php p($l->t('⛔ Restricted by ENS policy [mp.info.6]')); ?></span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?php p($l->t('Viewer export permission:')); ?></strong>
                        <?php if ($_['userCanExport']): ?>
                            <span style="color:#16a34a; font-weight:600;"><?php p($l->t('✓ Authorized')); ?></span>
                        <?php else: ?>
                            <span style="color:#dc2626; font-weight:600;"><?php p($l->t('⛔ Restricted by ENS policy [mp.info.6]')); ?></span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?php p($l->t('Forensic watermark:')); ?></strong>
                        <span style="color:#0284c7; font-weight:600;"><?php p($l->t('Active in document viewer with your user and IP')); ?></span>
                    </li>
                </ul>
            </div>

            <p class="settings-hint">
                <?php p($l->t('Document access and extraction policies are managed and authorized by the Institution Management / Information Owner in accordance with Royal Decree 311/2022 (ENS).')); ?>
            </p>
        </div>
    </div>
</div>
