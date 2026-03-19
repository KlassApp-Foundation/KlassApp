<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now()->format('Y-m-d H:i:s'); // or use Carbon\Carbon::now()

        $templates = [
            [
                'id'           => 1,
                'name'         => 'login',
                'subject'      => 'Logged In',
                'mail_content' => "Hi :name <br>
                                Successful Authorization. <br>
                                You have successfully logged into your account. <br>
                                Don\"t recognize this activity? <br>
                                Please change password for your email immediately <br>
                                Thanks & Regards <br>
                                Administration Team <br>",
                'status'       => 'active',
            ],

            [
                'id'           => 2,
                'name'         => 'new_user_register',
                'subject'      => 'New User Registration',
                'mail_content' => "Hello ! <br>
                                New user has registered - :mail. Please login to see details.<br>
                                Thanks & Regards <br>
                                Administration Team <br>",
                'status'       => 'active',
            ],

            [
                'id'           => 3,
                'name'         => 'reset_password',
                'subject'      => 'Reset Password',
                'mail_content' => "Hi :name <br>
                                Please click below link to reset your password. <br>
                                <a href=\":resetlink\" style=\"border: none; color: white; padding: 10px 15px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; background-color: #008CBA;\">Reset Password</a> <br>
                                Thanks & Regards <br>
                                Administration Team <br>",
                'status'       => 'active',
            ],

            [
                'id'           => 4,
                'name'         => 'expired_approve_alert',
                'subject'      => 'New Expired Alert',
                'mail_content' => "Hi <br>
                                Subscription Expiration Details<br><br>

                                School Name - :school_name <br>
                                User Name   - :name <br>
                                End Date    - :end_date <br><br>

                                New expired has been posted for this school <br>
                                If you want to continue subscription , Please click the below link<br><br>

                                :url <br><br>

                                Thanks & Regards <br>
                                Administration Team",
                'status'       => 'active',
            ],

            [
                'id'           => 5,
                'name'         => 'site_expired_mail',
                'subject'      => 'Site Expired Mail',
                'mail_content' => "Hi <br>
                                Subscription Expiration Details<br><br>

                                School Name - :school_name <br>
                                User Name   - :name <br>
                                End Date    - :end_date <br><br>

                                Your site going to expiry within a week <br><br>
                                Thanks & Regards <br>
                                Administration Team",
                'status'       => 'active',
            ],

            [
                'id'           => 6,
                'name'         => 'contact',
                'subject'      => 'Contact',
                'mail_content' => "Hi <br>
                                Contact Details.<br><br>

                                Name          - :fullname <br>
                                Email         - :emailid <br>
                                Serve         - :serve_at <br>
                                Role          - :role <br>
                                Phone number  - :contact_no <br>
                                Select        - :select <br><br>

                                Contact Details Created <br>
                                Thanks & Regards <br>
                                Administration Team",
                'status'       => 'active',
            ],

            [
                'id'           => 7,
                'name'         => 'send_mail',
                'subject'      => ':subject',
                'mail_content' => "Hi :name, <br>
                                :message <br>
                                <a href=\":attachments\" style=\"border:none; color:white; padding:10px 15px; text-align:center; text-decoration:none; display:inline-block; font-size:16px; margin:4px 2px; cursor: pointer; background-color:#008CBA;\">Click Here</a> <br>
                                Thanks & Regards <br>
                                Administration Team",
                'status'       => 'active',
            ],

            [
                'id'           => 8,
                'name'         => 'calendar_event',
                'subject'      => 'Calendar Event',
                'mail_content' => "Hi <br>
                                Event Details.<br><br>

                                Title       - :title <br>
                                Location    - :location <br>
                                Category    - :category <br>
                                Start Date  - :start_date <br>
                                End Date    - :end_date <br><br>

                                New Event Created <br>
                                Thanks & Regards <br>
                                Administration Team",
                'status'       => 'active',
            ],

            [
                'id'           => 9,
                'name'         => 'event_reminder',
                'subject'      => 'Event Reminder',
                'mail_content' => "Hi <br>
                                Event Reminder Details<br><br>

                                School Name   - :school_name <br>
                                Title         - :title <br>
                                Description   - :description <br>
                                Location      - :location <br>
                                Start Date    - :start_date <br>
                                End Date      - :end_date <br><br>

                                New event has been posted for this church.<br><br>
                                Thanks & Regards <br>
                                Administration Team",
                'status'       => 'active',
            ],

            [
                'id'           => 10,
                'name'         => 'birthday_reminder',
                'subject'      => 'Birthday Wishes',
                'mail_content' => ":message <br><br>
                                Thanks & Regards <br>
                                Administration Team",
                'status'       => 'active',
            ],

            [
                'id'           => 11,
                'name'         => 'email_verification',
                'subject'      => 'Email Verification',
                'mail_content' => "Hi :name <br>
                                To verify your account.<br>
                                <a href=\":url\" style=\"border: none; color: white; padding: 10px 15px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; background-color: #008CBA;\">Click here to verify</a> <br>
                                Thanks & Regards <br>
                                Administration Team <br>",
                'status'       => 'active',
            ],

            [
                'id'           => 12,
                'name'         => 'new_message',
                'subject'      => 'Send Mail to User',
                'mail_content' => "Hi :name, <br>
                                :message <br>
                                Thanks & Regards <br>
                                Administration Team <br>",
                'status'       => 'active',
            ],

            [
                'id'           => 13,
                'name'         => 'room_invitation',
                'subject'      => 'Room Invitation',
                'mail_content' => "Hi :name, <br>
                                Title - :title <br>
                                Description - :description <br>
                                :message <br>
                                Thanks & Regards <br>
                                Administration Team <br>",
                'status'       => 'active',
            ],

            [
                'id'           => 14,
                'name'         => 'change_password',
                'subject'      => 'Change Password',
                'mail_content' => "Hi :name <br>
                                Your Password is changed successfully. <br>
                                Thanks & Regards <br>
                                Administration Team <br>",
                'status'       => 'active',
            ],

            [
                'id'           => 15,
                'name'         => 'admission_confirmation',
                'subject'      => 'Admission Confirmation Mail',
                'mail_content' => "Hi Sir/Madam <br>
                                Your Application No. :application_no for the admission in :school_name has been approved. <br>
                                Thanks & Regards <br>
                                Administration Team <br>",
                'status'       => 'active',
            ],
        ];

        foreach ($templates as $template) {
            DB::table('mailtemplates')->updateOrInsert(
                ['id' => $template['id']],
                [
                    ...$template,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}