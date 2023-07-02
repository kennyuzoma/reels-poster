<?php

namespace App\Console\Commands;

use App\Helpers\SiteSettingsHelper;
use App\Jobs\ProcessSocialPost;
use App\Models\Account;
use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPost extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:scheduled-publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes a scheduled post';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!SiteSettingsHelper::get('enable_scheduler_posting')) {
            throw new \Exception('Post scheduler is disabled. Please enable it to run');
        }

        $accounts = Account::get();
        foreach ($accounts as $account) {
            $post = $account
                ->posts()
                ->where('post_at', '<=', date('Y-m-d H:i:s'))
                ->where('status', Post::$statuses['ready'])
                ->whereNull('posted_at')
                ->first();

            if ($post) {
                ProcessSocialPost::dispatch($post);
            }
        }

    }

}
