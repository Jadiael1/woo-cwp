<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class ApiToken
{
    public static function generate_token()
    {
        $username = 'woo-cwp-user';
        $tokenPassword = get_option('woo_cwp_api_token_basic', null);
        if($tokenPassword){
            $basic_auth = base64_encode("$username:$tokenPassword");
            return "Basic " . $basic_auth;
        }
        $password = wp_generate_password(32, false);
        $basic_auth = base64_encode("$username:$password");
        // Tempo de expiração (24 horas)
        $expiration = time() + (1440 * 60);
        update_option('woo_cwp_api_token_basic', $password);
        update_option('woo_cwp_api_token_basic_expiration', $expiration);
        return "Basic " . $basic_auth;
    }

    public static function validate_token(\WP_REST_Request $request)
    {
        $auth_header = $request->get_header('authorization');
        // Obtém o token salvo
        $saved_token = get_option('woo_cwp_api_token_basic', '');
        $expiration = get_option('woo_cwp_api_token_basic_expiration', 0);
        // Se expirou ou não existir, rejeita
        if (time() > $expiration || empty($saved_token)) {
            return false;
        }
        // Extrai credenciais do header
        if (preg_match('/Basic\s+(\S+)/', $auth_header, $matches)) {
            $decoded = base64_decode($matches[1]);
            list($user, $pass) = explode(':', $decoded, 2);
            // Verifica se a senha bate
            if ($pass === $saved_token) {
                // delete_option('woo_cwp_api_token_basic');
                // delete_option('woo_cwp_api_token_basic_expiration');
                return true;
            }
        }
        return false;
    }
}
