<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Controller;

use OCA\SecureOffice\Service\AuditService;
use OCA\SecureOffice\Service\EnsDiagnosticService;
use OCA\SecureOffice\Service\SecurityConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsApiController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private SecurityConfigService $configService,
        private EnsDiagnosticService $diagnosticService,
        private AuditService $auditService,
        private IConfig $config,
        private IGroupManager $groupManager,
        private IUserSession $userSession,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function updateSettings(): DataResponse {
        $user = $this->userSession->getUser();
        $userId = $user ? $user->getUID() : 'anonymous';

        if (!$this->configService->canUserManagePolicies($userId)) {
            return new DataResponse([
                'status' => 'error',
                'message' => 'No dispone de autorización para gestionar directivas de seguridad ENS.',
            ], Http::STATUS_FORBIDDEN);
        }

        // --- Módulo Collabora Online ---
        $collaboraProtection = $this->resolveParam('collabora_protection_enabled');
        if ($collaboraProtection !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'collabora_protection_enabled',
                filter_var($collaboraProtection, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $watermarkEnabled = $this->resolveParam('watermark_enabled');
        if ($watermarkEnabled !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'watermark_enabled',
                filter_var($watermarkEnabled, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $watermarkTemplate = $this->resolveParam('watermark_template');
        if ($watermarkTemplate !== null && is_string($watermarkTemplate)) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'watermark_template',
                trim($watermarkTemplate)
            );
        }

        $dlpExport = $this->resolveParam('dlp_disable_export');
        if ($dlpExport !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_disable_export',
                filter_var($dlpExport, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $exportGroups = $this->formatGroupList($this->resolveParam('dlp_export_allowed_groups'));
        if ($exportGroups !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_export_allowed_groups',
                $exportGroups
            );
        }

        $dlpCopy = $this->resolveParam('dlp_disable_copy');
        if ($dlpCopy !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_disable_copy',
                filter_var($dlpCopy, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $copyGroups = $this->formatGroupList($this->resolveParam('dlp_copy_allowed_groups'));
        if ($copyGroups !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_copy_allowed_groups',
                $copyGroups
            );
        }

        $dlpPrint = $this->resolveParam('dlp_disable_print');
        if ($dlpPrint !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_disable_print',
                filter_var($dlpPrint, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $printGroups = $this->formatGroupList($this->resolveParam('dlp_print_allowed_groups'));
        if ($printGroups !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_print_allowed_groups',
                $printGroups
            );
        }

        // --- Módulo Archivos Nativos ---
        $nativeProtection = $this->resolveParam('native_protection_enabled');
        if ($nativeProtection !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'native_protection_enabled',
                filter_var($nativeProtection, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $nativeAudit = $this->resolveParam('native_audit_enabled');
        if ($nativeAudit !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'native_audit_enabled',
                filter_var($nativeAudit, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $nativeDlp = $this->resolveParam('native_dlp_disable_download');
        if ($nativeDlp !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'native_dlp_disable_download',
                filter_var($nativeDlp, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $nativeGroups = $this->formatGroupList($this->resolveParam('native_dlp_allowed_groups'));
        if ($nativeGroups !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'native_dlp_allowed_groups',
                $nativeGroups
            );
        }

        // --- Gestión Delegada ENS / Responsable de la Información ---
        $delegatedGroups = $this->formatGroupList($this->resolveParam('delegated_admin_groups'));
        if ($delegatedGroups !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'delegated_admin_groups',
                $delegatedGroups
            );
        }

        // --- Clasificación Global ---
        $ensClassification = $this->resolveParam('ens_classification');
        if ($ensClassification !== null && is_string($ensClassification)) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'ens_classification',
                trim($ensClassification)
            );
        }

        // Registrar en auditoría ENS el cambio formal de directivas [mp.info.2]
        try {
            $this->auditService->recordAccess(
                $userId,
                $this->request->getRemoteAddress(),
                0,
                'POLICIES',
                'settings/security_policies',
                $this->configService->getEnsClassification(),
                false,
                false,
                false,
                'POLICY_CHANGE'
            );
        } catch (\Throwable) {
        }

        return new DataResponse([
            'status' => 'success',
            'message' => 'Directivas de seguridad ENS actualizadas correctamente.',
            'policies' => $this->configService->getAllPolicies(),
        ]);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getGroups(): DataResponse {
        $user = $this->userSession->getUser();
        $userId = $user ? $user->getUID() : 'anonymous';

        if (!$this->configService->canUserManagePolicies($userId)) {
            return new DataResponse([
                'status' => 'error',
                'message' => 'No autorizado.',
            ], Http::STATUS_FORBIDDEN);
        }

        $groups = $this->groupManager->search('');
        $groupList = [];
        foreach ($groups as $group) {
            $groupList[] = [
                'id' => $group->getGID(),
                'name' => $group->getDisplayName(),
                'count' => $group->count(),
            ];
        }

        return new DataResponse([
            'status' => 'success',
            'groups' => $groupList,
        ]);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getDiagnostics(): DataResponse {
        $user = $this->userSession->getUser();
        $userId = $user ? $user->getUID() : 'anonymous';

        if (!$this->configService->canUserManagePolicies($userId)) {
            return new DataResponse([
                'status' => 'error',
                'message' => 'No autorizado.',
            ], Http::STATUS_FORBIDDEN);
        }

        return new DataResponse($this->diagnosticService->runDiagnostics());
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function exportAudit(): DataDownloadResponse {
        $user = $this->userSession->getUser();
        $userId = $user ? $user->getUID() : 'anonymous';

        if (!$this->configService->canUserManagePolicies($userId)) {
            throw new \OCP\HintException('No autorizado para exportar registros de auditoría ENS.');
        }

        $csv = $this->auditService->exportCsv();
        $fileName = 'nextcloud_secure_office_audit_' . date('Ymd_His') . '.csv';

        return new DataDownloadResponse($csv, $fileName, 'text/csv');
    }

    private function formatGroupList(mixed $val): ?string {
        if ($val === null) {
            return null;
        }
        if (is_array($val)) {
            $filtered = array_filter(array_map('trim', $val), fn($v) => $v !== '');
            return implode(',', array_unique($filtered));
        }
        if (is_string($val)) {
            $parts = array_filter(array_map('trim', explode(',', $val)), fn($v) => $v !== '');
            return implode(',', array_unique($parts));
        }
        return null;
    }

    private function resolveParam(string $key): mixed {
        $val = $this->request->getParam($key);
        if ($val !== null) {
            return $val;
        }

        static $jsonBody = null;
        if ($jsonBody === null) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                $jsonBody = is_array($decoded) ? $decoded : [];
            } else {
                $jsonBody = [];
            }
        }

        return $jsonBody[$key] ?? null;
    }
}