<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LoginDto
{
    #[Assert\NotBlank]
    public string $login;

    #[Assert\NotBlank]
    public string $pass;
}

