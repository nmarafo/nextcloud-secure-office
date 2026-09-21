<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Service;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;

class SecurityConfigService {
    public const APP_ID = 'secure_office';

    public function __construct(
        private IConfig $config,
        private IGroupManager $groupManager,
        private IUserManager $userManager,
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

    public function getDlpExportAllowedGroups(): array {
        return $this->parseGroupList($this->config->getAppValue(self::APP_ID, 'dlp_export_allowed_groups', ''));
    }

    public function isCopyDisabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'dlp_disable_copy', 'yes') === 'yes';
    }

    public function getDlpCopyAllowedGroups(): array {
        return $this->parseGroupList($this->config->getAppValue(self::APP_ID, 'dlp_copy_allowed_groups', ''));
    }

    public function isPrintDisabled(): bool {
        return $this->config->getAppValue(self::APP_ID, 'dlp_disable_print', 'yes') === 'yes';
    }

    public function getDlpPrintAllowedGroups(): array {
        return $this->parseGroupList($this->config->getAppValue(self::APP_ID, 'dlp_print_allowed_groups', ''));
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

    public function getNativeDlpAllowedGroups(): array {
        return $this->parseGroupList($this->config->getAppValue(self::APP_ID, 'native_dlp_allowed_groups', ''));
    }

    // --- Gestión Delegada ENS / Responsable de la Información ---

    public function getDelegatedAdminGroups(): array {
        return $this->parseGroupList($this->config->getAppValue(self::APP_ID, 'delegated_admin_groups', ''));
    }

    public function canUserManagePolicies(?string $userId): bool {
        if ($userId === null || $userId === '' || $userId === 'anonymous') {
            return false;
        }

        if ($this->groupManager->isAdmin($userId)) {
            return true;
        }

        $delegatedGroups = $this->getDelegatedAdminGroups();
        if (empty($delegatedGroups)) {
            return false;
        }

        $user = $this->userManager->get($userId);
        if (!$user) {
            return false;
        }

        $userGroups = $this->groupManager->getUserGroupIds($user);
        return !empty(array_intersect($userGroups, $delegatedGroups));
    }

    // --- Comprobación Contextual de Permisos DLP por Rol / Grupo ---

    public function isUserAllowedToExport(?string $userId): bool {
        if (!$this->isExportDisabled()) {
            return true;
        }
        return $this->isUserInAllowedGroups($userId, $this->getDlpExportAllowedGroups());
    }

    public function isUserAllowedToPrint(?string $userId): bool {
        if (!$this->isPrintDisabled()) {
            return true;
        }
        return $this->isUserInAllowedGroups($userId, $this->getDlpPrintAllowedGroups());
    }

    public function isUserAllowedToCopy(?string $userId): bool {
        if (!$this->isCopyDisabled()) {
            return true;
        }
        return $this->isUserInAllowedGroups($userId, $this->getDlpCopyAllowedGroups());
    }

    public function isUserAllowedToDownloadNative(?string $userId): bool {
        if (!$this->isNativeDlpDownloadDisabled()) {
            return true;
        }
        return $this->isUserInAllowedGroups($userId, $this->getNativeDlpAllowedGroups());
    }

    private function isUserInAllowedGroups(?string $userId, array $allowedGroups): bool {
        if ($userId === null || $userId === '' || $userId === 'anonymous') {
            return false;
        }

        // Los administradores de sistemas siempre disponen de acceso
        if ($this->groupManager->isAdmin($userId)) {
            return true;
        }

        if (empty($allowedGroups)) {
            return false;
        }

        $user = $this->userManager->get($userId);
        if (!$user) {
            return false;
        }

        $userGroups = $this->groupManager->getUserGroupIds($user);
        return !empty(array_intersect($userGroups, $allowedGroups));
    }

    private function parseGroupList(string $raw): array {
        if (trim($raw) === '') {
            return [];
        }
        $parts = explode(',', $raw);
        $groups = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed !== '') {
                $groups[] = $trimmed;
            }
        }
        return array_values(array_unique($groups));
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
            'dlp_export_allowed_groups' => $this->getDlpExportAllowedGroups(),
            'dlp_disable_copy' => $this->isCopyDisabled(),
            'dlp_copy_allowed_groups' => $this->getDlpCopyAllowedGroups(),
            'dlp_disable_print' => $this->isPrintDisabled(),
            'dlp_print_allowed_groups' => $this->getDlpPrintAllowedGroups(),
            // Nativos
            'native_protection_enabled' => $this->isNativeProtectionEnabled(),
            'native_audit_enabled' => $this->isNativeAuditEnabled(),
            'native_dlp_disable_download' => $this->isNativeDlpDownloadDisabled(),
            'native_dlp_allowed_groups' => $this->getNativeDlpAllowedGroups(),
            // Gestión Delegada
            'delegated_admin_groups' => $this->getDelegatedAdminGroups(),
            // Global
            'ens_classification' => $this->getEnsClassification(),
        ];
    }
}
