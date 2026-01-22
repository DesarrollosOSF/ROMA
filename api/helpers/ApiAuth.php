<?php

require_once __DIR__ . '/ApiResponse.php';
require_once __DIR__ . '/../../models/Usuario.php';

class ApiAuth
{
    public static function allowCors(): void
    {
        header('Access-Control-Allow-Origin: ' . API_ALLOWED_ORIGINS);
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    }

    public static function handlePreflight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    public static function generateToken(array $user, int $ttl = API_TOKEN_EXPIRATION): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $issuedAt = time();
        $payload = [
            'sub' => $user['id_usuario'],
            'email' => $user['email'],
            'rol' => $user['rol'],
            'iat' => $issuedAt,
            'exp' => $issuedAt + $ttl,
        ];

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, API_TOKEN_SECRET, true));

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }

    public static function requireUser(): array
    {
        $token = self::getBearerToken();

        if (!$token) {
            ApiResponse::error('Token no proporcionado', 401);
        }

        $payload = self::validateToken($token);

        if (!$payload) {
            ApiResponse::error('Token inválido o expirado', 401);
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorId($payload['sub']);

        if (!$usuario || !(int)$usuario['activo']) {
            ApiResponse::error('Usuario no válido o inactivo', 401);
        }

        return $usuario;
    }

    public static function ensureRole(array $usuario, array $rolesPermitidos): void
    {
        if (!in_array($usuario['rol'], $rolesPermitidos, true)) {
            ApiResponse::error('No tiene permisos para esta operación', 403);
        }
    }

    public static function ensurePermission(string $rol, array $permisos): void
    {
        foreach ($permisos as $permiso) {
            if (Usuario::tienePermiso($rol, $permiso)) {
                return;
            }
        }

        ApiResponse::error('No tiene permisos para esta operación', 403);
    }

    public static function getPathSegments(): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        $apiIndex = array_search('api', $segments, true);
        if ($apiIndex === false) {
            return $segments;
        }

        return array_slice($segments, $apiIndex + 1);
    }

    public static function parseJsonBody(): array
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            return [];
        }

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::error('JSON inválido: ' . json_last_error_msg(), 400);
        }

        return $data;
    }

    private static function getBearerToken(): ?string
    {
        $headers = self::getAuthorizationHeader();
        if (!$headers) {
            return null;
        }

        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function getAuthorizationHeader(): ?string
    {
        if (isset($_SERVER['Authorization'])) {
            return trim($_SERVER['Authorization']);
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                return trim($requestHeaders['Authorization']);
            }
        }

        return null;
    }

    private static function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureProvided] = $parts;
        $signature = self::base64UrlEncode(hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, API_TOKEN_SECRET, true));

        if (!hash_equals($signature, $signatureProvided)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}

