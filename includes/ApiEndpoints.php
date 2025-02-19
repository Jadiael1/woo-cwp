<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class ApiEndpoints
{
    public static function register_routes()
    {
        register_rest_route(
            'cwp-woo/v1',
            '/save-settings/',
            [
                'methods'  => 'POST',
                'callback' => [\WooCWP\Includes\AdminMenu\AddAdminMenu::class, 'handleRequest'],
                'permission_callback' => [\WooCWP\Includes\ApiToken::class, 'validate_token'],
            ]
        );

        register_rest_route(
            'cwp-woo/v1',
            '/get-orders/',
            [
                'methods'  => 'GET',
                'callback' => [\WooCWP\Includes\GetCompletedOrders::class, 'getCompletedOrders'],
                'permission_callback' => [\WooCWP\Includes\ApiToken::class, 'validate_token'],
                'args' => [
                    'page' => [
                        'description'       => 'Número da página de resultados.',
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function ($param) {
                            return $param > 0;
                        },
                    ],
                    'per_page' => [
                        'description'       => 'Quantidade de pedidos por página.',
                        'type'              => 'integer',
                        'default'           => 10,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function ($param) {
                            return $param > 0 && $param <= 100;
                        },
                    ],
                ],
            ]
        );
    }
}
