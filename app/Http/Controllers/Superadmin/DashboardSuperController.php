<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Superadmin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WhatsAppUser;
use App\Models\MessageDeliveryLog;
use App\Models\User;

class DashboardSuperController extends Controller
{
    /**
     * Show the superadmin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $admin_id  = Auth::id();

        $stats = Cache::remember('superadmin_dashboard_stats', 300, function () {
            $now = now();

            // ── School metrics ──
            $totalSchools     = School::count();
            $activeSchools    = School::where('status', 1)->count();
            $inactiveSchools  = $totalSchools - $activeSchools;
            $schoolsThisMonth = School::where('created_at', '>=', $now->copy()->startOfMonth())->count();
            $schoolsLastMonth = School::whereBetween('created_at', [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ])->count();

            // ── User metrics ──
            $totalUsers       = User::count();
            $schoolAdmins     = User::where('usergroup_id', 3)->count();
            $teachers         = User::where('usergroup_id', 5)->count();
            $students         = User::where('usergroup_id', 6)->count();
            $parents          = User::where('usergroup_id', 7)->count();
            $usersThisMonth   = User::where('created_at', '>=', $now->copy()->startOfMonth())->count();
            $usersLastMonth   = User::whereBetween('created_at', [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ])->count();

            // ── Subscription metrics ──
            $subscriptionsTotal = Subscription::count();
            $activeSubs = Subscription::where('status', 'active')->count();
            $expiredSubs = Subscription::where('status', 'expired')->count();
            $cancelledSubs = Subscription::where('status', 'cancelled')->count();

            // Revenue: sum of plan amounts for active subscriptions
            $estimatedMRR = DB::table('subscriptions')
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->where('subscriptions.status', 'active')
                ->sum('plans.amount');

            // ── WhatsApp metrics ──
            $whatsappUsers     = WhatsAppUser::count();
            $whatsappMessages  = MessageDeliveryLog::whereDate('sent_at', '>=', $now->copy()->startOfMonth())
                ->whereDate('sent_at', '<=', $now->copy()->endOfMonth())
                ->count();
            $whatsappLastMonth = MessageDeliveryLog::whereDate('sent_at', '>=', $now->copy()->subMonth()->startOfMonth())
                ->whereDate('sent_at', '<=', $now->copy()->subMonth()->endOfMonth())
                ->count();
            $whatsappSuccessRate = $whatsappMessages > 0
                ? round(MessageDeliveryLog::whereDate('sent_at', '>=', $now->copy()->startOfMonth())
                    ->whereDate('sent_at', '<=', $now->copy()->endOfMonth())
                    ->where('status', 'delivered')->count() / max($whatsappMessages, 1) * 100)
                : 0;

            // ── Plans with subscriber counts and revenue ──
            $subscriptionCounts = Subscription::selectRaw('plan_id, count(*) as count')
                ->groupBy('plan_id')
                ->pluck('count', 'plan_id');

            $plans = Plan::where('is_active', true)
                ->orderBy('amount')
                ->get()
                ->map(function ($plan) use ($subscriptionCounts) {
                    $count = $subscriptionCounts[$plan->id] ?? 0;
                    $plan->subscriptions_count = $count;
                    $plan->estimated_revenue = $count * $plan->amount;
                    return $plan;
                });

            // ── Trends: monthly school/user signups over last 6 months ──
            $monthlyTrends = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $monthlyTrends[] = [
                    'label' => $month->format('M'),
                    'schools' => School::whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)->count(),
                    'users' => User::whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)->count(),
                ];
            }

            // ── System health ──
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs  = DB::table('failed_jobs')->count();

            return compact(
                'totalSchools', 'activeSchools', 'inactiveSchools',
                'schoolsThisMonth', 'schoolsLastMonth',
                'totalUsers', 'schoolAdmins', 'teachers', 'students', 'parents',
                'usersThisMonth', 'usersLastMonth',
                'subscriptionsTotal', 'activeSubs', 'expiredSubs', 'cancelledSubs',
                'estimatedMRR',
                'whatsappUsers', 'whatsappMessages', 'whatsappLastMonth',
                'whatsappSuccessRate',
                'plans',
                'monthlyTrends',
                'pendingJobs', 'failedJobs',
            );
        });

        // ── Recent activity (not cached — always fresh) ──
        $recentSchools = School::latest()->take(5)->get();
        $recentUsers   = User::with('school')->latest()->take(5)->get();

        $stats['recentSchools'] = $recentSchools;
        $stats['recentUsers']   = $recentUsers;
        $stats['refreshedAt']   = now()->diffForHumans();

        return view('/superadmin/dashboard', ['stats' => $stats]);
    }
}
