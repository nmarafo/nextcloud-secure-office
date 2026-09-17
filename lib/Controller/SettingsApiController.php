<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Controller;

use OCA\SecureOffice\Service\AuditService;
use OCA\SecureOffice\Service\EnsDiagnosticService;
use OCA\SecureOffice\Service\SecurityConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\IConfig;
use OCP\IRequest;

class SettingsApiController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private SecurityConfigService $configService,
        private EnsDiagnosticService $diagnosticService,
        private AuditService $auditService,
        private IConfig $config,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoCSRFRequired]
    public function updateSettings(): DataResponse {
        $watermarkEnabled = $this->request->getParam('watermark_enabled');
        if ($watermarkEnabled !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'watermark_enabled',
                filter_var($watermarkEnabled, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $watermarkTemplate = $this->request->getParam('watermark_template');
        if ($watermarkTemplate !== null && is_string($watermarkTemplate)) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'watermark_template',
                trim($watermarkTemplate)
            );
        }

        $dlpExport = $this->request->getParam('dlp_disable_export');
        if ($dlpExport !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_disable_export',
                filter_var($dlpExport, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $dlpCopy = $this->request->getParam('dlp_disable_copy');
        if ($dlpCopy !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_disable_copy',
                filter_var($dlpCopy, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $dlpPrint = $this->request->getParam('dlp_disable_print');
        if ($dlpPrint !== null) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'dlp_disable_print',
                filter_var($dlpPrint, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'
            );
        }

        $ensClassification = $this->request->getParam('ens_classification');
        if ($ensClassification !== null && is_string($ensClassification)) {
            $this->config->setAppValue(
                SecurityConfigService::APP_ID,
                'ens_classification',
                trim($ensClassification)
            );
        }

        return new DataResponse([
            'status' => 'success',
            'message' => 'Configuración de seguridad actualizada correctamente.',
            'policies' => $this->configService->getAllPolicies(),
        ]);
    }

    #[NoCSRFRequired]
    public function getDiagnostics(): DataResponse {
        return new DataResponse($this->diagnosticService->runDiagnostics());
    }

    #[NoCSRFRequired]
    public function exportAudit(): DataDownloadResponse {
        $csv = $this->auditService->exportCsv();
        $filename = 'ens_audit_trail_' . gmdate('Ymd_His') . '.csv';

        return new DataDownloadResponse($csv, $filename, 'text/csv');
    }
}