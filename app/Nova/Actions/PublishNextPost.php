<?php

namespace App\Nova\Actions;

use App\Console\SocialPublisher;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class PublishNextPost extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $post = Post::whereNull('post_at')
            ->where('status', Post::$statuses['ready'])
            ->whereNull('posted_at')
            ->first();

        $publisher = new SocialPublisher();
        $publisher->doPublish($post);

        return Action::redirect('/resources/pending-posts');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
