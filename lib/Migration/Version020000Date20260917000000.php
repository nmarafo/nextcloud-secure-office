<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version020000Date20260917000000 extends SimpleMigrationStep {

    #[\Override]
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('secure_office_audit')) {
            $table = $schema->createTable('secure_office_audit');
            $table->addColumn('id', 'bigint', [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('remote_ip', 'string', [
                'notnull' => false,
                'length' => 45,
            ]);
            $table->addColumn('file_id', 'bigint', [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('file_name', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('file_path', 'string', [
                'notnull' => false,
                'length' => 2000,
            ]);
            $table->addColumn('classification', 'string', [
                'notnull' => false,
                'length' => 128,
            ]);
            $table->addColumn('dlp_export_disabled', 'boolean', [
                'notnull' => false,
                'default' => true,
            ]);
            $table->addColumn('dlp_copy_disabled', 'boolean', [
                'notnull' => false,
                'default' => true,
            ]);
            $table->addColumn('dlp_print_disabled', 'boolean', [
                'notnull' => false,
                'default' => true,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['file_id'], 'sec_off_audit_fid_idx');
            $table->addIndex(['user_id'], 'sec_off_audit_uid_idx');
            $table->addIndex(['created_at'], 'sec_off_audit_time_idx');
        }

        return $schema;
    }
}