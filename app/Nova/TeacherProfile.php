<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class TeacherProfile extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\TeacherProfile';

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

            BelongsTo::make('School','school',\App\Nova\School::class)->hideWhenUpdating()->hideFromIndex(),

            BelongsTo::make('Academic Year','academicyear',\App\Nova\AcademicYear::class)->hideWhenUpdating()->hideFromIndex(),

            BelongsTo::make('User','user',\App\Nova\Teacher::class)->hideWhenUpdating(),

            BelongsTo::make('Qualification','qualification',\App\Nova\Qualification::class)->hideFromIndex(),

            BelongsTo::make('UG Degree','ugdegree',\App\Nova\Qualification::class)->hideFromIndex(),

            BelongsTo::make('PG Degree','pgdegree',\App\Nova\Qualification::class)->hideFromIndex(),

            Text::make('Sub Qualification','sub_qualification')->nullable()->hideFromIndex(),

            Text::make('Specialization','specialization')->nullable()->hideFromIndex(),

            Select::make('Designation','designation')->options([
                'assistant_teacher'         =>  'Assistant Teacher',
                'head_of_the_department'    =>  'Head Of The Department',
                'librarian'                 =>  'Librarian',
                'others'                    =>  'Others',
                'principal'                 =>  'Principal',
                'senior_teacher'            =>  'Senior Teacher',
                'vice_principal'            =>  'Vice Principal',
            ])->displayUsingLabels()->nullable(),

            Text::make('Sub Designation','sub_designation')->nullable(),

            Text::make('Employee Id','employee_id')->nullable(),

            Boolean::make('status','status')->nullable(),
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
