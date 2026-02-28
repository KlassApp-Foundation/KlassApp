<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class FeePaymentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // array of data to be seeded
	$fee_payments=[
	 ['id'=> '1','fee_id' => '3','user_id' => '9','paid_on' => '2020-06-06','notify_parent'=> '1','status'=>'1'],
     ['id'=> '2','fee_id' => '1','user_id'=>'3','paid_on'=> '2020-06-03','notify_parent'=> '1','status'=>'1'],
	 ['id' => '3','fee_id'=> '1','user_id'=> '4','paid_on'=> '2020-06-02','notify_parent' => '1','status'=> '1'],
	 ['id'=> '4','fee_id'=> '1','user_id'=> '5','paid_on'=> '2020-06-02','notify_parent' => '1','status'=> '1',],
	 ['id'=> '5','fee_id'=> '1','user_id'=> '6','paid_on'=> '2020-06-02','notify_parent' => '1','status'=> '1',]
	 ];
	 foreach($fee_payments as $fee_payment){
		DB::table("fee_payments")->insert([
               'id' 			=> $fee_payment['id'],
  				'fee_id' 		=> $fee_payment['fee_id'],
  				'user_id' 		=> $fee_payment['user_id'],
  				'paid_on' 		=> $fee_payment['paid_on'],
  				'notify_parent' => $fee_payment['notify_parent'],
  				'status' 		=> $fee_payment['status'],
  				'created_at' 	=> '2020-06-08 18:50:02',
  				'updated_at' 	=> '2020-06-08 18:50:02',
  				'deleted_at' 	=> NULL
		]);
	 }
        
    }
}
