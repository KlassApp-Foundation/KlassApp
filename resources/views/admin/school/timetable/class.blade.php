{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
    <div class="relative">
        
            <div class="flex items-center justify-between my-3">
                <h1 class="admin-h1">Class Timetable Detail</h1>
                <a href="#" onclick="print()" class="blue-bg text-sm text-white px-2 py-1 rounded mx-1">Print</a>
            </div>
        <livewire:timetable.class-time-table/>    
    </div>
@endsection

@push('scripts')
    <script>
        function print()
        {
            var print_ = document.getElementById("printDiv");
            var htmlToPrint = '' +
            '<style type="text/css">' +
            'table th, table td {' +
                'border:1px solid #000;' +
                'padding: 10px;' +
            '}' +
            '.flex {' +
                'display: flex;' +
            '}' +
            '.flex-row {' +
                'flex-direction: row;' +
            '}' +
            'table th, table td .bg-grey-light {' +
            'background-color: #dae1e7;' +
            '}' +
            'a {' +
                'text-decoration: none;' +  
                'color: #4a5568;' +
            '}' +
            '.font-semibold {' +
                'font-weight: 600;' +
            '}' +
            'text-gray-700 {' +
                '--text-opacity: 1;' +
                'color: #4a5568;' +
            '}' +
            'table.borderTable thead th, table.borderTable thead td {' +
                'border-bottom: 1px solid #1110;' +
            '}' +
            'table.borderTable {' +
                'width: 100%;' +
                'margin: 0 auto;' +
                'clear: both;' +
                'border-collapse: collapse;' +
                'border-spacing: 0;' +
            '}' +

            '@media only screen and (max-width: 760px), (max-device-width: 1024px) and (min-device-width: 768px){ ' +
                'td {' +
                    'border: none;' +
                    'border-bottom: 1px solid #eee;' +
                    'position: relative;' +
                    'padding-left:50%;' +
                    'padding-top:5%;' +
                    'padding-bottom:5%;' +
                '}' +
                '.borderTables_wrapper .borderTables_length, .borderTables_wrapper .borderTables_filter {' +
                    'float: none;' +
                    'text-align: left;' +
                '}' +
            '}' +
     
            '.t-dropdown.t-dropdown-size-sm button' +
            '{' +
                'border:none! important;' +
            '}' +
            '</style>';
            htmlToPrint += print_.outerHTML;
            win = window.open("");
            win.document.write( "<link rel='stylesheet' href='css/app.css' type='text/css' media='print'/>" );
            win.document.write(htmlToPrint);
            win.print();
            win.close();
        }
    </script>
@endpush