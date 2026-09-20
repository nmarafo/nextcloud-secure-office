<?php

declare(strict_types=1);

namespace OCA\SecureOffice\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version030000Date20260920000000 extends SimpleMigrationStep {

    #[\Override]
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('secure_office_audit')) {
            $table = $schema->getTable('secure_office_audit');
            if (!$table->hasColumn('action')) {
                $table->addColumn('action', 'string', [
                    'notnull' => false,
                    'length' => 32,
                    'default' => 'COLLABORA_VIEW',
                ]);
                $table->addIndex(['action'], 'sec_off_audit_act_idx');
            }
        }

        return $schema;
    }
}
