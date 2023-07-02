<?php

namespace App\Jobs;

use App\Helpers\ConsoleMessage;
use App\Models\Post;
use App\Notifications\SocialPostProcessing;
use App\Notifications\SocialPostFailed;
use App\Notifications\SocialPostSuccessful;
use App\Notifications\SponsoredPostTimeToDelete;
use App\Services\Instagram\Instagram;
use App\Services\Instagram\InstagramPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SponsoredPostNotifyDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * The post instance.
     *
     * @var \App\Models\Post
     */
    public Post $post;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->post->notify(new SponsoredPostTimeToDelete());

        return 0;
    }

}
