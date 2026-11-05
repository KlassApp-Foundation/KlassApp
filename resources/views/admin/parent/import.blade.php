{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
    <div class="">
        <div class="container-body">
            <h1 class="admin-h1 my-3">Import Parents</h1>
        </div>
        <div class="panel panel-default bg-white shadow px-3 py-3">
            <form action="{{ url('admin/importParents') }}" method="post" enctype="multipart/form-data">
                @csrf
                @include('partials.message')
                <p>Columns: firstname, lastname, mobile_no, email. Parents are added to this school only.</p>
                <input type="file" name="import_file" accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
                <span class="text-red-500 text-xs font-semibold">{{ $errors->first('import_file') }}</span>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit">Import</button>
                </div>
            </form>
        </div>
    </div>
@endsection
