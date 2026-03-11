<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public array $mancanti = [],
        string $message = "Giacenza insufficiente per completare l'operazione"
    ) {
        parent::__construct($message);
    }
}
