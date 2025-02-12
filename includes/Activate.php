<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class Activate
{
    private function __construct() {}

    public static function activate()
    {
        if (is_admin()) {
            flush_rewrite_rules();
        }
    }
}
