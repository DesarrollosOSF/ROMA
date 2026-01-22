<?php

class ApiResponse
{
    public static function success($data = null, $message = 'Operación exitosa', $status = 200, array $extra = []): void
    {
        self::send($status, array_merge([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $extra));
    }

    public static function error($message = 'Error interno del servidor', $status = 500, array $extra = []): void
    {
        self::send($status, array_merge([
            'success' => false,
            'message' => $message,
        ], $extra));
    }

    public static function validationError(array $errors, $message = 'Datos inválidos'): void
    {
        self::send(422, [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    private static function send(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }
}

