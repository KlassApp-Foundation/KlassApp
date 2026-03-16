{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
<head>
    <title>{{$payroll->user->school->name}}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
</head>
<body>
        <div style=" border:1px dashed #696969;  margin-top: 10px;">
            <div style="padding:10px;background: #ffffff;">
                <table border="0" style="width:100%;margin-top: 20px;height: 100px;">
                    <tr>
                      <td style="width:40%;vertical-align: top;">
                            @if($logo != 'null')
                            <img src="{{ $logo }}" style="max-width:125px;">
                             @endif
                      </td>
                       <td style="width:60%;vertical-align: top;">
                          <table align="right" class="table-head">
                             <tr>
                               <th style="font-size: 20px;">{{$payroll->user->school->name}}</th>
                             </tr>
                             <tr>
                               <td>
                                 <span class="comma">{{$payroll->user->school->address}}</span>                                         
                                 <span class="comma">{{$payroll->user->school->city->name}},</span>                                         
                                 <span class="comma">{{$payroll->user->school->state->name}},</span>                                       
                                 <span class="comma">{{$payroll->user->school->country->name}}</span>                                       
                                 <span class="comma">{{$payroll->user->school->pincode}}</span>                                   
                               </td>
                              </tr>
                              <tr>
                                <td>{{$payroll->user->school->phone}}</td>
                              </tr>
                              <tr>
                               <td>{{$payroll->user->school->email}}</td>
                              </tr>
                              <tr>
                               <td>{{url('/')}}</td>
                              </tr>
                           </table>
                        </td>
                    </tr>
                </table>
    <h2 style="text-align: center;">Term 19/20</h2>
    <table class="heading" style="width: 100%; margin-bottom: 20px;">
       <tr>
         <td>Payroll {{$payroll->payrollno}}</td>
         <td style="text-align: right;">Date: {{date('d-m-Y')}}</td>
       </tr>
    </table>
    <table class="fancy-detail">
       <tbody>
         <tr>
           <td><strong>Name</strong></td>
           <td>{{$payroll->user->userprofile->firstname}} {{$payroll->user->userprofile->lastname}}</td>
           <td><strong>Employee Id</strong></td>
           <td>{{$payroll->user->teacherprofile[0][employee_id]}}</td>
         </tr>
         <tr>
           <td><strong>Designation</strong></td>
           <td>{{ucfirst($payroll->user->teacherprofile[0][designation])}}</td>
           <td><strong>Contact Number</strong></td>
           <td>{{$payroll->user->mobile_no}}</td>
         </tr>
         <tr>
           <td><strong>Payroll Period</strong></td>
           <td>{{date('d-m-Y',strtotime($payroll->start_date))}} to {{date('d-m-Y',strtotime($payroll->end_date))}}</td>
           <td></td>
           <td></td>
          </tr>
        </tbody>
    </table>
    <table class="fancy-detail" style="margin-top: 20px;">
        <tbody>
          <tr>
           <th>Earning Salary</th>
           <th>Deduction Salary</th>
          </tr>
          <tr>
           <td valign="top">
             <table class="no-border">
              <tbody> 
                <tr>
              <td width="50%">Salary</td>
                <td style="text-align: right;"><span style="font-family:'DeJaVu Sans Mono' ">&#8377;</span>{{$payroll->salarypercentage()}}</td>
               </tr>
              @foreach($payroll->payslipitems as $payslip)
              @if($payslip->salaryitem->templateitem->payrollitem->type=='earning')  
               <tr>
                <td width="50%">{{$payslip->salaryitem->templateitem->payrollitem->name}}</td>
                <td style="text-align: right;"><span style="font-family:'DeJaVu Sans Mono' ">&#8377;</span>{{$payslip->amount}}</td>
               </tr>    
               @endif
               @endforeach                                                          <!--                                   <tr>
                <td width="50%">Dearness Allowance</td>
                <td style="text-align: right;">$400</td>
               </tr>                                                                                                 <tr>
                <td width="50%">Transport Allowance</td>
                <td style="text-align: right;">$200</td>
                </tr>                                                                                                <tr>
                  <td width="50%">Mobile Allowance</td>
                  <td style="text-align: right;">$100</td>
                   </tr>           -->                                                                                   </tbody>
                    </table>                    
                </td>
                <td valign="top">
                    <table class="no-border">
                        <tbody>
             @foreach($payroll->payslipitems as $payslip)
              @if($payslip->salaryitem->templateitem->payrollitem->type=='deduction')  
               <tr>
                <td width="50%">{{$payslip->salaryitem->templateitem->payrollitem->name}}</td>
                <td style="text-align: right;"><span style="font-family:'DeJaVu Sans Mono' ">&#8377;</span>{{round($payslip->amount)}}</td>
               </tr>    
               @endif
               @endforeach                                                   
                         <!--                                  <tr>
                           <td width="50%">Security Deposit</td>
                            <td style="text-align: right;">$100</td>
                          </tr>       -->                                                                                </tbody>
                    </table>                
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <table class="no-border">
                        <tbody>
                            <tr>
                                <td width="50%">Total Earning</td>
                                <td style="text-align: right;"><span style="font-family:'DeJaVu Sans Mono' ">&#8377;</span>{{$payroll->totalearnings()+$payroll->salarypercentage()}}</td>
                            </tr>
                        </tbody>
                    </table>                    
                </td>
                <td valign="top">
                    <table class="no-border">
                        <tbody>
                            <tr>
                                <td width="50%">Total Deduction</td>
                                <td style="text-align: right;"> <span style="font-family:'DeJaVu Sans Mono' ">&#8377;</span>{{$payroll->totaldeductions()}}</td>
                            </tr>
                        </tbody>
                    </table>                
                </td>
            </tr>
            <tr>
                <th>Net Salary</th>
                <th style="text-align: right;"><span style="font-family:'DeJaVu Sans Mono' ">&#8377;</span>{{$payroll->totalsalary()}}</th>
            </tr>
        </tbody>
    </table>

<p style="margin-top:40px; font-size: 12px; font-weight: bold;">Authorized Signatory</p>
            <p style="text-align: right; font-size: 12px;">Printed on {{date('d-m-Y')}}</p>
        </div>
    </div>
</body>
 <style>
        *{font-family:'Helvetica';}
        body{width:auto;  max-width:800px;  min-width: 600px; margin:0 auto;font-size:12px;}
        h2{font-size: 16px;font-weight: bold;}
        .heading{font-size: 16px;font-weight: bold;}
        .font-weight-bold{font-weight: bold;}
        .font-120pc{font-size: 14px;}
        .tw-50 {width: 50%;}
        table.table-head th{font-size: 12px; font-weight: bold;text-align: right;}
        table.table-head td{font-size: 11px;text-align: right;}

        table.fancy-detail {  font-size:12px; border-collapse: collapse;  width:100%;  margin:0 auto;}
        table.fancy-detail th{  background:#696969; color:#FFFFFF; border-bottom: 1px #2e2e2e solid;  padding: 0.5em;  padding-left:10px; vertical-align:top;text-align: left;}
        table.fancy-detail td {padding: 0.5em;  padding-left:10px; border-bottom:1px solid #2e2e2e;text-align: left;}
        table.fancy-detail caption {  margin-left: inherit;  margin-right: inherit;}
        table.fancy-detail tr:hover{}

        table.report-card {  font-size:12px; border-collapse: collapse;  width:100%;  margin:0 auto;}
        table.report-card th{  background:#696969; color:#FFFFFF; border-bottom: 1px #2e2e2e solid;  padding: 0.5em;  padding-left:10px; vertical-align:top;text-align: left;}
        table.report-card td {padding: 0.5em;  padding-left:10px; border:1px solid #2e2e2e;text-align: left;}
        table.report-card caption {  margin-left: inherit;  margin-right: inherit;}
        table.report-card tr:hover{}
        table.no-border {width: 100%;}
        table.no-border td {border:0px;}
        .tc {font-size: 14px;}
        .page-break {page-break-after: always;}
        .table-striped {
            vertical-align: top;
            border-top: 0.0625rem solid #e1e5f1;
        }
        .font-weight-bold {
            font-weight: bold;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(1, 5, 17, 0.05);
        }
        .table-striped tr {
            display: table-row;
            vertical-align: inherit;
            border-color: inherit;
        }
        .comma:not(:first-child) {
        margin-left: -.3em;  
        }
        .comma:empty {
        display: none;
        }
        .comma:not(:first-child):before {
        content: ", ";
        }
        .table-padded-lg td {
            padding: 0.8em;
        }
        .pagination {
            list-style: none;
            display: flex;
        }
        .pagination .page-item {
            margin-right: 10px;
        }
        @media  print {
            .hide-print {display:none;}
            .pagination {display:none;}
        }
    </style>
</html>