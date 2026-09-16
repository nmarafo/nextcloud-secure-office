<div id="app">
    <div id="app-content" style="padding: 30px; max-width: 900px; margin: 0 auto;">
        <div class="card" style="padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: var(--color-main-background);">
            <h1 style="color: var(--color-primary-element);"><?php p($_['appName']); ?></h1>
            <p style="font-size: 1.1em; line-height: 1.6;"><?php p($_['message']); ?></p>
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--color-border);" />
            <p><strong>Estado API:</strong> <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('secure_office.page.status')); ?>" target="_blank">Consultar endpoint /api/v1/status</a></p>
        </div>
    </div>
</div>
