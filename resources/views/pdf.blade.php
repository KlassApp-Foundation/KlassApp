{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
    <table class="table table-bordered">
    <thead>
      <tr>
        <td><b>School Name</b></td>
        <td><b>Exam Name</b></td>
        <td><b>Academic Year</b></td>     
        <td><b>Standard </b></td>     
      </tr>
      </thead>
      <tbody>
        @foreach($marks as $mark)
      <tr>
        <td>
         xcvzxv
        </td>
        <td>
         {{$mark[0]['exam']['name']}}
        </td>
        <td>
         fdgdf
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </body>
</html>