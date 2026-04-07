<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

class SplitPageResultsFactory
{
    public function create(
        int &$currentPage,
        int $resultsPerPage,
        string &$query,
        int &$queryNumRows,
        string $letterGroupColumn = '',
        int $letterGroupLength = 0
    ): object {
        return new \splitPageResults(
            $currentPage,
            $resultsPerPage,
            $query,
            $queryNumRows,
            $letterGroupColumn,
            $letterGroupLength
        );
    }
}
