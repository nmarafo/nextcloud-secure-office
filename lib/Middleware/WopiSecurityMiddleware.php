<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Middleware;

use OCA\SecureOffice\Service\FileSecurityService;
use OCA\SecureOffice\Service\SecurityConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class WopiSecurityMiddleware extends Middleware {
    private const TARGET_CONTROLLER = 'OCA\Richdocuments\Controller\WopiController';
    private const TARGET_METHOD = 'checkFileInfo';

    public function __construct(
        private SecurityConfigService $configService,
        private FileSecurityService $fileSecurityService,
        private IRequest $request,
        private LoggerInterface $logger,
    ) {
    }

    public function afterController(Controller $controller, string $methodName, Response $response): Response {
        if (!$this->configService->isCollaboraProtectionEnabled()) {
            return $response;
        }

        if ($methodName !== self::TARGET_METHOD || !is_a($controller, self::TARGET_CONTROLLER)) {
            return $response;
        }

        if (!($response instanceof JSONResponse)) {
            return $response;
        }

        if ($response->getStatus() !== \OCP\AppFramework\Http::STATUS_OK) {
            return $response;
        }

        $data = $response->getData();
        if (!is_array($data)) {
            return $response;
        }

        $userId = (string)($data['UserId'] ?? 'anonymous');
        $rawFileId = (string)($this->request->getParam('fileId') ?? '');
        $fileId = (int)explode('_', $rawFileId)[0];

        // Apply granular DLP restrictions per file and user/role
        $perms = $this->fileSecurityService->evaluatePermissions($fileId, $userId);

        $data['DisableExport'] = !$perms['can_export'];
        $data['HideExportOption'] = !$perms['can_export'];

        $data['DisableCopy'] = !$perms['can_copy'];

        $data['DisablePrint'] = !$perms['can_print'];
        $data['HidePrintOption'] = !$perms['can_print'];

        // Apply Dynamic Forensic Watermarking
        if ($this->configService->isWatermarkEnabled()) {
            $userDisplayName = (string)($data['UserFriendlyName'] ?? $userId);
            $userIp = $this->request->getRemoteAddress();
            $date = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s \U\T\C');
            $classification = $perms['classification'];

            $template = $perms['watermark_template'];
            $replacements = [
                '{userId}' => $userId,
                '{userDisplayName}' => $userDisplayName,
                '{userIp}' => $userIp,
                '{date}' => $date,
                '{classification}' => $classification,
            ];

            $watermarkText = strtr($template, $replacements);
            $data['WatermarkText'] = $watermarkText;

            $this->logger->info(
                'Secure Office: Evaluated granular DLP policies for user {userId} on {fileName} [fileId: {fileId}, export: {export}, print: {print}, copy: {copy}, source: {source}]',
                [
                    'app' => SecurityConfigService::APP_ID,
                    'fileId' => $fileId,
                    'userId' => $userId,
                    'userIp' => $userIp,
                    'fileName' => $data['BaseFileName'] ?? 'unknown',
                    'watermark' => $watermarkText,
                    'export' => $perms['can_export'] ? 'allowed' : 'blocked',
                    'print' => $perms['can_print'] ? 'allowed' : 'blocked',
                    'copy' => $perms['can_copy'] ? 'allowed' : 'blocked',
                    'source' => $perms['rule_source'],
                ]
            );
        }

        $response->setData($data);
        return $response;
    }
}
