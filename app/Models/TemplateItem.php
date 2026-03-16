<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateItem extends Model
{
  use SoftDeletes;

    protected $with=['payrollitem','paycategory'];

    protected $fillable = ['template_id' , 'item_id','paycategory_id','category_value'];
    
   public function payrolltemplate()
  {
        return $this->belongsTo(PayrollTemplate::class,'template_id');
   }
     public function payrollitem()
  {
        return $this->belongsTo(PayrollItem::class,'item_id');
   }
    public function paycategory()
  {
        return $this->belongsTo(PayCategory::class,'paycategory_id');
   }
}
