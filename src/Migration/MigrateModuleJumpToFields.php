<?php

declare(strict_types=1);

/*
 * This file is part of Contao Diveclub Bundle.
 *
 * (c) Eckhard Becker 2026 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class MigrateModuleJumpToFields extends AbstractMigration
{
    private const FIELD_BY_MODULE_TYPE = [
        'dc_student_courses' => 'courseProgressJumpTo',
        'dc_course_event_reader' => 'courseConfirmationJumpTo',
        'dc_tank_check' => 'tankConfirmationJumpTo',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Contao Diveclub Bundle: Migrate module jumpTo fields';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_module');
        $columnNames = array_map(
            static fn($column): string => strtolower($column->getName()),
            $columns,
        );

        if (!\in_array('jumpto', $columnNames, true)) {
            return false;
        }

        foreach (self::FIELD_BY_MODULE_TYPE as $moduleType => $field) {
            if (!\in_array(strtolower($field), $columnNames, true)) {
                return false;
            }

            $count = $this->connection->fetchOne(
                'SELECT COUNT(*) FROM tl_module WHERE type = ? AND jumpTo > 0',
                [$moduleType],
            );

            if ((int)$count > 0) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $migratedRecords = 0;

        foreach (self::FIELD_BY_MODULE_TYPE as $moduleType => $field) {
            $migratedRecords += $this->connection->executeStatement(
                \sprintf(
                    'UPDATE tl_module SET %1$s = CASE WHEN %1$s = 0 THEN jumpTo ELSE %1$s END, jumpTo = 0 WHERE type = ? AND jumpTo > 0',
                    $field,
                ),
                [$moduleType],
            );
        }

        return new MigrationResult(
            true,
            \sprintf('%d module redirect(s) migrated to dedicated jumpTo fields.', $migratedRecords),
        );
    }
}
