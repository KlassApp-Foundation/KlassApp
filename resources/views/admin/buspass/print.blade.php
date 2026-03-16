{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
<head>
   <title>Buss Pass</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans">
    <div class="" style="">

     @foreach($students as $student)

    <div class="">
        <div class="card-width" style="width: 100%;"> <!-- card-width w-full lg:w-1/3 md:w-1/3 margin-top: 90px;padding: 10px;-->
            <div style="margin-top: 10px;border-radius: 10px;background-size: cover;background-position: top;background-repeat: no-repeat;background-color: #fff;">
                 <div style="border-radius: 8px;border:1px solid #ccc;">
                    <!-- header start -->
                    <div style="background: url(images/idcardbg.png);background-position: top;background-repeat: no-repeat;background-size: cover; border-top-right-radius: 10px;border-top-left-radius: 10px;width: 100%;">
                         <div style="">
                             
                                
                                    
                                    
                                 
                                          <div style="">
            <h4 class="text-base" style="font-weight: 400;color: #fff;font-weight: 800;font-size: 13px;text-align: center;padding-bottom: 0;margin-bottom: 0;">{{ Auth::user()->school->name }}</h4>  
          
          
            <!-- <td><p style="font-weight: 700;font-size: 12px;color: #fff;padding-top: 10px;">Year : {{$academic->name}}</p></td> -->
           <p style="font-weight: 700;font-size: 11px;color: #fff;padding-bottom: 8px !important;margin: 0 !important;text-align: center;padding-top: 0;">Office Phone : {{Auth::user()->school->phone}}</p>
        
         
            <!-- <td><p style="font-weight: 700;font-size: 12px;color: #fff;padding-top: 10px;">Year : {{$academic->name}}</p></td> -->
          <p style="font-weight: 700;font-size: 18px;color: #fff;padding-bottom: 8px !important;margin: 0 !important; text-align: center;">BUS PASS  2022-2023</p>
        
       
           </div>
                                     
                    
                    
                   
                    </div>
                    </div>
                    <!-- header end -->

                   <!--  main section start -->
                <div>
                    <div style="padding: 10px;">
                        <table style="width: 100%;">
                          <tr style="font-size: 13px;padding: 10px 13px;" class="visitor-log">
                              <td style="width: 80%;">
                                 <table style="width: 100%;">
                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 25%;"> <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Name :</b></div></td>
                                          <td>  <div style="padding-top: 4px;padding-bottom: 4px;"><span style="font-size: 15px;font-weight: 800;">{{$student->FullName}}</span></div></td>
                                        </tr>
                                     

                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 25%;"> <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Class : </b></div></td>
                                           <td> <div style="padding-top: 4px;padding-bottom: 4px;"><span>{{optional(optional($student->studentAcademicLatest)->standardLink)->StandardSection}}</span></div>
                                            </td>
                                        </tr>

                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 25%;"> <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Address : </b></div></td>
                                           <td> <div style="padding-top: 4px;padding-bottom: 4px;line-height: 1.5;padding-right: 4px;"><span>{{$student->userprofile->address}},<br/>
                                            {{$student->userprofile->city->name}},<br/>
                                            {{$student->userprofile->pincode}}</span></div>
                                            </td>
                                        </tr>
                                        <tr style="vertical-align: baseline;">
                                            <td style="width: 25%;"> <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Mobile :</b></div></td>
                                         <td>
                                            <div style="padding-top: 4px;padding-bottom: 4px;">
                                          {{$student->mobile_no}}
                                          </div>
                                        </td>
                                        </tr>

                                         <tr>
                                             <td style="width: 25%;"> <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Boarding Point :</b></div></td>
                                           <td> 
                                            <div style="padding-top: 4px;padding-bottom: 4px;">
                                          Nilaiyir, Temple Stop
                                          </div>
                                            </td>
                                          
                                        </tr>
                               </table>
                              </td>
                              <td style="width:20%;">
                                    
                                    <!-- <span><img src="{{ $student->userprofile->AvatarPath }}" style="width: 100px;height: 100px;border-radius: 10px;"></span> -->
                                    <span><img src="{{url('images/dm-logo.png')}}" style="width: 75px;height: 75px;border-radius: 10px;margin-left: auto;"></span>
                                </td>

                          </tr>
                      </table>
                    </div>

                    <!-- section 2 start -->
                      <div style="font-size: 11px;padding: 10px;padding-top: 0;">
                                  <table class="border buspass-table" style="width: 100%;">
                                    <thead style="background-color: #fff;">
                                      <tr>
                                        <th class="border-r p-1">June</th>
                                        <th class="border-r p-1">July</th>
                                        <th class="border-r p-1">Aug</th>
                                        <th class="border-r p-1">Sep</th>
                                        <th class="border-r p-1">Oct</th>
                                        <th class="border-r p-1">Nov</th>
                                        <th class="border-r p-1">Dec</th>
                                        <th class="border-r p-1">Jan </th>
                                        <th class="border-r p-1">Feb</th>
                                        <th class="border-r p-1">March</th>
                                        <th class="border-r p-1">April</th>
                                        <th class="border-r p-1">May</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td colspan="13" class="border-t border-b text-center pt-2 pb-1" style="text-align: center;padding: 3px;">Fair Paid on with Sign</td>
                                      </tr>
                                      <tr>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                               
                                </div>
                    <!-- section 2 end -->
                    
                </div>
                   <!--  main section end -->
                 </div>
            </div>
        </div>
   </div>

  @if( $loop->iteration % 1 == 0 ) 
    @php echo '<div class="page-break"></div>';
   @endphp
  @endif
@endforeach
</div>

</body>
</html>
<style>
.page-break {
 page-break-after: always;
}
h1,h2,h3,h4,h5,h6,p {
 margin: unset !important;
}
@page {
  size:  200mm 145mm;

}
/*.card-width {
     transform:rotate(-90deg);
}
*/@media print {  
  @page {
    size: 297mm 210mm; /* landscape */
    /* you can also specify margins here: */
    margin: 25mm;
    margin-right: 45mm; /* for compatibility with both A4 and Letter size: 430px 698px portrait;*/
  }
}
.buspass-table th, .buspass-table td {
  padding: 0.25rem;
  border: 1px solid #e2e8f0;
}
.buspass-table {
  border:1px solid #e2e8f0;
}
.h-8 {
  height: 1rem;
}
</style>