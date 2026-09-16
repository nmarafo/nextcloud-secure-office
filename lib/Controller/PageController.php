<?php
declare(strict_types=1);

namespace OCA\SecureOffice\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\IRequest;

class PageController extends Controller {
    public function __construct(string $appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        return new TemplateResponse('secure_office', 'main', [
            'appName' => 'Secure Office',
            'message' => 'Nextcloud Secure Office plugin iniciado correctamente.',
        ]);
    }

    #[PublicPage]
    #[NoCSRFRequired]
    public function status(): DataResponse {
        return new DataResponse([
            'app' => 'secure_office',
            'status' => 'ok',
            'version' => '0.1.0'
        ]);
    }
}
