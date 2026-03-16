<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class UserProfessionFilter extends Filter
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
       return 'Occupation';
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
        return $query->where('profession',$value);
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
            'Business'                      =>  'business',
            'Central Government Employee'   =>  'central_government_employee',
            'Home Maker'                    =>  'home_maker',
            'Others'                        =>  'others',
            'Private'                       =>  'private',
            'State Government Employee'     =>  'state_government_employee',
        ];
    }
}
