<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class ProcessSharesAfterPayment
{

    private function __construct() {}

    private static function getPostData($username, $password, $domain, $email)
    {
        $api_url_encrypted = get_option('woo_cwp_api_url');
        $api_token_encrypted = get_option('woo_cwp_api_token');
        $api_ip_encrypted = get_option('woo_cwp_api_ip');
        if (!$api_url_encrypted || !$api_token_encrypted || !$api_ip_encrypted) {
            $errorSettingsNotFound = array('status' => 'error', 'message' => 'Configurações da API não encontradas.');
            error_log(json_encode($errorSettingsNotFound) . PHP_EOL, 3, WOO_CWP_LOG_DIR . '/error_settings_not_found.json');
            return;
        }
        $api_url = \WooCWP\Includes\SecureStorage::decrypt($api_url_encrypted) . 'account';
        $api_token = \WooCWP\Includes\SecureStorage::decrypt($api_token_encrypted);
        $api_ip = \WooCWP\Includes\SecureStorage::decrypt($api_ip_encrypted);
        if (!$api_url || !$api_token || !$api_ip) {
            $errorDecrypt = array('status' => 'error', 'message' => 'Falha ao descriptografar as configurações da API.');
            error_log(json_encode($errorDecrypt) . PHP_EOL, 3, WOO_CWP_LOG_DIR . '/error_decrypt.json');
            return;
        }

        return array(
            'key' => $api_token,
            'action' => 'add',
            'user' => $username,
            'pass' => base64_encode($password),
            'domain' => $domain,
            'email' => $email,
            'encodepass' => 'true',
            'package' => "Starter",
            'lang' => 'pt',
            'inode' => 0,
            'limit_nproc' => 999,
            'limit_nofile' => 999999,
            'server_ips' => $api_ip,
            'api_url' => $api_url
        );
    }

    private static function verifyCurlBinSystem(): bool
    {
        if (function_exists('exec')) {
            $hasCurl = false;
            exec('command -v curl', $outputCommand, $returnCodeCommand);
            if ($returnCodeCommand !== 0) {
                exec('which curl', $outputWhich, $returnCodeWhich);
                if ($returnCodeWhich === 0) {
                    $hasCurl = true;
                }
            } else {
                $hasCurl = true;
            }
            return $hasCurl;
        } else {
            return false;
        }
    }

    public static function createAccountCWP($postData, $apiUrl)
    {
        try {
            $apiUrl = apply_filters('woo_cwp_api_url_modify', $apiUrl);
            if (self::verifyCurlBinSystem()) {
                $command = sprintf(
                    "curl -s -X POST -d %s '%s' > " . WOO_CWP_LOG_DIR . "/response_curl_cli_" . $postData['user'] . ".json",
                    escapeshellarg(http_build_query($postData)),
                    $apiUrl
                );
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                exec($command);
                return;
            } else {
                error_log(json_encode(array('api_url' => $apiUrl, 'post_data' => $postData, 'error_message' => 'curl is probably not enabled on your operating system')) . PHP_EOL, 3, WOO_CWP_LOG_DIR . '/status_curl_cli_' . $postData['user'] . '.json');
            }

            // fallback
            // Faz a solicitação à API do CWP para criar a conta
            error_log(json_encode(array('api_url' => $apiUrl, 'post_data' => $postData, 'client' => 'wp_rempte_post')) . PHP_EOL, 3, WOO_CWP_LOG_DIR . '/response_curl_cli_' . $postData['user'] . '.json');
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            $response = wp_remote_post($apiUrl, array(
                'body' => $postData,
                'blocking' => false
            ));

            if (is_wp_error($response)) {
                $isWpError = array('status' => 'error');
                error_log(json_encode($isWpError) . PHP_EOL, 3, WOO_CWP_LOG_DIR . '/response_wp_error.json');
                return;
            }
        } catch (\Exception $e) {
            $data = json_encode(array('api_url' => $apiUrl, 'post_data' => $postData, 'error_message' => $e->getMessage(), 'error' => $e));
            error_log($data . PHP_EOL, 3, WOO_CWP_LOG_DIR . '/error_create_account_cwp_' . $postData['user'] . '.json');
        }
    }

    public static function sendEmailUser($email, $username, $password, $domain)
    {
        $subject = 'Sua conta no CWP foi criada';
        $message = "Olá,\n\nSua conta no CWP foi criada com sucesso.\n\nDetalhes da conta:\nUsuário: $username\nSenha: $password\nDomínio: $domain\n\n";
        $message .= "URL de acesso ao painel do usuário: https://cpanel.juvhost.com/\n\n";
        $message .= "Para apontar seu domínio corretamente, utilize as seguintes informações:\n";
        $message .= "Servidor IP: 161.97.121.93\n";
        $message .= "Servidor DNS:\n";
        $message .= "Ns1: ns1.juvhost.com\n";
        $message .= "Ns2: ns2.juvhost.com\n\n";
        $message .= "Por favor, acesse o painel do CWP para mais informações.";
        wp_mail($email, $subject, $message);
    }


    private static function appendToMeta(int $post_id, string $meta_key, $new_value): bool
    {
        $current_values = get_post_meta($post_id, $meta_key, true);
        if (empty($current_values) || $current_values === false) {
            $values_array = array();
        } else {
            $values_array = unserialize($current_values);
            if (!is_array($values_array)) {
                $values_array = array();
            }
        }
        $values_array[] = $new_value;
        return update_post_meta($post_id, $meta_key, serialize($values_array));
    }

    public static function processSharesAfterPayment($order_id)
    {
        // Obtém o pedido
        $order = wc_get_order($order_id);
        // Verifica se o pedido é válido
        if (!$order) {
            return;
        }

        $domainsSerialized = get_post_meta($order_id, '_billing_domains', true);
        if (empty($domainsSerialized) || $domainsSerialized === false) {
            return;
        }
        $domainsByCategories = maybe_unserialize($domainsSerialized);

        $time = 25;

        foreach ($domainsByCategories as $key => $domainsByCategory) {
            foreach ($domainsByCategory as $key1 => $domainByCategory) {
                $post_object = get_post($domainByCategory['product_id']);
                if (!$post_object) {
                    continue;
                }
                $planName = $post_object->post_name;
                $pos = strpos($planName, '-plan');
                if ($pos === false) {
                    continue;
                }
                $planName = ucfirst(substr($planName, 0, $pos));
                $user_cwp_login = GenerateUniqueUserName::generateFromEmail($order->get_billing_email());
                $user_cwp_password = PasswordGenerator::generate();

                self::appendToMeta($order_id, 'cwp_logins', $user_cwp_login);
                self::appendToMeta($order_id, 'cwp_passwords', $user_cwp_password);
                self::appendToMeta($order_id, 'cwp_emails', $order->get_billing_email());

                $postData = self::getPostData($user_cwp_login, $user_cwp_password, $domainByCategory['domain'], $order->get_billing_email());
                $apiUrl = $postData['api_url'];
                unset($postData['api_url']);

                // exemplo com WP-Cron;
                wp_schedule_single_event(time() + $time, 'woo_cwp_create_account', [
                    'postData' => $postData,
                    'apiUrl' => $apiUrl
                ]);
                $time += 25;
                // self::sendEmailUser($email, $username, $password, $domain);
            }
        }
    }
}
