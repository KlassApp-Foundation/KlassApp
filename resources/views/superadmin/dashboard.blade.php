{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')

@section('content')

    <div class="">
        <div>
            <h1 class="admin-h1 font-plex my-3">Dashboard</h1>
        </div>
        @include('partials.message')
        <!-- start -->
        <div class="flex flex-wrap my-2">
            <div class="w-full lg:w-1/3 px-1 my-3">
                <livewire:superadmin.academics.total-school-widget />
            </div>
        </div>
    </div>
        <!-- end -->

       


        
   

          
        


        

       

     

        
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
    

    <style>
        /* messagetable */
        table.messageTable tbody th, table.messageTable tbody td {
            /*    padding: 1rem 1.5rem;*/
        }
        table.messageTable thead th, table.messageTable thead td {
            /*padding: 1rem;*/    
            border-bottom: 1px solid #1110;
        }
        table.messageTable {
            width: 100%;
            margin: 0 auto;
            clear: both;
            border-collapse: collapse;
            border-spacing: 0;
        }

        /* @media only screen and (max-width: 760px), (max-device-width: 1024px) and (min-device-width: 768px)
        {
            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left:50%;
                padding-top:5%;
                padding-bottom:5%;
            }*/
        .messageTables_wrapper .messageTables_length, .messageTables_wrapper .messageTables_filter {
            float: none;
            text-align: left;
        }
        }
        @media only screen and (max-width: 760px), (min-device-width: 768px) 
        and (max-device-width: 1024px)  {

    /* Force table to not be like tables anymore */
    table, thead, tbody, th, td, tr {
      display: block;
      font-size:15px;
    }

    /* Hide table headers (but not display: none;, for accessibility) */
    thead tr {
      position: absolute;
      top: -9999px;
      left: -9999px;
    }

    tr {
      margin: 0 0 1rem 0;
    }
      
    tr:nth-child(odd) {
      background: #ccc! important;
    }
    
    td {
      /* Behave  like a "row" */
      border: none;
      border-bottom: 1px solid #eee;
      position: relative;
      padding-left:60%! important;

    }

    td:before {
      /* Now like a table header */
      position: absolute;
      /* Top/left values mimic padding */
      top: 0;
      left: 6px;
      width: 45%;
      padding-right: 10px;
      white-space: nowrap;
      padding-top: 5%;   
    }


    td:nth-of-type(1):before { content: "{{ trans('forms.from') }}"; }
    td:nth-of-type(2):before { content: "{{ trans('forms.to') }}"; }
    td:nth-of-type(3):before { content: "{{ trans('forms.message') }}"; }
    td:nth-of-type(4):before { content: "{{ trans('forms.createdon') }}"; }
    td:nth-of-type(5):before { content: "{{ trans('forms.lastreplyby') }}"; }
    td:nth-of-type(6):before { content: "{{ trans('forms.lastreplyon') }}"; }
  }
.t-dropdown.t-dropdown-size-sm button
{
    border:none! important;
}
</style>
@endpush      