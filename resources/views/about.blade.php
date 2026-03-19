{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('content')
<!-- start -->
<div class="container-wrapper about-bg">
	<div class="container mx-auto h-full">
		<div class="text-center flex flex-col justify-center items-center py-5 leading-loose tracking-wider h-full">
		<h1 class="text-white text-5xl">{!! __('about.about') !!}</h1>
		<h2 class="text-xl text-white">{!! __('about.text_1') !!}</h2>
		</div>
		
	</div>
</div>
<!-- end -->
<!-- start -->
<div class="bg-gray-200 py-5">
	<div class="container mx-auto">
		<div>
			<h2 class="text-2xl text-gray-800 py-3 italic text-center">{!! __('about.text_2') !!}</h2>
			<div class="my-3 w-full px-3 lg:px-0 md:px-0 lg:w-3/5 mx-auto ">
				<ul class="list-reset text-base text-gray-700 leading-relaxed">
				<li class="py-3">{!! __('about.text_3') !!}</li>
				<li class="py-3">{!! __('about.text_4') !!}</li>
				<li class="py-3">{!! __('about.text_5') !!}</li>
			    <li class="py-3"> {!! __('about.text_6') !!}</li>
			     <li class="py-3">{!! __('about.text_7') !!}</li>
			     <!-- <li class="py-3">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable.</li> -->
				</ul>
			</div>
		</div>
	</div>
</div>
<!-- end -->
<!-- start -->
<div class="bg-white py-5">
<div class="container mx-auto ">
<div class="flex flex-col lg:flex-row md:flex-row justify-center px-3 lg:px-0 md:px-0" >
	<div class="w-full lg:w-2/6 md:w-2/6 lg:mx-5 md:mx-5 text-center">
		<h2 class="text-2xl text-gray-800 py-3">{!! __('about.text_8') !!}</h2>
		<div class="leading-loose py-2">
		<p class="text-base text-gray-700 my-2">
			{!! __('about.text_9') !!}
		</p>
		<p class="text-base text-gray-700 my-2">
			{!! __('about.text_10') !!}
		</p>
		</div>
	</div>
	<div class="w-full lg:w-2/6 md:w-2/6 lg:mx-5 md:mx-5 text-center">
		<h2 class="text-2xl text-gray-800 py-3">{!! __('about.text_11') !!}</h2>
		<div class="leading-loose py-2">
		<p class="text-base text-gray-700 my-2">
			{!! __('about.text_12') !!}
		</p>
		<p class="text-base text-gray-700 my-2">
			{!! __('about.text_13') !!}
		</p>
		</div>
	</div>
</div>
</div>
</div>
<!-- end -->
<!-- start -->
<div class="flex flex-col lg:flex-row md:flex-row">
    
  	<!-- <div class="w-full">
  		<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d125766.26802637281!2d78.05278257478408!3d9.917638536782231!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3b00c582b1189633%3A0xdc955b7264f63933!2sMadurai%2C%20Tamil%20Nadu!5e0!3m2!1sen!2sin!4v1571733095996!5m2!1sen!2sin" height="350" frameborder="0" style="border:0;" allowfullscreen="" class="w-full"></iframe>
  	</div> -->
  <div class="w-full">
    <div class="tw-form-group w-1/2" hidden>
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="address" class="col-md-4 control-label">Address</label>
        </div>
        <div class="mb-2 w-full relative">
          @if( Auth::user() )
            <input type="text" name="address" class="tw-form-control w-full" id="address" value="{{ $school->address }}" required>
          @else
            <input type="text" name="address" class="tw-form-control w-full" id="address" value="Madurai,Tamilnadu,India" required>
          @endif
          <span class="absolute m-2 top-0 right-0">
            <a href="#" onclick="codeAddress(); return false;" dusk="getCords" id="getCords">
              <img src="{{asset('/uploads/icons/search.svg')}}" class="w-4 h-4">
            </a>
          </span>
          <span class="text-red-500 text-xs font-semibold">{{$errors->first('address')}}</span>
        </div>   
      </div>
    </div>

    <div class="w-full">
      <div class="lg:mr-8 md:mr-8">
        <div id="map_canvas" class="w-full" style="height: 250px; width: 1348px;">
        </div> 
      </div>
    </div> 

    <div class="tw-form-group w-1/2" hidden>
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="password" class="col-md-4 control-label">Latitude</label>
        </div>
        <div class="mb-2 w-full relative">
          <input id="latitude" type="text" class="tw-form-control w-1/2" name="latitude" value="{{old('latitude')}}"> 
          @if ($errors->has('latitude'))
            <span class="help-block">
              <strong>{{ $errors->first('latitude') }}</strong>
            </span> 
          @endif
        </div>
      </div>
      <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
          <label for="password-confirm" class="col-md-4 control-label">Longitude</label>
        </div>
        <div class="mb-2 w-full relative">
          <input id="longitude" type="text" class="tw-form-control w-1/2" name="longitude" value="{{old('longitude')}}"> 
          @if ($errors->has('longitude'))
            <span class="help-block">
              <strong>{{ $errors->first('longitude') }}</strong>
            </span>
          @endif
        </div>
      </div>      
    </div>
  </div>
</div>
<!-- end -->

@endsection 

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places&key=AIzaSyBO00niIGAyv2GkZZi-W26Ii6ff3YEyu_w"></script>
<script type="text/javascript">

var map;

function initialize() 
{
    var address = (document.getElementById('address'));
    var autocomplete = new google.maps.places.Autocomplete(address);
    autocomplete.setTypes(['geocode']);
    google.maps.event.addListener(autocomplete, 'place_changed', function() {
        var place = autocomplete.getPlace();
        if (!place.geometry) {
            return;
        }

        var address = '';
        if (place.address_components) {
            address = [
                (place.address_components[0] && place.address_components[0].short_name || ''),
                (place.address_components[1] && place.address_components[1].short_name || ''),
                (place.address_components[2] && place.address_components[2].short_name || '')
            ].join(' ');
        }
    });
    codeAddress("address");
}

function longlat(lat, lng) 
{
    //Map
    var myLatlng = new google.maps.LatLng(lat, lng);

    var myOptions = {
        zoom: 15,
        center: myLatlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    }

    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

    var marker = new google.maps.Marker({
        draggable: true,
        position: myLatlng,
        map: map,
        title: "Your location"
    });
    google.maps.event.addListener(marker, 'mouseup', function(event) {
        document.getElementById('latitude').value = event.latLng.lat()
        document.getElementById('longitude').value = event.latLng.lng()
    });

    //map
}

function codeAddress() 
{
    geocoder = new google.maps.Geocoder();
    var address = document.getElementById("address").value;
    geocoder.geocode({ 'address': address }, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) 
        {
            //alert("Latitude: "+results[0].geometry.location.lat());
            // alert("Longitude: "+results[0].geometry.location.lng());
            document.getElementById('latitude').value = results[0].geometry.location.lat();
            document.getElementById('longitude').value = results[0].geometry.location.lng();
            longlat(results[0].geometry.location.lat(), results[0].geometry.location.lng());
        } 
        else 
        {
            //alert("Geocode was not successful for the following reason: " + status);
        }
    });
}
google.maps.event.addDomListener(window, 'load', initialize);
</script>
@endpush