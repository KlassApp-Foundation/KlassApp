<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Illuminate\Http\Request;
use App\Models\School;

class SchoolFilter extends Filter
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
       return 'School';
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
        return $query->whereHas('school' ,function ($query) use($value)
            {
                $query->where('school_id',$value);
            });
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        return School::pluck('id','name');
    }
}
