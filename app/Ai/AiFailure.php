<?php

namespace App\Ai;

use RuntimeException;

/** An AI request that did not work, with a message fit to show a school, and any tokens Google still counted */
class AiFailure extends RuntimeException
{
    public function __construct(string $message, public readonly array $tokens = ['input_tokens' => 0, 'output_tokens' => 0])
    {
        parent::__construct($message);
    }
}
