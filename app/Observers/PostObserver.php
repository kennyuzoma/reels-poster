<?php

namespace App\Observers;

use App\Helpers\AppHelper;
use App\Helpers\GeneralHelper;
use App\Helpers\SiteSettingsHelper;
use App\Jobs\ImportSocialPost;
use App\Models\HashtagSet;
use App\Models\Post;
use App\Services\Instagram\Instagram;
use App\Services\TikTok\TikTok;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class PostObserver
{
    public $serviceMap = [
        'tiktok' => TikTok::class,
        'instagram' => Instagram::class
    ];

    /**
     * Handle the post "creating" event.
     *
     * @param \App\Models\Post $post
     *
     * @return void
     * @throws \Exception
     */
    public function creating(Post $post)
    {
        $service = $this->getService($post->original_public_url);

        $post->source_service = $service;
        $serviceClass = $this->serviceMap[$service];

        $publicMediaUrl = $post->original_public_url;
        $externalId = $serviceClass::externalId($publicMediaUrl);

        if (!SiteSettingsHelper::get('allow_duplicate_posts')) {
            if (Post::where('service', $post->service)->where('external_id', $externalId)->exists()) {
                throw new \Exception('This content has already been posted.');
            }
        }

        $post->external_id = $serviceClass::externalId($publicMediaUrl);
        $post->author = $serviceClass::author($publicMediaUrl);
        $post->thumbnail_url = $serviceClass::thumbnailUrl($publicMediaUrl);
        $post->original_caption = $serviceClass::caption($publicMediaUrl);

        $post->type = AppHelper::determinePostType($publicMediaUrl);

        if ($post->type == 'reels' || $post->type == 'video') {
            $post->video_url = $serviceClass::videoUrl($publicMediaUrl);
        }

        $post->status = Post::$statuses['importing'];

        // default
        if (request()->has('post_at') && !is_null(request()->get('post_at'))) {
            $post->utc_post_at = GeneralHelper::convertCurrentTimezoneTimeToUTCTime(request()->get('post_at'), SiteSettingsHelper::get('app_timezone'));
        }

        if (request()->get('use_account_timezone') == '1' && request()->get('post_at')) {
            if ($timezone = $post->account->post_time_override->timezone) {
                $post->utc_post_at = GeneralHelper::convertCurrentTimezoneTimeToUTCTime(request()->get('post_at'), $timezone);
            }
        }

        if (request()->get('save_as_draft') == 1) {
            $post->status = Post::$statuses['draft'];
        } else {
            $post->status = Post::$statuses['ready'];
        }

        unset($post->custom_hashtags);
        unset($post->hashtag_select);
        unset($post->save_new_hashtag_set);
        unset($post->schedule_post);
        unset($post->use_account_timezone);
        unset($post->edit_author);
        unset($post->save_as_draft);
        unset($post->post_now);
        unset($post->add_more_hashtags);
        unset($post->additional_hashtag_manual);
        unset($post->additional_hashtags_placement);
    }

    /**
     * Handle the post "created" event.
     *
     * @param  \App\Models\Post $post
     *
     * @return void
     */
    public function created(Post $post)
    {
        if (!is_null($post->thumbnail_url)) {
            $post
                ->addMediaFromUrl($post->thumbnail_url)
                ->toMediaCollection();
        } else {
            FFMpeg::openUrl($post->video_url)
                ->getFrameFromSeconds(1)
                ->export()
                ->toDisk('local')
                ->save($post->external_id . '.jpg');

            $post
                ->addMedia(storage_path('app/' . $post->external_id . '.jpg'))
                ->toMediaCollection();
        }

        if (request()->get('save_new_hashtag_set') == 1) {
            $hashtagSet = HashtagSet::create([
                'account_id' => $post->account->id
            ]);

            $tags = explode('-----', request()->get('hashtags'));

            $hashtagSet->attachTags($tags);

            $post->hashtag_set_id = $hashtagSet->id;
            $post->save();

            request()->except(['hashtags']);
            unset($post->hashtags);
        }

        // import the main media
        ImportSocialPost::dispatch($post, (int) request()->get('post_now'));
    }

    /**
     * Handle the post "updating" event.
     *
     * @param  \App\Models\Post  $post
     *
     * @return void
     */
    public function updating(Post $post)
    {
        unset($post->custom_hashtags);
        unset($post->hashtag_select);
        unset($post->save_new_hashtag_set);
        unset($post->schedule_post);
        unset($post->use_account_timezone);
        unset($post->edit_author);
        unset($post->save_as_draft);
        unset($post->post_now);
        unset($post->add_more_hashtags);
        unset($post->additional_hashtag_manual);
        unset($post->additional_hashtags_placement);

        if (request()->get('hashtag_select') == 'custom') {
            $post->hashtag_set_id = null;
        }

        if (request()->has('use_account_timezone')) {
            $post->utc_post_at = GeneralHelper::convertCurrentTimezoneTimeToUTCTime(request()->get('post_at'), SiteSettingsHelper::get('app_timezone'));

            if (request()->get('use_account_timezone') == '1' && request()->get('post_at')) {
                if ($timezone = $post->account->post_time_override->timezone) {
                    $post->utc_post_at = GeneralHelper::convertCurrentTimezoneTimeToUTCTime(request()->get('post_at'), $timezone);
                }
            }
        }

        if (request()->get('save_as_draft') == 1) {
            $post->status = Post::$statuses['draft'];
        } else {
            $post->status = Post::$statuses['ready'];
        }
    }

    /**
     * Handle the post "updated" event.
     *
     * @param \App\Models\Post  $post
     *
     * @return void
     */
    public function updated(Post $post)
    {
        if (request()->get('save_new_hashtag_set') == 1) {
            $hashtagSet = HashtagSet::create([
                'account_id' => $post->account->id
            ]);

            $tags = explode('-----', request()->get('hashtags'));

            $hashtagSet->attachTags($tags);

            $post->hashtag_set_id = $hashtagSet->id;
            $post->save();

            request()->except(['hashtags']);
            unset($post->hashtags);
        }

    }

    /**
     * Handle the post "deleted" event.
     *
     * @param \App\Models\Post  $post
     *
     * @return void
     */
    public function deleted(Post $post)
    {
        //
    }

    /**
     * Handle the post "restored" event.
     *
     * @param  \App\Models\Post  $post
     *
     * @return void
     */
    public function restored(Post $post)
    {
        //
    }

    /**
     * Handle the post "force deleted" event.
     *
     * @param  \App\Models\Post  $post
     *
     * @return void
     */
    public function forceDeleted(Post $post)
    {
        //
    }

    private function getService($videoUrl): string
    {
        if (Str::contains($videoUrl, 'tiktok')) {
            $service = 'tiktok';
        } elseif (Str::contains($videoUrl, 'instagram')) {
            $service = 'instagram';
        } else {
            throw new \Exception('Link not supported');
        }

        return $service;
    }

    private function determinePostType()
    {

    }
}
