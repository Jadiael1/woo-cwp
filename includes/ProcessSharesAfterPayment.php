<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class ProcessSharesAfterPayment
{
    private function __construct() {}

    public static function generateUniqueUserName($order)
    {
        $email = $order->get_billing_email();

        // Obtém a parte do e-mail antes do '@' e sanitiza
        $base_username = sanitize_user(current(explode('@', $email)), true);

        // Remove caracteres inválidos, mantendo apenas letras minúsculas e números
        $base_username = strtolower(preg_replace('/[^a-z0-9]/', '', $base_username));

        // Garante que tenha no máximo 8 caracteres
        $base_username = substr($base_username, 0, 8);

        // Se após sanitização estiver vazio, cria um nome padrão
        if (empty($base_username)) {
            $base_username = 'user';
        }

        $username = $base_username;
        $suffix = 1;

        // Se o nome base já tiver 8 caracteres, removemos um para dar espaço ao número
        if (strlen($base_username) === 8) {
            $base_username = substr($base_username, 0, 7);
        }

        // Garante que o nome seja único, adicionando um número (1 a 99)
        while (username_exists($username)) {
            $suffix_str = str_pad($suffix, 2, '0', STR_PAD_LEFT); // Mantém 2 dígitos

            // Concatena e garante que nunca ultrapasse 8 caracteres
            $username = substr($base_username, 0, 8 - strlen($suffix_str)) . $suffix_str;

            // Se passarmos do 99, criamos um nome totalmente novo baseado no timestamp
            if ($suffix > 99) {
                $username = 'u' . substr(md5(uniqid()), 0, 7);
                break; // Evita loop infinito
            }

            $suffix++;
        }

        return $username;
    }

    public static function createAccountCWP($username, $password, $domain, $email)
    {
        // Recupera as configurações da API
        $api_url_encrypted = get_option('woo_cwp_api_url');
        $api_token_encrypted = get_option('woo_cwp_api_token');
        $api_ip_encrypted = get_option('woo_cwp_api_ip');

        // Verifica se as configurações existem
        if (!$api_url_encrypted || !$api_token_encrypted || !$api_ip_encrypted) {
            return array('status' => 'error', 'message' => 'Configurações da API não encontradas.');
        }

        // Descriptografa as configurações
        $api_url = \WooCWP\Includes\SecureStorage::decrypt($api_url_encrypted) . 'account';
        $api_token = \WooCWP\Includes\SecureStorage::decrypt($api_token_encrypted);
        $api_ip = \WooCWP\Includes\SecureStorage::decrypt($api_ip_encrypted);

        // Verifica se a descriptografia foi bem-sucedida
        if (!$api_url || !$api_token || !$api_ip) {
            return array('status' => 'error', 'message' => 'Falha ao descriptografar as configurações da API.');
        }

        // Faz a solicitação à API do CWP para criar a conta
        $response = wp_remote_post($api_url, array(
            'body' => array(
                'key' => $api_token,
                'action' => 'add',
                'user' => $username,
                'pass' => base64_encode($password),
                'domain' => $domain,
                'email' => $email,
                'encodepass' => 'true',
                'package' => 5,
                'lang' => 'pt',
                'inode' => 0,
                'limit_nproc' => 999,
                'limit_nofile' => 999999,
                'server_ips' => $api_ip
            ),
        ));

        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
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

    public static function processSharesAfterPayment($order_id)
    {
        // Obtém o pedido
        $order = wc_get_order($order_id);

        // Verifica se o pedido é válido
        if (!$order) {
            return;
        }
        // Obtém o ID do usuário associado ao pedido
        $user_id = $order->get_user_id();

        // Gera um nome de usuário único
        $user_login = self::generateUniqueUserName($order);

        // Gera uma senha segura
        $password = PasswordGenerator::generate();

        // Obtém o domínio informado pelo usuário no checkout
        $domain = get_post_meta($order_id, '_billing_domain', true);

        // Adiciona metadados ao usuário
        update_post_meta($order_id, 'cwp_login', $user_login);
        update_post_meta($order_id, 'cwp_password', $password);

        // Faz a solicitação à API do CWP para criar a conta
        $api_response = self::createAccountCWP($user_login, $password, $domain, $order->get_billing_email());

        error_log(json_encode($api_response) . PHP_EOL, 3, WOO_CWP_PLUGIN_PATH . '/includes/log.json');
        // Verifica se a conta foi criada com sucesso
        if ($api_response['status'] === 'OK') {
            // Envia um e-mail ao usuário com os dados da conta
            self::sendEmailUser($order->get_billing_email(), $user_login, $password, $domain);
        } else {
            // Lida com o erro conforme necessário
            // Você pode registrar o erro ou notificar o administrador
        }
    }
}
