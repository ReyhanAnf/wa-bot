<?php

namespace App\Contracts;

interface CommandInterface
{
    /**
     * Handle the incoming command.
     *
     * @param array $args The arguments passed with the command.
     * @param string $waNumber The sender's WhatsApp number.
     * @return string|array The response message or array with 'message' and 'source'.
     */
    public function handle(array $args, string $waNumber): string|array;
}
