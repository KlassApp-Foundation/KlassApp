<?php

namespace App\Nova\Metrics;

use Laravel\Nova\Metrics\Value;
use Illuminate\Http\Request;
use App\Models\User;

class NewStudents extends Value
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        //$user = User::where('usergroup_id',6)->count();
        //return $this->result($user);
        return $this->count($request, User::where('usergroup_id',6));
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30      => '30 Days',
            60      => '60 Days',
            365     => '365 Days',
            'TODAY' => 'Today',
            'MTD'   => 'Month To Date',
            'QTD'   => 'Quarter To Date',
            'YTD'   => 'Year To Date',
        ];
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'new-students';
    }
}
