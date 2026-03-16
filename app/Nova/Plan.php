<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class Plan extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\Plan';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name'
    ];

    /**
     * The logical category associated with the resource.
     *
     * @var string
     */
    public static $category = "Setting";

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

            Text::make('Cycle','cycle')->rules('required'),

            Text::make('Name','name')->rules('required'),

            Number::make('Order','order')->rules('required'),

            Boolean::make('Status','is_active')->rules('required'),

            Number::make('Amount','amount')->rules('required'),

            Number::make('Number Of Members','no_of_members')->rules('required')->hideFromIndex(),

            Number::make('Number Of Events','no_of_events')->rules('required')->hideFromIndex(),

            Number::make('Number Of Folders','no_of_folders')->rules('required')->hideFromIndex(),

            Number::make('Number Of Files','no_of_files')->rules('required')->hideFromIndex(),

            Number::make('Number Of Videos','no_of_videos')->rules('required')->hideFromIndex(),

            Number::make('Number Of Files','no_of_bulletins')->rules('required')->hideFromIndex(),

            Number::make('Number Of Videos','no_of_groups')->rules('required')->hideFromIndex(),
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
