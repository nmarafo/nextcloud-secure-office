<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Controller;

use OCA\SecureOffice\Service\AuditService;
use OCA\SecureOffice\Service\EnsDiagnosticService;
use OCA\SecureOffice\Service\FileSecurityService;
use OCA\SecureOffice\Service\SecurityConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private SecurityConfigService $configService,
        private FileSecurityService $fileSecurityService,
        private EnsDiagnosticService $diagnosticService,
        private AuditService $auditService,
        private IGroupManager $groupManager,
        private IUserSession $userSession,
        private IL10N $l10n,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        Util::addTranslations('secure_office');
        Util::addScript('secure_office', 'admin');
        $user = $this->userSession->getUser();
        $userId = $user ? $user->getUID() : 'anonymous';
        $canManage = $this->configService->canUserManagePolicies($userId);

        if ($canManage) {
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
                    'isDelegatedView' => true,
                ]
            );
        }

        return new TemplateResponse('secure_office', 'main', [
            'appName' => 'Nextcloud Secure Office',
            'message' => $this->l10n->t('Office Protection and DLP System compliant with the National Security Scheme (ENS RD 311/2022).'),
            'policies' => $this->configService->getAllPolicies(),
            'userCanExport' => $this->configService->isUserAllowedToExport($userId),
            'userCanPrint' => $this->configService->isUserAllowedToPrint($userId),
            'userCanCopy' => $this->configService->isUserAllowedToCopy($userId),
            'userCanDownloadNative' => $this->configService->isUserAllowedToDownloadNative($userId),
        ]);
    }

    #[PublicPage]
    #[NoCSRFRequired]
    public function status(): DataResponse {
        return new DataResponse([
            'app' => 'secure_office',
            'status' => 'ok',
            'version' => '0.5.0',
            'compliance' => 'ENS RD 311/2022',
            'policies' => $this->configService->getAllPolicies(),
        ]);
    }
}
