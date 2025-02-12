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
        if (empty($params['api_url']) || empty($params['api_token'])) {
            return new \WP_REST_Response(['message' => 'URL da API e Token são obrigatórios!'], 400);
        }

        // Sanitiza os inputs para evitar XSS/injeção de código
        $api_url   = \WooCWP\Includes\SecureStorage::encrypt(esc_url_raw($params['api_url']));
        // echo "<pre>", var_dump($api_url), "</pre>"; exit;
        $api_token = \WooCWP\Includes\SecureStorage::encrypt(sanitize_text_field($params['api_token']));

        // Salva os dados nas opções do WordPress
        update_option('woo_cwp_api_url', $api_url);
        update_option('woo_cwp_api_token', $api_token);

        return new \WP_REST_Response(['message' => 'Configurações salvas com sucesso!'], 200);
    }
}
