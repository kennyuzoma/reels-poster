<?php

namespace App\Nova;

use App\Rules\MilitaryTime;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laraning\NovaTimeField\TimeField;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Timezone;
use Naoray\NovaJson\JSON;
use R64\NovaFields\Row;
use Spatie\TagsField\Tags;

class Account extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Account::class;

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
    public static $group = 'Users';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email',
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
            ID::make()->sortable(),

            BelongsTo::make('User'),

            Select::make('Service')
                ->options([
                    'instagram' => 'Instagram',
                ])
                ->default('instagram')
                ->displayUsingLabels()
                ->hideWhenUpdating()
                ->rules('required'),

            Text::make('External ID')
                ->hideFromIndex(),

            Text::make('Username'),

            Text::make('Name'),

            Text::make('Default Hashtags', function() {
                return Str::limit($this->default_hashtags, 30);
            })->onlyOnIndex(),

            Boolean::make('Caption Template Override', function() {
               return !is_null($this->caption_template_override);
            })->onlyOnIndex(),

            Textarea::make('Caption Template Override')
                ->alwaysShow(),

            Select::make('Status')
                ->options([
                    1 => 'Active',
                    2 => 'Disconnected',
                    3 => 'Banned',
                ])
                ->default(1)
                ->displayUsingLabels()
                ->hideWhenUpdating()
                ->rules('required'),

            Heading::make('Override Global Post Times'),

            JSON::make('Post Time Override', [
                Timezone::make('Timezone')->rules('required_with:time_3'),
//                Row::make('Lines', [
//                    \R64\NovaFields\Number::make('Hour')
//                        ->fieldClasses('w-full px-8 py-6')
//                        ->hideLabelInForms(),
//                    \R64\NovaFields\Number::make('Minute')
//                        ->fieldClasses('w-full px-8 py-6')
//                        ->hideLabelInForms(),
//
//                ])
//                    ->maxRows(5)
//                    ->addRowText('Add Time'),
                Text::make('Time 1')->rules('nullable', new MilitaryTime)->help('In military time... "23:00"'),
                Text::make('Time 2')->rules('nullable', new MilitaryTime)->help('In military time... "23:00"'),
                Text::make('Time 3')->rules('nullable', new MilitaryTime)->help('In military time... "23:00"'),
                Text::make('Time 4')->rules('nullable', new MilitaryTime)->help('In military time... "23:00"'),
                Text::make('Time 5')->rules('nullable', new MilitaryTime)->help('In military time... "23:00"'),
            ])->hideFromIndex(),

            Heading::make('Other Settings'),

            JSON::make('Custom Settings', [
                Number::make('Daily Post Limit')
            ]),

            HasMany::make('Hashtag Set', 'hashtagSets'),

            HasMany::make('Pending Posts', 'posts'),

            HasMany::make('Published Posts', 'posts')
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

}
