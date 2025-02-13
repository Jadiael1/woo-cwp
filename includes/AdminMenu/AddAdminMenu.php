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
            $apiURL = rest_url() . 'woo-cwp/v1/save-settings/';
            $isToken = (bool) get_option('woo_cwp_api_token') ?? false;
            $isUrl = (bool) get_option('woo_cwp_api_url') ?? false;
            $isIP = (bool) get_option('woo_cwp_api_ip') ?? false;

            $fileContent = str_replace("{{AUTHORIZATION}}", esc_html($token), $fileContent);
            $fileContent = str_replace("{{API_URL}}", esc_html($apiURL), $fileContent);
            $fileContent = str_replace("{{IS_TOKEN}}", esc_html($isToken), $fileContent);
            $fileContent = str_replace("{{IS_URL}}", esc_html($isUrl), $fileContent);
            $fileContent = str_replace("{{IS_IP}}", esc_html($isIP), $fileContent);

            echo $fileContent;
        } else {
            echo '<div class="wrap"><h1>Configurações do WooCWP</h1><p>Arquivo de conteúdo não encontrado.</p></div>';
        }
    }

    public static function addAdminMenu()
    {
        add_menu_page(
            'WooCWP Menu',                                                      // Título da página
            'WooCWP',                                                           // Nome do menu
            'manage_options',                                                   // Permissão necessária
            'woo-cwp-menu',                                                     // Slug do menu
            array(self::class, 'addAdminMenuContent'),                          // Callback para exibir o conteúdo
            'dashicons-admin-generic',                                          // Ícone do menu
            56                                                                  // Posição do menu no painel
        );

        add_submenu_page(
            'woo-cwp-menu',
            'Geral',
            'Geral',
            'manage_options',
            'woo-cwp-menu',
            [self::class, 'addAdminMenuContent']
        );

        add_submenu_page(
            'woo-cwp-menu',                         // Slug do menu principal
            'Configurações',                        // Título da página
            'Configurações',                        // Nome do submenu
            'manage_options',                       // Permissão necessária
            'woo-cwp-settings',                     // Slug do submenu
            [self::class, 'addAdminMenuContent']    // Callback para exibir o conteúdo
        );
    }
}
