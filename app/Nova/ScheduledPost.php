<?php

namespace App\Nova;

use App\Helpers\SiteSettingsHelper;
use App\Models\HashtagSet;
use App\Models\Post;
use App\Nova\Actions\PublishNow;
use App\Nova\Actions\ReQueue;
use Chaseconey\ExternalImage\ExternalImage;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Epartment\NovaDependencyContainer\HasDependencies;
use Epartment\NovaDependencyContainer\NovaDependencyContainer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Status;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use OptimistDigital\NovaSortable\Traits\HasSortableRows;
use Orlyapps\NovaBelongsToDepend\NovaBelongsToDepend;
use Spatie\TagsField\Tags;

class ScheduledPost extends Resource
{
    use HasDependencies;
    use HasSortableRows{
        indexQuery as indexSortableQuery;
    }

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Post::class;

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Main (Posts)';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'caption'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        $fields = [
            ID::make()->sortable(),

            Select::make('Source Service')
                ->options([
                    'tiktok' => 'TikTok',
                    'instagram' => 'Instagram'
                ])
                ->displayUsingLabels()
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->rules('required'),

            Number::make('Sort Order')
                ->readonly()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            NovaBelongsToDepend::make('Account')
                ->placeholder('Select Account')
                ->options(\App\Models\Account::all()),

            Select::make('Type')
                ->options([
                    'reels' => 'Reels',
                    'photo' => 'Single Photo',
                    'carousel' => 'Carousel'
                ])
                ->default('reels')
                ->displayUsingLabels()
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->rules('required'),

            Status::make('Status', function() {
                return ucwords(str_replace('_', ' ', array_flip(Post::$statuses)[$this->status]));
            })
                ->loadingWhen(['Processing', 'Importing'])
                ->failedWhen(['Failed']),

            ExternalImage::make('Thumbnail', function() {
                return $this->getFirstMediaUrl();
            })
                ->width(100)
                ->hideFromDetail()
                ->hideWhenUpdating()
                ->hideWhenCreating(),

            NovaDependencyContainer::make([
                Images::make('Media', $this->type) // second parameter is the media collection name
                ->fullSize() // full size column
                ->singleImageRules('dimensions:min_width=100')
                    ->hideWhenCreating()
            ])
                ->dependsOn('type', 'carousel')
                ->dependsOn('type', 'photo'),


            Text::make('Link (Instagram, TikTok)', 'original_public_url')
                ->hideFromIndex()
                ->required(),

            NovaDependencyContainer::make([
                Text::make('Video', function () {
                    if ($this->source_service == 'instagram') {
                        return '<a href="'.$this->video_url.'" target="_blank">View Video in new tab</a>';
                    }
                    return '
                    <video height="400" controls autoplay muted loop>
                      <source  src="' . $this->video_url . '" type="video/mp4">
                    </video>
                ';
                })
                    ->onlyOnDetail()
                    ->asHtml()
                    ->help('<a href="' . $this->video_url .'">View Large Video</a>')
            ])
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->dependsOn('type', 'video')
                ->dependsOn('type', 'reels'),

            Textarea::make('Caption')
                ->rules('required')
                ->hideFromIndex()
                ->alwaysShow(),

            Textarea::make('Caption')->displayUsing(function ($value) {
                return Str::of($value)->limit(50);
            })
                ->onlyOnIndex()
                ->alwaysShow(),

            Text::make('Author')
                ->rules('required')
                ->hideWhenCreating(),

            Select::make('Choose Hashtag Type', 'hashtag_select')->options([
                'from_account' => 'Hashtag Set from Account',
                'custom' => 'Hashtag Cloud',
            ])
                ->withMeta(['value' => $this->getHashtagMode() ?? 'from_account'])
                ->displayUsingLabels()
                ->onlyOnForms(),

            Text::make('Hashtag Type', 'hashtag_type')
                ->displayUsing(function() {
                    $type = null;
                    if ($this->getHashtagMode() == 'custom') {
                        $type = 'Hashtag Cloud';
                    } elseif ($this->getHashtagMode() == 'from_account') {
                        $type = 'Hashtag Set from Account';
                    }

                    return $type;
                })
                ->readonly()
                ->onlyOnDetail(),

            Text::make('Hashtags', 'hashtags')
                ->displayUsing(function() {
                    return $this->hashtags;
                })
                ->readonly()
                ->onlyOnDetail(),

            NovaDependencyContainer::make([
                NovaBelongsToDepend::make('Hashtag Set from Account', 'hashtagSet', \App\Nova\HashtagSet::class)
                    ->openDirection('bottom')
                    ->placeholder('Select a hashtag set from an account')
                    ->optionsResolve(function ($account) {
                        $sets = [];
                        foreach ($account->hashtagSets as $singleHashTagSet) {
                            $newHTS = new HashtagSet();
                            $newHTS->tag_list = $singleHashTagSet->tag_list;
                            $newHTS->id = $singleHashTagSet->id;
                            $sets[] = $newHTS;
                        }
                        return collect($sets);

                    })
                    ->dependsOn('Account')
                    ->nullable(),
            ])->dependsOn('hashtag_select', 'from_account'),

            NovaDependencyContainer::make([
                Tags::make('Hashtag Cloud', 'hashtags')->help('Hit ENTER to add a new tag'),
                Boolean::make('Save as new Hashtag Set to Account?', 'save_new_hashtag_set', null)
                    ->onlyOnForms(),
            ])->dependsOn('hashtag_select', 'custom'),

            Textarea::make('Original Caption')
                ->rules('required')
                ->hideFromIndex()
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->alwaysShow(),

            Boolean::make('Schedule Post?', 'schedule_post', null)
                ->onlyOnForms(),

            NovaDependencyContainer::make([
                Heading::make('Schedule Post'),

                Boolean::make('Use the account\'s timezone?', 'use_account_timezone')
                    ->onlyOnForms()
                    ->showOnCreating(function() {
                        return $this->showPostToAccountTimezoneOption();
                    })
                    ->withMeta(['value' => true])
                    ->help('Uses the account\'s timezone to schedule this post. If unchecked, it will use the app\'s timezone (' . SiteSettingsHelper::get('app_timezone') . ').'),

                DateTime::make('Post At')
                    ->hideFromIndex(),
            ])->dependsOn('schedule_post', 1),
        ];

        foreach ($this->hashtagFields() as $hashtag_field) {
            $fields = $this->insertIntoArray($fields, 3, $hashtag_field );
        }

        return $fields;
    }

    public function hashtagFields()
    {
        $accounts = \App\Models\Account::get();
        $hashtags = [];
        $hashtagContainer = [];

        foreach ($accounts as $account) {
            $hashtags[$account->id] = [
                Textarea::make('Hashtags for ' . $account->username, function() use($account) {
                    return $account->default_hashtags;
                })
            ];
        }

        foreach ($accounts as $account) {
            $hashtagContainer[] = NovaDependencyContainer::make($hashtags[$account->id])
                ->dependsOn('account.id', $account->id);
        }

        return $hashtagContainer;
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            (new PublishNow())->exceptOnIndex()->canSee(function ($request) {
                return $this->status === Post::$statuses['ready'];
            }),
            (new ReQueue())->showOnTableRow()->canSee(function ($request) {
                return $this->status === Post::$statuses['failed'];
            })
            // PublishNextPost::make()->standalone()
        ];
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        $query->whereNotNull('utc_post_at');
        return parent::indexQuery($request, static::indexSortableQuery($request, $query));
    }

    function insertIntoArray($fields, $position, $itemToInsert)
    {
        return array_merge(array_slice($fields, 0, $position), array($itemToInsert), array_slice($fields, $position));
    }

    public function showPostToAccountTimezoneOption()
    {
        $return = true;
        if (isset($this->account->post_time_override['timezone'])) {
            return $this->account->post_time_override['timezone'] != SiteSettingsHelper::get('app_timezone');
        }

        return $return;
    }


}
