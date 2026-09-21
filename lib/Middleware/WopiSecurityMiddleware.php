<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Middleware;

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

        // Apply DLP restrictions dynamically per role / group (ENS compliance)
        $exportAllowed = $this->configService->isUserAllowedToExport($userId);
        if (!$exportAllowed) {
            $data['DisableExport'] = true;
            $data['HideExportOption'] = true;
        } else {
            $data['DisableExport'] = false;
            $data['HideExportOption'] = false;
        }

        $copyAllowed = $this->configService->isUserAllowedToCopy($userId);
        if (!$copyAllowed) {
            $data['DisableCopy'] = true;
        } else {
            $data['DisableCopy'] = false;
        }

        $printAllowed = $this->configService->isUserAllowedToPrint($userId);
        if (!$printAllowed) {
            $data['DisablePrint'] = true;
            $data['HidePrintOption'] = true;
        } else {
            $data['DisablePrint'] = false;
            $data['HidePrintOption'] = false;
        }

        // Apply Dynamic Forensic Watermarking
        if ($this->configService->isWatermarkEnabled()) {
            $userDisplayName = (string)($data['UserFriendlyName'] ?? $userId);
            $userIp = $this->request->getRemoteAddress();
            $date = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s \U\T\C');
            $classification = $this->configService->getEnsClassification();

            $template = $this->configService->getWatermarkTemplate();
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
                'Secure Office: Evaluated granular DLP policies for user {userId} on {fileName} [export: {export}, print: {print}, copy: {copy}]',
                [
                    'app' => SecurityConfigService::APP_ID,
                    'userId' => $userId,
                    'userIp' => $userIp,
                    'fileName' => $data['BaseFileName'] ?? 'unknown',
                    'watermark' => $watermarkText,
                    'export' => $exportAllowed ? 'allowed' : 'blocked',
                    'print' => $printAllowed ? 'allowed' : 'blocked',
                    'copy' => $copyAllowed ? 'allowed' : 'blocked',
                ]
            );
        }

        $response->setData($data);
        return $response;
    }
}
