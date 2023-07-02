<?php

namespace App\Helpers;

use Carbon\Carbon;

class GeneralHelper {

    public static function extractHashtags($string): string
    {
        preg_match_all("/(?:^|\s)[#@][^ @#]+\b/", $string, $m);

        return trim(implode("", $m[0]));
    }

    public static function getStringWithoutEndingHashtags($string): string
    {
        $endOfString = substr($string, strrpos($string, '.') + 1);

        preg_match_all('/(?P<hashtags>\#\w+)(?:\s)?/', $endOfString, $matches);
        $string = str_replace($matches['hashtags'], '', $string);

        return trim($string);
    }

    public static function searchArray($key, $value, $array)
    {
        foreach ($array as $v) {
            if ($v[$key] === $value) {
                return $v;
            }
        }
        return null;
    }

    public static function getInnerSubstring($substring, $left, $right)
    {

        $string = explode($left,$substring)[1];

        return explode($right,$string)[0];
    }

    public static function urlEncodeHashtags($text)
    {
        return str_replace('#', '%23', $text);
    }

    public static function trimWhitespacePerLine(string $text)
    {
        // trim whitespace line by line
        $text = explode("\n", $text);
        foreach ($text as &$item) {
            $item = trim($item);
        }

        return implode("\n",$text);
    }

    public static function replaceBetween($str, $needle_start, $needle_end, $replacement)
    {
        $pos = strpos($str, $needle_start);
        $start = $pos === false ? 0 : $pos + strlen($needle_start);

        $pos = strpos($str, $needle_end, $start);
        $end = $pos === false ? strlen($str) : $pos;

        return substr_replace($str, $replacement, $start, $end - $start);
    }

    public static function getAccountHashtags()
    {
        $return = "Here are some hashtags \n";
        foreach (\App\Models\Account::all() as $account) {
            if ($account->default_hashtags) {
                $return .= $account->default_hashtags . "\n";
            }
        }
        $return .= "sadsa";

        return $return;
    }

    public static function getTimezoneTime($timestamp, $timezone, $format = 'Y-m-d H:i:s')
    {
        date_default_timezone_set($timezone);
        $utcOffset =  date('Z') / 3600;
        date_default_timezone_set('UTC');

        return date($format, strtotime($utcOffset . ' hours', strtotime(date($timestamp))));
    }

    public static function convertToTimezone($time, $timezone, $format = 'H:i')
    {
        $date = new \DateTime($time, new \DateTimeZone($timezone));
        return $date->format($format);
    }

    public static function convertTimeToUTC($timestamp, $timezone = 'UTC', $format = 'Y-m-d H:i:s')
    {
        $date = Carbon::createFromFormat($format, $timestamp, $timezone);

        return $date->setTimezone($timezone);
    }

    public static function convertCurrentTimezoneTimeToUTCTime($timestamp, $targetTimezone)
    {
        $timezoneDifference = ((new \DateTime('now', new \DateTimeZone( $targetTimezone )))->format('Z') / 3600);

        $helperTime = self::getTimezoneTime($timestamp, SiteSettingsHelper::get('app_timezone'));

        return date('Y-m-d H:i:s', strtotime($helperTime . ' ' . $timezoneDifference * -1 . ' hours'));
    }
}
