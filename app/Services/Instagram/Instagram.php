<?php

namespace App\Services\Instagram;

use App\Helpers\GeneralHelper;
use App\Helpers\SiteSettingsHelper;
use App\Services\ServicePlatform;

class Instagram implements ServicePlatform {

    public static function getMediaData($url): array
    {
        $url = $url . '?__a=1&__d=dis';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authority: www.instagram.com',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'accept-language: en-US,en;q=0.9',
            'cache-control: no-cache',
            'cookie: ig_nrcb=1; csrftoken=FfqY7UXX8BU6qfY3YxEIAjgeT6iAWerX; mid=Y4yrhwAEAAE_YVFmnQsrZXjFRwuJ; ig_did=E444A4FA-9699-4480-B293-943AD9035DC9',
            'pragma: no-cache',
            'sec-ch-prefers-color-scheme: dark',
            'sec-ch-ua: "Google Chrome";v="107", "Chromium";v="107", "Not=A?Brand";v="24"' ,
            'sec-ch-ua-mobile: ?0' ,
            'sec-ch-ua-platform: "macOS"' ,
            'sec-fetch-dest: document' ,
            'sec-fetch-mode: navigate' ,
            'sec-fetch-site: none' ,
            'sec-fetch-user: ?1' ,
            'upgrade-insecure-requests: 1' ,
            'viewport-width: 963',
        ]);

//        curl_setopt($ch, CURLOPT_COOKIE, 'sessionid='. SiteSettingsHelper::get('instagram_session_id') .';');

        $data = curl_exec($ch);

        if (curl_errno($ch)){
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        try {
            $data = json_decode($data, true);
        } catch (\Exception $e) {
            throw new \Exception('Could not return data');
        }

        return $data;
    }

    public static function videoUrl($url): string
    {
        return self::getMediaData($url)['graphql']['shortcode_media']['video_url'];
    }

    public static function carouselMediaUrls($url)
    {
        if (!isset(self::getMediaData($url)['carousel_media'])) {
            throw new \Exception('This Instagram post does not contain carousel media.');
        }

        $urls = [];
        $carouselMedia = self::getMediaData($url)['graphql']['shortcode_media']['edge_sidecar_to_children']['edges'];
        foreach ($carouselMedia as $media) {
            if (isset($media['node']['video_url'])) {
                $urls[] = [
                    'type' => 'video',
                    'url' => $media['node']['video_url']
                ];
            } else if (isset($media['image_versions2'])) {
                $urls[] = [
                    'type' => 'image',
                    'url' => $media['node']['display_url']
                ];
            }
        }

        return $urls;
    }

    public static function author($url): string
    {
        return self::getMediaData($url)['graphql']['shortcode_media']['owner']['username'];
    }

    public static function caption($url): string
    {
        return self::getMediaData($url)['graphql']['shortcode_media']['edge_media_to_caption']['edges'][0]['node']['text'];
    }

    public static function externalId($url)
    {
        return self::getMediaData($url)['graphql']['shortcode_media']['shortcode'];
    }

    public static function thumbnailUrl($url): string
    {
        $mediaData = self::getMediaData($url);
        $thumbnailUrl = $mediaData['graphql']['shortcode_media']['display_resources'][0]['src'];

        return $thumbnailUrl;
    }

    public static function imageUrl($url): string
    {
        return self::thumbnailUrl($url);
    }

    private static function mediaId($mediaUrl)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $mediaUrl);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);

        $data = curl_exec($ch);

        if (curl_errno($ch)){
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        try {
            $data = GeneralHelper::getInnerSubstring($data, '<meta property="al:ios:url" content="instagram://media?id=', '" />');
        } catch (\Exception $e) {
            throw new \Exception ('Instagram URL: 404 not found.');
        }

        $mediaId = json_decode($data, true);
        curl_close($ch);

        return $mediaId;
    }

}
