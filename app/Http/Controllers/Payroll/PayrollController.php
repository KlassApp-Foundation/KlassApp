<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Payroll;

use App\Http\Requests\Payroll\PayrollDetailRequest;
use App\Http\Requests\Payroll\PayrollUpdateRequest;
use App\Http\Resources\Payroll\PayrollDetailResource;
use App\Http\Resources\Payroll\PayrollListResource;
use App\Http\Resources\Payroll\SalaryResource;
use App\Http\Requests\Payroll\PayrollRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Traits\Common;
use App\Models\Payroll;
use App\Models\Salary;
use App\Models\PayslipItem;
use App\Models\TeacherLeaveApplication;
use App\Models\PayrollTemplate;
use App\Services\UgandaPayrollCalculator;
use Carbon\Carbon;
use Exception;
use PDF;
use Log;

class PayrollController extends Controller
{
    use Common;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('accountant/payroll/payslip/index');
    }

     public function downloadpayroll($id)
    {
      $logo=null;
      $payroll=Payroll::where('id',$id)->first();
      //dd($payroll);
      if(Auth::user()->SchoolLogo['meta_value'] != '-'){
      $logo=Auth::user()->SchoolLogoPath;
      }
      $filename="payroll-".$payroll->user->userprofile->firstname.'-'.date('Y-m-dH-i-s');
      /*  $pass_path='uploads/payroll/'.$filename.'.pdf';
        $folder=Auth::user()->school_id.'/visitorpass';*/
      $pdf = PDF::loadView('accountant/payroll/payslip/payslip',[
        'payroll' => $payroll,
        'logo'    => $logo
      ]);
                   
      return $pdf->stream($filename.'.pdf', array('Attachment'=>0));              
        

    }


    public function showlist(Request $request)
    {
       // $salary=Payroll::where('school_id',Auth::user()->school_id)->with('user')->get();
        $payroll=Payroll::with('user')->where([['school_id',Auth::user()->school_id],['status',$request->status]])->paginate(20);
        $payroll=PayrollListResource::collection($payroll);
        return $payroll;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('accountant/payroll/payslip/create');
    }

    public function details(PayrollDetailRequest $request)
    {
        $array=[];
        $start_date=date('Y-m-d',strtotime($request->start_date));
        $end_date=date('Y-m-d',strtotime($request->end_date));
         $salary=Salary::where([['school_id',Auth::user()->school_id],['staff_id',$request->staff_id],['effective_date','>=',date('Y-m-d')]])->with('salaryitems')->first();
       //$leave=TeacherLeaveApplication::where('user_id',$request->staff_id)->where([['from_date','>=',$start_date],['status','approved']])->first();
       // $from_date=date('Y-m-d',strtotime($leave->from_date));
        //$to_date=date('Y-m-d',strtotime($leave->to_date));
        //$leavedays=0;
        $totaldays=$this->dayscount($start_date,$end_date)+1;
        $array['totaldays']=$totaldays;
        /*if($leave){
        if($to_date>$end_date){
         $leavedays=$this->dayscount($from_date,$end_date)+1;
        }else
        {
         $leavedays=$this->dayscount($from_date,$to_date)+1; 
        }}*/
         //$array['leavecount']=$leavedays;
        $array['salary']=new SalaryResource($salary);
        $array['perday_salary']=round($salary->gross_salary/$totaldays);
       

        return $array;
        
    }

    public function dayscount($start_date,$end_date)
    {
         $days = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date));
         return $days;
    }

    protected function getpayrollnumber()
    {
        $payrollnonew;
        if(count(Payroll::where('school_id',Auth::user()->school_id)->get())<=0)
        {
           $payrollnonew="#_001";
        }
        else{
          $payrollno=Payroll::where('school_id',Auth::user()->school_id)->orderBy("payrollno","DESC")->take(1)->first()->payrollno;
          $payrollnonew=++$payrollno;
        } 
        return $payrollnonew;
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PayrollRequest $request)
    {
       //dd($request->all());
         \DB::beginTransaction();
        try 
        {
       
        $payslip=new Payroll;
        $payslip->school_id=Auth::user()->school_id;
        $payslip->payrollno=$request->payroll_no;
        $payslip->salary_id=$request->salary_id;
        $payslip->staff_id=$request->staff_id;
        $payslip->start_date=$request->start_date;
        $payslip->end_date=$request->end_date;
        $payslip->leave=$request->leave;
        $payslip->late=$request->late;
        $payslip->percentage=$request->percentage;
        $payslip->leave_deduction=$request->loss_of_pay;
        $payslip->comments=$request->comments;
        $payslip->save();
        
        for ($i=0 ; $i < $request->payrollscount ; $i++)
            { 
                $salary_item ='salary_item'.$i;
                $amount ='amount'.$i;
                $category_id ='category_id'.$i;
                $category_value ='category_value'.$i;
             $payslipitems=new PayslipItem;
             $payslipitems->payroll_id=$payslip->id;
             $payslipitems->salary_item_id=$request->$salary_item;
             if($request->$category_id==4){
             $payslipitems->amount=$this->calculation($request,$category_value);
             }
             else{
             $payslipitems->amount=$request->$amount;
             }
             $payslipitems->save();
         }
         $res['success'] = 'Payroll added successfully';

          \DB::commit();
            return $res;
        }
        catch(Exception $e) 
        {
            \DB::rollBack();
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payroll=Payroll::find($id);
        return view('accountant/payroll/payslip/show',['payroll'=>$payroll]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
         return view('accountant/payroll/payslip/edit',['payrollid'=>$id]);
    }

    public function editshow($id)
    {
        return new PayrollDetailResource(Payroll::find($id));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PayrollUpdateRequest $request, $id)
    {
         \DB::beginTransaction();
        try 
        {
       
        $payslip=Payroll::find($id);
        $payslip->comments=$request->comments;
        $payslip->leave=$request->leave;
        $payslip->late=$request->late;
        $payslip->percentage=$request->percentage;
        $payslip->leave_deduction=$request->loss_of_pay;
        $payslip->save();
        $payslip->payslipitems()->delete();
        for ($i=0 ; $i < $request->payrollscount ; $i++)
            { 
                $salary_item ='salary_item'.$i;
                $amount ='amount'.$i;
             $payslipitems=new PayslipItem;
             $payslipitems->payroll_id=$payslip->id;
             $payslipitems->salary_item_id=$request->$salary_item;
             $payslipitems->amount=$request->$amount;
             $payslipitems->save();
         }
         $res['success'] = 'Payroll added successfully';
          \DB::commit();
            return $res;
        }
        catch(Exception $e) 
        {
            \DB::rollBack();
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function calculation($request,$category_value)
    {
        $newArray=[];
        foreach(json_decode($request->payrollskey,true) as $k=>$v) {
            foreach ($v as $key => $value) {
                $newArray[0][$key] = $value;
            }
        }
        $total_amount = str_replace(array_keys($newArray[0]), $newArray[0], $request->$category_value); 

          eval('$amount_tot ='.$total_amount.";");
          return "$amount_tot";

    }

    /**
     * Show the batch payroll run page.
     *
     * GET /accountant/payroll/batch
     */
    public function batchIndex()
    {
        $templates = PayrollTemplate::where([
            ['school_id', Auth::user()->school_id],
            ['status', 1],
        ])->get();

        return view('accountant/payroll/batch/index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Preview computed payrolls for a batch run.
     *
     * POST /accountant/payroll/batch/preview
     *
     * For each active salary on the selected template, compute
     * PAYE, NSSF (employee), LST, and net pay using UgandaPayrollCalculator.
     * Returns JSON with per-row data and totals.
     */
    public function batchPreview(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:payroll_templates,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ]);

        $schoolId = Auth::user()->school_id;
        $templateId = $request->input('template_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $salaries = Salary::with(['user.userprofile', 'salaryitems.templateitem.payrollitem'])
            ->where([
                ['school_id', $schoolId],
                ['template_id', $templateId],
                ['effective_date', '<=', $endDate->format('Y-m-d')],
            ])
            ->get();

        if ($salaries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active salaries found for this template.',
            ]);
        }

        $rows = [];
        $totals = ['gross' => 0, 'paye' => 0, 'nssf' => 0, 'lst' => 0, 'net' => 0, 'earnings' => 0, 'deductions' => 0];

        foreach ($salaries as $salary) {
            $gross = (float) $salary->gross_salary;
            $payPeriod = $startDate->copy();

            $statutory = UgandaPayrollCalculator::computeAll($gross, $payPeriod);

            // Check for existing leave deductions
            $leaveDays = (new Payroll)->getLeaveDays(
                $salary->staff_id,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );
            $perDaySalary = $gross / 30;
            $leaveDeduction = round($leaveDays * $perDaySalary);

            // Sum earnings and deductions from salary items
            $itemEarnings = (float) $salary->totalearnings();
            $itemDeductions = (float) $salary->totaldeductions();

            $grossPay = $gross + $itemEarnings;
            $statDeductions = $statutory['total_deductions'];
            $otherDeductions = $itemDeductions + $leaveDeduction;
            $netPay = $grossPay - $statDeductions - $otherDeductions;

            $rows[] = [
                'staff_id'       => $salary->staff_id,
                'staff_name'     => $salary->user?->userprofile?->firstname . ' ' . $salary->user?->userprofile?->lastname,
                'salary_id'      => $salary->id,
                'gross'          => round($grossPay),
                'paye'           => $statutory['paye'],
                'nssf'           => $statutory['nssf_employee'],
                'lst'            => $statutory['lst'],
                'leave_deduction'=> $leaveDeduction,
                'item_earnings'  => round($itemEarnings),
                'item_deductions'=> round($itemDeductions),
                'net_pay'        => max(0, round($netPay)),
            ];

            $totals['gross']       += $grossPay;
            $totals['paye']        += $statutory['paye'];
            $totals['nssf']        += $statutory['nssf_employee'];
            $totals['lst']         += $statutory['lst'];
            $totals['earnings']    += $itemEarnings;
            $totals['deductions']  += $itemDeductions + $leaveDeduction;
            $totals['net']         += max(0, $netPay);
        }

        return response()->json([
            'success'           => true,
            'rows'              => $rows,
            'totals'            => $totals,
            'row_count'         => count($rows),
            'template_id'       => $templateId,
            'start_date'        => $startDate->format('Y-m-d'),
            'end_date'          => $endDate->format('Y-m-d'),
        ]);
    }

    /**
     * Confirm and execute a batch payroll run.
     *
     * POST /accountant/payroll/batch/confirm
     *
     * Creates payroll + payslip_items records in a single transaction.
     * For large batches (> 20 staff), dispatches a queue job.
     */
    public function batchConfirm(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:payroll_templates,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'rows'        => 'required|array|min:1',
            'rows.*.staff_id'        => 'required|exists:users,id',
            'rows.*.salary_id'       => 'required|exists:salaries,id',
            'rows.*.gross'           => 'required|numeric|min:0',
            'rows.*.paye'            => 'required|numeric|min:0',
            'rows.*.nssf'            => 'required|numeric|min:0',
            'rows.*.lst'             => 'required|numeric|min:0',
            'rows.*.leave_deduction' => 'nullable|numeric|min:0',
            'rows.*.net_pay'         => 'required|numeric|min:0',
        ]);

        $schoolId = Auth::user()->school_id;
        $staffCount = count($request->input('rows'));

        if ($staffCount > 20) {
            // Dispatch to queue for large batches
            \App\Jobs\ProcessBatchPayroll::dispatch(
                $schoolId,
                Auth::id(),
                $request->input('template_id'),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('rows')
            );

            return response()->json([
                'success' => true,
                'message' => "Batch payroll for {$staffCount} staff has been queued for processing.",
                'queued'  => true,
            ]);
        }

        // Process inline for small batches
        $payrollNumbers = $this->processBatchRows(
            $schoolId,
            Auth::id(),
            $request->input('template_id'),
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('rows')
        );

        return response()->json([
            'success' => true,
            'message' => "Payroll created for {$staffCount} staff.",
            'payroll_numbers' => $payrollNumbers,
            'queued'  => false,
        ]);
    }

    /**
     * Process batch payroll rows — creates payroll + payslip_items.
     */
    private function processBatchRows(int $schoolId, int $userId, int $templateId, string $startDate, string $endDate, array $rows): array
    {
        $payrollNumbers = [];

        \DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Generate payroll number
                $payrollNo = $this->getNextPayrollNumber($schoolId);

                $payroll = Payroll::create([
                    'school_id'      => $schoolId,
                    'payrollno'      => $payrollNo,
                    'staff_id'       => $row['staff_id'],
                    'salary_id'      => $row['salary_id'],
                    'start_date'     => $startDate,
                    'end_date'       => $endDate,
                    'percentage'     => 100,
                    'leave'          => 0,
                    'leave_deduction'=> $row['leave_deduction'] ?? 0,
                    'status'         => 'unpaid',
                    'comments'       => "Batch run from template #{$templateId}",
                ]);

                // Create payslip items for statutory deductions
                $salary = Salary::with('salaryitems')->find($row['salary_id']);
                if ($salary && $salary->salaryitems->isNotEmpty()) {
                    foreach ($salary->salaryitems as $item) {
                        PayslipItem::create([
                            'payroll_id'     => $payroll->id,
                            'salary_item_id' => $item->id,
                            'amount'         => $item->amount,
                        ]);
                    }
                }

                $payrollNumbers[] = $payrollNo;
            }

            \DB::commit();
        } catch (Exception $e) {
            \DB::rollBack();
            Log::error('Batch payroll failed: ' . $e->getMessage());

            throw $e;
        }

        return $payrollNumbers;
    }

    /**
     * Generate the next payroll number for a school.
     */
    private function getNextPayrollNumber(int $schoolId): string
    {
        $last = Payroll::where('school_id', $schoolId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$last) {
            return '#_' . str_pad('1', 3, '0', STR_PAD_LEFT);
        }

        $parts = explode('_', $last->payrollno);
        $num = isset($parts[1]) ? (int) $parts[1] + 1 : 1;

        return '#_' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Display a printable branded payslip (HTML, print-styled).
     */
    public function printPayslip($id)
    {
        $payroll = Payroll::with(['user.school', 'user.userprofile', 'user.teacherprofile', 'payslipitems.salaryitem.templateitem.payrollitem'])->findOrFail($id);

        $logo = null;
        if (Auth::user()->SchoolLogo['meta_value'] != '-') {
            $logo = Auth::user()->SchoolLogoPath;
        }

        return view('accountant.payroll.payslip.payslip-branded', [
            'payroll' => $payroll,
            'logo'    => $logo,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
         $template=Payroll::find($id);
         $template->payslipitems()->delete();
         $template->delete();
         $res['message'] = 'Payroll deleted successfully';
         return $res;
    }
}