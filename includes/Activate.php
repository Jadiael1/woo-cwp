<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class Activate
{
    private function __construct() {}

    public static function activate()
    {
        if (!file_exists(WOO_CWP_LOG_DIR)) {
            wp_mkdir_p(WOO_CWP_LOG_DIR);
        }
        
        if (is_admin()) {
            flush_rewrite_rules();
        }
    }
}
