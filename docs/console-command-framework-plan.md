# Console Command Framework Plan

## Goal

Create a core console-command framework for Zen Cart that:

- provides a stable CLI runtime and command-discovery system
- supports both core commands and plugin-provided commands
- keeps command execution decoupled from admin-page and web-request flows
- makes future command authoring predictable and testable
- does not rely on any third-party console framework or command library

The framework itself should live in core.

Plugin commands should extend the framework, not provide it.

## Non-Goal

This framework should not depend on third-party console tooling.

That means:

- no Symfony Console dependency
- no Laravel Artisan-style dependency
- no external CLI parsing package as the foundation

The runtime, command registry, discovery layer, input parsing, and output helpers should all be implemented in Zen Cart core.

This is a design constraint, not a temporary preference.

## Why Core, Not Plugin

The base command system is infrastructure. It needs to exist before any plugin can participate.

Reasons to keep the framework in core:

- plugin commands need a stable bootstrap path
- command discovery needs a trusted central registry
- input/output handling and exit codes should be consistent across all commands
- one plugin should not define the runtime that every other plugin depends on

Plugins should be able to register commands into the core runtime through a documented convention.

## High-Level Architecture

The first version should be intentionally small and boring.

It should also be fully first-party.

Suggested core pieces:

- `bin/zencart` or `zc_cli.php`
  - CLI entrypoint
- `includes/classes/Console/ConsoleKernel.php`
  - bootstraps the console runtime
- `includes/classes/Console/ConsoleCommand.php`
  - base command contract
- `includes/classes/Console/CommandRegistry.php`
  - holds registered commands
- `includes/classes/Console/CommandResolver.php`
  - resolves a command name or alias
- `includes/classes/Console/ConsoleInput.php`
  - parsed args/options accessor
- `includes/classes/Console/ConsoleOutput.php`
  - stdout/stderr helpers
- `includes/classes/Console/PluginCommandDiscovery.php`
  - discovers commands from installed plugins

## Command Contract

The first command API should be minimal.

Each command should define:

- a unique name, like `cache:clear`
- a short description
- optional aliases
- optional argument and option definitions
- a `handle(ConsoleInput $input, ConsoleOutput $output): int` method

The return value should be the shell exit code.

Argument parsing and output formatting should stay lightweight and homegrown in version 1.

This plan assumes they remain first-party in later iterations too.

## Command Discovery

The framework should support two sources of commands:

- core commands
- plugin commands

### Core Commands

Core commands should live in a known namespace and directory, for example:

- `includes/classes/Console/Commands/`

These should be registered directly by the kernel.

### Plugin Commands

Plugin command discovery should use a convention first, with optional metadata later.

Recommended first convention:

- `zc_plugins/<Plugin>/<Version>/Console/commands.php`

That file returns an array of command classes or command-definition objects.

This keeps plugin support simple and avoids forcing immediate changes to plugin manifests.

## Plugin Integration Strategy

Version 1 should not require manifest changes.

Instead:

- core loads installed plugins
- discovery checks for `Console/commands.php`
- any commands found there are registered

Later, if useful, plugin manifests can optionally support fields like:

- `consoleCommands`
- `consoleNamespace`
- `consoleBootstrap`

Those should be phase-2 enhancements, not first-version requirements.

## Runtime Flow

The expected execution path is:

1. CLI entrypoint boots Zen Cart in command mode.
2. Kernel registers built-in commands.
3. Kernel discovers and registers plugin commands.
4. Resolver finds the requested command.
5. Input and output objects are passed to the command.
6. Command returns an exit code.
7. The runtime exits with that code.

## Built-In Commands For First Version

The first useful core commands should be:

- `list`
  - show available commands
- `help <command>`
  - show command usage/details

Useful follow-up built-ins:

- `plugin:list`
- a safe demo command for framework validation

## Error Handling

The runtime should be defensive.

Requirements:

- one broken plugin command should not break all console usage
- duplicate command names should be detected clearly
- registration failures should be visible in error output
- command exceptions should produce a non-zero exit code

The `list` command should still work even if some plugin commands fail to load, though it should surface those failures clearly.

## Command Authoring Principles

Console commands should:

- use services and repositories directly where practical
- avoid depending on web-request globals
- avoid assuming admin-page rendering context
- be safe for unattended execution
- produce explicit exit codes and readable output

Commands should be small wrappers over domain logic where possible, not giant procedural scripts.

## Testing Strategy

The first framework rollout should include unit coverage for:

- command registration
- command resolution by name and alias
- duplicate-name conflicts
- plugin command discovery
- command execution and exit codes
- graceful handling of broken plugin command definitions

Later, feature-level CLI tests can be added once the runtime path is stable.

## Phased Rollout

### Phase 1

Build the core runtime:

- CLI entrypoint
- kernel
- registry
- resolver
- input/output wrappers
- `list` and `help`

### Phase 2

Add plugin command discovery:

- load installed plugins
- scan for `Console/commands.php`
- register plugin commands

### Phase 3

Add one core proof command and one plugin proof command.

This validates:

- end-to-end execution
- plugin registration
- collision/error handling

### Phase 4

Consider optional manifest metadata and richer argument/option schemas if the first version proves useful.

## Open Questions

- Should the entrypoint be `bin/zencart`, `zc_cli.php`, or both?
- Should command discovery read directly from plugin directories or from plugin-manager state only?
- How much argument parsing should version 1 support before bringing in more structure?
- Should command registration use only classes, or support lightweight array definitions too?

Question explicitly settled:

- the framework should remain first-party and should not be built on a third-party CLI package

## Recommended First Implementation

The first build target should be:

- `bin/zencart`
- `ConsoleKernel`
- `ConsoleCommand`
- `CommandRegistry`
- `CommandResolver`
- `ConsoleInput`
- `ConsoleOutput`
- core `list` command
- core `help` command
- plugin discovery from `zc_plugins/*/*/Console/commands.php`

That gives Zen Cart a practical, extensible CLI foundation without overcommitting to a large framework up front.
