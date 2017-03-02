<?php
namespace Piwik\Plugins\ClickHeat\Utils;

class Helper
{
    /**
     * @param $str
     *
     * @return string
     */
    public static function cleanStrings($str)
    {
        if (function_exists('mb_strtolower')) {
            $str = mb_strtolower($str, 'utf-8');
        } else {
            $str = strtolower($str);
        }
        /* strtr() correctly handles multibyte */
        $str = strtr($str, ['à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ā' => 'a', 'ă' => 'a', 'ą' => 'a', 'ç' => 'c', 'ć' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'č' => 'c', 'ď' => 'd', 'đ' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ę' => 'e', 'ě' => 'e', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ĩ' => 'i', 'ī' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'i', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ĺ' => 'l', 'ļ' => 'l', 'ľ' => 'l', 'ŀ' => 'l', 'ł' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ņ' => 'n', 'ň' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ð' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ō' => 'o', 'ŏ' => 'o', 'ő' => 'o', 'œ' => 'o', 'ø' => 'o', 'ŕ' => 'r', 'ř' => 'r', 'ś' => 's', 'ŝ' => 's', 'ş' => 's', 'š' => 's', 'ſ' => 's', 'ţ' => 't', 'ť' => 't', 'ŧ' => 't', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ũ' => 'u', 'ū' => 'u', 'ŭ' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y', 'ź' => 'z', 'ż' => 'z', 'ž' => 'z']);

        return substr(preg_replace('/[^a-z_0-9\-]+/', '.', $str), 0, 250);
    }

    /**
     * @param $ip
     * @param $ipRanges
     *
     * @return bool
     */
    public static function isIpInRange($ip, $ipRanges)
    {
        $ip = \Piwik\Network\IP::fromBinaryIP($ip);

        return $ip->isInRanges($ipRanges);
    }

    /**
     * @param $browser
     *
     * @return mixed
     */
    public static function getBrowser($browser)
    {
        return preg_replace('/[^a-z]+/', '', strtolower($browser));
    }

    /**
     * @return int|string
     */
    public static function getServerPort()
    {
        $port = isset($_SERVER['X-Forwarded-Port']) ? $_SERVER['X-Forwarded-Port'] : (
        isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80
        );
        $port = intval($port) == 80 ? '' : ':' . $port;

        return $port;
    }
}