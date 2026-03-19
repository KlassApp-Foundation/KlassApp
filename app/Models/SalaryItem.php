<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryItem extends Model
{
    //
  use SoftDeletes;
   
   protected $with=['templateitem'];

   protected $fillable = ['salary_id' , 'template_item_id','amount'];
   
    public function salary()
  {
        return $this->belongsTo(Salary::class,'salary_id');
   }
  
   public function templateitem()
  {
        return $this->belongsTo(TemplateItem::class,'template_item_id');
   }
}
