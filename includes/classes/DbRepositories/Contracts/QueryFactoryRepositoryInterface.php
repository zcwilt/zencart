<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\DbRepositories\Contracts;

use queryFactory;

interface QueryFactoryRepositoryInterface
{
    public function __construct(?queryFactory $db = null);
}
