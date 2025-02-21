<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class GetCompletedOrders
{
    private function __construct() {}

    private static function insertOrUpdate(string $login_cwp, bool $status): void
    {
        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'cwp_woo_status';
        $existingRecord = $wpdb->get_var($wpdb->prepare("SELECT id FROM $nome_tabela WHERE login_cwp = %s", $login_cwp));
        $data = array('login_cwp' => $login_cwp, 'status' => $status);
        $formats = array('%s', '%d');
        $result = null;
        if ($existingRecord) {
            $result = $wpdb->update($nome_tabela, $data, array('id' => $existingRecord), $formats, array('%d'));
        } else {
            $result = $wpdb->insert($nome_tabela, $data, $formats);
        }
        if ($result === false) {
            \WooCWP\Includes\Log::registerLog($wpdb->last_error . ' - insertOrUpdate');
        }
    }

    private static function getStatusFromDb(string $login): ?string
    {
        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'cwp_woo_status';
        $existingRecord = $wpdb->get_var($wpdb->prepare("SELECT status FROM $nome_tabela WHERE login_cwp = %s", $login));
        if ($existingRecord === null) {
            \WooCWP\Includes\Log::registerLog($wpdb->last_error . ' - getStatusFromDb');
            return null;
        }
        return $existingRecord ? 'Sucesso' : 'Aguardando';
    }

    private static function getStatusFromFile(string $login): string
    {
        if (Log::findResponseCurlLog($login)) {
            $content = Log::getResponseCurlLogContent($login);
            if ($content !== null && isset($content['status']) && $content['status'] === 'OK') {
                self::insertOrUpdate($login, true);
                return 'Sucesso';
            }
            if ($content !== null && isset($content['status']) && $content['status'] !== 'OK') {
                return 'Erro';
            }
            if (empty($content)) {
                return 'Aguardando';
            }
        }
        return 'Aguardando';
    }

    private static function getStatusFromApi(string $login): ?string
    {
        $api_url_encrypted = get_option('cwp_woo_api_url');
        $api_token_encrypted = get_option('cwp_woo_api_token');
        $api_ip_encrypted = get_option('cwp_woo_api_ip');
        if (!$api_url_encrypted || !$api_token_encrypted || !$api_ip_encrypted) {
            \WooCWP\Includes\Log::registerLog('API settings not found. - getStatusFromApi');
            return null;
        }
        $api_url = \WooCWP\Includes\SecureStorage::decrypt($api_url_encrypted) . 'accountdetail';
        $api_token = \WooCWP\Includes\SecureStorage::decrypt($api_token_encrypted);
        $api_ip = \WooCWP\Includes\SecureStorage::decrypt($api_ip_encrypted);
        if (!$api_url || !$api_token || !$api_ip) {
            \WooCWP\Includes\Log::registerLog('Failed to decrypt API settings. - getStatusFromApi');
            return null;
        }
        $postData = array(
            'key' => $api_token,
            'action' => 'list',
            'user' => $login
        );
        $responseUserCWP = wp_remote_post($api_url, array('body' => $postData));
        if (is_wp_error($responseUserCWP)) {
            \WooCWP\Includes\Log::registerLog('Failed to verify user in CWP API. - getStatusFromApi');
            return null;
        }
        $userCWP = wp_remote_retrieve_body($responseUserCWP);
        $userCWP = json_decode($userCWP, true);
        if ($userCWP === null) {
            \WooCWP\Includes\Log::registerLog('Failed to decode json. - getStatusFromApi');
            return null;
        }
        $timestamp = wp_next_scheduled('cwp_woo_create_account');
        if ($userCWP['status'] === 'Error' && $userCWP['msj'] === 'User does not exist' && false === $timestamp) {
            return 'Erro';
        }
        if ($userCWP['status'] === 'Error' && $userCWP['msj'] === 'User does not exist' && false !== $timestamp) {
            return 'Aguardando';
        }
        if ($userCWP['status'] === 'OK') {
            self::insertOrUpdate($login, true);
            return 'Sucesso';
        }
    }

    private static function getStatus(string $login): string
    {
        $statusFromDb = self::getStatusFromDb($login);
        if ($statusFromDb !== null && $statusFromDb === 'Sucesso') {
            $logFile = WOO_CWP_LOG_DIR . "/response_{$login}.json";
            if (file_exists($logFile)) {
                unlink($logFile);
            }
            return $statusFromDb;
        }
        $statusFromFile = self::getStatusFromFile($login);
        if ($statusFromFile === 'Sucesso' || $statusFromFile === 'Erro') {
            return $statusFromFile;
        }
        $statusFromApi = self::getStatusFromApi($login);
        if ($statusFromApi === null) {
            return 'Erro';
        }
        return $statusFromApi;
    }

    public static function getCompletedOrders(\WP_REST_Request $request)
    {
        // Obtém os parâmetros da requisição
        $page     = max(1, (int) $request->get_param('page') ?? 1); // Página atual (mínimo 1)
        $per_page = max(1, (int) $request->get_param('per_page') ?? 10); // Pedidos por página (mínimo 1)

        // Define os parâmetros para buscar pedidos com status 'completed'
        $args = [
            'status'   => 'completed',
            'limit'    => $per_page,
            'page'     => $page,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'paginate' => true, // Ativa a paginação
        ];

        $orders_query = wc_get_orders($args);
        $orders = $orders_query->orders; // Obtém os pedidos paginados
        $total_orders = $orders_query->total; // Obtém o total de pedidos
        $total_pages = ceil($total_orders / $per_page); // Calcula o total de páginas

        // Inicializa um array para armazenar os dados dos pedidos
        $orders_data = [];
        $totalAccounts = 0;
        $totalSuccess = 0;
        $totalWaiting = 0;
        $totalError = 0;
        foreach ($orders as $order) {
            // Obtém os metadados cwp_logins e cwp_emails
            $cwp_logins_serialized = get_post_meta($order->get_id(), 'cwp_logins', true);
            $cwp_emails_serialized = get_post_meta($order->get_id(), 'cwp_emails', true);

            $cwp_logins = $cwp_logins_serialized === false ? null : maybe_unserialize($cwp_logins_serialized);
            $cwp_emails = $cwp_emails_serialized === false ? null : maybe_unserialize($cwp_emails_serialized);


            if (is_array($cwp_logins)) {
                foreach ($cwp_logins as $key => $login) {
                    $totalAccounts++;
                    $status = self::getStatus($login);
                    if ($status === 'Sucesso') {
                        $totalSuccess++;
                    }
                    if ($status === 'Aguardando') {
                        $totalWaiting++;
                    }
                    if ($status === 'Erro') {
                        $totalError++;
                    }
                    $cwp_logins[$key] = array('login' => $login, 'status' => $status);
                }
            }

            $orders_data[] = [
                'order_id'   => $order->get_id(),
                'cwp_logins' => $cwp_logins,
                'cwp_emails' => $cwp_emails,
            ];
        }



        // Constrói URLs para a próxima e a página anterior
        $base_url = rest_url('cwp-woo/v1/get-orders/');
        $query_params = ['per_page' => $per_page];

        $next_page_url = $page < $total_pages ? add_query_arg(array_merge($query_params, ['page' => $page + 1]), $base_url) : null;
        $prev_page_url = $page > 1 ? add_query_arg(array_merge($query_params, ['page' => $page - 1]), $base_url) : null;

        // Retorna a resposta REST com paginação e URLs
        return new \WP_REST_Response([
            'success'        => true,
            'message'        => 'Orders found successfully',
            'data'           => $orders_data,
            'total_accounts' => $totalAccounts,
            'total_success'  => $totalSuccess,
            'total_awaiting' => $totalWaiting,
            'total_erro'     => $totalError,
            'total_orders'   => $total_orders, // Total de pedidos disponíveis
            'total_pages'    => $total_pages, // Total de páginas disponíveis
            'current_page'   => $page, // Página atual
            'per_page'       => $per_page, // Quantidade de pedidos por página
            'next_page'      => $next_page_url, // URL da próxima página (ou null se não houver)
            'previous_page'  => $prev_page_url, // URL da página anterior (ou null se não houver)
            'error'          => null,
        ], 200);
    }
}
