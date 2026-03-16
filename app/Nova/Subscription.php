<?php

namespace App\Nova;

use Gegosoft\Subscriptioninfo\Subscriptioninfo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;

class Subscription extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\Subscription';

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
     * The logical category associated with the resource.
     *
     * @var string
     */
    public static $category = "Reports";

    /**
    * Build an "index" query for the given resource.
    *
    * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
    * @param  \Illuminate\Database\Eloquent\Builder  $query
    * @return \Illuminate\Database\Eloquent\Builder
    */
    public static function indexQuery(NovaRequest $request, $query)
   {
       return $query;
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

            BelongsTo::make('User','user',\App\Nova\User::class)->hideFromIndex(),

            BelongsTo::make('Plan', 'plan', \App\Nova\Plan::class)->hideFromIndex(),

            BelongsTo::make('School','school',\App\Nova\School::class),

            Textarea::make('Payment Details','payment_details')->hideFromIndex(),

            Textarea::make('Plan Details','plan_details')->hideFromIndex(),

            Select::make('Status','status')->options([
               'pending'    => 'Pending',
               'approve'    => 'Approve',
               'cancel'     => 'Cancel',
               'expired'    => 'Expired',
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
        return [
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
        return [
            new Filters\SubscriptionStatusFilter,
            new Filters\SubscriptionExpiryFilter,
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
            new Actions\SubscriptionExpiryMessageToUser,
        ];
    }
}
