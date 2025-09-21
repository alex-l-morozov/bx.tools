<?php

namespace Campus\Services\Tools;

use Bitrix\Main\Config\Option;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Класс для работы с JWT (JSON Web Tokens) в Bitrix.
 */
class JwtService
{
    private string $secretKey;
    private string $algorithm;
    private int $tokenLifetime;

    /**
     * Конструктор класса.
     *
     * @param string $secretKey Секретный ключ для подписи JWT.
     * @param string $algorithm Алгоритм подписи (по умолчанию 'HS256').
     * @param int $tokenLifetime Срок действия токена в секундах (по умолчанию 3600 секунд).
     */
    public function __construct(string $secretKey, string $algorithm = 'HS256', int $tokenLifetime = 1200)
    {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
        $this->tokenLifetime = Option::get('campus.services','session_time', $tokenLifetime);;
    }

    /**
     * Генерация JWT токена.
     *
     * @param array $data Данные, которые будут храниться в токене.
     * @return string Сгенерированный JWT токен.
     */
    public function generateToken(array $data): string
    {
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'],
            'aud' => $_SERVER['HTTP_HOST'],
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $this->tokenLifetime,
            'data' => $data
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Проверка JWT токена.
     *
     * @param string $token Токен для проверки.
     * @return array|null Декодированные данные из токена или null, если токен недействителен.
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array)$decoded->data;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Авторизация пользователя по JWT токену.
     */
    public function authorizeUserByToken(): void
    {
        $jwt = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if ($jwt) {
            $data = $this->validateToken($jwt);
            if ($data && isset($data['userId'])) {
                global $USER;
                $USER->Authorize($data['userId']);
            } else {
                $this->unauthorizedResponse("Invalid token.");
            }
        } else {
            $this->unauthorizedResponse("JWT token is required.");
        }
    }

    /**
     * Возвращает ответ при неавторизованном доступе.
     *
     * @param string $message Сообщение об ошибке.
     */
    private function unauthorizedResponse(string $message): void
    {
        header('HTTP/1.0 401 Unauthorized');
        echo $message;
        die();
    }
}
