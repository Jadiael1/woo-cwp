<?php

namespace WooCWP\Includes\AdminMenu;

defined('ABSPATH') || exit;

class AddAdminMenu
{
    private function __construct() {}

    public static function addAdminMenuContent()
    {
        $html_file = WOO_CWP_PLUGIN_PATH . '/includes/AdminMenu/assets/admin-page.html';
        if (file_exists($html_file)) {
            $fileContent = file_get_contents($html_file);
            $token = \WooCWP\Includes\ApiToken::generate_token();
            $apiURL = rest_url() . 'cwp-woo/v1/save-settings/';
            $isToken = (bool) get_option('woo_cwp_api_token') ?? false;
            $isUrl = (bool) get_option('woo_cwp_api_url') ?? false;
            $isIP = (bool) get_option('woo_cwp_api_ip') ?? false;
            $isIntermediateApiUrl = (bool) get_option('woo_cwp_intermediate_api_url') ?? false;

            $fileContent = str_replace("{{AUTHORIZATION}}", esc_html($token), $fileContent);
            $fileContent = str_replace("{{API_URL}}", esc_html($apiURL), $fileContent);
            $fileContent = str_replace("{{IS_TOKEN}}", esc_html($isToken), $fileContent);
            $fileContent = str_replace("{{IS_URL}}", esc_html($isUrl), $fileContent);
            $fileContent = str_replace("{{IS_IP}}", esc_html($isIP), $fileContent);
            $fileContent = str_replace("{{IS_INTERMEDIATE_API_URL}}", esc_html($isIntermediateApiUrl), $fileContent);

            echo $fileContent;
        } else {
            echo '<div class="wrap"><h1>Configurações do CWPWoo</h1><p>Arquivo de conteúdo não encontrado.</p></div>';
        }
    }

    public static function addAdminMenu()
    {
        add_menu_page(
            'CWPWoo Menu',                                                      // Título da página
            'CWPWoo',                                                           // Nome do menu
            'manage_options',                                                   // Permissão necessária
            'cwp-woo-menu',                                                     // Slug do menu
            array(self::class, 'addAdminMenuContent'),                          // Callback para exibir o conteúdo
            'dashicons-admin-generic',                                          // Ícone do menu
            56                                                                  // Posição do menu no painel
        );

        add_submenu_page(
            'cwp-woo-menu',
            'Geral',
            'Geral',
            'manage_options',
            'cwp-woo-menu',
            [self::class, 'addAdminMenuContent']
        );

        add_submenu_page(
            'cwp-woo-menu',                         // Slug do menu principal
            'Configurações',                        // Título da página
            'Configurações',                        // Nome do submenu
            'manage_options',                       // Permissão necessária
            'cwp-woo-settings',                     // Slug do submenu
            [self::class, 'addAdminMenuContent']    // Callback para exibir o conteúdo
        );
    }

    public static function addEnqueueScriptAdminMenu($hook)
    {
        $allowed_hooks = ['toplevel_page_cwp-woo-menu'];
        if (!in_array($hook, $allowed_hooks, true)) {
            return;
        }
        wp_enqueue_script(
            'cwp-woo-admin-js',
            WOO_CWP_PLUGIN_URL . '/includes/AdminMenu/assets/table.js',
            [],
            '1.0.0',
            true
        );

        $token = \WooCWP\Includes\ApiToken::generate_token();

        // Dados PHP a serem passados para o JavaScript
        $php_data = array(
            'url' => get_rest_url(null, '/cwp-woo/v1/get-orders'),
            'token'    => $token,
        );

        // Passa os dados para o script enfileirado
        wp_localize_script('cwp-woo-admin-js', 'cwpWooData', $php_data);
    }

    public static function handleRequest(\WP_REST_Request $request)
    {

        // Obtém os parâmetros da requisição
        $params = $request->get_json_params();
        // return $params;

        // Valida os campos obrigatórios
        if (!isset($params['api_url']) && !isset($params['api_token']) && !isset($params['api_ip']) && !isset($params['intermediate_api_url'])) {
            return new \WP_REST_Response(['message' => 'URL da API, IP do servidor CWP, Token ou url intermediadora. Pelo menos um é esperado!'], 400);
        }

        // Salva os dados nas opções do WordPress
        // Sanitiza os inputs para evitar XSS/injeção de código
        if (!empty($params['api_url'])) {
            $api_url   = \WooCWP\Includes\SecureStorage::encrypt(esc_url_raw($params['api_url']));
            update_option('woo_cwp_api_url', $api_url);
        } else if (isset($params['api_url']) && empty($params['api_url'])) {
            delete_option('woo_cwp_api_url');
        }
        if (!empty($params['api_token'])) {
            $api_token = \WooCWP\Includes\SecureStorage::encrypt(sanitize_text_field($params['api_token']));
            update_option('woo_cwp_api_token', $api_token);
        } else if (isset($params['api_token']) && empty($params['api_token'])) {
            delete_option('woo_cwp_api_token');
        }
        if (!empty($params['api_ip']) && filter_var($params['api_ip'], FILTER_VALIDATE_IP)) {
            $api_ip = \WooCWP\Includes\SecureStorage::encrypt($params['api_ip']);
            update_option('woo_cwp_api_ip', $api_ip);
        } else if (isset($params['api_ip']) && empty($params['api_ip'])) {
            delete_option('woo_cwp_api_ip');
        }
        if (!empty($params['intermediate_api_url'])) {
            $intermediate_api_url = \WooCWP\Includes\SecureStorage::encrypt($params['intermediate_api_url']);
            update_option('woo_cwp_intermediate_api_url', $intermediate_api_url);
        } else if (isset($params['intermediate_api_url']) && empty($params['intermediate_api_url'])) {
            delete_option('woo_cwp_intermediate_api_url');
        }

        return new \WP_REST_Response(['message' => 'Configurações salvas com sucesso!'], 200);
    }
}
