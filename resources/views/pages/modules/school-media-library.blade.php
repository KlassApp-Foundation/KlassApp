{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
GegoK12 - school-media-library | Online School Management 
@endsection

@section('content')
<div class="bg-red-600">
<div class="container mx-auto py-8 px-1">
	<h1 class="lg:text-4xl text-2xl font-bold text-center text-white">Media Library </h1>
</div>
</div>
<div class="container mx-auto">
<div class="leading-loose py-5 px-3">
	<p class="text-base text-gray-700 my-3">
	You can able to access photos, videos of all functions of the school. The admin can enter details of Option of Media Upload and Study material, Titles, Description, Type like Video, Audio and Image.</p>
	<p class="text-base text-gray-700 my-3">
	The media library actually refers to a place to store image files (gif, jpg, png), PDF documents, Word documents and other HTML files.The Media Library contains all media items, respectively as images, documents, videos, and audio files. In addition the Media Library can keep all your media files in one place and organize them in a folder structure similar to the content tree.</p>
	<p class="text-xl lg:text-2xl md:text-2xl text-gray-800 my-3 italic ">Types of Media Image Files</p>
	<ul class="list-reset text-gray-700 px-4 lg:px-10 md:px-10 flex flex-wrap">
		<li class="w-full lg:w-1/2 flex items-center py-3">
	<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<g>
			<path d="M32,464V48c0-8.837,7.163-16,16-16h240v64c0,17.673,14.327,32,32,32h64v48h32v-64c0.025-4.253-1.645-8.341-4.64-11.36
				l-96-96C312.341,1.645,308.253-0.024,304,0H48C21.49,0,0,21.491,0,48v416c0,26.51,21.49,48,48,48h112v-32H48
				C39.164,480,32,472.837,32,464z"/>
			<path d="M480,320h-32v32h32v32h-64v-96h96c0-17.673-14.327-32-32-32h-64c-17.673,0-32,14.327-32,32v96c0,17.673,14.327,32,32,32
				h64c17.673,0,32-14.327,32-32v-32C512,334.327,497.673,320,480,320z"/>
			<path d="M336,256h-64c-8.837,0-16,7.163-16,16v144h32v-48h48c17.673,0,32-14.327,32-32v-48C368,270.327,353.673,256,336,256z
				 M336,336h-48v-48h48V336z"/>
			<path d="M208,384h-64v32h64c17.673,0,32-14.327,32-32V256h-32V384z"/>
		</g>
	</g>
</g></svg>
		<span class="mx-4">JPEG (or JPG) - Joint Photographic Experts Group</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
	<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<g>
			<path d="M32,464V48c0-8.837,7.163-16,16-16h240v64c0,17.673,14.327,32,32,32h64v48h32v-64c0.025-4.253-1.645-8.341-4.64-11.36
				l-96-96C312.341,1.645,308.253-0.024,304,0H48C21.49,0,0,21.491,0,48v416c0,26.51,21.49,48,48,48h112v-32H48
				C39.164,480,32,472.837,32,464z"/>
			<path d="M480,320h-32v32h32v32h-64v-96h96c0-17.673-14.327-32-32-32h-64c-17.673,0-32,14.327-32,32v96c0,17.673,14.327,32,32,32
				h64c17.673,0,32-14.327,32-32v-32C512,334.327,497.673,320,480,320z"/>
			<path d="M192,256h-64c-8.837,0-16,7.163-16,16v144h32v-48h48c17.673,0,32-14.327,32-32v-48C224,270.327,209.673,256,192,256z
				 M192,336h-48v-48h48V336z"/>
			<path d="M336,352l-67.2-89.6c-5.302-7.069-15.331-8.502-22.4-3.2c-4.029,3.022-6.4,7.764-6.4,12.8v144h32v-96l67.2,89.6
				c3.022,4.029,7.764,6.4,12.8,6.4c1.739,0.013,3.468-0.257,5.12-0.8c6.517-2.201,10.898-8.321,10.88-15.2V256h-32V352z"/>
		</g>
	</g>
</g></svg>
		<span class="mx-4">PNG - Portable Network Graphics</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<g>
			<path d="M32,464V48c0-8.837,7.163-16,16-16h240v64c0,17.673,14.327,32,32,32h64v48h32v-64c0.025-4.253-1.645-8.341-4.64-11.36
				l-96-96C312.341,1.645,308.253-0.024,304,0H48C21.49,0,0,21.491,0,48v416c0,26.51,21.49,48,48,48h112v-32H48
				C39.164,480,32,472.837,32,464z"/>
			<path d="M512,288v-32h-80c-8.837,0-16,7.163-16,16v144h32v-64h64v-32h-64v-32H512z"/>
			<rect x="368" y="256" width="32" height="160"/>
			<path d="M352,288c0-17.673-14.327-32-32-32h-64c-17.673,0-32,14.327-32,32v96c0,17.673,14.327,32,32,32h64
				c17.673,0,32-14.327,32-32v-32c0-17.673-14.327-32-32-32h-32v32h32v32h-64v-96H352z"/>
		</g>
	</g>
</g></svg>
		<span class="mx-4">GIF - Graphics Interchange Format</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
	<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="550.801px" height="550.801px" viewBox="0 0 550.801 550.801" style="enable-background:new 0 0 550.801 550.801;"
	 xml:space="preserve">
<g>
	<path d="M488.426,197.019H475.2v-63.816c0-0.401-0.063-0.799-0.116-1.205c-0.021-2.534-0.827-5.023-2.562-6.992L366.325,3.691
		c-0.032-0.031-0.063-0.042-0.085-0.073c-0.633-0.707-1.371-1.298-2.151-1.804c-0.231-0.158-0.464-0.287-0.706-0.422
		c-0.676-0.366-1.393-0.675-2.131-0.896c-0.2-0.053-0.38-0.135-0.58-0.19C359.87,0.119,359.037,0,358.193,0H97.2
		c-11.918,0-21.6,9.693-21.6,21.601v175.413H62.377c-17.049,0-30.873,13.818-30.873,30.87v160.542
		c0,17.044,13.824,30.876,30.873,30.876h13.224V529.2c0,11.907,9.682,21.601,21.6,21.601h356.4c11.907,0,21.6-9.693,21.6-21.601
		V419.302h13.226c17.044,0,30.871-13.827,30.871-30.87V227.89C519.297,210.838,505.47,197.019,488.426,197.019z M97.2,21.605
		h250.193v110.51c0,5.967,4.841,10.8,10.8,10.8h95.407v54.108H97.2V21.605z M363.688,290.678v29.663h-57.902v64.857h-36.611V223.916
		h98.585v29.916h-61.974v36.851h57.902V290.678z M237.579,223.916v161.288h-36.616V223.916H237.579z M57.626,254.544v-30.628H181.57
		v30.628h-44.025v130.66h-36.605v-130.66H57.626z M453.601,523.353H97.2V419.302h356.4V523.353z M493.884,253.832h-61.989v36.851
		h57.913v29.669h-57.913v64.853h-36.598V223.916h98.587V253.832z"/>
</g></svg>
		<span class="mx-4">TIFF - Tagged Image File</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
		<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<g>
			<path d="M416,176v-64c0.025-4.253-1.645-8.341-4.64-11.36l-96-96C312.341,1.645,308.253-0.024,304,0H48C21.49,0,0,21.491,0,48
				v416c0,26.51,21.49,48,48,48h112v-32H48c-8.837,0-16-7.163-16-16V48c0-8.837,7.163-16,16-16h240v64c0,17.673,14.327,32,32,32h64
				v48H416z"/>
			<path d="M208,256h-64c-8.837,0-16,7.163-16,16v144h32v-48h48c17.673,0,32-14.327,32-32v-48C240,270.327,225.673,256,208,256z
				 M208,336h-48v-48h48V336z"/>
			<path d="M304,288h32c8.837,0,16,7.163,16,16h32c0-26.51-21.49-48-48-48h-32c-26.51,0-48,21.49-48,48c0,26.51,21.49,48,48,48h32
				c8.837,0,16,7.163,16,16s-7.163,16-16,16h-32c-8.837,0-16-7.163-16-16h-32c0,26.51,21.49,48,48,48h32c26.51,0,48-21.49,48-48
				c0-26.51-21.49-48-48-48h-32c-8.837,0-16-7.163-16-16S295.164,288,304,288z"/>
			<path d="M480,256h-64c-8.837,0-16,7.163-16,16v128c0,8.837,7.163,16,16,16h64c17.673,0,32-14.327,32-32v-96
				C512,270.327,497.673,256,480,256z M480,384h-48v-96h48V384z"/>
		</g>
	</g>
</g></svg>
		<span class="mx-4">PSD - Photoshop Document</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
	<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<g>
			<path d="M368,256h-64c-8.837,0-16,7.163-16,16v128c0,8.837,7.163,16,16,16h64c17.673,0,32-14.327,32-32v-96
				C400,270.327,385.673,256,368,256z M368,384h-48v-96h48V384z"/>
			<path d="M512,288v-32h-80c-8.837,0-16,7.163-16,16v144h32v-64h64v-32h-64v-32H512z"/>
			<path d="M32,464V48c0-8.837,7.163-16,16-16h240v64c0,17.673,14.327,32,32,32h64v48h32v-64c0.025-4.253-1.645-8.341-4.64-11.36
				l-96-96C312.341,1.645,308.253-0.024,304,0H48C21.49,0,0,21.491,0,48v416c0,26.51,21.49,48,48,48h112v-32H48
				C39.164,480,32,472.837,32,464z"/>
			<path d="M240,256h-64c-8.837,0-16,7.163-16,16v144h32v-48h48c17.673,0,32-14.327,32-32v-48C272,270.327,257.673,256,240,256z
				 M240,336h-48v-48h48V336z"/>
		</g>
	</g>
</g></svg>
		<span class="mx-4">PDF - Portable Document Format</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
		<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="550.801px" height="550.801px" viewBox="0 0 550.801 550.801" style="enable-background:new 0 0 550.801 550.801;"
	 xml:space="preserve">
<g>
	<path d="M488.427,197.014h-13.226v-63.817c0-0.401-0.063-0.799-0.116-1.205c-0.021-2.531-0.828-5.021-2.563-6.993L366.325,3.694
		c-0.031-0.031-0.063-0.045-0.084-0.076c-0.633-0.707-1.371-1.295-2.151-1.804c-0.232-0.155-0.465-0.285-0.707-0.422
		c-0.675-0.366-1.393-0.675-2.131-0.896c-0.2-0.053-0.379-0.135-0.58-0.188C359.871,0.119,359.037,0,358.193,0H97.201
		c-11.918,0-21.6,9.693-21.6,21.601v175.413H62.378c-17.044,0-30.874,13.818-30.874,30.873v160.545
		c0,17.038,13.83,30.87,30.874,30.87h13.223V529.2c0,11.907,9.682,21.601,21.6,21.601h356.4c11.907,0,21.601-9.693,21.601-21.601
		V419.302h13.226c17.054,0,30.87-13.832,30.87-30.87V227.887C519.297,210.832,505.48,197.014,488.427,197.014z M97.201,21.601
		h250.193v110.51c0,5.97,4.841,10.8,10.8,10.8h95.407v54.108h-356.4V21.601z M347.521,277.657c0,16.158-5.379,29.869-15.183,39.171
		c-12.714,11.992-31.577,17.382-53.609,17.382c-4.894,0-9.295-0.253-12.717-0.738v58.988h-36.97V229.69
		c11.507-1.962,27.665-3.431,50.425-3.431c23.019,0,39.419,4.406,50.43,13.215C340.422,247.802,347.521,261.513,347.521,277.657z
		 M196.513,292.35v30.354h-60.697v39.16h67.793v30.597H98.351V227.486h101.83v30.596h-64.37v34.268H196.513z M453.601,523.347
		h-356.4V419.302h356.4V523.347z M411.635,394.907c-18.848,0-37.446-4.894-46.754-10.041l7.604-30.828
		c10.025,5.126,25.439,10.272,41.365,10.272c17.128,0,26.188-7.098,26.188-17.866c0-10.283-7.847-16.158-27.653-23.256
		c-27.422-9.551-45.288-24.722-45.288-48.711c0-28.149,23.498-49.687,62.405-49.687c18.615,0,32.326,3.916,42.114,8.321
		l-8.322,30.101c-6.612-3.174-18.361-7.826-34.52-7.826c-16.163,0-23.994,7.341-23.994,15.904c0,10.521,9.303,15.177,30.607,23.256
		c29.119,10.769,42.83,25.951,42.83,49.201C478.217,371.408,456.912,394.907,411.635,394.907z"/>
	<path d="M281.434,254.412c-7.594,0-12.728,0.738-15.417,1.461v48.716c3.175,0.728,7.096,0.97,12.479,0.97
		c19.828,0,32.068-10.024,32.068-26.92C310.564,263.461,300.027,254.412,281.434,254.412z"/>
</g></svg>
		<span class="mx-4">EPS - Encapsulated Postscript</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
		<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="548.29px" height="548.291px" viewBox="0 0 548.29 548.291" style="enable-background:new 0 0 548.29 548.291;"
	 xml:space="preserve">
<g>
	<path d="M486.205,196.116h-13.166V132.59c0-0.399-0.062-0.795-0.109-1.2c-0.021-2.52-0.828-4.997-2.556-6.96L364.656,3.677
		c-0.031-0.031-0.064-0.044-0.085-0.075c-0.629-0.704-1.364-1.29-2.141-1.796c-0.231-0.154-0.462-0.283-0.704-0.419
		c-0.672-0.365-1.386-0.672-2.121-0.893c-0.199-0.052-0.377-0.134-0.576-0.186C358.229,0.118,357.4,0,356.562,0H96.757
		C84.893,0,75.256,9.649,75.256,21.502v174.613H62.093c-16.972,0-30.733,13.756-30.733,30.733v159.812
		c0,16.961,13.761,30.731,30.733,30.731h13.163V526.79c0,11.854,9.637,21.501,21.501,21.501h354.777
		c11.853,0,21.502-9.647,21.502-21.501V417.392H486.2c16.966,0,30.729-13.771,30.729-30.731V226.849
		C516.93,209.872,503.177,196.116,486.205,196.116z M451.534,520.962H96.757v-103.57h354.777V520.962z M158.811,382.609
		l50.184-164.228h48.722l50.927,164.228h-39.947l-12.682-42.158h-47.02l-11.695,42.158H158.811z M330.566,382.609V218.381h37.292
		v164.228H330.566z M451.534,196.116H96.757V21.502h249.053v110.006c0,5.943,4.818,10.751,10.751,10.751h94.973V196.116z"/>
	<path d="M240.426,277.832c-2.919-9.744-5.843-21.93-8.284-31.676h-0.488c-2.431,9.746-4.872,22.174-7.549,31.676l-9.745,34.846
		h36.305L240.426,277.832z"/>
</g></svg>
		<span class="mx-4">AI - Adobe Illustrator Document</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
		<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<g>
			<path d="M416,176v-64c0.025-4.253-1.645-8.341-4.64-11.36l-96-96C312.341,1.645,308.253-0.024,304,0H48C21.49,0,0,21.491,0,48
				v416c0,26.51,21.49,48,48,48h112v-32H48c-8.837,0-16-7.163-16-16V48c0-8.837,7.163-16,16-16h240v64c0,17.673,14.327,32,32,32h64
				v48H416z"/>
			<path d="M224,352l-67.2-89.6c-5.302-7.069-15.331-8.502-22.4-3.2c-4.029,3.022-6.4,7.764-6.4,12.8v144h32v-96l67.2,89.6
				c5.302,7.069,15.331,8.502,22.4,3.2c4.029-3.022,6.4-7.764,6.4-12.8V256h-32V352z"/>
			<rect x="80" y="256" width="32" height="160"/>
			<path d="M352,256h-64c-8.837,0-16,7.163-16,16v128c0,8.837,7.163,16,16,16h64c17.673,0,32-14.327,32-32v-96
				C384,270.327,369.673,256,352,256z M352,384h-48v-96h48V384z"/>
			<path d="M480,256h-64c-8.837,0-16,7.163-16,16v128c0,8.837,7.163,16,16,16h64c17.673,0,32-14.327,32-32v-96
				C512,270.327,497.673,256,480,256z M480,384h-48v-96h48V384z"/>
		</g>
	</g>
</g></svg>
		<span class="mx-4">INDD - Adobe Indesign Document</span>
		</li>
		<li class="w-full lg:w-1/2 flex items-center py-3">
		<svg class="w-6 h-6 fill-current text-red-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<g>
			<path d="M32,464V48c0-8.837,7.163-16,16-16h240v64c0,17.673,14.327,32,32,32h64v48h32v-64c0.025-4.253-1.645-8.341-4.64-11.36
				l-96-96C312.341,1.645,308.253-0.024,304,0H48C21.49,0,0,21.491,0,48v416c0,26.51,21.49,48,48,48h112v-32H48
				C39.164,480,32,472.837,32,464z"/>
			<path d="M336,256h-64c-17.673,0-32,14.327-32,32v128h32v-48h64v48h32V288C368,270.327,353.673,256,336,256z M336,336h-64v-48h64
				V336z"/>
			<path d="M192,256h-80c-8.837,0-16,7.163-16,16v144h32v-64h9.44l59.36,59.36l22.56-22.56l-36.8-36.8H192c17.673,0,32-14.327,32-32
				v-32C224,270.327,209.673,256,192,256z M192,320h-64v-32h64V320z"/>
			<path d="M480,256v105.44l-20.64-20.64c-6.241-6.204-16.319-6.204-22.56,0L416,361.44V256h-32v144
				c-0.051,8.836,7.07,16.041,15.907,16.093c4.299,0.025,8.426-1.681,11.453-4.733l36.64-36.8l36.64,36.64
				c6.223,6.274,16.353,6.316,22.627,0.093c3.013-2.988,4.715-7.05,4.733-11.293V256H480z"/>
		</g>
	</g>
</g></svg>
		<span class="mx-4">RAW - Raw Image Formats</span>
		</li>
	</ul>
	<p class="text-base text-gray-700 my-3">Every business requires some space for data storage, irrespective of the volume or size of the firm. Particularly, when you’re talking about media files, this storage has to be bulk. To make your workload easier, our media library management help with organizing files in an easy and straightforward manner.</p>
	<p class="text-base text-gray-700 my-3">Sometimes, you will have files that become obsolete. These media files contain images or presentations that are no longer of any use. Don’t forget to delete these files.Nobody else knows your requirements better than you, not even the smartest of devices. Organizing your files yourself will help you locate files efficiently whenever you need them the most.</p>
</div>
</div>
@endsection 