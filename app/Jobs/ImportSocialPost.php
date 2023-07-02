<?php

namespace App\Jobs;

use App\Helpers\ConsoleMessage;
use App\Models\Post;
use App\Notifications\SocialPostProcessing;
use App\Notifications\SocialPostFailed;
use App\Notifications\SocialPostSuccessful;
use App\Services\Instagram\Instagram;
use App\Services\Instagram\InstagramPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportSocialPost implements ShouldQueue
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

    public bool $postNow;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Post $post, bool $postNow = false)
    {
        $this->post = $post;
        $this->postNow = $postNow;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->post->status = Post::$statuses['importing'];
        $this->post->save();

        if ($this->post->type == 'reels') {
            $this->post
                ->addMediaFromUrl($this->post->video_url)
                ->toMediaCollection('reels');
        }

        if ($this->post->type == 'video') {
            $this->post
                ->addMediaFromUrl($this->post->video_url)
                ->toMediaCollection('video');
        }

        if ($this->post->type == 'carousel') {
            foreach (Instagram::carouselMediaUrls($this->post->original_public_url) as $media) {
                $this->post
                    ->addMediaFromUrl($media['url'])
                    ->toMediaCollection('carousel');
            }
        }

        if ($this->post->type == 'photo') {
            $this->post
                ->addMediaFromUrl(Instagram::imageUrl($this->post->original_public_url))
                ->toMediaCollection('photo');
        }

        $this->post->downloaded = 1;
        $this->post->status = 1;
        $this->post->save();

        if ($this->postNow) {
            ProcessSocialPost::dispatch($this->post);
        }

        return 0;
    }

}
