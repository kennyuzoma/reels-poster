<?php

namespace App\Services\TikTok;

use App\Helpers\GeneralHelper;
use App\Helpers\SiteSettingsHelper;
use App\Services\ServicePlatform;
use App\Services\TikTok\Providers\GotTikApp;
use Illuminate\Support\Str;

class TikTok implements ServicePlatform {

    protected $dataProviders = [
        GotTikApp::class
    ];

    public static function getMediaData($url): array
    {
        return [];
    }

    public static function videoUrl($url): string
    {
        return TikTok::videoUrlWithoutWatermark($url);
    }

    public static function videoUrlWithWatermark($url)
    {
        return self::getMediaData($url)['video']['playAddr'];
    }

    /**
     * @throws \Exception
     */
    public static function videoUrlWithoutWatermark($url)
    {
        $api = 'https://www.tikwm.com/api/';

        $postData = [
            'url' => $url,
            'hd' => 1   //input 1, get HD Video
        ];

        $response = self::curlRequest($api . '?' . http_build_query($postData));
        $obj = json_decode($response, true);

        if ($obj['code'] != 0) {
            throw new \Exception($obj['msg']);
        }

        return $obj['data']['play'];
    }

    public static function externalId($url)
    {
        return self::getUrlInfo($url)['video_id'];
    }

    public static function author($url): string
    {
        return self::getUrlInfo($url)['author'];
    }

    public static function thumbnailUrl($url)
    {
        return null;
    }

    public static function caption($url)
    {
        return null;
    }

    private static function getIdFromUrl($url)
    {
        return self::getUrlInfo('video_id');
    }

    public static function curlRequest($url, $postData = [], $ajax = false, $cookies = [])
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);

        if ($postData) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        if ($ajax) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest"));
        }

        if ($cookies) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookies[0].';');
        }

        curl_setopt($curl, CURLOPT_ACCEPTTIMEOUT_MS, 10000);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function getUrl($url)
    {
        if (Str::contains($url, '@')) {
            return $url;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        curl_exec($ch);

        $redirectedUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);

        return $redirectedUrl;
    }

    public static function getUrlInfo($url): array
    {
        $url = self::getUrl($url);

        $path = parse_url($url)['path'];

        $info = explode('/', $path);

        $data = [];
        $data['full_url'] = $url;
        $data['author'] = str_replace('@', '', $info[1]);
        $data['video_id'] = $info[3];

        return $data;
    }
}
