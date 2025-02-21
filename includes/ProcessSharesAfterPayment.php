<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class ProcessSharesAfterPayment
{

    private function __construct() {}

    private static function schedule_cwp_woo_event(array $postData, int $minDelay = WOO_CWP_DELAY_REGISTER)
    {
        $next_available = (int) get_option('cwp_woo_next_available_time', time());
        $current_time   = time();
        $scheduled_time = max($current_time + $minDelay, $next_available);
        update_option('cwp_woo_next_available_time', $scheduled_time);
        wp_schedule_single_event($scheduled_time, 'cwp_woo_create_account', [$postData]);
    }

    private static function getPostData($username, $password, $domain, $email, $plan)
    {
        $api_url_encrypted = get_option('cwp_woo_api_url');
        $api_token_encrypted = get_option('cwp_woo_api_token');
        $api_ip_encrypted = get_option('cwp_woo_api_ip');
        $intermediate_api_url_encrypted = get_option('cwp_woo_intermediate_api_url', null);
        if (!$api_url_encrypted || !$api_token_encrypted || !$api_ip_encrypted) {
            \WooCWP\Includes\Log::registerLog('Configurações da API não encontradas.');
            return null;
        }
        $api_url = \WooCWP\Includes\SecureStorage::decrypt($api_url_encrypted) . 'account';
        $api_token = \WooCWP\Includes\SecureStorage::decrypt($api_token_encrypted);
        $api_ip = \WooCWP\Includes\SecureStorage::decrypt($api_ip_encrypted);

        $intermediate_api_url = $intermediate_api_url_encrypted !== null ? \WooCWP\Includes\SecureStorage::decrypt($intermediate_api_url_encrypted) : null;
        if (!$api_url || !$api_token || !$api_ip) {
            \WooCWP\Includes\Log::registerLog('Falha ao descriptografar as configurações da API.');
            return null;
        }

        return array(
            'key' => $api_token,
            'action' => 'add',
            'user' => $username,
            'pass' => base64_encode($password),
            'domain' => $domain,
            'email' => $email,
            'encodepass' => 'true',
            'package' => $plan,
            'lang' => 'pt',
            'inode' => 0,
            'limit_nproc' => 999,
            'limit_nofile' => 999999,
            'server_ips' => $api_ip,
            'api_url' => $api_url,
            'intermediate_api_url' => $intermediate_api_url
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

    public static function createAccountCWP($postData)
    {
        try {
            $apiUrl = $postData['intermediate_api_url'] === null ? $postData['api_url'] : $postData['intermediate_api_url'];
            $logFile = WOO_CWP_LOG_DIR . "/response_{$postData['user']}.json";
            if (self::verifyCurlBinSystem()) {
                \WooCWP\Includes\Log::registerSyncDb($postData['user']);
                \WooCWP\Includes\Log::registerLog('creating account using exec and curl');
                if (file_exists($logFile)) {
                    unlink($logFile);
                }
                $command = sprintf(
                    "curl -s -X POST -d %s '%s' > %s 2>&1",
                    escapeshellarg(http_build_query($postData)),
                    $apiUrl,
                    escapeshellarg($logFile)
                );
                // if (function_exists('fastcgi_finish_request')) {
                //     fastcgi_finish_request();
                // }
                exec($command);
                return;
            } else {
                \WooCWP\Includes\Log::registerLog('exec or curl is probably not enabled on your operating system');
            }

            // fallback
            // Faz a solicitação à API do CWP para criar a conta
            \WooCWP\Includes\Log::registerSyncDb($postData['user']);
            \WooCWP\Includes\Log::registerLog('creating account using wp_remote_post');
            // if (function_exists('fastcgi_finish_request')) {
            //     fastcgi_finish_request();
            // }
            wp_remote_post($apiUrl, array(
                'body' => $postData,
                'blocking' => false
            ));

            // if (is_wp_error($response)) {
            //     $isWpError = array('status' => 'Error', 'response' => $response);
            //     error_log(json_encode($isWpError) . PHP_EOL, 3, $logFile);
            //     return;
            // }
            // $responseBody = wp_remote_retrieve_body($response);
            // if (file_exists($logFile)) {
            //     unlink($logFile);
            // }
            // error_log(json_encode($responseBody) . PHP_EOL, 3, $logFile);
        } catch (\Exception $e) {
            $logFile = WOO_CWP_LOG_DIR . "/error_create_account_cwp_{$postData['user']}.json";
            $data = json_encode(array('api_url' => $apiUrl, 'post_data' => $postData, 'error_message' => $e->getMessage(), 'error' => $e));
            error_log($data . PHP_EOL, 3, $logFile);
        }
    }

    public static function sendEmailUser($order_id)
    {
        $cwp_logins_serialized = get_post_meta($order_id, 'cwp_logins', true);
        $cwp_passwords_serialized = get_post_meta($order_id, 'cwp_passwords', true);
        $cwp_emails_serialized = get_post_meta($order_id, 'cwp_emails', true);
        $cwp_domains_serialized = get_post_meta($order_id, 'cwp_domains', true);
        if ($cwp_logins_serialized === false || empty($cwp_logins_serialized) || $cwp_passwords_serialized === false || empty($cwp_passwords_serialized) && $cwp_emails_serialized === false && empty($cwp_emails_serialized)) {
            \WooCWP\Includes\Log::registerLog('error sending account creation email in cwp. - sendEmailUser');
        }
        $cwp_logins = maybe_unserialize($cwp_logins_serialized);
        $cwp_passwords = maybe_unserialize($cwp_passwords_serialized);
        $cwp_domains = maybe_unserialize($cwp_domains_serialized);
        $cwp_emails = maybe_unserialize($cwp_emails_serialized);

        $users = implode(', ', $cwp_logins);
        $passwords = implode(', ', $cwp_passwords);
        $domains = implode(', ', $cwp_domains);

        $subject = 'Sua conta no CWP foi criada';
        $message = "Olá,\n\nSua conta no CWP foi criada com sucesso.\n\nDetalhes da conta:\nUsuário: $users\nSenha: $passwords\nDomínio: $domains\n\n";
        $message .= "URL de acesso ao painel do usuário: https://cpanel.juvhost.com/\n\n";
        $message .= "Para apontar seu domínio corretamente, utilize as seguintes informações:\n";
        $message .= "Servidor IP: 161.97.121.93\n";
        $message .= "Servidor DNS:\n";
        $message .= "Ns1: ns1.juvhost.com\n";
        $message .= "Ns2: ns2.juvhost.com\n\n";
        $message .= "Por favor, acesse o painel do CWP para mais informações.";
        wp_mail($cwp_emails[0], $subject, $message);
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

        $cwp_logins_serialized = get_post_meta($order_id, 'cwp_logins', true);
        $cwp_passwords_serialized = get_post_meta($order_id, 'cwp_passwords', true);
        $cwp_emails_serialized = get_post_meta($order_id, 'cwp_emails', true);
        $cwp_domains_serialized = get_post_meta($order_id, 'cwp_domains', true);
        $cwp_plans_serialized = get_post_meta($order_id, 'cwp_plans', true);

        if ($cwp_logins_serialized !== false && !empty($cwp_logins_serialized) && $cwp_passwords_serialized !== false && !empty($cwp_passwords_serialized) && $cwp_emails_serialized !== false && !empty($cwp_emails_serialized)) {
            $cwp_logins = maybe_unserialize($cwp_logins_serialized);
            $cwp_passwords = maybe_unserialize($cwp_passwords_serialized);
            $cwp_emails = maybe_unserialize($cwp_emails_serialized);
            $cwp_domains = maybe_unserialize($cwp_domains_serialized);
            $cwp_plans = maybe_unserialize($cwp_plans_serialized);
            foreach ($cwp_logins as $key => $login) {
                $postData = self::getPostData($login, $cwp_passwords[$key], $cwp_domains[$key], $cwp_emails[$key], $cwp_plans[$key]);
                if ($postData === null) {
                    return;
                }
                if ($postData['intermediate_api_url'] !== null) {
                    self::createAccountCWP($postData);
                }
                self::schedule_cwp_woo_event($postData);
            }
            self::sendEmailUser($order_id);
            return;
        }

        // fallback
        foreach ($domainsByCategories as $domainsByCategory) {
            foreach ($domainsByCategory as $domainByCategory) {
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

                $user_cwp_login =  \WooCWP\Includes\GenerateUniqueUserName::generateFromEmail($order->get_billing_email());
                $user_cwp_password = \WooCWP\Includes\PasswordGenerator::generate();

                self::appendToMeta($order_id, 'cwp_logins', $user_cwp_login);
                self::appendToMeta($order_id, 'cwp_passwords', $user_cwp_password);
                self::appendToMeta($order_id, 'cwp_emails', $order->get_billing_email());
                self::appendToMeta($order_id, 'cwp_domains', $domainByCategory['domain']);
                self::appendToMeta($order_id, 'cwp_plans', $planName);

                $postData = self::getPostData($user_cwp_login, $user_cwp_password, $domainByCategory['domain'], $order->get_billing_email(), $planName);
                if ($postData === null) {
                    return;
                }
                if ($postData['intermediate_api_url'] !== null) {
                    self::createAccountCWP($postData);
                }
                self::schedule_cwp_woo_event($postData);
            }
        }
        self::sendEmailUser($order_id);
    }

    public static function processCron($postData)
    {
        $next_available = (int) get_option('cwp_woo_next_available_time', time());
        $current_time = time();
        if ($current_time < $next_available) {
            wp_schedule_single_event($next_available, 'cwp_woo_create_account', [$postData]);
            return;
        }
        \WooCWP\Includes\ProcessSharesAfterPayment::createAccountCWP($postData);
        update_option('cwp_woo_next_available_time', $current_time + intval(WOO_CWP_DELAY_REGISTER));
    }
}
