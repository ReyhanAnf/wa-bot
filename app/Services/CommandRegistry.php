<?php

namespace App\Services;

use App\Contracts\CommandInterface;
use Illuminate\Support\Facades\Log;

class CommandRegistry
{
    protected array $commands = [];

    /**
     * Register a command.
     *
     * @param string $keyword The command keyword (e.g., '/menu').
     * @param string $class The class name implementing CommandInterface.
     */
    public function register(string $keyword, string $class): void
    {
        $this->commands[$keyword] = $class;
    }

    /**
     * Get the command instance for a given keyword.
     *
     * @param string $keyword
     * @return CommandInterface|null
     */
    public function getCommand(string $keyword): ?CommandInterface
    {
        if (!isset($this->commands[$keyword])) {
            return null;
        }

        $class = $this->commands[$keyword];

        if (!class_exists($class)) {
            Log::error("Command class $class not found for keyword $keyword");
            return null;
        }

        return app($class);
    }

    /**
     * Get all registered commands.
     *
     * @return array
     */
    public function getAllCommands(): array
    {
        return $this->commands;
    }
}
