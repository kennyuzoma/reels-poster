<?php

namespace App\Nova;

use Epartment\NovaDependencyContainer\NovaDependencyContainer;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Spatie\TagsField\Tags;

class HashtagSet extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\HashtagSet::class;

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
        'name',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make('Account')->withoutTrashed(),
            Text::make('Name'),

            Select::make('Choose Hashtag Input', 'choose')
                ->options([
                    'raw' => 'Raw Hashtags',
                    'cloud' => 'Hashtag Cloud'
                ])
                ->rules('required')
                ->showOnCreating()
                ->hideFromDetail()
                ->hideFromIndex(),

            NovaDependencyContainer::make([
                RawHashtags::make('Raw Hashtags', 'raw_hashtags')
                    ->placeholder('#fun #cookies #games')
                    ->rows(2)
                    ->alwaysShow(),
            ])->dependsOn('choose', 'raw'),

            NovaDependencyContainer::make([
                Tags::make('Tags')
            ])->dependsOn('choose', 'cloud'),

            Tags::make('Hashtags')
                ->hideWhenCreating()
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
        return [];
    }

//    /**
//     * Get the value that should be displayed to represent the resource.
//     *
//     * @return string
//     */
//    public function title()
//    {
//        $tags = $this->tags;
//
//        return $tags->map(function (Tag $tag) {
//            return $tag->name;
//        })->values();
//    }
}
