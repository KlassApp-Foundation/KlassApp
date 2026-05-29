<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageDeliveryLog;
use App\Models\WhatsAppUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsAppDashboardController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $period = $request->input('period', '7d');

        $dateFrom = match ($period) {
            '24h'   => Carbon::now()->subDay(),
            '7d'    => Carbon::now()->subWeek(),
            '30d'   => Carbon::now()->subMonth(),
            '90d'   => Carbon::now()->subMonths(3),
            default => Carbon::now()->subWeek(),
        };

        // — Aggregate stats —
        $totalSent = MessageDeliveryLog::where('direction', 'outbound')
            ->where('sent_at', '>=', $dateFrom)
            ->count();

        $delivered = MessageDeliveryLog::where('direction', 'outbound')
            ->where('sent_at', '>=', $dateFrom)
            ->whereIn('status', ['delivered', 'read'])
            ->count();

        $failed = MessageDeliveryLog::where('direction', 'outbound')
            ->where('sent_at', '>=', $dateFrom)
            ->where('status', 'failed')
            ->count();

        $pending = MessageDeliveryLog::where('direction', 'outbound')
            ->where('sent_at', '>=', $dateFrom)
            ->where('status', 'sent')
            ->count();

        $inbound = MessageDeliveryLog::where('direction', 'inbound')
            ->where('sent_at', '>=', $dateFrom)
            ->count();

        $deliveryRate = $totalSent > 0 ? round(($delivered / $totalSent) * 100, 1) : 0;
        $failureRate = $totalSent > 0 ? round(($failed / $totalSent) * 100, 1) : 0;

        // — Linked users count —
        $linkedUsers = WhatsAppUser::count();
        $optedIn = WhatsAppUser::where('opted_in', true)->count();

        // — Breakdown by flow_type —
        $flowBreakdown = MessageDeliveryLog::where('direction', 'outbound')
            ->where('sent_at', '>=', $dateFrom)
            ->selectRaw("flow_type, COUNT(*) as total, SUM(status = 'failed') as failed_count")
            ->groupBy('flow_type')
            ->orderByDesc('total')
            ->get();

        // — Daily trend (last 7 days) —
        $dailyStats = collect();
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayStart = Carbon::parse($day);
            $dayEnd = Carbon::parse($day)->endOfDay();

            $sent = MessageDeliveryLog::where('direction', 'outbound')
                ->whereBetween('sent_at', [$dayStart, $dayEnd])->count();
            $dayDelivered = MessageDeliveryLog::where('direction', 'outbound')
                ->whereBetween('sent_at', [$dayStart, $dayEnd])
                ->whereIn('status', ['delivered', 'read'])->count();
            $dayFailed = MessageDeliveryLog::where('direction', 'outbound')
                ->whereBetween('sent_at', [$dayStart, $dayEnd])
                ->where('status', 'failed')->count();

            $dailyStats->push([
                'date'      => $day,
                'label'     => Carbon::parse($day)->format('D'),
                'sent'      => $sent,
                'delivered' => $dayDelivered,
                'failed'    => $dayFailed,
            ]);
        }

        // — Recent activity —
        $recentActivity = MessageDeliveryLog::with('user')
            ->where('sent_at', '>=', $dateFrom)
            ->orderByDesc('sent_at')
            ->limit(20)
            ->get();

        return view('admin.whatsapp.dashboard', compact(
            'period',
            'totalSent',
            'delivered',
            'failed',
            'pending',
            'inbound',
            'deliveryRate',
            'failureRate',
            'linkedUsers',
            'optedIn',
            'flowBreakdown',
            'dailyStats',
            'recentActivity',
        ));
    }
}
