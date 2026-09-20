<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Service;

use OCP\IConfig;

class SecurityConfigService {
    public const APP_ID = 'secure_office';

    public function __construct(
        private IConfig $config,
    ) {
    }

    // --- Módulo Collabora Online ---

    public function isCollaboraProtectionEnabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'collabora_protection_enabled', 'yes') === 'yes';
    }

    public function isWatermarkEnabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'watermark_enabled', 'yes') === 'yes';
    }

    public function getWatermarkTemplate(): string {
        return $this->config->getAppValue(
            self::APP_ID,
            'watermark_template',
            '{classification} - User: {userDisplayName} ({userId}) - IP: {userIp} - {date}'
        );
    }

    public function isExportDisabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'dlp_disable_export', 'yes') === 'yes';
    }

    public function isCopyDisabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'dlp_disable_copy', 'yes') === 'yes';
    }

    public function isPrintDisabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'dlp_disable_print', 'yes') === 'yes';
    }

    // --- Módulo Archivos Nativos (Nextcloud Files) ---

    public function isNativeProtectionEnabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'native_protection_enabled', 'yes') === 'yes';
    }

    public function isNativeAuditEnabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'native_audit_enabled', 'yes') === 'yes';
    }

    public function isNativeDlpDownloadDisabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'native_dlp_disable_download', 'no') === 'yes';
    }

    // --- Clasificación Global ENS ---

    public function getEnsClassification(): string {
        return $this->config->getAppValue(self::APP_ID, 'ens_classification', 'CONFIDENCIAL (ENS RD 311/2022)');
    }

    public function getAllPolicies(): array {
        return [
            // Collabora
            'collabora_protection_enabled' => $this->isCollaboraProtectionEnabled(),
            'watermark_enabled' => $this->isWatermarkEnabled(),
            'watermark_template' => $this->getWatermarkTemplate(),
            'dlp_disable_export' => $this->isExportDisabled(),
            'dlp_disable_copy' => $this->isCopyDisabled(),
            'dlp_disable_print' => $this->isPrintDisabled(),
            // Nativos
            'native_protection_enabled' => $this->isNativeProtectionEnabled(),
            'native_audit_enabled' => $this->isNativeAuditEnabled(),
            'native_dlp_disable_download' => $this->isNativeDlpDownloadDisabled(),
            // Global
            'ens_classification' => $this->getEnsClassification(),
        ];
    }
}
