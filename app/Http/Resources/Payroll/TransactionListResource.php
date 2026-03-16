<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if($this->attachment != null)
        {
            $attachment = url($this->attachment);
        }
        else
        {
            $attachment = '';
        }
        return [
            'id'=>$this->id,
            'transaction_no'=>$this->transaction_no,
            'payrollno'=>$this->payroll->payrollno,
            'staffname'=>$this->user->FullName,
            'transaction_date'=>$this->transaction_date,
            'account'=>$this->account->key,
            'amount'=>$this->amount,
            'paytype'=>$this->transactiontype->name,
            'payment_method'=>$this->payment_method,
            'attachment'=>$attachment,
            'transaction_detail'=>$this->transaction_detail,
            'reference_number'=>$this->reference_number,



        ];
    }
}
