<?php

namespace App\Nova;

use App\Nova\Actions\PublishNextPost;
use App\Nova\Actions\PublishNow;
use Benjacho\BelongsToManyField\BelongsToManyField;
use Chaseconey\ExternalImage\ExternalImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Pdewit\ExternalUrl\ExternalUrl;

class PublishedPost extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Post::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Main (Posts)';

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
        $igHandle = $this->metadata['instagram']['shortcode'] ?? null;

        return [
            ID::make()->sortable(),

            BelongsTo::make('Account'),

            ExternalUrl::make('Public Link', function () use($igHandle) {
                return 'https://www.instagram.com/p/' . $igHandle;
            })->linkText('https://www.instagram.com/p/' . $igHandle),

            Select::make('Type')
                ->options([
                    'video' => 'Video',
                ])
                ->default('video')
                ->displayUsingLabels()
                ->hideWhenUpdating()
                ->rules('required'),

            ExternalImage::make('Thumbnail', function() {
                return $this->getFirstMediaUrl();
            })
                ->width(100)
                ->hideWhenUpdating()
                ->hideWhenCreating(),

            Text::make('Author')
                ->rules('required')
                ->hideWhenCreating(),


            Textarea::make('Caption')
                ->rules('required')
                ->hideFromIndex()
                ->alwaysShow(),

            Textarea::make('Caption')->displayUsing(function ($value) {
                return Str::of($value)->limit(25);
            })
                ->onlyOnIndex()
                ->alwaysShow(),

            Textarea::make('Hashtags')
                ->hideFromIndex()
                ->alwaysShow()
                ->default(function () {
                    return config('main.app.default_hashtags');
                }),

            Textarea::make('Hashtags')
                ->rules('required')
                ->displayUsing(function ($value) {
                    return Str::of($value)->limit(25);
                })
                ->onlyOnIndex()
                ->alwaysShow(),

            Textarea::make('Original Caption')
                ->rules('required')
                ->hideFromIndex()
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->alwaysShow(),

            DateTime::make('Posted At'),

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
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->whereNotNull('posted_at');
    }

    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    public function authorizedToDelete(Request $request): bool
    {
        return false;
    }

    public function authorizedToUpdate(Request $request): bool
    {
        return false;
    }

}
