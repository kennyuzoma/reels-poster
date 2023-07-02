<?php

namespace App\Console\Commands;

use App\Jobs\SponsoredPostNotifyDelete;
use App\Models\Post;
use App\Notifications\SponsoredPostTimeToDelete;
use Illuminate\Console\Command;


class SponsoredPostCheckForScheduledDelete extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:sponsored-check-for-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for sponsored posts that scheduled to be deleted';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $sponsoredPosts = Post::whereNotNull('sponsor_id')
            ->where('status', Post::$statuses['published'])
            ->where('delete_at', '<=', date('Y-m-d H:i:s'))
            ->where('status', '!=', Post::$statuses['social_deleted'])
            ->get();

        foreach ($sponsoredPosts as $post) {
            SponsoredPostNotifyDelete::dispatch($post);
        }
    }
}
