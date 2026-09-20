<?php

declare(strict_types=1);

namespace OCA\SecureOffice\AppInfo;

use OCA\Richdocuments\Events\DocumentOpenedEvent;
use OCA\SecureOffice\Listener\DocumentOpenedListener;
use OCA\SecureOffice\Listener\NativeFileAccessListener;
use OCA\SecureOffice\Middleware\WopiSecurityMiddleware;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;

class Application extends App implements IBootstrap {
    public const APPNAME = 'secure_office';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APPNAME, $urlParams);
    }

    #[\Override]
    public function register(IRegistrationContext $context): void {
        // Register global WOPI security middleware for Collabora Office
        $context->registerMiddleware(WopiSecurityMiddleware::class, true);

        // Register document access audit listener for Collabora Office
        $context->registerEventListener(DocumentOpenedEvent::class, DocumentOpenedListener::class);

        // Register native file access and download listener (PDF, images, archives, etc.)
        $context->registerEventListener(BeforeNodeReadEvent::class, NativeFileAccessListener::class);
        $context->registerEventListener(BeforeDirectFileDownloadEvent::class, NativeFileAccessListener::class);
    }

    #[\Override]
    public function boot(IBootContext $context): void {
    }
}
