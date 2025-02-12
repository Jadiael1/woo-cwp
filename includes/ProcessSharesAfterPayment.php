<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class ProcessSharesAfterPayment
{
    private function __construct() {}

    public static function generateUniqueUserName($order)
    {
        $email = $order->get_billing_email();
        $base_username = sanitize_user(current(explode('@', $email)), true);
        $username = $base_username;
        $suffix = 1;
        while (username_exists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }
        return $username;
    }

    public function createAccountCWP($username, $password, $domain, $email)
    {
        $api_url = 'https://juvhost.com:2304/v1/account';
        $api_key = 'SUA_CHAVE_API';
        $response = wp_remote_post($api_url, array(
            'body' => array(
                'key' => $api_key,
                'action' => 'add',
                'user' => $username,
                'pass' => base64_encode($password),
                'domain' => $domain,
                'email' => $email,
                'encodepass' => 'true',
            ),
        ));
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        return $data;
    }

    public function sendEmailUser($email, $username, $password, $domain) {
        $subject = 'Sua conta no CWP foi criada';
        $message = "Olá,\n\nSua conta no CWP foi criada com sucesso.\n\nDetalhes da conta:\nUsuário: $username\nSenha: $password\nDomínio: $domain\n\nPor favor, acesse o painel do CWP para mais informações.";
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

        // Verifica se o usuário está registrado
        if ($user_id) {
            // Adiciona metadados ao usuário
            update_user_meta($user_id, 'meta_key', 'meta_value');
        }

        // Gera um nome de usuário único
        $user_login = self::generateUniqueUserName($order);

        // Gera uma senha segura
        $password = wp_generate_password(12, true);

        // Obtém o domínio informado pelo usuário no checkout
        $domain = get_post_meta($order_id, '_billing_domain', true);

        // Faz a solicitação à API do CWP para criar a conta
        $api_response = self::createAccountCWP($user_login, $password, $domain, $order->get_billing_email());

        // Verifica se a conta foi criada com sucesso
        if ($api_response['status'] === 'OK') {
            // Envia um e-mail ao usuário com os dados da conta
            self::sendEmailUser($order->get_billing_email(), $user_login, $password, $domain);
        } else {
            // Lida com o erro conforme necessário
        }
    }
}
