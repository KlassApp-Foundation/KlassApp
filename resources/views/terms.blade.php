{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
    KlassApp - Terms of Service
@endsection

@section('content')
<div class="bg-red-600">
    <div class="container mx-auto h-full">
        <div class="text-center flex flex-col justify-center items-center py-5 leading-loose tracking-wider h-full">
            <h1 class="text-white font-plex text-4xl">{!! __('terms.terms_title') !!}</h1>
            <p class="text-base font-exo text-white">{!! __('terms.last_updated', ['date' => date('F j, Y')]) !!}</p>
        </div>
    </div>
</div>
<div class="bg-gray-100 py-5">
    <div class="container mx-auto">
        <div class="w-full lg:w-2/3 lg:mx-auto leading-loose py-2">
            <p class="text-base text-gray-700 my-4">{!! __('terms.welcome') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.introduction') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.introduction_detail') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.eligibility') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.eligibility_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.school_data') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.school_data_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.parental_consent') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.parental_consent_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.data_protection') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.data_protection_content_1') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.data_protection_encryption') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.data_protection_access') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.data_protection_backup') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.data_protection_compliance') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.data_protection_audits') !!}</p>
            <p class="text-base text-gray-700 my-2">{!! __('terms.data_protection_content_2') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.ugandan_law_compliance') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.ugandan_law_content_1') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.ugandan_dpa') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.ugandan_computer_misuse') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.ugandan_education') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.ugandan_taxation') !!}</p>
            <p class="text-base text-gray-700 my-2">{!! __('terms.ugandan_law_content_2') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.account_responsibilities') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.account_content') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.responsibility_accuracy') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.responsibility_compliance') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.responsibility_security') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.responsibility_termination') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.responsibility_audit') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.acceptable_use') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.acceptable_use_content') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.prohibited_harassment') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.prohibited_false_info') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.prohibited_attack') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.prohibited_commercial') !!}</p>
            <p class="text-base text-gray-700 my-2">• {!! __('terms.prohibited_mining') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.intellectual_property') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.ip_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.third_party_services') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.third_party_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.payments') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.payments_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.term_and_termination') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.termination_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.dispute_resolution') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.dispute_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.governing_law') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.governing_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.limitation_of_liability') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.liability_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.indemnification') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.indemnification_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.changes_to_terms') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.changes_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.contact_information') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.contact_content') !!}</p>
            
            <h2 class="text-2xl text-gray-800 py-3">{!! __('terms.acknowledgment') !!}</h2>
            <p class="text-base text-gray-700 my-2">{!! __('terms.acknowledgment_content') !!}</p>
        </div>
    </div>
</div>
@endsection