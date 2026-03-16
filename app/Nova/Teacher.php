<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use KABBOUCHI\NovaImpersonate\Impersonate;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class Teacher extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\User';

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
        'id', 'name', 'email',
    ];

    /**
     * The logical category associated with the resource.
     *
     * @var string
     */
 /*   public static $category = "Members";*/

 /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation =  false ;

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->where('usergroup_id',5);
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

            BelongsTo::make('School','school',\App\Nova\School::class)->hideWhenUpdating()->hideFromIndex(),
            
            BelongsTo::make('Usergroup','usergroup',\App\Nova\Usergroup::class)->hideWhenUpdating()->hideFromIndex(),

            //Gravatar::make(),

            Text::make('Name')->sortable()->rules('required', 'max:255'),

            Text::make('Email')->sortable()->rules('required', 'email', 'max:254')->creationRules('unique:users,email')->updateRules('unique:users,email,{{resourceId}}'),

            Text::make('Mobile_no')->sortable()->rules('required', 'numeric', 'digits:10')->creationRules('unique:users,mobile_no')->updateRules('unique:users,mobile_no,{{resourceId}}'),

            Password::make('Password')->onlyOnForms()->creationRules('required', 'string', 'min:8')->updateRules('nullable', 'string', 'min:8'),

            HasOne::make('Userprofile','userprofile',\App\Nova\Userprofile::class),

            HasMany::make('TeacherProfile','teacherProfile',\App\Nova\TeacherProfile::class),

            Impersonate::make($this),
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
            new Filters\SchoolFilter,
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
        return [
            new Actions\ResetPasswordToUser,
            new Actions\NewMessageToUser,
            new Actions\SuspendorActivateUser,
        ];
    }
}
