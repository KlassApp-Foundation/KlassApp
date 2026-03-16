<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;
use Carbon\Carbon;

class SubscriptionExpiryFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Get the displayable name of the resource tool.
     *
     * @return string
     */
    public function name()
    {
       return 'Expire Within';
    }

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        return $query->where('status','approve')->where('end_date',$value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        $now = Carbon::now();
        $within_2_days = date('Y-m-d',strtotime('+ 2 days',strtotime($now)));
        $within_1_week = date('Y-m-d',strtotime('+ 7 days',strtotime($now)));

        return [
            'Within 2 Days' => $within_2_days,
            'Within 1 Week' => $within_1_week,
        ];
    }
}
