<?php

namespace App\Http\Controllers;

use App\Helpers\GeneralHelper;
use App\Models\Account;
use App\Models\SocialIdentity;
use App\Models\User;
use App\Services\Facebook\FacebookApi;
use Facebook\Facebook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FacebookController extends Controller
{
    public $fb;

    public function __construct()
    {
        session_start();

        $this->fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v2.5',
        ]);
    }

    public function login()
    {
        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = [
            'email',
            'instagram_basic',
            'instagram_content_publish',
            'pages_show_list',
            'pages_read_engagement'
        ];
        $loginUrl = $helper->getLoginUrl(config('app.url') . '/facebook/callback', $permissions);

        echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
    }

    /**
     * @throws \Facebook\Exceptions\FacebookSDKException
     * @throws \Exception
     */
    public function callback()
    {
        $helper = $this->fb->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\facebookResponseException $e) {
            throw new \Exception('Graph returned an error: ' . $e->getMessage());
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            throw new \Exception('Facebook SDK returned an error: ' . $e->getMessage());
        }

        if (isset($accessToken)) {
            try {
                $user = auth()->user();

                $oAuth2Client = $this->fb->getOAuth2Client();
                $accessToken = (string) $oAuth2Client->getLongLivedAccessToken((string) $accessToken);
                $this->fb->setDefaultAccessToken($accessToken);

                $me = json_decode($this->fb->get('/me?fields=id,name')->getBody(), true);

                $identity = SocialIdentity::where('user_id', $user->id)->where('provider_name', 'facebook')->where('provider_id', $me['id'])->first();

                if (!$identity) {
                    SocialIdentity::create([
                        'user_id' => $user->id,
                        'provider_name' => 'facebook',
                        'provider_id' => $me['id'],
                        'access_token' => $accessToken,
                        'metadata' => $me
                    ]);
                } else {
                    $identity->access_token = $accessToken;
                    $identity->metadata = $me;
                    $identity->save();
                }

            } catch(Facebook\Exceptions\FacebookResponseException $e) {
                throw new \Exception('Graph returned an error: ' . $e->getMessage());
            } catch(Facebook\Exceptions\FacebookSDKException $e) {
                throw new \Exception('Facebook SDK returned an error: ' . $e->getMessage());
            }

            return redirect(route('home'));

        } else {
            return redirect('/facebook/login');
        }
    }

    public function postVideo()
    {

    }

    public function get($endpoint)
    {
        return json_decode($this->fb->get($endpoint)->getBody(), true);
    }

    public function post($endpoint, $params)
    {
        return json_decode($this->fb->post($endpoint, $params)->getBody(), true);

    }

    public function igAccAdd(Request $request): RedirectResponse
    {
        $this->fb->setDefaultAccessToken(auth()->user()->identities()->where('provider_name', 'facebook')->first()->access_token);
        $accountInfo = GeneralHelper::searchArray(
            'id',
            $request->get('accountId'),
            $this->get('/me/accounts?fields=instagram_business_account,name,category,category_list')['data']
        );

        $igInfo = $this->get($accountInfo['instagram_business_account']['id'].'?fields=username');

        // if this account doesnt exist
        if (!Account::where('external_id', $accountInfo['instagram_business_account']['id'])->first()) {

            // if this account existed before, we will restore it
            if ($originalAccount = Account::withTrashed()->where('external_id', $accountInfo['instagram_business_account']['id'])->first()) {
                $originalAccount->restore();
            } else {
                // $user = auth()->user();
                $user = User::find(1);

                Account::create([
                    'user_id' => $user->id,
                    'service' => 'instagram',
                    'external_id' => $accountInfo['instagram_business_account']['id'],
                    'username' => $igInfo['username'],
                    'name' => $accountInfo['name'],
                    'metadata' => [
                        'category' => $accountInfo['category'],
                        'category_list' => $accountInfo['category_list']
                    ]
                ]);
            }
        }

        return redirect(route('home'));
    }

    public function igAccRemove(Request $request): RedirectResponse
    {
        Account::find($request->get('accountId'))->delete();

        return redirect(route('home'));
    }
}
