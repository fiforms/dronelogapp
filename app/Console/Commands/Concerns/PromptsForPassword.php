<?php

namespace App\Console\Commands\Concerns;

trait PromptsForPassword
{
    /**
     * Prompt for a password with echo disabled.
     *
     * Uses stty directly to avoid a Symfony Console bug on some Linux terminals
     * where the generated stty command contains an unmatched quote, causing
     * "stty: invalid argument" and the password being printed in plain text.
     */
    private function askPassword(string $prompt): string
    {
        $this->output->write("<info>{$prompt}:</info> ");
        shell_exec('stty -echo');
        $value = rtrim(fgets(STDIN), PHP_EOL);
        shell_exec('stty echo');
        $this->newLine();
        return $value;
    }
}
