<?php

namespace WooCWP\Includes;

defined('ABSPATH') || exit;

class GenerateUniqueUserName
{
    private function __construct() {}

    public static function generateFromEmail(string $email): string
    {
        $uniqueString = $email . microtime(true);
        $hash = hash('sha256', $uniqueString);
        $localPart = strtolower(explode('@', $email)[0]);
        if (function_exists('transliterator_transliterate')) {
            $localPart = transliterator_transliterate('Any-Latin; Latin-ASCII', $localPart);
        } else {
            $unwanted_array = array(
                'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
            );
            $localPart = strtr($localPart, $unwanted_array);
        }
        $localPart = preg_replace('/[^a-z0-9]/', '', $localPart);
        if (is_numeric($localPart[0])) {
            $localPart = 'u' . $localPart;
        }
        $start = substr($localPart, 0, 4);
        $hashPart = substr($hash, 0, 8);
        $hashNum = base_convert($hashPart, 16, 36);
        $end = substr($hashNum, 0, 4);
        $end = explode(',', $end);
        shuffle($end);
        $end = implode('', $end);
        return $start . $end;
    }
}
