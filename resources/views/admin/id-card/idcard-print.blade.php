{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
<head>
   <title>ID CARD</title>
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
                    <div style="background: url(images/idcardbg-pdf.png);background-position: center;background-repeat: no-repeat;background-size: cover; border-top-right-radius: 10px;border-top-left-radius: 10px;width: 100%;">
                         <div style="padding: 10px;padding-bottom: 15px;">
                              <table style="width: 100%;">
                                 <tbody>
                                     <tr>
                                      <td style="padding: 5px;"><img src="{{url('images/dm-logo.png')}}" style="height: 70px;"></td>
                                      <td style="width: 90%;padding: 5px;">
                                        <div style="padding-left: 15px;">
                                         <div class="text-base" style="font-weight: 500;color: #fff;font-weight: 800;font-size: 22px;">{{ Auth::user()->school->name }}</div>  
                                          <table style="width: 100%;">
                                           <tr>
                                            <td><div style="font-weight: 700;font-size: 12px;color: #fff;padding-top: 10px; margin-top: 5px;">Year : {{$academic->name}}</div></td>
                                            <td style="text-align: right;"><div style="font-weight: 700;font-size: 12px;color: #fff;padding-top: 10px; margin-top: 5px;">Cell : {{Auth::user()->school->phone}}</div></td>
                                           </tr>
                                         </table>
                                      </div>
                                      </td>
                    </tr>
                    </tbody>
                    </table>
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
                                       <!--  <tr class="visitor-log" style="vertical-align: baseline;">
                                         <td colspan="2">
                                            <table style="width: 100%;">
                                                <tbody>
                                            <tr>
                                            <td style="width: 50%;" >
                                                <table style="width: 100%;">
                                                    <tr>
                                                    <td style="width: 50%;"><p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>ID :</b></p></td>
                                                    <td><p style="padding-top: 4px;padding-bottom: 4px;font-weight: 700;">132923</p></td>
                                                </tr>
                                                </table>
                                            </td>
                                            <td  style="text-align: right;width: 50%;padding-right: 10px;">
                                              <table style="width: 100%;">
                                                    <tr>
                                                    <td><p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Class :</b></p></td>
                                                    <td><p style="padding-top: 4px;padding-bottom: 4px;font-weight: 700;">1-B</p></td>
                                                </tr>
                                                </table>
                                            </td>
                                                </tr>
                                            </tbody>
                                            </table>
                                          </td>
                                        </tr> -->

                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 25%;"> <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Class : </b></div></td>
                                           <td> <div style="padding-top: 4px;padding-bottom: 4px;"><span>{{optional(optional($student->studentAcademicLatest)->standardLink)->StandardSection}}</span></div><!--  {{ $user->userprofile->address }} -->
                                            </td>
                                        </tr>

                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 25%;"> <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Address : </b></div></td>
                                           <td> <div style="padding-top: 4px;padding-bottom: 4px;line-height: 1.5;padding-right: 4px;"><span>{{$student->userprofile->address}},<br/>
                                            {{$student->userprofile->city->name}},<br/>
                                            {{$student->userprofile->pincode}}</span></div><!--  {{ $user->userprofile->address }} -->
                                            </td>
                                        </tr>
                                        <tr style="vertical-align: baseline;">
                                            <td style="width: 25%;"> <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Parent name :</b></div></td>
                                         <td>
                                            <div style="padding-top: 4px;padding-bottom: 4px;">
                                           @if(count($student->parents)>0)
                                         {{$student->parents[0]['userParent']['userprofile']['firstname'].' '.$student->parents[0]['userParent']['userprofile']['lastname'].'('.ucfirst($student->parents[0]['userParent']->getParentDetails()['relation']).')'}}
                                         @endif
                                          </div>
                                        </td>
                                        </tr>
                               </table>
                              </td>
                              <td style="width:20%;">
                                    <p style="padding-bottom: 4px;color: #000;text-align: center;font-size: 13px;"><b>ID : </b>{{optional($student->studentAcademicLatest)->roll_number}}</p>
                                    <span><img src="{{ $student->userprofile->AvatarPath }}" style="width: 100px;height: 100px;border-radius: 10px;"></span>
                                    <!-- <span><img src="{{url('images/dm-logo.png')}}" style="width: 100px;height: 100px;border-radius: 10px;"></span> -->
                                </td>

                          </tr>
                      </table>
                    </div>

                    <!-- section 2 start -->
                     <div style="font-size: 12px;padding: 10px;padding-top: 0;padding-right: 25px;padding-left: 25px;">
                                    <div style="padding: 5px 13px;border-top: 1px dashed #64748b; border-bottom: 1px dashed #64748b;"> 
                                    <table style="width: 100%;">
                                        <tbody>
                                            <tr>
                                                
                                                <td>
                                                    <div style="text-align: center;">
                                                    <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>D.O.B</b></div>
                                                    <div style="padding-top: 2px;padding-bottom: 2px;"><span >{{ \Carbon\Carbon::parse($student->userprofile->date_of_birth)->format('d-m-Y') }}</span></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div  style="border-right: 1px dashed #64748b;border-left: 1px dashed #64748b;text-align: center;">
                                                    <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Mobile :</b></div>
                                                    <div style="padding-top: 2px;padding-bottom: 2px;"><span >{{$student->mobile_no}}</span></div>
                                                </div>
                                                </td>
                                                <td>
                                                    <div style="text-align: center;">
                                                    <div style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Blood Group : </b></div>
                                                    <div style="padding-top: 2px;padding-bottom: 2px;text-transform: uppercase;"><span > 
                                                        @if($student->userprofile->blood_group!=null) 
                                                        {{$student->userprofile->blood_group}} VE
                                                        @else
                                                        -
                                                        @endif
                                                    </span></div>
                                                </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                    <!-- section 2 end -->
                    <!-- section 3 start -->
                    <div style="padding: 0 25px 10px 25px;color: #000;font-size: 13px;font-style: italic;font-weight: 800;">
                        <table style="width: 100%;">
                           <tr>
                             <td style="text-align: right;">Signature of Principal</td>
                           </tr>
                        </table>
                   </div>
                    <!-- section 3 end -->
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
</style>