<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Listener;

use OCA\SecureOffice\Service\AuditService;
use OCA\SecureOffice\Service\FileSecurityService;
use OCA\SecureOffice\Service\SecurityConfigService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\File;
use OCP\HintException;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Audit and DLP listener for native Nextcloud file accesses and downloads under ENS (RD 311/2022).
 * Fulfills measure [mp.info.2] activity registration and [mp.info.6] native data loss prevention.
 *
 * @template-implements IEventListener<BeforeNodeReadEvent|BeforeDirectFileDownloadEvent|Event>
 */
class NativeFileAccessListener implements IEventListener {
    private static array $processedReads = [];

    public function __construct(
        private SecurityConfigService $configService,
        private FileSecurityService $fileSecurityService,
        private AuditService $auditService,
        private IRequest $request,
        private IUserSession $userSession,
        private IGroupManager $groupManager,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Event $event): void {
        if ($event instanceof BeforeNodeReadEvent) {
            $this->handleNodeRead($event);
        } elseif ($event instanceof BeforeDirectFileDownloadEvent) {
            $this->handleDirectDownload($event);
        }
    }

    private function handleNodeRead(BeforeNodeReadEvent $event): void {
        if (!$this->configService->isNativeProtectionEnabled()) {
            return;
        }

        $node = $event->getNode();
        if (!($node instanceof File)) {
            return;
        }

        $path = $node->getPath();

        // Anti-noise filter: Ignore internal Nextcloud system files, thumbnails, appdata, versions
        if ($this->isSystemOrInternalPath($path)) {
            return;
        }

        $user = $this->userSession->getUser();
        $userId = $user ? $user->getUID() : 'anonymous';
        $remoteIp = $this->request->getRemoteAddress();

        // Evaluate granular DLP per file and user/role
        $perms = $this->fileSecurityService->evaluatePermissions((int)$node->getId(), $userId);
        $classification = $perms['classification'];

        // Native DLP: Restrict direct file download if configured or specific file/user rule restricts it
        if (!$perms['can_download']) {
            try {
                $this->auditService->recordAccess(
                    $userId,
                    $remoteIp,
                    (int)$node->getId(),
                    $node->getName(),
                    $path,
                    $classification,
                    true,
                    false,
                    false,
                    'BLOCKED_DOWNLOAD',
                );
            } catch (\Throwable $e) {
                $this->logger->error('Secure Office: Failed to audit blocked download: ' . $e->getMessage(), ['exception' => $e]);
            }

            $this->logger->warning(
                'ENS DLP [mp.info.6]: Native file download blocked for user {userId} on {fileName} [source: {source}]',
                [
                    'app' => SecurityConfigService::APP_ID,
                    'userId' => $userId,
                    'remoteIp' => $remoteIp,
                    'fileName' => $node->getName(),
                    'filePath' => $path,
                    'source' => $perms['rule_source'],
                ]
            );

            throw new HintException(
                'Descarga no permitida según la política de seguridad y DLP del ENS [mp.info.6].',
                'Descarga denegada por política de seguridad y DLP del ENS [mp.info.6].'
            );
        }

        // Deduplication: Avoid multi-logging range requests or chunked streams for the same file in one request
        $cacheKey = $this->request->getId() . '_' . $node->getId();
        if (isset(self::$processedReads[$cacheKey])) {
            return;
        }
        self::$processedReads[$cacheKey] = true;
        if (count(self::$processedReads) > 200) {
            self::$processedReads = array_slice(self::$processedReads, -50, null, true);
        }

        // Native Audit: Log regular file access/download
        if ($this->configService->isNativeAuditEnabled()) {
            try {
                $this->auditService->recordAccess(
                    $userId,
                    $remoteIp,
                    (int)$node->getId(),
                    $node->getName(),
                    $path,
                    $classification,
                    false,
                    false,
                    false,
                    'NATIVE_DOWNLOAD',
                );
            } catch (\Throwable $e) {
                $this->logger->error('Secure Office: Failed to audit native file read: ' . $e->getMessage(), ['exception' => $e]);
            }

            $this->logger->info(
                'ENS AUDIT [mp.info.2]: Native file accessed/downloaded by user {userId} from {remoteIp}. File: {filePath} (ID: {fileId})',
                [
                    'app' => SecurityConfigService::APP_ID,
                    'event' => 'ENS_NATIVE_FILE_READ',
                    'userId' => $userId,
                    'remoteIp' => $remoteIp,
                    'fileId' => $node->getId(),
                    'fileName' => $node->getName(),
                    'filePath' => $path,
                ]
            );
        }
    }

    private function handleDirectDownload(BeforeDirectFileDownloadEvent $event): void {
        if (!$this->configService->isNativeProtectionEnabled()) {
            return;
        }

        $user = $this->userSession->getUser();
        $userId = $user ? $user->getUID() : 'anonymous';

        $fileId = 0;
        if (method_exists($event, 'getFile') && $event->getFile() instanceof File) {
            $fileId = (int)$event->getFile()->getId();
        }

        $perms = $this->fileSecurityService->evaluatePermissions($fileId, $userId);

        if (!$perms['can_download']) {
            $event->setSuccessful(false);
            $event->setErrorMessage('Descarga denegada por política de seguridad y DLP del ENS [mp.info.6].');

            $remoteIp = $this->request->getRemoteAddress();
            $classification = $perms['classification'];

            try {
                $this->auditService->recordAccess(
                    $userId,
                    $remoteIp,
                    $fileId,
                    basename($event->getPath()),
                    $event->getPath(),
                    $classification,
                    true,
                    false,
                    false,
                    'BLOCKED_DOWNLOAD',
                );
            } catch (\Throwable) {
            }
        }
    }

    private function isSystemOrInternalPath(string $path): bool {
        // Exclude system caches, appdata, avatars, thumbnails, trash, versions
        $ignoredPatterns = [
            'appdata_',
            '/.thumbnails/',
            '/thumbnails/',
            '/avatar/',
            '/avatars/',
            '/files_trashbin/',
            '/files_versions/',
            '/preview/',
        ];

        foreach ($ignoredPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
