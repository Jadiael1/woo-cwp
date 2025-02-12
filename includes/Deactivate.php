<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class Deactivate
{
    private function __construct() {}

    public static function deactivate()
    {
        if (is_admin()) {
            flush_rewrite_rules();
        }
    }
}
