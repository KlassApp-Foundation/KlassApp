<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PayslipItem extends Model
{
    //
  use SoftDeletes;
  
     protected $with=['salaryitem'];

     protected $fillable = ['payroll_id' , 'salary_item_id','amount'];
   
    public function payroll()
  {
        return $this->belongsTo(Payroll::class,'payroll_id');
   }

     public function salaryitem()
  {
        return $this->belongsTo(SalaryItem::class,'salary_item_id');
   }

}
