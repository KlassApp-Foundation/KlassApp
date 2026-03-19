{{-- SPDX-License-Identifier: MIT --}}
@php
	$faviconCandidates = [
		config('settings.favicon'),
		'favicons/klassapp-favicon.png',
		'favicon/klassapp-favicon.png',
		'favicon/favicon-32x32.png',
		'favicon/favicon.ico',
		'favicon.ico',
	];

	$resolvedFavicon = null;

	foreach ($faviconCandidates as $candidate) {
		if (!is_string($candidate) || trim($candidate) === '') {
			continue;
		}

		$candidate = ltrim($candidate, '/');

		if (file_exists(public_path($candidate))) {
			$resolvedFavicon = $candidate;
			break;
		}
	}

	$resolvedFavicon = $resolvedFavicon ?? 'favicon/klassapp-favicon.png';
	$faviconVersion = @filemtime(public_path($resolvedFavicon)) ?: time();
@endphp
<link rel="shortcut icon" href="{{ asset($resolvedFavicon) }}?v={{ $faviconVersion }}">
<link rel="icon" type="image/png" href="{{ asset($resolvedFavicon) }}?v={{ $faviconVersion }}">
<link rel="apple-touch-icon" href="{{ asset($resolvedFavicon) }}?v={{ $faviconVersion }}">
<meta name="theme-color" content="#ffffff">