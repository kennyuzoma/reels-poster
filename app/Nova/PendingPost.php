<?php

namespace App\Nova;

use App\Helpers\SiteSettingsHelper;
use App\Models\HashtagSet;
use App\Models\Post;
use App\Nova\Actions\PublishNow;
use App\Nova\Actions\RetryPublish;
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
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use OptimistDigital\NovaSortable\Traits\HasSortableRows;
use Orlyapps\NovaBelongsToDepend\NovaBelongsToDepend;
use Spatie\TagsField\Tags;

class PendingPost extends Resource
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
        $listOfAccounts = \App\Models\Account::get();

        if (isset($this->account)) {

            if ($this->account->trashed()) {
                $name = $this->account->name;

                $name = '(DELETED) ' . $name;

                $listOfAccounts->push([
                    'name' => $name,
                    'id' => $this->account->id
                ]);
            }
        }

        return [
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
                ->options($listOfAccounts),

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
                ->dependsOn('type', "carousel")
                ->dependsOn('type', "photo"),


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
                ->dependsOn('type', "video")
                ->dependsOn('type', "reels"),

            Textarea::make('Caption')
                ->rules('required')
                ->hideFromIndex()
                ->rows(2)
                ->alwaysShow(),

            Textarea::make('Caption')->displayUsing(function ($value) {
                return Str::of($value)->limit(50);
            })
                ->onlyOnIndex()
                ->alwaysShow(),

            NovaBelongsToDepend::make('Call To Action Phrase', 'cta', Cta::class)
                ->optionsResolve(function ($account) {
                    return $account->ctas()->get(['id','content']);
                })
                ->openDirection('bottom')
                ->dependsOn('Account')
                ->nullable()
                ->hideFromIndex(),

            Heading::make('Author')
                ->hideWhenCreating()
                ->hideFromDetail(),

            Text::make('Author')
                ->onlyOnDetail(),

            Boolean::make('Edit Author?', 'edit_author')
                ->help($this->author ? 'The current author is <b>' . $this->author .'</b>' : null)
                ->onlyOnForms()
                ->hideWhenCreating(),

            NovaDependencyContainer::make([

                Text::make('Author')
                    ->hideWhenCreating(),

                Boolean::make('Hide Author')
                    ->hideWhenCreating(),

                Boolean::make('Hide Author Tag')
                    ->hideWhenCreating(),
            ])->dependsOn('edit_author', 1),

            Heading::make('Hashtags')
                ->hideFromDetail(),

            Select::make('Choose Hashtags Type', 'hashtag_type')->options([
                'set' => 'Hashtag Set from Account',
                'set_with_raw' => 'Hashtag Set + Raw Input',
                'set_with_cloud' => 'Hashtag Set + Cloud Tags',
                'cloud' => 'Hashtag Cloud',
                'raw' => 'Raw Hashtag Input',
                null => 'None'
            ])
                ->default('set')
                ->displayUsingLabels()
                ->onlyOnForms(),

            Text::make('Hashtag Type', 'hashtag_type')
                ->displayUsing(function() {
                    $type = null;
                    if ($this->hashtag_type == 'cloud') {
                        $type = 'Hashtag Cloud';
                    } elseif ($this->hashtag_type == 'set') {
                        $type = 'Hashtag Set from Account';
                    } elseif ($this->hashtag_type == 'raw') {
                        $type = 'Raw Hashtags';
                    } elseif ($this->hashtag_type == 'set_with_raw') {
                        $type = 'Hashtags Set + Raw Input';
                    } elseif ($this->hashtag_type == 'set_with_cloud') {
                        $type = 'Hashtag Set + Cloud Tags';
                    }

                    return $type;
                })
                ->readonly()
                ->onlyOnDetail()
                ->hideFromDetail(),

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
                            $newHTS->name = $singleHashTagSet->name ?? $singleHashTagSet->tag_list;
                            $newHTS->id = $singleHashTagSet->id;
                            $sets[] = $newHTS;
                        }
                        return collect($sets);

                    })
                    ->setResourceParentClass(self::class)
                    ->dependsOn('Account')
                    ->nullable(),
            ])
                ->dependsOn('hashtag_type', 'set')
                ->dependsOn('hashtag_type', 'set_with_raw')
                ->dependsOn('hashtag_type', 'set_with_cloud')
                ->hideFromDetail(),

            NovaDependencyContainer::make([
                 Tags::make('... hashtag cloud', 'additional_hashtags')->help('Search or enter hashtags without the "#". Hit ENTER to add a new tag'),
                Select::make('... where are we placing these hashtags?', 'hashtag_position')->options([
                    'beginning' => 'Beginning',
                    'end' => 'End',
                ])
                    ->displayUsingLabels()
                    ->default('beginning')
             ])->dependsOn('hashtag_type', 'set_with_cloud')
                ->hideFromDetail(),

            NovaDependencyContainer::make([
                Textarea::make('... type or paste (manual)', 'raw_hashtags')
                    ->placeholder('#fun #cookies #games')
                    ->rows(2)
                    ->alwaysShow(),
                Select::make('... where are we placing these hashtags?', 'hashtag_position')->options([
                    'beginning' => 'Beginning',
                    'end' => 'End',
                ])
                    ->default('beginning')
            ])->dependsOn('hashtag_type', 'set_with_raw')
                ->hideFromDetail(),

            NovaDependencyContainer::make([
                Tags::make('Hashtag Cloud', 'hashtags')->help('Search or enter hashtags without the "#". Hit ENTER to add a new tag'),
            ])->dependsOn('hashtag_type', 'cloud'),

            NovaDependencyContainer::make([
                Textarea::make('Hashtags (raw type or paste)', 'raw_hashtags')
                    ->placeholder('#fun #cookies #games')
                    ->rows(2),
            ])->dependsOn('hashtag_type', 'raw'),

            NovaDependencyContainer::make([
                Boolean::make('Save as new Hashtag Set to Account?', 'save_new_hashtag_set', null)
                    ->onlyOnForms(),
            ])
                ->dependsOn('hashtag_type', "cloud")
                ->dependsOn('hashtag_type', "raw"),

            Text::make('Original Caption')
                ->rules('required')
                ->hideFromIndex()
                ->hideWhenUpdating()
                ->hideWhenCreating(),

            Heading::make('Final Steps')
                ->onlyOnForms(),
//            NovaDependencyContainer::make([
                Boolean::make('Schedule Post?', 'schedule_post')
                    ->onlyOnForms(),
//            ])->dependsOn('post_now', 1),

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

            Boolean::make('Draft?', 'save_as_draft')
                ->help('Changes the status to "Draft" to prevent it from being posted')
                ->onlyOnForms()
                ->withMeta(['value' => $this->status == Post::$statuses['draft']]),

            NovaDependencyContainer::make([
                Boolean::make('Post Now?', 'post_now')
                    ->hideWhenUpdating()
                    ->hideFromDetail()
            ])
                ->dependsOn('schedule_post', 0)
                ->dependsOn('save_as_draft', 0)
                ->hideWhenUpdating()
                ->hideFromDetail()
        ];
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
                if ($request instanceof ActionRequest) {
                    return true;
                }

                return $request instanceof ActionRequest || $this->resource->status === Post::$statuses['ready'];
            }),
            (new RetryPublish())->showOnTableRow()->canSee(function ($request) {
                if ($request instanceof ActionRequest) {
                    return true;
                }

                return $request instanceof ActionRequest || $this->resource->status === Post::$statuses['failed'];
            })
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
        $query->whereNull('posted_at');
        return parent::indexQuery($request, static::indexSortableQuery($request, $query));
    }

    function insertIntoArray($fields, $position, $itemToInsert)
    {
        return array_merge(array_slice($fields, 0, $position), array($itemToInsert), array_slice($fields, $position));
    }

    public function showPostToAccountTimezoneOption()
    {
        $return = true;
        if (isset($this->account->post_time_override->timezone)) {
            $return = $this->account->post_time_override->timezone != SiteSettingsHelper::get('app_timezone');
        }

        return $return;
    }


}
