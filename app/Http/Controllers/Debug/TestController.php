<?php

namespace App\Http\Controllers\Debug;

use App\Models\HashtagSet;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Instagram\Instagram;
use App\Services\Instagram\InstagramPublisher;
use Illuminate\Support\Facades\DB;

class TestController {

    public function igPostTester(Post $post)
    {
        dd((new InstagramPublisher())->setPost($post)->caption());
    }

    public function test()
    {
        $hashtagSet = HashtagSet::find(32);
        $hashtagSet->attachTags(['o','m']);

        $this->hashtagSet->attachTags($this->hashtags)

        $tags = explode(' ', str_replace('#', '', '#yeah #baby #yeahsdasd'));
        $tags = collect(Tag::findOrCreate($tags));
        $tags = $tags->pluck('id')->toArray();

        foreach ($tags as $tag) {
            $insertStatement[] = [
                'tag_id' => $tag,
                'taggable_type' => 'App\Models\HashtagSet',
                'taggable_id' => $hashtagSet->id
            ];
        }

        DB::table('taggables')->insert($insertStatement);

//        dd(Instagram::getMediaData('https://www.instagram.com/p/Cj042yKOtjx/'));
    }

}
