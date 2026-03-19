{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@section('content')

        <div class="w-1/3 mx-auto my-4 flex flex-col">
            @include('layouts.partials.logo')
            <div class="bg-white shadow border border-grey p-5">
                <div class=""><h1 class="font-heading font-bold text-2xl mb-3">{{ __('Reset Password') }}</h1></div>

                <div class="">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" aria-label="{{ __('Reset Password') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="email" class="tw-form-label">{{ __('E-Mail Address') }}</label>

                            <div class="mt-2 mb-3">
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }} tw-form-control w-full" name="email" value="{{ old('email') }}" required>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback text-xs text-red-600" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row mt-4">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary submit-btn">
                                    {{ __('Send Password Reset Link') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

@endsection
