<?php
/**
 * SPDX-License-Identifier: MIT
 */
namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

/**
 * Librarian notifications. Mirrors the other role controllers (Student/Teacher/
 * Accountant/Receptionist/Parent) and is entirely scoped to the authenticated
 * user's own notifications — never a caller-supplied id.
 */
class NotificationController extends Controller
{
    /** Unread + read lists for the notifications page. */
    public function indexList()
    {
        $array = [];

        $unread = \DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->get();

        $read = \DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->whereNotNull('read_at')
            ->orderBy('read_at', 'ASC')
            ->get();

        $array['read_list']   = \App\Http\Resources\Notification::collection($read);
        $array['unread_list'] = \App\Http\Resources\Notification::collection($unread);

        return $array;
    }

    public function index()
    {
        return view('library/notification/index');
    }

    /** Mark one notification (or all) as read — only the caller's own. */
    public function store(Request $request)
    {
        try {
            if (! Auth::user()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            if ($request->notification_id != 'all') {
                \DB::table('notifications')
                    ->where('id', $request->notification_id)
                    ->where('notifiable_id', Auth::id())
                    ->whereNull('read_at')
                    ->update(['read_at' => Carbon::now()]);

                return ['success' => 'Notification Read Successfully'];
            }

            \DB::table('notifications')
                ->where('notifiable_id', Auth::id())
                ->whereNull('read_at')
                ->update(['read_at' => Carbon::now()]);

            return ['success' => 'All Notifications Read Successfully'];
        } catch (Exception $e) {
            return response()->json(['error' => 'Unable to update notification'], 500);
        }
    }

    /** Latest unread notifications for the header bell (max 5). */
    public function showList()
    {
        try {
            $array = [];

            if (Auth::user()) {
                $array['count'] = count(Auth::user()->unreadNotifications);
                $notifications = Auth::user()->unreadNotifications->take(5);

                $i = 0;
                foreach ($notifications as $notification) {
                    $val = '';
                    $type = null;
                    if ((count($notification->data) > 0) && (isset($notification->data['data']))) {
                        // Cast: payloads may be a plain string (NewMessageNotification)
                        // or a ['data' => ..., 'type' => ...] array.
                        if (count((array) $notification->data['data']) > 1) {
                            $val = $notification->data['data']['data'];
                            $type = $notification->data['data']['type'] ?? null;
                        } else {
                            $val = $notification->data['data'];
                        }
                    }
                    $array['list'][$i]['notification_id'] = $notification['id'];
                    $array['list'][$i]['data'] = $val;
                    $array['list'][$i]['type'] = $type;
                    $array['list'][$i]['date'] = $notification->created_at->diffForHumans();
                    $i++;
                }
            }

            return $array;
        } catch (Exception $e) {
            return ['count' => 0, 'list' => []];
        }
    }
}
