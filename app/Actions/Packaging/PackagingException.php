<?php

namespace App\Actions\Packaging;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Packing business rule violation with a stable machine-readable code. */
class PackagingException extends RuntimeException
{
    /** @param  array<string, mixed>  $context */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $context = [],
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => (object) $this->context,
        ], $this->status);
    }
}
