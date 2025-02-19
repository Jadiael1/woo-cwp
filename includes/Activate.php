<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class Activate
{
    private function __construct() {}

    public static function activate()
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Este plugin requer o WooCommerce ativo. Por favor, instale e ative o WooCommerce.', 'cwp-woo'),
                __('Erro de dependência', 'cwp-woo'),
                ['back_link' => true]
            );
        }
        if (!file_exists(WOO_CWP_LOG_DIR)) {
            wp_mkdir_p(WOO_CWP_LOG_DIR);
        }
        self::createTable();
        if (is_admin()) {
            flush_rewrite_rules();
        }
    }

    private static function createTable() {
        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'cwp_woo_status';
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $nome_tabela (
            id INTEGER NOT NULL AUTO_INCREMENT,
            login_cwp VARCHAR(255) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
    
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
