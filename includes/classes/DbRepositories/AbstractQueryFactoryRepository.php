<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\DbRepositories;

use queryFactory;
use Zencart\DbRepositories\Contracts\QueryFactoryRepositoryInterface;

abstract class AbstractQueryFactoryRepository implements QueryFactoryRepositoryInterface
{
    protected queryFactory $db;

    public function __construct(?queryFactory $db = null)
    {
        $this->db = $db ?? $this->resolveGlobalDb();
    }

    protected function fetchAllRows(string $sql): array
    {
        $rows = [];
        $result = $this->db->Execute($sql);
        foreach ($result as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Backward-compatible alias retained for older repository call sites.
     */
    protected function fetchAll(string $sql): array
    {
        return $this->fetchAllRows($sql);
    }

    protected function fetchFirstRow(string $sql): ?array
    {
        $result = $this->db->Execute($sql);
        if ($result->EOF) {
            return null;
        }

        return $result->fields;
    }

    protected function resolveGlobalDb(): queryFactory
    {
        global $db;
        return $db;
    }
}
