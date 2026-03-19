<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salary extends Model
{
    //
  use SoftDeletes;

  protected $fillable = ['school_id' , 'staff_id','template_id','effective_date','comments'];

  public function payrolltemplate()
    {
        return $this->belongsTo(PayrollTemplate::class,'template_id');
    }

  public function user()
    {
         return $this->belongsTo(User::class,'staff_id');
    }

  public function salaryitems()
    {
        return $this->hasMany(SalaryItem::class, 'salary_id', 'id');
    }

     public function payrolls()
    {
         return $this->hasMany(Payroll::class,'salary_id');
    }

    public function totalearnings()
   {
     if(count($this->salaryitems)!=0 )
        {
          /*$earning=$this->salaryitems()->whereHas('payrollitem', function($query) {
            $query->where('type', 'earning');
          })->sum('amount');*/
            $earning=$this->salaryitems()->whereHas('templateitem', function($query) {
            $query->whereHas('payrollitem',function($query){
              $query->where('type', 'earning');
            });
          })->sum('amount');
           return $earning;
        }
        return 0;
   }

   public function totaldeductions()
   {

    if(count($this->salaryitems)!=0 )
        {
/*          $deduction=$this->salaryitems()->whereHas('payrollitem', function($query) {
            $query->where('type', 'deduction');
          })->sum('amount');
*/
          $deduction=$this->salaryitems()->whereHas('templateitem', function($query) {
            $query->whereHas('payrollitem',function($query){
              $query->where('type', 'deduction');
            });
          })->sum('amount');


           return $deduction;
        }
        return 0;
    
   }

   public function totalsalary()
   {

    if(count($this->salaryitems)!=0 )
        {
          //$total=$this->totalearnings()-$this->totaldeductions();
           $total=($this->gross_salary+$this->totalearnings())-$this->totaldeductions();

           return round($total);
        }
        return 0;
    
   }


}
