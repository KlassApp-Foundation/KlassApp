<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;
use App\Models\Userprofile;
use App\Models\User;

class UserStatusFilter extends Filter
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
       return 'Status';
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
        return $query->where('status',$value);
        /*return User::whereHas('userprofile', function ($query)
            {
                $query->where('status',$value);
            });*/
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        return [
            'Active'    => 'active',
            'Inactive'  => 'inactive',
            'Exit'      => 'exit'
        ];
    }
}
