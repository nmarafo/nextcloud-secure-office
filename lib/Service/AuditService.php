<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Service;

use OCP\IDBConnection;

class AuditService {
    public const TABLE_NAME = 'secure_office_audit';

    public function __construct(
        private IDBConnection $db,
    ) {
    }

    public function recordAccess(
        string $userId,
        string $remoteIp,
        int $fileId,
        string $fileName,
        string $filePath,
        string $classification,
        bool $dlpExport,
        bool $dlpCopy,
        bool $dlpPrint,
        string $action = 'COLLABORA_VIEW',
    ): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert(self::TABLE_NAME)
            ->values([
                'user_id' => $qb->createNamedParameter($userId),
                'remote_ip' => $qb->createNamedParameter($remoteIp),
                'file_id' => $qb->createNamedParameter($fileId),
                'file_name' => $qb->createNamedParameter($fileName),
                'file_path' => $qb->createNamedParameter($filePath),
                'classification' => $qb->createNamedParameter($classification),
                'action' => $qb->createNamedParameter($action),
                'dlp_export_disabled' => $qb->createNamedParameter($dlpExport ? 1 : 0),
                'dlp_copy_disabled' => $qb->createNamedParameter($dlpCopy ? 1 : 0),
                'dlp_print_disabled' => $qb->createNamedParameter($dlpPrint ? 1 : 0),
                'created_at' => $qb->createNamedParameter(
                    (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')
                ),
            ])
            ->executeStatement();
    }

    public function getRecentAuditEntries(int $limit = 50): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->orderBy('id', 'DESC')
            ->setMaxResults($limit);

        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function countEntries(): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('id', 'total'))
            ->from(self::TABLE_NAME);

        $row = $qb->executeQuery()->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    public function exportCsv(): string {
        $entries = $this->getRecentAuditEntries(1000);
        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return '';
        }

        // CSV Header
        fputcsv($output, [
            'ID',
            'Timestamp (UTC)',
            'User ID',
            'Remote IP',
            'File ID',
            'File Name',
            'File Path',
            'Action / Type',
            'ENS Classification',
            'DLP Disable Export',
            'DLP Disable Copy',
            'DLP Disable Print',
        ]);

        foreach ($entries as $row) {
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $row['user_id'],
                $row['remote_ip'],
                $row['file_id'],
                $row['file_name'],
                $row['file_path'],
                $row['action'] ?? 'COLLABORA_VIEW',
                $row['classification'],
                $row['dlp_export_disabled'] ? 'YES' : 'NO',
                $row['dlp_copy_disabled'] ? 'YES' : 'NO',
                $row['dlp_print_disabled'] ? 'YES' : 'NO',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }
}