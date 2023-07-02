<?php

namespace App\Observers;

use App\Jobs\ProcessTags;
use App\Models\HashtagSet;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class HashtagSetObserver
{
    public function creating(HashtagSet $hashtagSet)
    {
        unset($hashtagSet->choose);
        unset($hashtagSet->raw_hashtags);

//        $hashtagSet->tags(['1','2']);
//        request()->merge(['tags' => 'oh-----le-----baba']);
//        dd(explode(' ', str_replace('#', '', request()->get('hashtags'))));
//        $hashtagSet->tags = explode(' ', str_replace('#', '', request()->get('hashtags')));
//
//        dd($hashtagSet, request()->get('hashtags'));
//        $tags = explode(' ', str_replace('#', '', request()->get('raw_hashtags')));
//        $hashtagSet->tags = $tags;
    }

    /**
     * Handle the HashtagSet "created" event.
     *
     * @param  \App\Models\HashtagSet  $hashtagSet
     * @return void
     */
    public function created(HashtagSet $hashtagSet)
    {

    }

    public function updating(HashtagSet $hashtagSet)
    {
//        Log::info('updating');

//        unset($hashtagSet->choose);
//        unset($hashtagSet->raw_hashtags);

        $insertStatement = [];
//
//        $tags = explode(' ', str_replace('#', '', request()->get('raw_hashtags')));
//
//        $tags = collect(Tag::findOrCreate($tags));
//        $tags = $tags->pluck('id')->toArray();
//
//        foreach ($tags as $tag) {
//            $insertStatement[] = [
//                'tag_id' => $tag,
//                'taggable_type' => 'App\Models\HashtagSet',
//                'taggable_id' => $hashtagSet->id
//            ];
//        }
//
//        DB::table('taggables')->insert($insertStatement);
    }


    /**
     * Handle the HashtagSet "updated" event.
     *
     * @param  \App\Models\HashtagSet  $hashtagSet
     * @return void
     */
    public function updated(HashtagSet $hashtagSet)
    {
//        Log::info('updated');
//        $tags = explode(' ', str_replace('#', '', request()->get('raw_hashtags')));
//        ProcessTags::dispatchSync($hashtagSet, $tags);
//
//        Log::info($hashtagSet);
//        $hashtagSet->attachTag('2ed');
    }

    /**
     * Handle the HashtagSet "deleted" event.
     *
     * @param  \App\Models\HashtagSet  $hashtagSet
     * @return void
     */
    public function deleted(HashtagSet $hashtagSet)
    {
        //
    }

    /**
     * Handle the HashtagSet "restored" event.
     *
     * @param  \App\Models\HashtagSet  $hashtagSet
     * @return void
     */
    public function restored(HashtagSet $hashtagSet)
    {
        //
    }

    /**
     * Handle the HashtagSet "force deleted" event.
     *
     * @param  \App\Models\HashtagSet  $hashtagSet
     * @return void
     */
    public function forceDeleted(HashtagSet $hashtagSet)
    {
        //
    }

    public function saved(HashtagSet $hashtagSet)
    {
        $tags = explode(' ', str_replace('#', '', request()->get('raw_hashtags')));

        $hashtagSet->syncTags($tags);
    }
}
