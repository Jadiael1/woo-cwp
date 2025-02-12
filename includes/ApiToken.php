<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class ApiToken
{
    public static function generate_token()
    {
        // Gera um identificador único e seguro
        $username = 'woo-cwp-user';
        $password = wp_generate_password(32, false);
        $basic_auth = base64_encode("$username:$password");

        // Tempo de expiração (5 minutos)
        $expiration = time() + (5 * 60);

        // Salva o token e a expiração no banco de dados
        update_option('woo_cwp_api_token', $password);
        update_option('woo_cwp_api_token_expiration', $expiration);

        return "Basic " . $basic_auth;
    }

    public static function validate_token(\WP_REST_Request $request)
    {
        $auth_header = $request->get_header('authorization');
        // error_log(json_encode($auth_header) . PHP_EOL, 3, WOO_CWP_PLUGIN_PATH . '/includes/log.json');
        
        // Obtém o token salvo
        $saved_token = get_option('woo_cwp_api_token', '');
        $expiration = get_option('woo_cwp_api_token_expiration', 0);

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
                // Token válido, remove-o para evitar reutilização
                delete_option('woo_cwp_api_token');
                delete_option('woo_cwp_api_token_expiration');

                return true;
            }
        }

        return false;
    }
}
