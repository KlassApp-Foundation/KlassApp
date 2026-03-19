{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title></title>
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500&family=IBM+Plex+Sans:wght@500;600;700&family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  </head>
  <body>
  <div class="p-2 shadow w-full my-4 overflow-auto">
    <table class="w-full">
    <thead class="text-white">
      <tr class="border-b">
      	<td class="text-left text-xs px-2 py-2"><b>Payroll No</b></td>
      	<td class="text-left text-xs px-2 py-2"><b>Salary period</b></td>
        <td  class="text-left text-xs px-2 py-2"><b>Name</b></td>
        <td class="text-left text-xs px-2 py-2"><b>Designation</b></td> 
        <td class="text-left text-xs px-2 py-2"><b>Bank Name</b></td> 
        <td class="text-left text-xs px-2 py-2"><b>Account No</b></td>
        <td class="text-left text-xs px-2 py-2"><b>IFSC Code</b></td>
        <td class="text-left text-xs px-2 py-2"><b>Net Salary </b></td>

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
        <td class="font-semibold text-xs">
         {{$payroll->user->bankdetail->name}}
        </td>
        <td class="font-semibold text-xs">
         {{$payroll->user->bankdetail->account_number}}
        </td>
        <td class="font-semibold text-xs">
         {{$payroll->user->bankdetail->ifsc_code}}
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