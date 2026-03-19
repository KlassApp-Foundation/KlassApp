{{-- SPDX-License-Identifier: MIT --}}
<meta property="og:title" content="{!!\config::get('settings.sitename')!!}"/>
<meta property="og:url" content="{{env('APP_URL')}}">
<meta property="og:image" content="{!!url(\config::get('settings.sitelogo'))!!}"/>
<meta property="twitter:image" content="{!!url(\config::get('settings.twitter_card_image'))!!}"/>
<meta property="facebook:image" content="{!!url(\config::get('settings.facebook_card_image'))!!}"/>
<meta property="twitter:description" content="{!!\config::get('settings.twitter_description')!!}"/>