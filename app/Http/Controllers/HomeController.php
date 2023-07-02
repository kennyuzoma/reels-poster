<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\SocialIdentity;
use App\Models\User;
use App\Services\Facebook\FacebookApi;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{

    public function index()
    {
        try {
            // initialize the facebook api
            $fbApi = new FacebookApi();

            $user = auth()->user();

            $socialIdentity = $user->identities()->where('provider_name', 'facebook')->first();
            if (!$socialIdentity) {
                return redirect(route('facebook.login'));
            }

            if (!$socialIdentity->access_token) {
                return 'Access Token is not valid. Please <a href="/facebook/login">re-login</a>';
            }

            $fbApi->setDefaultAccessToken($socialIdentity->access_token);
            $accounts = $fbApi->get('/me/accounts?fields=instagram_business_account,name')['data'];

            $this->syncDisconnectAccounts($accounts);

            // get the user's pages
            $data['accounts'] = $accounts;
            $data['accessToken'] = $socialIdentity->access_token;

            $data['localhostAccessToken'] = false;
            if (config('app.env') == 'production') {
                $data['localhostAccessToken'] = true;
            }

            return view('home', $data);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function syncDisconnectAccounts($accounts)
    {
        $accounts = collect($accounts);
        $externalIds = $accounts->pluck('id');

        Account::where('service', 'instagram')
            ->whereNotIn('external_id', $externalIds->toArray())
            ->update(['status' => 2]);
    }

}
