<?php

namespace App\Console;

use App\Helpers\SiteSettingsHelper;
use App\Jobs\ProcessSocialPost;
use App\Models\Account;
use App\Models\Post;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $email = 'kenny@uzouniverse.com';

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        $schedule->command('instagram:session-check')->timezone('America/Los_Angeles')->twiceDaily(rand(1,9), rand(13,23));

        $schedule->command('post:scheduled-publish')->timezone('America/Los_Angeles')->everyMinute()->emailOutputOnFailure($this->email);

        $schedule->command('post:publish')->hourly();

        $schedule->command('post:sponsored-check-for-delete')->hourly();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
