<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Driver\Postgres\Schema\PostgresTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Spiral\Database\Schema\AbstractColumn as SpiralAbstractColumn;
use Spiral\Database\Schema\AbstractTable as SpiralAbstractTable;
use Spiral\Database\Driver\Postgres\PostgresHandler as SpiralPostgresHandler;

class_exists(SpiralAbstractColumn::class);
class_exists(SpiralAbstractTable::class);

class PostgresHandler extends Handler
{
    /**
     * @inheritDoc
     */
    public function getSchema(string $table, string $prefix = null): AbstractTable
    {
        return new PostgresTable($this->driver, $table, $prefix ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getTableNames(): array
    {
        $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'
          AND table_type = 'BASE TABLE'";

        $tables = [];
        foreach ($this->driver->query($query) as $row) {
            $tables[] = $row['table_name'];
        }

        return $tables;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        return (bool)$this->driver->query(
            "SELECT COUNT(table_name) FROM information_schema.tables WHERE table_schema = 'public'
          AND table_type = 'BASE TABLE' AND table_name = ?",
            [$name]
        )->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseTable(SpiralAbstractTable $table): void
    {
        $this->driver->execute(
            "TRUNCATE TABLE {$this->driver->identifier($table->getName())}"
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws SchemaException
     */
    public function alterColumn(
        SpiralAbstractTable $table,
        SpiralAbstractColumn $initial,
        SpiralAbstractColumn $column
    ): void {
        if (!$initial instanceof PostgresColumn || !$column instanceof PostgresColumn) {
            throw new SchemaException('Postgres handler can work only with Postgres columns');
        }

        //Rename is separate operation
        if ($column->getName() !== $initial->getName()) {
            $this->renameColumn($table, $initial, $column);

            //This call is required to correctly built set of alter operations
            $initial->setName($column->getName());
        }

        //Postgres columns should be altered using set of operations
        $operations = $column->alterOperations($this->driver, $initial);
        if (empty($operations)) {
            return;
        }

        //Postgres columns should be altered using set of operations
        $query = sprintf(
            'ALTER TABLE %s %s',
            $this->identify($table),
            trim(implode(', ', $operations), ', ')
        );

        $this->run($query);
    }

    /**
     * @inheritdoc
     */
    protected function run(string $statement, array $parameters = []): int
    {
        if ($this->driver instanceof PostgresDriver) {
            // invaliding primary key cache
            $this->driver->resetPrimaryKeys();
        }

        return parent::run($statement, $parameters);
    }

    /**
     * @param AbstractTable  $table
     * @param AbstractColumn $initial
     * @param AbstractColumn $column
     */
    private function renameColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
    ): void {
        $statement = sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->identify($table),
            $this->identify($initial),
            $this->identify($column)
        );

        $this->run($statement);
    }
}
\class_alias(PostgresHandler::class, SpiralPostgresHandler::class, false);
