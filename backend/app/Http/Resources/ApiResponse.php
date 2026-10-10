<?php

namespace App\Http\Resources;

class ApiResponse
{
    public static function success(string $message, $data = null, int $code = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    public static function error(string $message, $data = null, int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => $data,
        ];
    }
}
