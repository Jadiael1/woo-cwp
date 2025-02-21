<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class Log
{
    private function __construct() {}

    public static function findResponseCurlLog(string $username): bool
    {
        // Caminho completo do diretório de logs
        $logDir = WOO_CWP_LOG_DIR;

        // Verifica se o diretório existe e é acessível
        if (!is_dir($logDir) || !is_readable($logDir)) {
            return null;
        }

        // Caminho esperado do arquivo
        $logFile = "{$logDir}/response_{$username}.json";

        // Verifica se o arquivo existe e retorna seu caminho, ou null se não encontrado
        return file_exists($logFile);
    }

    public static function getResponseCurlLogContent(string $username): array|null
    {
        // Caminho completo do diretório de logs
        $logDir = WOO_CWP_LOG_DIR;

        // Caminho esperado do arquivo
        $logFile = "{$logDir}/response_{$username}.json";

        // Verifica se o arquivo existe e é legível
        if (!file_exists($logFile) || !is_readable($logFile)) {
            return null;
        }

        // Obtém o conteúdo do arquivo
        $content = file_get_contents($logFile);
        $content = str_replace("}\"\"", "}", $content);

        // Verifica se o conteúdo é um JSON válido e decodifica
        $decodedContent = json_decode($content, true);

        return is_array($decodedContent) ? $decodedContent : null;
    }


    public static function registerLog($message)
    {
        $date = new \DateTimeImmutable();
        $formattedDate = $date->format('d-m-Y H:i:s');
        $logFile = WOO_CWP_LOG_DIR . "/cwp_woo.log";
        error_log("[$formattedDate]: $message" . PHP_EOL, 3, $logFile);
    }

    public static function registerSyncDb($loginCWP, $status = false)
    {
        global $wpdb;
        $success = $wpdb->insert(
            $wpdb->prefix . 'cwp_woo_status',
            [
                'login_cwp' => $loginCWP,
                'status'  => $status
            ],
            ['%s', '%d']
        );
        if ($success === false) {
            self::registerLog('error inserting account creation status in CWP');
        }
    }
}
