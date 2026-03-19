<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class Contact extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\Query';

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
        'id', 'fullname' , 'serve_at'
    ];

    /**
     * The logical category associated with the resource.
     *
     * @var string
     */
    public static $category = "Reports";

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

            Text::make('Fullname','name')->sortable()->rules('required', 'max:15'),

            Text::make('Email','email')->rules('required', 'email'),

            Text::make('Contact_no','phone')->rules('required', 'numeric', 'digits:10'),

            Text::make('School / Organization Name','school_name')->sortable()->rules('required', 'max:30'),

            Text::make('Role / Designation','designation')->rules('required', 'max:10'),

            Select::make('Select','channel')->options([
               'socialmedia'    => 'Social media',
               'conference'     => 'Conference',
               'wordofmouth'    => 'Word of mouth',
               'search-engine'  => 'Search Engines',
               'press-release'  => 'Press Ads / News',
               'others'         => 'Others'
            ])->displayUsingLabels()->rules('required')->hideFromIndex(),

            Textarea::make('Message','message')->hideFromIndex(),
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
