<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Query\Traits\TokenTrait;
use Cycle\Database\Query\Traits\WhereTrait;
use Spiral\Database\Query\DeleteQuery as SpiralDeleteQuery;

/**
 * Update statement builder.
 */
class DeleteQuery extends ActiveQuery
{
    use TokenTrait;
    use WhereTrait;

    /** @var string */
    protected $table;

    /**
     * @param string $table Associated table name.
     * @param array  $where Initial set of where rules specified as array.
     */
    public function __construct(string $table = null, array $where = [])
    {
        $this->table = $table ?? '';

        if ($where !== []) {
            $this->where($where);
        }
    }

    /**
     * Change target table.
     *
     * @param string $into Table name without prefix.
     * @return self
     */
    public function from(string $into): DeleteQuery
    {
        $this->table = $into;

        return $this;
    }

    /**
     * Alias for execute method();
     *
     * @return int
     */
    public function run(): int
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        return $this->driver->execute($queryString, $params->getParameters());
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return CompilerInterface::DELETE_QUERY;
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return [
            'table' => $this->table,
            'where' => $this->whereTokens
        ];
    }
}
\class_alias(DeleteQuery::class, SpiralDeleteQuery::class, false);
