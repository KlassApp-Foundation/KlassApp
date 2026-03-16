{{-- SPDX-License-Identifier: MIT --}}
<html>
   
   <head>
      <title>Upload List</title>
   </head>
   
   <body>
      <table border = 1>
         <tr>
            <td>Id</td>
            <td>Church Id</td>
            <td>Name</td>
            <td>Description</td>
            <td>Path</td>
        
                

         </tr>
         @foreach ($files as $file)
         <tr>
           <td>{{ $file->id}}</td>
            <td>{{ $file->name }}</td>
            <td>{{ $file->description }}</td>
            <td><a href="{{ $file->path }}">{{ $file->path }}</a></td>
           
         </tr>
         @endforeach


      </table>
   </body>
</html>