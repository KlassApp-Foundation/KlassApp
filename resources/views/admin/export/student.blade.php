{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
<head>
<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
</style>
	<title></title>
    <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">  
</head>
<body>

	       
	 <h4 class="text-2xl" style="text-align: center;"></h4>       
    <h2 class="text-sm" style="text-align: center;"><strong>Export Student List</strong></h2>


<table style="width:100%">
  <tr>
    <th>Name</th>
    <th>Class</th>
    <th>Parent Name</th>
    <th>Mobile Number</th>
    <th>Aadhaar Number </th>
    
  </tr>
  @foreach($users as $user)
  <tr>
    <td>{{$user->FullName}}</td>
    <td>{{$user->studentAcademicLatest->standardLink->StandardSection}}</td>
    <td> @if(count($user->parents)>0){{$user->parents[0]['userParent']['userprofile']['firstname'].' '.$user->parents[0]['userParent']['userprofile']['lastname']}}@endif</td>
    <td>@if(count($user->parents)>0){{$user->parents[0]['userParent']['mobile_no']}}@endif</td> 
    <td>{{$user->userprofile->aadhar_number}}</td>
  </tr>
  @endforeach
  
</table>

	

</body>

</html>