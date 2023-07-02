<?php

namespace App\Console\Commands;

use App\Helpers\SiteSettingsHelper;
use App\Notifications\InstagramSessionExpired;
use App\Notifications\InstagramSessionRefreshing;
use App\Services\Instagram\Instagram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

class InstagramSessionCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:session-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if instagram session is valid';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!is_null(SiteSettingsHelper::get('instagram_session_id'))) {
            try {
                $url = 'https://www.instagram.com/p/Cgf9B6SOMpH/';
                Instagram::getMediaData($url);
            } catch (\Exception $e) {
                Notification::route('slack', config('services.slack.webhook_url'))
                    ->notify(new InstagramSessionExpired());

                Artisan::call('instagram:session-refresh');
            }
        }

        return 0;
    }
}
