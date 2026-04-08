<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Console;

abstract class ConsoleCommand
{
    abstract public function getName(): string;

    abstract public function getDescription(): string;

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getUsageLines(): array
    {
        return ['php zc_cli.php ' . $this->getName()];
    }

    abstract public function handle(ConsoleInput $input, ConsoleOutput $output): int;
}
