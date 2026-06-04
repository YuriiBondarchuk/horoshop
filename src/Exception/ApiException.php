<?php

declare(strict_types=1);

namespace App\Exception;

class ApiException extends \RuntimeException
{
    private array $details;

    public function __construct(string $message = "", int $code = 0, array $details = [])
    {
        parent::__construct($message, $code);

        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}

