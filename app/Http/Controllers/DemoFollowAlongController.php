<?php

namespace App\Http\Controllers;

use App\Services\Demo\DemoFollowAlong;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Public follow-along page for the live demo (/demo). No login. Only the
 * demo school's dummy students are listed, by their short code and first
 * name. The page is not indexed and the route is rate limited.
 */
class DemoFollowAlongController extends Controller
{
    public function show()
    {
        $joinUrl = DemoFollowAlong::waLink('JOIN');
        $roster = DemoFollowAlong::roster(DemoFollowAlong::school())
            ->map(fn (array $row) => $row + ['bind_url' => DemoFollowAlong::waLink('BIND ' . $row['code'])]);

        $qrSvg = null;
        if ($joinUrl !== null) {
            $writer = new Writer(new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd));
            $qrSvg = preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($joinUrl));
        }

        return response()
            ->view('demo.follow-along', [
                'joinUrl' => $joinUrl,
                'qrSvg' => $qrSvg,
                'roster' => $roster,
                'links' => DemoFollowAlong::links(),
                'removalNotice' => DemoFollowAlong::removalNotice(),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
