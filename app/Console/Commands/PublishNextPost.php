<?php

namespace App\Console\Commands;

use App\Helpers\GeneralHelper;
use App\Helpers\SiteSettingsHelper;
use App\Jobs\ProcessSocialPost;
use App\Models\Account;
use App\Models\Post;
use Illuminate\Console\Command;


class PublishNextPost extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes the next post in the queue';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $currentHiTimeInUTC = date('H:i');

        $postNow = false;

        // check what hour we are on
        // if we are on a correct hour, check if there are posts
        // if there are posts then post

        if (!SiteSettingsHelper::get('enable_scheduler_posting')) {
            throw new \Exception('Post scheduler is disabled. Please enable it to run');
        }

        $accounts = Account::get();

        foreach ($accounts as $account) {
            $postCount = $account->posts()->whereNull('posted_at')->count();
            $postTimeOverride = $account->post_time_override;

            // if there is a custom time set
            // note: these times are in the timezone of the acount
            if (isset($postTimeOverride->time_1) && isset($postTimeOverride->timezone)) {
                $times = [
                    $postTimeOverride->time_1 ?? null,
                    $postTimeOverride->time_2 ?? null,
                    $postTimeOverride->time_3 ?? null,
                    $postTimeOverride->time_4 ?? null,
                    $postTimeOverride->time_5 ?? null
                ];

                if ($account->daily_post_limit) {
                    $times = array_splice($times, 0, $account->daily_post_limit);
                }

                // convert times to the correct timezone
                $postNow = in_array(GeneralHelper::getTimezoneTime($currentHiTimeInUTC, $postTimeOverride->timezone, 'H:i'), $times);
            } else {
                $postNow = $this->globalPostNow($currentHiTimeInUTC, $postCount, $account->daily_post_limit);
            }

            if ($postNow) {
                $post = $account
                    ->posts()
                    ->whereNull('post_at')
                    ->where('status', Post::$statuses['ready'])
                    ->whereNull('posted_at')
                    ->orderBy('sort_order', 'asc')
                    ->first();

                if ($post) {
                    ProcessSocialPost::dispatch($post);
                }
            }
        }
    }

    private function globalPostNow($currentHiTime, int $postCount, $accountPostLimit = null)
    {
        $times = [
            SiteSettingsHelper::get('time_1'),
            SiteSettingsHelper::get('time_3'),
            SiteSettingsHelper::get('time_5')
        ];

        if ($postCount >= 4) {
            $times[] = SiteSettingsHelper::get('time_2');
            if ($postCount >= 5) {
                $times[] = SiteSettingsHelper::get('time_4');
            }
        }

        if ($accountPostLimit) {
            $times = array_splice($times, 0, $accountPostLimit);
        }

        return in_array(GeneralHelper::getTimezoneTime($currentHiTime, SiteSettingsHelper::get('app_timezone'), 'H:i'), $times);
    }
}
