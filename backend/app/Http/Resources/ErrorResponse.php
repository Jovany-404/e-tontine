<?php

namespace App\Http\Resources;

class ErrorResponse
{
    public static function fromException(\Exception $exception): array
    {
        return [
            'success' => false,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ];
    }
}
