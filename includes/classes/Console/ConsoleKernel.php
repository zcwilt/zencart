<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Console;

use Zencart\Console\Commands\HelpCommand;
use Zencart\Console\Commands\ListCommand;

class ConsoleKernel
{
    private CommandRegistry $registry;
    private CommandResolver $resolver;
    private bool $booted = false;

    /**
     * @var string[]
     */
    private array $bootWarnings = [];

    public function __construct(
        ?CommandRegistry $registry = null,
        ?PluginCommandDiscovery $pluginDiscovery = null
    ) {
        $this->registry = $registry ?? new CommandRegistry();
        $this->resolver = new CommandResolver($this->registry);
        $this->pluginDiscovery = $pluginDiscovery;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->registerCoreCommands();
        $this->registerPluginCommands();
        $this->booted = true;
    }

    public function run(ConsoleInput $input, ConsoleOutput $output): int
    {
        $this->boot();

        foreach ($this->bootWarnings as $warning) {
            $output->errorln('Warning: ' . $warning);
        }

        $command = $this->resolver->resolve($input);
        if ($command === null) {
            $requested = $input->getCommandName() ?? '(none)';
            $output->errorln('Unknown command: ' . $requested);
            $output->errorln('Run `php zc_cli.php list` to see available commands.');
            return 1;
        }

        if ($input->getCommandName() === null && $input->isHelpRequested()) {
            return $command->handle($input, $output);
        }

        if ($input->isHelpRequested() && $command->getName() !== 'help') {
            $helpInput = new ConsoleInput([$input->getScriptName(), 'help', $command->getName()]);
            $helpCommand = $this->registry->find('help');
            return $helpCommand?->handle($helpInput, $output) ?? 1;
        }

        return $command->handle($input, $output);
    }

    public function getRegistry(): CommandRegistry
    {
        $this->boot();
        return $this->registry;
    }

    private ?PluginCommandDiscovery $pluginDiscovery = null;

    private function registerCoreCommands(): void
    {
        $this->registry->register(new ListCommand($this->registry));
        $this->registry->register(new HelpCommand($this->registry));
    }

    private function registerPluginCommands(): void
    {
        if ($this->pluginDiscovery === null) {
            return;
        }

        foreach ($this->pluginDiscovery->discover() as $command) {
            try {
                $this->registry->register($command);
            } catch (\Throwable $exception) {
                $this->bootWarnings[] = $exception->getMessage();
            }
        }

        $this->bootWarnings = array_merge($this->bootWarnings, $this->pluginDiscovery->getErrors());
    }
}
