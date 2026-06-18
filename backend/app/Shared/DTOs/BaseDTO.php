<?php

namespace App\Shared\DTOs;

abstract class BaseDTO
{
    /**
     * Convierte el DTO a un array asociativo.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
