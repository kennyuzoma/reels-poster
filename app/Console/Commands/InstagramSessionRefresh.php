<?php

namespace App\Console\Commands;

use App\Helpers\GeneralHelper;
use App\Helpers\SiteSettingsHelper;
use App\Notifications\InstagramSessionExpired;
use App\Notifications\InstagramSessionRefreshed;
use App\Notifications\InstagramSessionRefreshing;
use App\Services\Instagram\Instagram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;

class InstagramSessionRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:session-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes an instagram session';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Notification::route('slack', config('services.slack.webhook_url'))
            ->notify(new InstagramSessionRefreshing());

        $this->info('Removing old igCookies');
        if (File::exists(storage_path('igCookies.txt'))) {
            unlink(storage_path('igCookies.txt'));
        }

        $this->info('Running Cypress to get the new cookie');

        if (config('app.env') == 'production') {
            $output = shell_exec('./node_modules/.bin/cypress run --spec "cypress/e2e/instagram.cy.js" --browser firefox');
        } else {
            $output = shell_exec('./node_modules/.bin/cypress run  --spec "cypress/e2e/instagram.cy.js"');
        }

        $this->line($output);

        $this->info('Waiting 90 seconds just incase');
        sleep(90);

        $this->info('Validating new igCookies file exists');
        $file = storage_path('igCookies.txt');
        if (!File::exists($file)) {
            throw new \Exception('The igCookies file is not available');
        }

        $igCookiesArray = json_decode(File::get($file), TRUE);
        $sessionInfo = GeneralHelper::searchArray('name', 'sessionid', $igCookiesArray);

        $this->info('Setting the new cookies file');
        SiteSettingsHelper::save('instagram_session_id', $sessionInfo['value']);

        $this->info('Instagram session successfully updated!');

        Notification::route('slack', config('services.slack.webhook_url'))
            ->notify(new InstagramSessionRefreshed());

        return 0;
    }
}
