<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;


class PasswordGenerator
{
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const NUMBERS = '0123456789';
    private const SPECIAL = '';

    private static function getRandomChar(string $charSet): string
    {
        $length = strlen($charSet);
        $randomPosition = random_int(0, $length - 1);
        return $charSet[$randomPosition];
    }

    public static function generate(): string
    {
        try {
            // Garante um caractere de cada tipo
            $password = [
                self::getRandomChar(self::UPPERCASE),     // 1 maiúscula
                self::getRandomChar(self::UPPERCASE),     // 1 maiúscula
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                // self::getRandomChar(self::SPECIAL),       // 1 especial
                // self::getRandomChar(self::SPECIAL),       // 1 especial
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                self::getRandomChar(self::LOWERCASE),     // 1 minúscula
                self::getRandomChar(self::NUMBERS),       // 1 número
                self::getRandomChar(self::NUMBERS),       // 1 número
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
}
