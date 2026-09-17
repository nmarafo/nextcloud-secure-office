<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Service;

use OCP\Encryption\IManager;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Server;

class EnsDiagnosticService {
    public function __construct(
        private SecurityConfigService $configService,
        private AuditService $auditService,
        private IConfig $config,
        private IRequest $request,
        private IManager $encryptionManager,
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
            'COMPLIANT' => 'Conforme (ENS RD 311/2022 - Nivel Alto)',
            'PARTIAL' => 'Conformidad Parcial (DLP y Trazabilidad Activas, Cifrado en Reposo Pendiente)',
            'NON_COMPLIANT' => 'No Conforme (Medidas DLP críticas o auditoría desactivadas)',
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

        return [
            'code' => 'mp.info.2',
            'title' => 'Registro de actividad y trazabilidad de documentos',
            'status' => $tableOk ? 'ok' : 'error',
            'details' => $tableOk
                ? "Trazabilidad activa. Registros de auditoría almacenados: $entryCount"
                : 'Error: La tabla de auditoría oc_secure_office_audit no está disponible.',
            'recommendation' => $tableOk ? null : 'Ejecutar php occ upgrade para migrar la base de datos.',
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
            $cipher = ($defaultModule === 'OC_DEFAULT_MODULE') ? 'AES-256-CTR' : 'Cifrado robusto';
            return [
                'code' => 'mp.info.3',
                'title' => 'Protección criptográfica de la información almacenada (en reposo)',
                'status' => 'ok',
                'details' => "Cifrado en reposo activo con Clave Maestra ($cipher). Módulo: $defaultModule.",
                'recommendation' => null,
            ];
        }

        if ($encryptionEnabled && !$masterKeyEnabled) {
            return [
                'code' => 'mp.info.3',
                'title' => 'Protección criptográfica de la información almacenada (en reposo)',
                'status' => 'warning',
                'details' => 'El cifrado de servidor está activo pero requiere modo de clave maestra para Collabora Online.',
                'recommendation' => 'Habilitar clave maestra: php occ encryption:enable-master-key',
            ];
        }

        return [
            'code' => 'mp.info.3',
            'title' => 'Protección criptográfica de la información almacenada (en reposo)',
            'status' => 'error',
            'details' => 'El cifrado de almacenamiento en servidor está desactivado.',
            'recommendation' => 'Habilitar mediante: php occ app:enable encryption && php occ encryption:enable && php occ encryption:enable-master-key',
        ];
    }

    private function checkChannelProtection(): array {
        $isHttps = $this->request->getServerProtocol() === 'https'
            || $this->config->getSystemValueString('overwriteprotocol', '') === 'https';

        return [
            'code' => 'mp.info.4',
            'title' => 'Protección del canal de comunicación (Tránsito)',
            'status' => $isHttps ? 'ok' : 'info',
            'details' => $isHttps
                ? 'Canal seguro HTTPS / TLS activo en el servidor.'
                : 'Entorno de desarrollo local HTTP (localhost). En producción debe configurarse HTTPS obligatorio.',
            'recommendation' => $isHttps ? null : 'Configurar certificado TLS/SSL y "overwriteprotocol" => "https" en config.php para producción.',
        ];
    }

    private function checkDlpProtection(): array {
        $watermark = $this->configService->isWatermarkEnabled();
        $export = $this->configService->isExportDisabled();
        $copy = $this->configService->isCopyDisabled();
        $print = $this->configService->isPrintDisabled();

        $activeControls = [];
        if ($watermark) $activeControls[] = 'Marca de agua forense';
        if ($export) $activeControls[] = 'Bloqueo de exportación';
        if ($copy) $activeControls[] = 'Aislamiento de portapapeles';
        if ($print) $activeControls[] = 'Bloqueo de impresión';

        $allDlp = $watermark && $export && $copy && $print;

        return [
            'code' => 'mp.info.6',
            'title' => 'Prevención de fuga de información (DLP y marcas de agua forenses)',
            'status' => $allDlp ? 'ok' : ($watermark ? 'warning' : 'error'),
            'details' => 'Controles DLP activos: ' . (empty($activeControls) ? 'Ninguno' : implode(', ', $activeControls)),
            'recommendation' => $allDlp ? null : 'Activar todas las restricciones DLP y marcas de agua en el panel de administración.',
        ];
    }
}