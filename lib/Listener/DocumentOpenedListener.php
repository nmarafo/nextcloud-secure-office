<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Listener;

use OCA\Richdocuments\Events\DocumentOpenedEvent;
use OCA\SecureOffice\Service\AuditService;
use OCA\SecureOffice\Service\SecurityConfigService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Audit listener for collaborative document access under Spanish ENS (RD 311/2022).
 * Fulfills measure [mp.info.2] activity registration and non-repudiation traceability.
 *
 * @template-implements IEventListener<DocumentOpenedEvent|Event>
 */
class DocumentOpenedListener implements IEventListener {
    public function __construct(
        private SecurityConfigService $configService,
        private AuditService $auditService,
        private IRequest $request,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Event $event): void {
        if (!($event instanceof DocumentOpenedEvent)) {
            return;
        }

        if (!$this->configService->isCollaboraProtectionEnabled()) {
            return;
        }

        $userId = $event->getUserId() ?? 'anonymous';
        $node = $event->getNode();
        $remoteIp = $this->request->getRemoteAddress();
        $classification = $this->configService->getEnsClassification();
        $dlpExport = !$this->configService->isUserAllowedToExport($userId);
        $dlpCopy = !$this->configService->isUserAllowedToCopy($userId);
        $dlpPrint = !$this->configService->isUserAllowedToPrint($userId);

        // 1. Persist audit record to database
        try {
            $this->auditService->recordAccess(
                $userId,
                $remoteIp,
                (int)$node->getId(),
                $node->getName(),
                $node->getPath(),
                $classification,
                $dlpExport,
                $dlpCopy,
                $dlpPrint,
                'COLLABORA_VIEW',
            );
        } catch (\Throwable $e) {
            $this->logger->error('Secure Office: Failed to write database audit entry: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        // 2. Log to PSR-3 Nextcloud security log

        $this->logger->info(
            'ENS AUDIT [mp.info.2]: Collaborative document opened by user {userId} from {remoteIp}. File: {filePath} (ID: {fileId}, Classification: {classification})',
            [
                'app' => SecurityConfigService::APP_ID,
                'event' => 'ENS_DOCUMENT_OPENED',
                'userId' => $userId,
                'remoteIp' => $remoteIp,
                'fileId' => $node->getId(),
                'fileName' => $node->getName(),
                'filePath' => $node->getPath(),
                'fileSize' => $node->getSize(),
                'mimeType' => $node->getMimetype(),
                'classification' => $this->configService->getEnsClassification(),
                'dlp_export_disabled' => $this->configService->isExportDisabled(),
                'dlp_copy_disabled' => $this->configService->isCopyDisabled(),
                'dlp_print_disabled' => $this->configService->isPrintDisabled(),
                'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            ]
        );
    }
}
