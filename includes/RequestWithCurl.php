<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class RequestWithCurl
{
    private function __construct() {}

    public static function post($api_url, $postData)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $ch = null;
        try {
            if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('URL inválida');
            }

            // Inicializa o CURL
            $ch = curl_init();
            if ($ch === false) {
                throw new \RuntimeException('Falha ao inicializar cURL');
            }

            // Define as opções do CURL
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Use apenas se necessário
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Use apenas se necessário

            // Executa a requisição
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errorNumber = curl_errno($ch);
            $errorMessage = curl_error($ch);

            if ($response === false) {
                throw new \RuntimeException(curl_error($ch));
            }
            return $response;
        } catch (\Exception $error) {
            error_log(json_encode(array('error' => $error, 'error_message' => $error->getMessage())) . PHP_EOL, 3, WOO_CWP_PLUGIN_PATH . '/includes/error_curl.json');
        } finally {
            if (isset($ch) && is_resource($ch)) {
                curl_close($ch);
            }
        }
    }
}
