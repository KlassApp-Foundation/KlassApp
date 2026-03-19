<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Country;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\Place;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class Userprofile extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\Userprofile';

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
        'id', 'firstname'
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

            Avatar::make('avatar')->disk(env(FILESYSTEM_DRIVER)),

            BelongsTo::make('School','school',\App\Nova\School::class)->hideWhenUpdating()->hideFromIndex(),

            BelongsTo::make('User','user',\App\Nova\User::class)->hideWhenUpdating(),
            
            BelongsTo::make('Usergorup','usergroup',\App\Nova\Usergroup::class)->hideWhenUpdating()->hideFromIndex(),

            Text::make('Firstname','firstname')->sortable()->rules('required', 'alpha' , 'max:15'),

            Text::make('Lastname','lastname')->sortable()->rules('nullable' , 'alpha' ,' max:15'),

            Text::make('Alternate No.','alternate_no')->sortable()->rules('nullable' , 'numeric', 'digits:10')->hideFromIndex(),

            Select::make('Gender','gender')->options([
               'male' => 'Boy',
               'female' => 'Girl',
            ])->displayUsingLabels()->rules('required'),
            
            Date::make('Date Of Birth','date_of_birth')->format('Y-M-D')->rules('required','before_or_equal:today','after:1920-01-01'),

            Select::make('Blood Group','blood_group')->options([
               'a+'     => 'A+',
               'b+'     => 'B+',
               'o+'     => 'O+',
               'ab+'    => 'AB+',
               'a-'     => 'A-',
               'b-'     => 'B-',
               'o-'     => 'O-',
               'ab-'    => 'AB-',
            ])->displayUsingLabels()->rules('required')->hideFromIndex(),

            Text::make('Birth Place','birth_place')->rules('required')->hideFromIndex(),

            Text::make('Native Place','native_place')->rules('required')->hideFromIndex(),

            Text::make('Mother Tongue','mother_tongue')->rules('required')->hideFromIndex(),

            Select::make('Caste','caste')->options([
               'BC'     => 'BC',
               'BCM'    => 'BCM',
               'FC'     => 'FC',
               'MBC'    => 'MBC',
               'OBC'    => 'OBC',
               'Others' => 'Others',
               'SC'     => 'SC',
               'SCA'    => 'SCA',
               'ST'     => 'ST',
            ])->displayUsingLabels()->rules('required')->hideFromIndex(),

            Place::make('Address','address')->rules('required')->hideFromIndex(),

            BelongsTo::make('City','city',\App\Nova\City::class)->rules('required')->hideFromIndex(),

            BelongsTo::make('State','state',\App\Nova\State::class)->rules('required')->hideFromIndex(),

            BelongsTo::make('Country','country',\App\Nova\Country::class)->rules('required')->hideFromIndex(),

            Text::make('Pincode','pincode')->rules('required', 'numeric' , 'digits:6')->hideFromIndex(),

            Number::make('Aadhar Number','aadhar_number')->rules('required', 'numeric' , 'digits:12')->hideFromIndex(),

            Text::make('Registration Number','registration_number')->rules('required', 'numeric')->hideFromIndex(),

            Text::make('EMIS Number','EMIS_number')->rules('required', 'numeric')->hideFromIndex(),

            Date::make('Joining Date','joining_date')->format('Y-M-D')->rules('required','before_or_equal:today','after:2000-01-01')->hideFromIndex(),

            Textarea::make('Notes','notes')->rules('nullable' , 'string')->hideFromIndex(),

            Select::make('Status','status')->options([
               'active'     =>  'Active',
               'inactive'   =>  'Inactive',
               'exit'       =>  'Exit'
            ])->displayUsingLabels()->rules('required'),
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
            new Filters\UserStatusFilter,
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
