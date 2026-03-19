<?php

namespace App\Nova\Metrics;

use Laravel\Nova\Metrics\Partition;
use Illuminate\Http\Request;
use App\Models\UserProfile;

class GenderStatus extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $userprofile = Userprofile::whereHas('user', function ($query)
            {
                $query->where('usergroup_id',6);
            }); 

        return $this->count($request, $userprofile, 'gender')->label(function ($value) {
                if($value == 'male')
                {
                    return 'Boys';
                }
                else if($value == 'female')
                {
                    return 'Girls';
                }
            });
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
        return 'gender-status';
    }
}
