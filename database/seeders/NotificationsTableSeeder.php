<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notifications = [
            [
                'id'              => Str::uuid()->toString(),
                'type'            => 'App\\Notifications\\NewMessageNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id'   => 2,
                'data'            => json_encode([
                    'message' => 'You have been removed from Video Room #45',
                    'room_id' => 45,
                    'actor'   => 'System',
                ]),
                'read_at'         => null,
            ],

            [
                'id'              => Str::uuid()->toString(),
                'type'            => 'App\\Notifications\\LikeNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id'   => 2,
                'data'            => json_encode([
                    'message'    => 'Briana Wiegand liked your comment on post #128',
                    'post_id'    => 128,
                    'comment_id' => 456,
                    'actor'      => 'Briana Wiegand',
                    'actor_id'   => 15,
                ]),
                'read_at'         => null,
            ],

            [
                'id'              => Str::uuid()->toString(),
                'type'            => 'App\\Notifications\\LikeNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id'   => 2,
                'data'            => json_encode([
                    'message'    => 'Briana Wiegand removed their like from your comment on post #128',
                    'post_id'    => 128,
                    'comment_id' => 456,
                    'actor'      => 'Briana Wiegand',
                    'actor_id'   => 15,
                ]),
                'read_at'         => now()->subHours(2), // Mark one as read for realism
            ],

            // Bonus: add one more variety (e.g. unread reply notification)
            [
                'id'              => Str::uuid()->toString(),
                'type'            => 'App\\Notifications\\ReplyNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id'   => 2,
                'data'            => json_encode([
                    'message'    => 'John Doe replied to your post: "Great insight!"',
                    'post_id'    => 201,
                    'reply_id'   => 789,
                    'actor'      => 'John Doe',
                    'actor_id'   => 7,
                ]),
                'read_at'         => null,
            ],
        ];

        foreach ($notifications as $notification) {
            DB::table('notifications')->updateOrInsert(
                ['id' => $notification['id']],
                [
                    ...$notification,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}