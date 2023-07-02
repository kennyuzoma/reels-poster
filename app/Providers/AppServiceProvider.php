<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\HashtagSet;
use App\Models\Post;
use App\Observers\AccountObserver;
use App\Observers\HashtagSetObserver;
use App\Observers\PostObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Post::observe(PostObserver::class);
        Account::observe(AccountObserver::class);
        HashtagSet::observe(HashtagSetObserver::class);

    }
}
