<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;


class PasswordGenerator
{
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const NUMBERS = '0123456789';
    private const SPECIAL = '';

    public static function generate(): string
    {
        try {
            // Garante um caractere de cada tipo
            $password = [
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                self::getRandomChar(self::UPPERCASE),     // 1 maiúscula
                self::getRandomChar(self::UPPERCASE),     // 1 maiúscula
                self::getRandomChar(self::NUMBERS),       // 1 número
                self::getRandomChar(self::NUMBERS),       // 1 número
                self::getRandomChar(self::SPECIAL),       // 1 especial
                self::getRandomChar(self::SPECIAL),       // 1 especial
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                self::getRandomChar(self::UPPERCASE),     // 1 maiúscula
            ];

            // Embaralha o array de caracteres
            shuffle($password);

            return implode('', $password);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Erro ao gerar senha: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    private static function getRandomChar(string $charSet): string
    {
        $length = strlen($charSet);
        $randomPosition = random_int(0, $length - 1);

        return $charSet[$randomPosition];
    }

    public static function validatePassword(string $password): bool
    {
        return match (true) {
            strlen($password) !== 10 => false,
            !preg_match('/[a-z]/', $password) => false,
            !preg_match('/[A-Z]/', $password) => false,
            !preg_match('/[0-9]/', $password) => false,
            substr_count(preg_replace('/[^!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', '', $password), '') - 1 < 2 => false,
            default => true
        };
    }
}
