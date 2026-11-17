<?php

namespace Framework\Auth;

/**
 * @since 2024
 * @author Iago Oliveira <prog.iago.oliveira@gmail.com>
 */
class General
{
    static $URL = 'http://localhost:8080';

    /**
     * Chave de assinatura dos tokens (HS256), lida de JWT_SECRET no .env.
     * O php-jwt 7 recusa chaves HS256 com menos de 32 bytes.
     */
    public static function secretJwt(): string
    {
        $secret = (string) ($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '');
        if (strlen($secret) < 32) {
            throw new \RuntimeException('Defina JWT_SECRET no .env com pelo menos 32 caracteres.');
        }

        return $secret;
    }
}