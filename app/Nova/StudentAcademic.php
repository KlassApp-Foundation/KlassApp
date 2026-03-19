<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Code;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class StudentAcademic extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\StudentAcademic';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'user';

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

            BelongsTo::make('Academic Year','academicyear',\App\Nova\AcademicYear::class)->hideFromIndex(),

            BelongsTo::make('School','school',\App\Nova\School::class)->hideFromIndex(),

            BelongsTo::make('User','user',\App\Nova\User::class),

            BelongsTo::make('StandardLink','standardLink',\App\Nova\StandardLink::class),

            Number::make('Roll Number','roll_number')->rules('required','numeric'),

            Number::make('Id Card Number','id_card_number')->rules('required','numeric')->hideFromIndex(),

            Number::make('Board Registration Number','board_registration_number')->rules('nullable','numeric')->hideFromIndex(),

            Select::make('Mode Of Transport','mode_of_transport')->options([
                'auto'          => 'Auto',
                'car'           => 'Car',
                'city_bus'      => 'City Bus',
                'cycle'         => 'Cycle',
                'rickshaw'      => 'Rickshaw',
                'school_bus'    => 'School Bus',
                'taxi'          => 'Taxi',
                'walking'       => 'Walking',
            ])->displayUsingLabels()->rules('required')->hideFromIndex(),

            KeyValue::make('Transport Details','transport_details')->rules('json')->hideFromIndex(),

            Select::make('Siblings','siblings')->options([
                'yes'   =>  'Yes',
                'no'    =>  'No',
            ])->displayUsingLabels()->rules('required')->hideFromIndex(),

            Code::make('Sibling Details','sibling_details')->json()->nullable()->hideFromIndex(),

            Select::make('Academic Status','academic_status')->options([
                'pass'   =>  'Pass',
                'fail'   =>  'Fail',
            ])->displayUsingLabels()->rules('nullable'),
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
