<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version040000Date20260925000000 extends SimpleMigrationStep {

    #[\Override]
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('secure_office_file_rules')) {
            $table = $schema->createTable('secure_office_file_rules');
            $table->addColumn('id', 'bigint', [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
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
            $table->addColumn('target_type', 'string', [
                'notnull' => true,
                'length' => 16,
                'default' => 'user',
            ]);
            $table->addColumn('target_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('dlp_export', 'smallint', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('dlp_print', 'smallint', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('dlp_copy', 'smallint', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('dlp_download', 'smallint', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('classification', 'string', [
                'notnull' => false,
                'length' => 128,
            ]);
            $table->addColumn('watermark_custom', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('created_by', 'string', [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['file_id'], 'sec_off_frule_fid_idx');
            $table->addIndex(['file_id', 'target_type', 'target_id'], 'sec_off_frule_lookup_idx');
            $table->addIndex(['target_id'], 'sec_off_frule_tid_idx');
        }

        return $schema;
    }
}
