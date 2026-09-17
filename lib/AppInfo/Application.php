<?php

declare(strict_types=1);

namespace OCA\SecureOffice\AppInfo;

use OCA\Richdocuments\Events\DocumentOpenedEvent;
use OCA\SecureOffice\Listener\DocumentOpenedListener;
use OCA\SecureOffice\Middleware\WopiSecurityMiddleware;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APPNAME = 'secure_office';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APPNAME, $urlParams);
    }

    #[\Override]
    public function register(IRegistrationContext $context): void {
        // Register global WOPI security middleware to intercept checkFileInfo
        $context->registerMiddleware(WopiSecurityMiddleware::class, true);

        // Register document access audit listener for ENS compliance
        $context->registerEventListener(DocumentOpenedEvent::class, DocumentOpenedListener::class);
    }

    #[\Override]
    public function boot(IBootContext $context): void {
    }
}
