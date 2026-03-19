{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $school->name }}</title>
    </head>
    <body>
        <p>#{{ $visitorlog->id }}</p>
        <p>{{ date('d-M-Y',strtotime($visitorlog->date_of_visit)) }}   {{ $visitorlog->entry_time }}</p>
        
        <table class="w-full">
            <tr class="border-b-2">
                <td class="py-3 px-2 w-1/5">Visiting Purpose   : </td>
                <td class="py-3 px-2">{{ ucwords(str_replace('_',' ',$visitorlog->visiting_purpose)) }}</td>
            </tr>

            <tr class="border-b-2">
                <td class="py-3 px-2 w-1/5">Visitor Details</td>    
            </tr>

            <tr class="border-b-2">
                <td class="py-3 px-2">Name : </td>
                <td class="py-3 px-2">{{ ucwords($visitorlog->name) }}</td>
            </tr> 

            <tr class="border-b-2">
                <td class="py-3 px-2">Company Name :</td>
                <td class="py-3 px-2">{{ ucfirst($visitorlog->company_name) }}</td>
            </tr>

            <tr class="border-b-2">
                <td class="py-3 px-2">Contact Number : </td>
                <td class="py-3 px-2">{{ $visitorlog->contact_number }}</td>
            </tr>

            <tr class="border-b-2">
                <td class="py-3 px-2">Address :</td>
                <td class="py-3 px-2">{{ $visitorlog->address }}</td>
            </tr>

            <tr class="border-b-2">
                <td class="py-3 px-2 w-1/5">Whom To Meet   : </td>
                <td class="py-3 px-2 mx-2">{{ $visitorlog->employee->FullName }} ( {{ ucwords(str_replace('_',' ',$visitorlog->employee[teacherprofile][0][designation]) ) }} )</td>
            </tr>

            <tr class="border-b-2">
                <td class="py-3 px-2 w-1/5">Visiting Count   : </td>
                <td class="py-3 px-2">{{ $visitorlog->number_of_visitors }}</td>
            </tr>
        </table>
    </body>
</html>