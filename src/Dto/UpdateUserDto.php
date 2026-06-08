<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserDto
{
    #[Assert\NotBlank]
    public int $id;

    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    public string $login;

    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    public string $pass;

    #[Assert\NotBlank]
    #[Assert\Length(max: 15)]
    public string $phone;
}

