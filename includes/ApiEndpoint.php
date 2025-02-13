<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class ApiEndpoint
{
    public static function register_routes()
    {
        register_rest_route(
            'woo-cwp/v1',
            '/save-settings/',
            [
                'methods'  => 'POST',
                'callback' => [self::class, 'handle_request'],
                'permission_callback' => [\WooCWP\Includes\ApiToken::class, 'validate_token'],
            ]
        );
    }

    public static function handle_request(\WP_REST_Request $request)
    {
        // Obtém os parâmetros da requisição
        $params = $request->get_json_params();

        // Valida os campos obrigatórios
        if (empty($params['api_url']) && empty($params['api_token']) && empty($params['api_ip'])) {
            return new \WP_REST_Response(['message' => 'URL da API, IP do servidor CWP ou Token são obrigatórios!'], 400);
        }

        // echo "<pre>", var_dump($api_url), "</pre>"; exit;

        // Salva os dados nas opções do WordPress
        // Sanitiza os inputs para evitar XSS/injeção de código
        if (!empty($params['api_url'])) {
            $api_url   = \WooCWP\Includes\SecureStorage::encrypt(esc_url_raw($params['api_url']));
            update_option('woo_cwp_api_url', $api_url);
        }
        if (!empty($params['api_token'])) {
            $api_token = \WooCWP\Includes\SecureStorage::encrypt(sanitize_text_field($params['api_token']));
            update_option('woo_cwp_api_token', $api_token);
        }
        if (!empty($params['api_ip']) && filter_var($params['api_ip'], FILTER_VALIDATE_IP)) {
            $api_ip = \WooCWP\Includes\SecureStorage::encrypt($params['api_ip']);
            update_option('woo_cwp_api_ip', $api_ip);
        }

        return new \WP_REST_Response(['message' => 'Configurações salvas com sucesso!'], 200);
    }
}
