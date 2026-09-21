<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Service;

use OCP\Encryption\IManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Server;

class EnsDiagnosticService {
    public function __construct(
        private SecurityConfigService $configService,
        private AuditService $auditService,
        private IConfig $config,
        private IRequest $request,
        private IManager $encryptionManager,
        private IL10N $l10n,
    ) {
    }

    public function runDiagnostics(): array {
        $traceability = $this->checkTraceability();
        $storageEncryption = $this->checkStorageEncryption();
        $channelProtection = $this->checkChannelProtection();
        $dlpProtection = $this->checkDlpProtection();

        $allOk = $traceability['status'] === 'ok'
            && $storageEncryption['status'] === 'ok'
            && ($channelProtection['status'] === 'ok' || $channelProtection['status'] === 'info')
            && $dlpProtection['status'] === 'ok';

        $partialOk = $traceability['status'] === 'ok' && $dlpProtection['status'] === 'ok';

        $overallStatus = $allOk ? 'COMPLIANT' : ($partialOk ? 'PARTIAL' : 'NON_COMPLIANT');
        $overallLabel = match ($overallStatus) {
            'COMPLIANT' => $this->l10n->t('Compliant (ENS RD 311/2022 - High Level)'),
            'PARTIAL' => $this->l10n->t('Partial Compliance (DLP and Traceability Active, Storage Encryption Pending)'),
            'NON_COMPLIANT' => $this->l10n->t('Non-Compliant (Critical DLP measures or auditing disabled)'),
        };

        return [
            'overall_status' => $overallStatus,
            'overall_label' => $overallLabel,
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s \U\T\C'),
            'measures' => [
                'mp_info_2' => $traceability,
                'mp_info_3' => $storageEncryption,
                'mp_info_4' => $channelProtection,
                'mp_info_6' => $dlpProtection,
            ],
        ];
    }

    private function checkTraceability(): array {
        $entryCount = 0;
        $tableOk = false;
        try {
            $entryCount = $this->auditService->countEntries();
            $tableOk = true;
        } catch (\Throwable) {
            $tableOk = false;
        }

        $collabOn = $this->configService->isCollaboraProtectionEnabled();
        $nativeOn = $this->configService->isNativeProtectionEnabled() && $this->configService->isNativeAuditEnabled();

        $modules = [];
        if ($collabOn) $modules[] = 'Collabora Office';
        if ($nativeOn) $modules[] = $this->l10n->t('Native Files (Files)');

        $modulesText = empty($modules) ? $this->l10n->t('Disabled') : implode(' + ', $modules);

        return [
            'code' => 'mp.info.2',
            'title' => $this->l10n->t('Activity logging and document traceability'),
            'status' => ($tableOk && ($collabOn || $nativeOn)) ? 'ok' : 'error',
            'details' => $tableOk
                ? $this->l10n->t('Active traceability [%s]. Stored audit records: %s', [$modulesText, $entryCount])
                : $this->l10n->t('Error: Audit table oc_secure_office_audit is not available.'),
            'recommendation' => $tableOk ? null : $this->l10n->t('Run php occ upgrade to migrate the database.'),
        ];
    }

    private function checkStorageEncryption(): array {
        $encryptionEnabled = $this->encryptionManager->isEnabled();
        $defaultModule = (string)$this->encryptionManager->getDefaultEncryptionModuleId();
        $masterKeyEnabled = false;

        $useMasterKey = $this->config->getAppValue('encryption', 'useMasterKey', '0');
        if ($useMasterKey === '1' || $useMasterKey === 'yes' || $useMasterKey === true) {
            $masterKeyEnabled = true;
        } elseif (class_exists('\OCA\Encryption\Util')) {
            try {
                $util = Server::get('\OCA\Encryption\Util');
                $masterKeyEnabled = $util->isMasterKeyEnabled();
            } catch (\Throwable) {
            }
        }

        if ($encryptionEnabled && $masterKeyEnabled) {
            $cipher = ($defaultModule === 'OC_DEFAULT_MODULE') ? 'AES-256-CTR' : $this->l10n->t('Strong encryption');
            return [
                'code' => 'mp.info.3',
                'title' => $this->l10n->t('Cryptographic protection of stored information (at rest)'),
                'status' => 'ok',
                'details' => $this->l10n->t('Encryption at rest active with Master Key (%s). Module: %s.', [$cipher, $defaultModule]),
                'recommendation' => null,
            ];
        }

        if ($encryptionEnabled && !$masterKeyEnabled) {
            return [
                'code' => 'mp.info.3',
                'title' => $this->l10n->t('Cryptographic protection of stored information (at rest)'),
                'status' => 'warning',
                'details' => $this->l10n->t('Server encryption is enabled but requires Master Key mode for Collabora Online.'),
                'recommendation' => $this->l10n->t('Enable master key: php occ encryption:enable-master-key'),
            ];
        }

        return [
            'code' => 'mp.info.3',
            'title' => $this->l10n->t('Cryptographic protection of stored information (at rest)'),
            'status' => 'error',
            'details' => $this->l10n->t('Server storage encryption is disabled.'),
            'recommendation' => $this->l10n->t('Enable via: php occ app:enable encryption && php occ encryption:enable && php occ encryption:enable-master-key'),
        ];
    }

    private function checkChannelProtection(): array {
        $isHttps = $this->request->getServerProtocol() === 'https'
            || $this->config->getSystemValueString('overwriteprotocol', '') === 'https';

        return [
            'code' => 'mp.info.4',
            'title' => $this->l10n->t('Protection of communication channel (in transit)'),
            'status' => $isHttps ? 'ok' : 'info',
            'details' => $isHttps
                ? $this->l10n->t('Secure HTTPS / TLS channel active on the server.')
                : $this->l10n->t('Local HTTP development environment (localhost). Mandatory HTTPS must be configured in production.'),
            'recommendation' => $isHttps ? null : $this->l10n->t('Configure TLS/SSL certificate and "overwriteprotocol" => "https" in config.php for production.'),
        ];
    }

    private function checkDlpProtection(): array {
        $collabOn = $this->configService->isCollaboraProtectionEnabled();
        $watermark = $this->configService->isWatermarkEnabled();
        $export = $this->configService->isExportDisabled();
        $copy = $this->configService->isCopyDisabled();
        $print = $this->configService->isPrintDisabled();

        $nativeOn = $this->configService->isNativeProtectionEnabled();
        $nativeAudit = $this->configService->isNativeAuditEnabled();
        $nativeDlp = $this->configService->isNativeDlpDownloadDisabled();

        $activeControls = [];
        if ($collabOn) {
            if ($watermark) $activeControls[] = $this->l10n->t('Collabora Watermark');
            if ($export) $activeControls[] = $this->l10n->t('Export Blocking');
            if ($copy) $activeControls[] = $this->l10n->t('Clipboard Isolation');
            if ($print) $activeControls[] = $this->l10n->t('Print Blocking');
        }
        if ($nativeOn) {
            if ($nativeAudit) $activeControls[] = $this->l10n->t('Native Traceability');
            if ($nativeDlp) $activeControls[] = $this->l10n->t('Direct Download Blocking');
        }

        return [
            'code' => 'mp.info.6',
            'title' => $this->l10n->t('Information leakage prevention (DLP and forensic watermarks)'),
            'status' => !empty($activeControls) ? 'ok' : 'error',
            'details' => $this->l10n->t('Active DLP controls: %s', [empty($activeControls) ? $this->l10n->t('None') : implode(', ', $activeControls)]),
            'recommendation' => !empty($activeControls) ? null : $this->l10n->t('Enable DLP restrictions in the administration panel.'),
        ];
    }
}