<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\PremiumPage;
use App\Models\SchoolDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PremiumPageController extends Controller
{
    public function edit()
    {
        $schoolId = Auth::user()->school_id;
        $page = PremiumPage::firstOrNew(['school_id' => $schoolId]);
        $details = SchoolDetail::where('school_id', $schoolId)
            ->pluck('meta_value', 'meta_key');

        return view('admin.settings.premium_page', compact('page', 'details'));
    }

    public function update(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $data = $request->validate([
            'template_id' => 'required|integer|between:1,5',
            'is_published' => 'boolean',
            'hero_image' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:500',
            'custom_colors' => 'nullable|array',
            'custom_colors.primary' => 'nullable|string|max:7',
            'custom_colors.secondary' => 'nullable|string|max:7',
            'custom_colors.accent' => 'nullable|string|max:7',
            'custom_content' => 'nullable|json',
        ]);

        $data['is_published'] = $request->boolean('is_published');
        $data['custom_colors'] = ! empty($data['custom_colors'])
            ? json_encode($data['custom_colors'])
            : null;

        if ($request->filled('custom_content')) {
            $decoded = json_decode($request->custom_content, true);
            $data['custom_content'] = json_encode($decoded ?: []);
        } else {
            $data['custom_content'] = null;
        }

        PremiumPage::updateOrCreate(
            ['school_id' => $schoolId],
            $data
        );

        return redirect()->back()->with('success', 'Premium page settings saved.');
    }
}
