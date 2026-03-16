{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
	<head>
    	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1">
 		<title>{{ $title }}</title>
	</head>
	<body>
	  	<table class="w-full">
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Class   : </td>
		        <td class="py-3 px-2">{{ $class }}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Subject  :  </td>
		        <td class="py-3 px-2">{{ $subject }}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Unit No.  :  </td>
		        <td class="py-3 px-2">{{ $unit_no }}</td>
		    </tr> 
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Unit Name : </td>
		        <td class="py-3 px-2">{{ $unit_name }}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Title   : </td>
		        <td class="py-3 px-2">{{ $title }}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Unit Breakdown  :  </td>
		        <td class="py-3 px-2">{{ $description }}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Objective  :  </td>
		        <td class="py-3 px-2">{!! $objective !!}</td>
		    </tr> 
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Duration : </td>
		        <td class="py-3 px-2">{{ $duration }}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Materials Required   : </td>
		        <td class="py-3 px-2">{!! $materials_required !!}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Introduction  :  </td>
		        <td class="py-3 px-2">{!! $introduction !!}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Procedure  :  </td>
		        <td class="py-3 px-2">{!! $procedure !!}</td>
		    </tr> 
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Conclusion : </td>
		        <td class="py-3 px-2">{!! $conclusion !!}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Assessment   : </td>
		        <td class="py-3 px-2">{{ $assessment }}</td>
		    </tr>
		    <tr class="border-b-2">
		        <td class="py-3 px-2 w-1/5">Modification  :  </td>
		        <td class="py-3 px-2">{{ $modification }}</td>
		    </tr>
	        @if($notes != null)
	   	        <tr class="border-b-2">
	   	         	<td class="py-3 px-2 w-1/5">Notes  :  </td>
	   	         	<td class="py-3 px-2">{{ $notes }}</td>
	   	      	</tr> 
	        @endif
		</table>
	</body>
</html>