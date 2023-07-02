<?php

namespace App\Services\TikTok\Providers;

use App\Helpers\GeneralHelper;
use App\Services\TikTok\TikTok;

class GotTikApp implements DataProviderInterface {

    public static function getData($url)
    {
        $api = 'https://gotik.app/cover-downloader';

        $tokenResponse = TikTok::curlRequest($api);
//
        $token = GeneralHelper::getInnerSubstring($tokenResponse, '<input type="hidden" id="token" class="token" value="', '">');

        $response = TikTok::curlRequest('https://gotik.app/ajax', [
            'url' => $url,
            'token' => $token,
            'type' => 'cover'
        ], true, ['PHPSESSID=fgipr8v6tt54p9npe69hh03vva']);
dd($response);
        $response = json_decode($response, true);

        if ($response['error'] == true) {
            throw new \Exception('There was an error getting the tiktok data');
        }

        $data = [];
        $data['desc'] = GeneralHelper::getInnerSubstring($response['html'], '<p class="card-subtitle ">', '</p>');
        $data['author'] = GeneralHelper::getInnerSubstring($response['html'], '<h3 class="card-title">', '</h3>');
        $data['video']['cover'] = GeneralHelper::getInnerSubstring($response['html'], '<div class="link pt-0"><a rel="noreferrer" target="_blank" href="', '" class="btn btn-success">Download Cover</a></div>');;

        return $data;
    }

}
