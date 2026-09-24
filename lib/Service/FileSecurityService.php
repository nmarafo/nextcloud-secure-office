<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Service;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;

class FileSecurityService {
    public const TABLE_NAME = 'secure_office_file_rules';

    public function __construct(
        private IDBConnection $db,
        private SecurityConfigService $configService,
        private IGroupManager $groupManager,
        private IUserManager $userManager,
    ) {
    }

    /**
     * Devuelve todas las reglas asociadas a un archivo específico.
     */
    public function getRulesForFile(int $fileId): array {
        if ($fileId <= 0) {
            return [];
        }

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
            ->orderBy('id', 'ASC');

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Devuelve todas las reglas de archivo configuradas en el sistema.
     */
    public function getAllRules(int $limit = 100): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->orderBy('updated_at', 'DESC')
            ->setMaxResults($limit);

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Guarda o actualiza una regla de política para un archivo determinado.
     *
     * @param int $fileId ID numérico del archivo en oc_filecache
     * @param string|null $fileName Nombre del archivo
     * @param string|null $filePath Ruta en el almacenamiento
     * @param string $targetType 'user', 'group', o 'all'
     * @param string $targetId ID del usuario, grupo o '*'
     * @param array $perms Matriz con 'dlp_export', 'dlp_print', 'dlp_copy', 'dlp_download' (-1, 0, 1)
     * @param string|null $classification Clasificación ENS personalizada
     * @param string|null $watermarkCustom Plantilla de marca de agua específica
     * @param string $createdBy Usuario que define la regla
     */
    public function saveRule(
        int $fileId,
        ?string $fileName,
        ?string $filePath,
        string $targetType,
        string $targetId,
        array $perms,
        ?string $classification = null,
        ?string $watermarkCustom = null,
        string $createdBy = 'admin'
    ): array {
        $targetType = in_array($targetType, ['user', 'group', 'all'], true) ? $targetType : 'user';
        if ($targetType === 'all') {
            $targetId = '*';
        }

        $dlpExport = isset($perms['dlp_export']) ? (int)$perms['dlp_export'] : 0;
        $dlpPrint = isset($perms['dlp_print']) ? (int)$perms['dlp_print'] : 0;
        $dlpCopy = isset($perms['dlp_copy']) ? (int)$perms['dlp_copy'] : 0;
        $dlpDownload = isset($perms['dlp_download']) ? (int)$perms['dlp_download'] : 0;

        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        // Comprobar si ya existe una regla para este archivo + target
        $qbCheck = $this->db->getQueryBuilder();
        $qbCheck->select('id')
            ->from(self::TABLE_NAME)
            ->where($qbCheck->expr()->eq('file_id', $qbCheck->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qbCheck->expr()->eq('target_type', $qbCheck->createNamedParameter($targetType)))
            ->andWhere($qbCheck->expr()->eq('target_id', $qbCheck->createNamedParameter($targetId)));
        $existing = $qbCheck->executeQuery()->fetchAssociative();

        if ($existing) {
            $ruleId = (int)$existing['id'];
            $qbUpdate = $this->db->getQueryBuilder();
            $qbUpdate->update(self::TABLE_NAME)
                ->set('file_name', $qbUpdate->createNamedParameter($fileName))
                ->set('file_path', $qbUpdate->createNamedParameter($filePath))
                ->set('dlp_export', $qbUpdate->createNamedParameter($dlpExport, IQueryBuilder::PARAM_INT))
                ->set('dlp_print', $qbUpdate->createNamedParameter($dlpPrint, IQueryBuilder::PARAM_INT))
                ->set('dlp_copy', $qbUpdate->createNamedParameter($dlpCopy, IQueryBuilder::PARAM_INT))
                ->set('dlp_download', $qbUpdate->createNamedParameter($dlpDownload, IQueryBuilder::PARAM_INT))
                ->set('classification', $qbUpdate->createNamedParameter($classification))
                ->set('watermark_custom', $qbUpdate->createNamedParameter($watermarkCustom))
                ->set('updated_at', $qbUpdate->createNamedParameter($now))
                ->where($qbUpdate->expr()->eq('id', $qbUpdate->createNamedParameter($ruleId, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
        } else {
            $qbInsert = $this->db->getQueryBuilder();
            $qbInsert->insert(self::TABLE_NAME)
                ->values([
                    'file_id' => $qbInsert->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
                    'file_name' => $qbInsert->createNamedParameter($fileName),
                    'file_path' => $qbInsert->createNamedParameter($filePath),
                    'target_type' => $qbInsert->createNamedParameter($targetType),
                    'target_id' => $qbInsert->createNamedParameter($targetId),
                    'dlp_export' => $qbInsert->createNamedParameter($dlpExport, IQueryBuilder::PARAM_INT),
                    'dlp_print' => $qbInsert->createNamedParameter($dlpPrint, IQueryBuilder::PARAM_INT),
                    'dlp_copy' => $qbInsert->createNamedParameter($dlpCopy, IQueryBuilder::PARAM_INT),
                    'dlp_download' => $qbInsert->createNamedParameter($dlpDownload, IQueryBuilder::PARAM_INT),
                    'classification' => $qbInsert->createNamedParameter($classification),
                    'watermark_custom' => $qbInsert->createNamedParameter($watermarkCustom),
                    'created_by' => $qbInsert->createNamedParameter($createdBy),
                    'created_at' => $qbInsert->createNamedParameter($now),
                    'updated_at' => $qbInsert->createNamedParameter($now),
                ])
                ->executeStatement();
            try {
                $ruleId = (int)$this->db->lastInsertId('*PREFIX*' . self::TABLE_NAME);
            } catch (\Throwable) {
                try {
                    $ruleId = (int)$this->db->lastInsertId(self::TABLE_NAME);
                } catch (\Throwable) {
                    $ruleId = 0;
                }
            }

            if ($ruleId <= 0) {
                $qbGet = $this->db->getQueryBuilder();
                $qbGet->select('id')
                    ->from(self::TABLE_NAME)
                    ->where($qbGet->expr()->eq('file_id', $qbGet->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
                    ->andWhere($qbGet->expr()->eq('target_type', $qbGet->createNamedParameter($targetType)))
                    ->andWhere($qbGet->expr()->eq('target_id', $qbGet->createNamedParameter($targetId)));
                $row = $qbGet->executeQuery()->fetchAssociative();
                $ruleId = $row ? (int)$row['id'] : 0;
            }
        }

        return [
            'id' => $ruleId,
            'file_id' => $fileId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'dlp_export' => $dlpExport,
            'dlp_print' => $dlpPrint,
            'dlp_copy' => $dlpCopy,
            'dlp_download' => $dlpDownload,
            'classification' => $classification,
            'watermark_custom' => $watermarkCustom,
        ];
    }

    /**
     * Elimina una regla por su ID primario.
     */
    public function deleteRule(int $ruleId): bool {
        $qb = $this->db->getQueryBuilder();
        $affected = $qb->delete(self::TABLE_NAME)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($ruleId, IQueryBuilder::PARAM_INT)))
            ->executeStatement();

        return $affected > 0;
    }

    /**
     * Evalúa la matriz de permisos para una combinación específica de archivo y usuario.
     *
     * Prioridad de resolución:
     * 1. Regla específica para el usuario sobre ese archivo (target_type='user', target_id=$userId)
     * 2. Reglas para los grupos a los que pertenece el usuario sobre ese archivo (target_type='group')
     * 3. Regla por defecto de ese archivo (target_type='all')
     * 4. Política general de Nextcloud Secure Office (SecurityConfigService)
     */
    public function evaluatePermissions(int $fileId, ?string $userId): array {
        $userId = ($userId === null || $userId === '') ? 'anonymous' : $userId;

        // Comprobación de administrador: los administradores de Nextcloud siempre tienen acceso completo
        $isAdmin = ($userId !== 'anonymous') && $this->groupManager->isAdmin($userId);

        $globalExport = $this->configService->isUserAllowedToExport($userId);
        $globalPrint = $this->configService->isUserAllowedToPrint($userId);
        $globalCopy = $this->configService->isUserAllowedToCopy($userId);
        $globalDownload = $this->configService->isUserAllowedToDownloadNative($userId);
        $globalClassification = $this->configService->getEnsClassification();
        $globalWatermark = $this->configService->getWatermarkTemplate();

        if ($isAdmin) {
            return [
                'can_export' => true,
                'can_print' => true,
                'can_copy' => true,
                'can_download' => true,
                'classification' => $globalClassification,
                'watermark_template' => $globalWatermark,
                'rule_source' => 'admin_override',
            ];
        }

        if ($fileId <= 0) {
            return [
                'can_export' => $globalExport,
                'can_print' => $globalPrint,
                'can_copy' => $globalCopy,
                'can_download' => $globalDownload,
                'classification' => $globalClassification,
                'watermark_template' => $globalWatermark,
                'rule_source' => 'global_policy',
            ];
        }

        // Obtener grupos del usuario
        $userGroups = [];
        if ($userId !== 'anonymous') {
            $userObj = $this->userManager->get($userId);
            if ($userObj !== null) {
                $userGroups = $this->groupManager->getUserGroupIds($userObj);
            }
        }

        // Cargar todas las reglas configuradas para este archivo
        $fileRules = $this->getRulesForFile($fileId);
        if (empty($fileRules)) {
            return [
                'can_export' => $globalExport,
                'can_print' => $globalPrint,
                'can_copy' => $globalCopy,
                'can_download' => $globalDownload,
                'classification' => $globalClassification,
                'watermark_template' => $globalWatermark,
                'rule_source' => 'global_policy',
            ];
        }

        $userRule = null;
        $matchedGroupRules = [];
        $allRule = null;

        foreach ($fileRules as $rule) {
            $type = $rule['target_type'];
            $tid = $rule['target_id'];

            if ($type === 'user' && $tid === $userId) {
                $userRule = $rule;
            } elseif ($type === 'group' && in_array($tid, $userGroups, true)) {
                $matchedGroupRules[] = $rule;
            } elseif ($type === 'all' || $tid === '*') {
                $allRule = $rule;
            }
        }

        // Resolución de permisos DLP
        $canExport = $this->resolveDirective(
            $userRule ? (int)$userRule['dlp_export'] : 0,
            $this->extractGroupDirectives($matchedGroupRules, 'dlp_export'),
            $allRule ? (int)$allRule['dlp_export'] : 0,
            $globalExport
        );

        $canPrint = $this->resolveDirective(
            $userRule ? (int)$userRule['dlp_print'] : 0,
            $this->extractGroupDirectives($matchedGroupRules, 'dlp_print'),
            $allRule ? (int)$allRule['dlp_print'] : 0,
            $globalPrint
        );

        $canCopy = $this->resolveDirective(
            $userRule ? (int)$userRule['dlp_copy'] : 0,
            $this->extractGroupDirectives($matchedGroupRules, 'dlp_copy'),
            $allRule ? (int)$allRule['dlp_copy'] : 0,
            $globalCopy
        );

        $canDownload = $this->resolveDirective(
            $userRule ? (int)$userRule['dlp_download'] : 0,
            $this->extractGroupDirectives($matchedGroupRules, 'dlp_download'),
            $allRule ? (int)$allRule['dlp_download'] : 0,
            $globalDownload
        );

        // Clasificación personalizada
        $classification = $globalClassification;
        if ($userRule !== null && !empty($userRule['classification'])) {
            $classification = (string)$userRule['classification'];
        } elseif ($allRule !== null && !empty($allRule['classification'])) {
            $classification = (string)$allRule['classification'];
        } elseif (!empty($matchedGroupRules)) {
            foreach ($matchedGroupRules as $gr) {
                if (!empty($gr['classification'])) {
                    $classification = (string)$gr['classification'];
                    break;
                }
            }
        }

        // Marca de agua personalizada
        $watermark = $globalWatermark;
        if ($userRule !== null && !empty($userRule['watermark_custom'])) {
            $watermark = (string)$userRule['watermark_custom'];
        } elseif ($allRule !== null && !empty($allRule['watermark_custom'])) {
            $watermark = (string)$allRule['watermark_custom'];
        }

        $source = 'file_policy';
        if ($userRule !== null) {
            $source = 'file_user_rule';
        } elseif (!empty($matchedGroupRules)) {
            $source = 'file_group_rule';
        } elseif ($allRule !== null) {
            $source = 'file_default_rule';
        }

        return [
            'can_export' => $canExport,
            'can_print' => $canPrint,
            'can_copy' => $canCopy,
            'can_download' => $canDownload,
            'classification' => $classification,
            'watermark_template' => $watermark,
            'rule_source' => $source,
        ];
    }

    /**
     * Búsqueda de archivos en oc_filecache para autocompletado y selección en la UI.
     */
    public function searchFiles(string $pattern, int $limit = 20): array {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return [];
        }

        $qb = $this->db->getQueryBuilder();
        $qb->select('fileid', 'path', 'name', 'mimetype', 'size')
            ->from('filecache')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->like('path', $qb->createNamedParameter('files/%')),
                    $qb->expr()->notLike('path', $qb->createNamedParameter('files_encryption/%')),
                    $qb->expr()->neq('mimetype', $qb->createNamedParameter('httpd/unix-directory')),
                    $qb->expr()->orX(
                        $qb->expr()->like('name', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($pattern) . '%')),
                        $qb->expr()->like('path', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($pattern) . '%'))
                    )
                )
            )
            ->orderBy('mtime', 'DESC')
            ->setMaxResults($limit);

        $files = $qb->executeQuery()->fetchAllAssociative();
        return array_values(array_filter($files, function(array $f): bool {
            $p = (string)($f['path'] ?? '');
            return str_starts_with($p, 'files/') && !str_starts_with($p, 'files_encryption/');
        }));
    }

    private function extractGroupDirectives(array $groupRules, string $key): array {
        $directives = [];
        foreach ($groupRules as $rule) {
            $val = isset($rule[$key]) ? (int)$rule[$key] : 0;
            if ($val !== 0) {
                $directives[] = $val;
            }
        }
        return $directives;
    }

    /**
     * Resuelve el valor final de un permiso según el orden de jerarquía:
     * 1. Usuario específico (1 => true, -1 => false, 0 => continuar)
     * 2. Grupos asociados (si hay conflicto de grupo, el bloqueo -1 prevalece por principio ENS de seguridad por defecto)
     * 3. Regla por defecto de archivo (1 => true, -1 => false, 0 => continuar)
     * 4. Política general de Secure Office
     */
    private function resolveDirective(int $userVal, array $groupVals, int $allVal, bool $globalFallback): bool {
        if ($userVal === 1) {
            return true;
        }
        if ($userVal === -1) {
            return false;
        }

        if (!empty($groupVals)) {
            // Si algún grupo impone bloqueo explícito (-1), prevalece la restricción de seguridad
            if (in_array(-1, $groupVals, true)) {
                return false;
            }
            if (in_array(1, $groupVals, true)) {
                return true;
            }
        }

        if ($allVal === 1) {
            return true;
        }
        if ($allVal === -1) {
            return false;
        }

        return $globalFallback;
    }
}
