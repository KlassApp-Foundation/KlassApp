<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Place;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class ParentProfile extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\ParentProfile';

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
        'id',
    ];

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation =  false ;

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

            BelongsTo::make('School','school',\App\Nova\School::class)->hideWhenUpdating()->hideFromIndex(),

            BelongsTo::make('User','user',\App\Nova\ParentUser::class)->hideWhenUpdating(),

            BelongsTo::make('Qualification','qualification',\App\Nova\Qualification::class)->hideFromIndex(),

            Select::make('Profession','profession')->options([
                'business'                      =>  'Business',
                'central_government_employee'   =>  'Central Government Employee',
                'home_maker'                    =>  'Home Maker',
                'others'                        =>  'Others',
                'private'                       =>  'Private',
                'state_government_employee'     =>  'State Government Employee',
            ])->displayUsingLabels()->nullable(),

            Text::make('Sub Occupation','sub_occupation')->nullable(),

            Text::make('Designation','designation')->nullable()->hideFromIndex(),

            Text::make('Organization Name','organization_name')->nullable()->hideFromIndex(),

            Place::make('Official Address','official_address')->nullable(),

            Text::make('Relation', function () {
                return ucfirst($this->relation);
            })->nullable(),

            Number::make('Annual Income','annual_income')->nullable()->hideFromIndex(),
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
        return [
            new Filters\UserProfessionFilter,
        ];
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
