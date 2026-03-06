<?php

namespace App\Http\Exceptions;

use DomainException;

class InvalidTransitionException extends DomainException
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly string $reason = 'Invalid workflow transition.'
    ) {
        parent::__construct($reason);
    }
}