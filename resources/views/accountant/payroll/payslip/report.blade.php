{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title></title>
     <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500&family=IBM+Plex+Sans:wght@500;600;700&family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
  </head>
  <body>
  <div class="p-2 shadow w-full my-4">
    <table class="w-full">
    <thead class="text-white">
      <tr class="border-b">
      	<th class="text-left text-xs px-2 py-2">Payroll No</th>
      	<th class="text-left text-xs px-2 py-2">Salary period</th>
        <th class="text-left text-xs px-2 py-2">Name</th>
        <th class="text-left text-xs px-2 py-2">Designation</th> 
        <th class="text-left text-xs px-2 py-2">Gross Salary</th> 
        <th class="text-left text-xs px-2 py-2">Total Days</th>
        <th class="text-left text-xs px-2 py-2">Working Days</th>   
        <th class="text-left text-xs px-2 py-2">Leave</th> 
        <th class="text-left text-xs px-2 py-2">Perday salary</th>  
        <th class="text-left text-xs px-2 py-2">Loss of pay</th> 
        <th class="text-left text-xs px-2 py-2">Salary percentage</th> 
        <th class="text-left text-xs px-2 py-2">Earnings</th>
        <th class="text-left text-xs px-2 py-2">Total Earning</th>
        <th class="text-left text-xs px-2 py-2">(PF/ESI/Deduction)</th>
        <th class="text-left text-xs px-2 py-2">Total Deduction</th> 
        <th class="text-left text-xs px-2 py-2">Net Salary</th>

      </tr>
      </thead>
      <tbody>
        @foreach($payrolls as $payroll)
      <tr>
      	<td class="font-semibold text-xs">
         {{$payroll->payrollno}}
        </td>
      	 <td class="font-semibold text-xs">
         {{date('d-m-Y', strtotime($payroll->start_date))}} <br>
         {{date('d-m-Y', strtotime($payroll->end_date))}}
        </td>
        <td class="font-semibold text-xs">
         {{$payroll->user->FullName}}
        </td>
        <td class="font-semibold text-xs">
         {{ucfirst($payroll->user->getTeacherDetails()['designation_name'])}}
         {{ucfirst($payroll->user->getTeacherDetails()['sub_designation'])}}
        </td>
        </td>
         <td class="font-semibold text-xs">
         {{$payroll->salary->gross_salary}}
        </td>
         <td class="font-semibold text-xs">
         {{$payroll->getTotalDays()}}
        </td>
        <td class="font-semibold text-xs">
         {{$payroll->getTotalDays()-$payroll->leave}}
        </td>
         <td class="font-semibold text-xs">
         {{$payroll->leave}}
        </td>
         <td class="font-semibold text-xs">
         {{$payroll->getDaySalary()}}
        </td>
         <td class="font-semibold text-xs">
         {{$payroll->leave_deduction}}
        </td>
         <td class="font-semibold text-xs">
         ({{$payroll->percentage}}%) {{$payroll->salarypercentage()}}
        </td>
        <td class="font-semibold text-xs">
          @if($payroll->totalearnings()>0)
         @foreach($payroll->payslipitems as $payslip)
              @if($payslip->salaryitem->templateitem->payrollitem->type=='earning')  
              <p>{{$payslip->salaryitem->templateitem->payrollitem->key}} <span>{{$payslip->amount}}</span></p>
              @endif
          @endforeach 
          @else
          <p>0</p>
          @endif
          </td>
         <td class="font-semibold text-xs">
         {{$payroll->totalearnings()}}
        </td>
        <td class="font-semibold text-xs">
          @if($payroll->totaldeductions()>0)
         @foreach($payroll->payslipitems as $payslip)
              @if($payslip->salaryitem->templateitem->payrollitem->type=='deduction')  
              <p>{{$payslip->salaryitem->templateitem->payrollitem->key}} <span>{{round($payslip->amount)}}</span></p>
              @endif
          @endforeach 
          @else
          <p>0</p>
          @endif
          </td>
         <td class="font-semibold text-xs">
         {{$payroll->totaldeductions()}}
        </td>
        <td class="font-semibold text-xs">
         {{$payroll->totalsalary()}}
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
    </div>
  </body>
  <style>
    body {
      font-family: Nunito Sans, Roboto, sans-serif;
    }
    table thead th {
    font-weight: 600;
    --text-opacity: 1;
    color: #fff;
    color: rgba(255, 255, 255, var(--text-opacity));
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;

}
table thead {
    --bg-opacity: 1;
    background-color: #a0aec0;
    background-color: rgba(160, 174, 192, var(--bg-opacity));
}
table tbody td {
    font-size: 0.875rem;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    border-width: 1px;
    --border-opacity: 1;
    border-color: #cbd5e0;
    border-color: rgba(203, 213, 224, var(--border-opacity));
    white-space: nowrap;
}
  </style>
</html>