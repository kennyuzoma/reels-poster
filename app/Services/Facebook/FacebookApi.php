<?php

namespace App\Services\Facebook;

use Facebook\Facebook;
use Illuminate\Support\Facades\Cache;

class FacebookApi {

    public $fb;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v2.5',
        ]);
    }

    public function setDefaultAccessToken($accessToken)
    {
        return $this->fb->setDefaultAccessToken($accessToken);
    }

    public function get($endpoint)
    {
        return json_decode($this->fb->get($endpoint)->getBody(), true);
    }

    public function post($endpoint, $params)
    {
        return json_decode($this->fb->post($endpoint, $params)->getBody(), true);
    }

}
