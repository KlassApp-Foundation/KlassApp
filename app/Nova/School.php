<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class School extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\School';

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
        'id','name'
    ];

    /**
     * The logical category associated with the resource.
     *
     * @var string
     */
    public static $category = "Academics";

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
       //
    }

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

            Text::make('Name', 'name')->rules('required'),

            HasMany::make('Admin','admin',\App\Nova\Admin::class),

            HasMany::make('User','user',\App\Nova\User::class),

            Text::make('Email', 'email')->rules('required', 'email'),

            Text::make('Phone', 'phone')->rules('required', 'numeric', 'digits:10'),

            Text::make('Address', 'address')->hideFromIndex()->rules('required'),

            BelongsTo::make('City','city',\App\Nova\City::class)->rules('required')->hideFromIndex(),

            BelongsTo::make('State','state',\App\Nova\State::class)->rules('required')->hideFromIndex(),

            BelongsTo::make('Country','country',\App\Nova\Country::class)->rules('required')->hideFromIndex(),

            Text::make('Pincode','pincode')->rules('required', 'numeric' , 'digits:6')->hideFromIndex(),
            
            /*Text::make('ActiveMembers', function() {
                if( count($this->ActiveMembers) )
                {
                    return $this->ActiveMembers;
                }  
                else
                {
                   return 0;
                }
            }),

            Text::make('PaidMembers', function() {
                if( count($this->PaidMembers) )
                {
                    return $this->PaidMembers;
                }  
                else
                {
                   return 0;
                }
            }),*/

            Boolean::make('status')->rules('required'),
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
        return [
            new Metrics\TotalSchool,
            new Metrics\ActiveSchool,
            new Metrics\PaidSchool,
        ];
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
            new Actions\SuspendOrActivateSchool,
        ];
    }
}
