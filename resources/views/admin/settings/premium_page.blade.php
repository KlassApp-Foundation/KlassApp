@php
    $templates = [
        1 => ['name' => 'Classic',  'desc' => 'Professional & traditional', 'color' => '#1E6FD9'],
        2 => ['name' => 'Modern',   'desc' => 'Bold & contemporary',       'color' => '#0D1526'],
        3 => ['name' => 'Minimal',  'desc' => 'Clean & airy',              'color' => '#6B7280'],
        4 => ['name' => 'Vibrant',  'desc' => 'Colorful & energetic',      'color' => '#22C55E'],
        5 => ['name' => 'Premium',  'desc' => 'Rich & immersive',          'color' => '#8B5CF6'],
    ];
@endphp
@extends('layouts.app')
@section('content')
<div class="container mx-auto">
<div class="w-full my-3 lg:mx-8 md:mx-8 px-3 lg:px-0 md:px-0">
    <h1 class="my-3">Premium School Page</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ url('/admin/subscriptions') }}" enctype="multipart/form-data">
        @csrf

        {{-- Publish toggle --}}
        <div class="tw-form-group">
            <label class="tw-form-label inline-flex items-center gap-2">
                <input type="checkbox" name="is_published" value="1" {{ $page->is_published ? 'checked' : '' }} />
                Publish premium page
            </label>
            <p class="text-gray-500 text-xs mt-1">When published, the page will be live at /schools/{{ optional(Auth::user()->school)->slug ?? '{slug}' }}</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Template selection --}}
            <div class="tw-form-group">
                <label class="tw-form-label">Template</label>
                <div class="grid grid-cols-2 gap-3 mt-2">
                    @foreach($templates as $id => $tpl)
                    <label class="border rounded-lg p-4 cursor-pointer hover:border-blue-500 transition {{ $page->template_id == $id ? 'border-blue-500 ring-2 ring-blue-200' : 'border-gray-200' }}" style="border-color: {{ $page->template_id == $id ? '#3B82F6' : '#E5E7EB' }};">
                        <input type="radio" name="template_id" value="{{ $id }}" {{ $page->template_id == $id ? 'checked' : '' }} class="sr-only" onchange="this.closest('label').querySelector('.preview-box').style.borderColor='#3B82F6'">
                        <div class="h-20 rounded mb-2 flex items-center justify-center text-white font-bold text-sm" style="background: linear-gradient(135deg, {{ $tpl['color'] }}, {{ $tpl['color'] }}88);">
                            Template {{ $id }}
                        </div>
                        <div class="font-medium text-sm">{{ $tpl['name'] }}</div>
                        <div class="text-xs text-gray-500">{{ $tpl['desc'] }}</div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Colors --}}
            <div class="tw-form-group">
                <label class="tw-form-label">Brand Colors</label>
                <div class="space-y-3 mt-2">
                    <div>
                        <label class="text-xs font-medium text-gray-500">Primary</label>
                        <input type="color" name="custom_colors[primary]" value="{{ $page->custom_colors['primary'] ?? '#1E6FD9' }}" class="block h-10 w-full rounded border border-gray-200 cursor-pointer" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500">Secondary</label>
                        <input type="color" name="custom_colors[secondary]" value="{{ $page->custom_colors['secondary'] ?? '#0D1526' }}" class="block h-10 w-full rounded border border-gray-200 cursor-pointer" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500">Accent</label>
                        <input type="color" name="custom_colors[accent]" value="{{ $page->custom_colors['accent'] ?? '#22C55E' }}" class="block h-10 w-full rounded border border-gray-200 cursor-pointer" />
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="grid lg:grid-cols-2 gap-6 mt-4">
            <div class="tw-form-group">
                <label class="tw-form-label">SEO Title</label>
                <input type="text" name="seo_title" value="{{ old('seo_title', $page->seo_title) }}" class="tw-form-control w-full lg:w-128" maxlength="120" />
            </div>
            <div class="tw-form-group">
                <label class="tw-form-label">SEO Description</label>
                <input type="text" name="seo_description" value="{{ old('seo_description', $page->seo_description) }}" class="tw-form-control w-full lg:w-128" maxlength="500" />
            </div>
        </div>

        {{-- Hero image path (placeholder) --}}
        <div class="tw-form-group mt-4">
            <label class="tw-form-label">Hero Image URL / Path</label>
            <input type="text" name="hero_image" value="{{ old('hero_image', $page->hero_image) }}" class="tw-form-control w-full lg:w-128" placeholder="storage/example.jpg" />
            <p class="text-gray-500 text-xs mt-1">Path relative to storage/app/public/</p>
        </div>

        {{-- Custom Content (JSON editor) --}}
        <div class="tw-form-group mt-4">
            <label class="tw-form-label">Custom Content <span class="text-gray-400 text-xs font-normal">(JSON)</span></label>
            <textarea name="custom_content" rows="12" class="tw-form-control w-full lg:w-192 font-mono text-sm">{{ old('custom_content', $page->custom_content ? json_encode($page->custom_content, JSON_PRETTY_PRINT) : '') }}</textarea>
            <details class="mt-2">
                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">Show JSON schema</summary>
                <pre class="text-xs text-gray-500 mt-1 bg-gray-50 p-3 rounded overflow-x-auto">
{
  "stats": [
    { "value": "500+", "label": "Students" },
    { "value": "50+",  "label": "Teachers" },
    { "value": "15",   "label": "Years" },
    { "value": "98%",  "label": "Pass Rate" }
  ],
  "about_heading": "Our Story",
  "about_body": "&lt;p&gt;Rich text content...&lt;/p&gt;",
  "features": [
    { "icon": "★", "title": "Feature", "body": "Description" }
  ],
  "testimonials": [
    { "quote": "...", "author": "Name", "role": "Title" }
  ],
  "gallery": [
    { "url": "/storage/image.jpg", "alt": "Caption" }
  ]
}</pre>
            </details>
        </div>

        <input type="submit" value="Save Settings" name="submit" class="btn btn-submit mt-4" />
    </form>
</div>
</div>
@endsection
