<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Settings;

use OCA\SecureOffice\Service\AuditService;
use OCA\SecureOffice\Service\EnsDiagnosticService;
use OCA\SecureOffice\Service\FileSecurityService;
use OCA\SecureOffice\Service\SecurityConfigService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings {
    public function __construct(
        private SecurityConfigService $configService,
        private FileSecurityService $fileSecurityService,
        private EnsDiagnosticService $diagnosticService,
        private AuditService $auditService,
        private IGroupManager $groupManager,
    ) {
    }

    #[\Override]
    public function getForm(): TemplateResponse {
        Util::addTranslations('secure_office');
        $policies = $this->configService->getAllPolicies();
        $diagnostics = $this->diagnosticService->runDiagnostics();
        $recentAudit = $this->auditService->getRecentAuditEntries(15);
        $totalAudit = $this->auditService->countEntries();
        $fileRules = $this->fileSecurityService->getAllRules(50);

        $groups = $this->groupManager->search('');
        $availableGroups = [];
        foreach ($groups as $g) {
            $availableGroups[] = [
                'id' => $g->getGID(),
                'name' => $g->getDisplayName(),
            ];
        }

        return new TemplateResponse(
            'secure_office',
            'admin',
            [
                'policies' => $policies,
                'diagnostics' => $diagnostics,
                'recentAudit' => $recentAudit,
                'totalAudit' => $totalAudit,
                'availableGroups' => $availableGroups,
                'fileRules' => $fileRules,
            ],
            'blank'
        );
    }

    #[\Override]
    public function getSection(): string {
        return 'security';
    }

    #[\Override]
    public function getPriority(): int {
        return 20;
    }
}