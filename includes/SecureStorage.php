<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class SecureStorage
{
    private static $encryption_key;

    public static function getEncryptionKey()
    {
        if (!isset(self::$encryption_key)) {
            $env_key = getenv('WOO_CWP_KEY');
            self::$encryption_key = !empty($env_key) && $env_key !== false && $env_key !== null ? $env_key : 'cRYFNnD2I6tI/w4lMfvY8LLxlEXRmx53/Jt/DShCUMw=';
        }
    }

    public static function encrypt($data)
    {
        if (empty($data)) {
            return null;
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

        $key = hash('sha256', self::$encryption_key, true);
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    public static function store_api_settings($api_url, $api_token)
    {
        update_option('woo_cwp_api_url', self::encrypt($api_url));
        update_option('woo_cwp_api_token', self::encrypt($api_token));
    }

    public static function get_api_settings()
    {
        return [
            'api_url' => self::decrypt(get_option('woo_cwp_api_url', '')),
            'api_token' => self::decrypt(get_option('woo_cwp_api_token', ''))
        ];
    }
}
