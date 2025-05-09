<?php

namespace App\Strategies;

interface PasswordValidationStrategy
{
    public function validate(string $password): ?string;
}
