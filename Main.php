<?php

/**
 * Plugin Name: Woo-CWP
 * Description: Integração entre WooCommerce e CWP
 * Author:      Jadiael
 * Author URI:  https://fcx.ferreiracosta.com.br
 * License:     GNU General Public License v3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Version:     0.1.0
 * 
 * @package     woo-cwp
 */
if (!defined('WOO_CWP_PLUGIN_FILE')) {
    define('WOO_CWP_PLUGIN_FILE', __FILE__);
}
if (!defined('WOO_CWP_PLUGIN_PATH')) {
    define('WOO_CWP_PLUGIN_PATH', untrailingslashit(plugin_dir_path(WOO_CWP_PLUGIN_FILE)));
}
if (!defined('WOO_CWP_PLUGIN_URL')) {
    define('WOO_CWP_PLUGIN_URL', untrailingslashit(plugins_url('/', WOO_CWP_PLUGIN_FILE)));
}

defined('ABSPATH') || exit;

// Verifica se o arquivo MainIncludes.php existe antes de tentar carregá-lo
$mainIncludesFile = WOO_CWP_PLUGIN_PATH . '/includes/MainIncludes.php';
if (file_exists($mainIncludesFile)) {
    require_once  $mainIncludesFile;
}

// Carrega autoload do Composer se existir
if (file_exists(WOO_CWP_PLUGIN_PATH . '/vendor/autoload.php')) {
    require_once WOO_CWP_PLUGIN_PATH . '/vendor/autoload.php';
}

if (class_exists('WooCWP\Includes\MainIncludes') && file_exists($mainIncludesFile) && !function_exists('WooCWP')) {
    function WooCWP()
    {
        return WooCWP\Includes\MainIncludes::getInstance();
    }

    $pluginInstance = WooCWP();

    // Adiciona o hook de inicialização
    add_action('plugins_loaded', array($pluginInstance, 'init'), 10);

    //activation
    register_activation_hook(WOO_CWP_PLUGIN_FILE, array($pluginInstance, 'activate'));
    //deactivation
    register_deactivation_hook(WOO_CWP_PLUGIN_FILE, array($pluginInstance, 'deactivate'));


    // Adiciona o menu na administração do WordPress
    add_action('admin_menu', array($pluginInstance, 'addAdminMenu'));

    // Hook para registrar os endpoints na API REST
    add_action('rest_api_init', array($pluginInstance, 'registerRoutes'));

    // hook para adicionar novos campos na tela de checkout do woocommerce
    add_action('woocommerce_after_order_notes', array($pluginInstance, 'addCustomCheckoutFields'), 10, 1);

    // Valida os campos personalizados no checkout
    add_action('woocommerce_after_checkout_validation', array($pluginInstance, 'validateCustomFieldsCheckout'), 10, 2);

    add_action('woocommerce_payment_complete', array($pluginInstance, 'processSharesAfterPayment'));
}
