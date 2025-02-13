<?php

namespace WooCWP\Includes;

use WooCWP\Includes\Activate;
use WooCWP\Includes\Deactivate;
use WooCWP\Includes\AdminMenu\AddAdminMenu;
use WooCWP\Includes\ApiEndpoint;
use WooCWP\Includes\AddCustomCheckoutFields;
use WooCWP\Includes\ProcessSharesAfterPayment;

defined('ABSPATH') || exit;

final class MainIncludes
{
    private $version = "0.1.0";
    private static $instances = null;

    private function __construct() {}

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): MainIncludes
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }
        return self::$instances[$cls];
    }

    public function init()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p><strong>Woo-CWP</strong> requires WooCommerce to work. Please activate WooCommerce first.</p></div>';
            });
            return;
        }
    }

    public function activate()
    {
        Activate::activate();
    }

    public function deactivate()
    {
        Deactivate::deactivate();
    }

    public function addAdminMenu()
    {
        AddAdminMenu::addAdminMenu();
    }

    public function registerRoutes()
    {
        ApiEndpoint::register_routes();
    }

    public function addCustomCheckoutFields($checkout)
    {
        AddCustomCheckoutFields::addCustomCheckoutFields($checkout);
    }

    public function validateCustomFieldsCheckout($data, $errors)
    {
        AddCustomCheckoutFields::validateCustomFieldsCheckout($data, $errors);
    }

    public function processSharesAfterPayment($order_id)
    {
        ProcessSharesAfterPayment::processSharesAfterPayment($order_id);
    }

    public function checkoutUpdateOrderMeta($order_id)
    {
        if (!empty($_POST['billing_domain'])) {
            update_post_meta($order_id, '_billing_domain', sanitize_text_field($_POST['billing_domain']));
        }
    }
}
