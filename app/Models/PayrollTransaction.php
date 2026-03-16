<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PayrollTransaction extends Model
{
    //
  use SoftDeletes;
  
    protected $casts=['transaction_detail'=>'array'];

    protected $fillable = [
        'school_id' , 'transaction_no','paytype_id','account_id','staff_id','payroll_id','transaction_date','amount','payment_method','transaction_detail','reference_number','attachment','remarks'
    ];
    
     public function user()
  {
        return $this->belongsTo(User::class,'staff_id');
   }

    public function transactiontype()
  {
        return $this->belongsTo(TransactionType::class,'paytype_id');
   }

   public function payroll()
  {
        return $this->belongsTo(Payroll::class,'payroll_id');
   }

    public function account()
  {
        return $this->belongsTo(TransactionAccount::class,'account_id');
   }
}
