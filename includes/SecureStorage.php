<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class SecureStorage
{
    private static $encryption_key;

    public static function getEncryptionKey()
    {
        if (!isset(self::$encryption_key)) {
            $env_key = getenv('CWP_WOO_KEY');
            self::$encryption_key = !empty($env_key) && $env_key !== false && $env_key !== null ? $env_key : 'cRYFNnD2I6tI/w4lMfvY8LLxlEXRmx53/Jt/DShCUMw=';
        }
    }

    public static function encrypt($data)
    {
        if (empty($data)) {
            return null;
        }
        if (empty($env_key) || $env_key === false || $env_key === null) {
            self::getEncryptionKey();
        }
        $key = hash('sha256', self::$encryption_key, true);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($data)
    {
        if (empty($data)) {
            return null;
        }
        if (empty($env_key) || $env_key === false || $env_key === null) {
            self::getEncryptionKey();
        }
        $key = hash('sha256', self::$encryption_key, true);
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}
