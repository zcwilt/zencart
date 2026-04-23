<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Console\Commands;

use Zencart\Console\ConsoleCommand;
use Zencart\Console\ConsoleInput;
use Zencart\Console\ConsoleOutput;

class ConfigGetCommand extends ConsoleCommand
{
    /**
     * @param null|callable(string): ?array<string, mixed> $configurationProvider
     */
    public function __construct(private $configurationProvider = null)
    {
    }

    public function getName(): string
    {
        return 'config:get';
    }

    public function getDescription(): string
    {
        return 'Show a single configuration value by key.';
    }

    public function getUsageLines(): array
    {
        return [
            'php zc_cli.php config:get <CONFIGURATION_KEY>',
        ];
    }

    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $key = strtoupper(trim((string)$input->getArgument(0, '')));
        if ($key === '') {
            $output->errorln('Missing required configuration key.');
            $output->errorln('Usage:');
            foreach ($this->getUsageLines() as $usageLine) {
                $output->errorln('  ' . $usageLine);
            }

            return 1;
        }

        if ($this->configurationProvider === null) {
            $output->errorln('Configuration lookup unavailable in the current CLI runtime.');
            return 1;
        }

        $row = ($this->configurationProvider)($key);
        if ($row === null) {
            $output->errorln('Configuration key not found: ' . $key);
            return 1;
        }

        $output->writeln('Configuration value:');
        $output->writeln(sprintf('  %-24s %s', (string)$row['configuration_key'], (string)$row['configuration_value']));

        return 0;
    }
}
