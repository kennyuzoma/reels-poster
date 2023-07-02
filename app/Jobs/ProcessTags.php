<?php

namespace App\Jobs;

use App\Helpers\ConsoleMessage;
use App\Models\Post;
use App\Notifications\SocialPostProcessing;
use App\Notifications\SocialPostFailed;
use App\Notifications\SocialPostSuccessful;
use App\Services\Instagram\InstagramPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessTags
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $hashtagSet;
    public $hashtags;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($hashtagSet, array $hashtags)
    {
        $this->hashtagSet = $hashtagSet;

        $this->hashtags = $hashtags;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
//        $arr = [
//            'tag_id' => 70,
//            'taggable_type' => 'App\\Models\\HashtagSet',
//            'taggable_id' => 75
//        ];
//        DB::table('taggables')->insert($arr);
//        dd($arr);
        $this->hashtagSet->syncTags($this->hashtags);

    }
}
