<?php

namespace WooCWP\Includes;

use WooCWP\Includes\Activate;
use WooCWP\Includes\Deactivate;
use WooCWP\Includes\AdminMenu\AddAdminMenu;
use WooCWP\Includes\ApiEndpoint;

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
}
