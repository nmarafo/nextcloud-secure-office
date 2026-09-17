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

        // Apply DLP restrictions (ENS compliance)
        if ($this->configService->isExportDisabled()) {
            $data['DisableExport'] = true;
            $data['HideExportOption'] = true;
        }

        if ($this->configService->isCopyDisabled()) {
            $data['DisableCopy'] = true;
        }

        if ($this->configService->isPrintDisabled()) {
            $data['DisablePrint'] = true;
            $data['HidePrintOption'] = true;
        }

        // Apply Dynamic Forensic Watermarking
        if ($this->configService->isWatermarkEnabled()) {
            $userId = (string)($data['UserId'] ?? 'anonymous');
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
                'Secure Office: Injected forensic watermark and DLP policies for user {userId} on {fileName}',
                [
                    'app' => SecurityConfigService::APP_ID,
                    'userId' => $userId,
                    'userIp' => $userIp,
                    'fileName' => $data['BaseFileName'] ?? 'unknown',
                    'watermark' => $watermarkText,
                    'dlp_export_disabled' => $data['DisableExport'] ?? false,
                    'dlp_copy_disabled' => $data['DisableCopy'] ?? false,
                    'dlp_print_disabled' => $data['DisablePrint'] ?? false,
                ]
            );
        }

        $response->setData($data);
        return $response;
    }
}
