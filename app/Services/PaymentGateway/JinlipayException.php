<?php

namespace App\Services\PaymentGateway;

use RuntimeException;

class JinlipayException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $upstreamCode,
        string $message,
        public readonly array $data = [],
        public readonly int $httpStatus = 400,
    ) {
        parent::__construct($message);
    }
}
