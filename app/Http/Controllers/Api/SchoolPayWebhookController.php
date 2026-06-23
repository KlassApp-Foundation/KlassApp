<?php
/**
 * School Pay Webhook Controller
 *
 * Receives real-time payment notifications from School Pay (schoolpay.co.ug),
 * verifies the SHA256 signature, matches the student to their parent's WhatsApp
 * number, and sends an instant receipt.
 *
 * Join chain:
 *   school_pay_code → schools.id
 *       ↓
 *   student_academics.std_school_pay_number → student_academics.user_id (student)
 *       ↓
 *   student_parent_links.student_id → student_parent_links.parent_id
 *       ↓
 *   whatsapp_users.user_id (parent) → whatsapp_users.phone
 *
 * Webhook payload (School Pay → KlassApp):
 *   POST /api/schoolpay/webhook
 *   {
 *     "signature": "sha256_hmac_of_receipt",
 *     "payment": {
 *       "studentPaymentCode": "1005416321",
 *       "studentName": "Nakamya Gloria",
 *       "studentRegistrationNumber": "REG-2024-0891",
 *       "studentClass": "P.6",
 *       "amount": 350000,
 *       "schoolpayReceiptNumber": "SP-20260623-001947",
 *       "sourcePaymentChannel": "MTN_MoMo",
 *       "transactionCompletionStatus": "COMPLETED"
 *     }
 *   }
 *   (Also tolerates flat payload without 'payment' wrapper during pilot.)
 *
 * Security: School Pay signs each webhook using SHA256 HMAC:
 *   signature = HMAC-SHA256(schoolpayReceiptNumber, apiPassword)
 *   Sent in the JSON body as the "signature" field.
 *
 * SPDX-License-Identifier: MIT
 * (c) 2026 KlassApp / GegoSoft Technologies
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageDeliveryLog;
use App\Models\School;
use App\Models\WhatsAppUser;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SchoolPayWebhookController extends Controller
{
    /**
     * Handle an incoming School Pay webhook.
     *
     * POST /api/schoolpay/webhook
     */
    public function handle(Request $request, WhatsAppService $whatsAppService): Response
    {
        // ── Parse the payload ──────────────────
        // School Pay webhook sends:
        // {
        //   "signature": "sha256_hmac_of_receipt",
        //   "payment": {
        //     "studentPaymentCode": "...",
        //     "schoolpayReceiptNumber": "...",
        //     "amount": "...",
        //     "studentName": "...",
        //     "studentClass": "...",
        //     "sourcePaymentChannel": "...",
        //     ...
        //   }
        // }
        $all = $request->all();

        $signature = $all['signature'] ?? null;
        $payment   = $all['payment'] ?? $all; // tolerate flat payload during pilot

        $studentPaymentCode = $payment['studentPaymentCode'] ?? null;
        $receiptNumber      = $payment['schoolpayReceiptNumber'] ?? null;
        $amount             = $payment['amount'] ?? null;
        $studentName        = $payment['studentName'] ?? null;
        $studentClass       = $payment['studentClass'] ?? null;
        $channel            = $payment['sourcePaymentChannel'] ?? null;
        $completionStatus   = $payment['transactionCompletionStatus'] ?? null;

        // ── Basic validation ──────────────────
        if (!$studentPaymentCode || !$receiptNumber) {
            Log::warning('SchoolPay webhook: missing required fields', compact('studentPaymentCode', 'receiptNumber'));
            return response('Missing required fields', 400);
        }

        // ── Deduplicate by receipt ────────────
        $alreadyExists = DB::table('schoolpay_transactions')
            ->where('schoolpay_receipt', $receiptNumber)
            ->exists();

        if ($alreadyExists) {
            Log::info('SchoolPay webhook: duplicate receipt', ['receipt' => $receiptNumber]);
            return response('OK — already processed', 200);
        }

        // ── Find the student (and thus the school) by payment code ──
        // School Pay doesn't include a school identifier in the webhook.
        // We find the student by their unique payment code, which gives us the school.
        $studentAcademic = DB::table('student_academics')
            ->where('std_school_pay_number', $studentPaymentCode)
            ->whereNull('deleted_at')
            ->first();

        $studentUserId = $studentAcademic?->user_id;
        $schoolId      = $studentAcademic?->school_id;

        // ── Find the school (for signature verification and webhook check) ──
        $school = null;
        if ($schoolId) {
            $school = School::find($schoolId);
        }

        // ── Verify SHA256 signature ──────────
        // School Pay signs: HMAC-SHA256(schoolpayReceiptNumber, apiPassword)
        // The signature comes in the JSON body as the "signature" field.
        if ($signature && $school?->school_pay_api_password) {
            $expected = hash_hmac('sha256', $receiptNumber, $school->school_pay_api_password);

            if (!hash_equals($expected, $signature)) {
                Log::warning('SchoolPay webhook: invalid signature', [
                    'school_id'  => $school->id,
                    'receipt'    => $receiptNumber,
                    'expected'   => $expected,
                    'received'   => $signature,
                ]);
                return response('Invalid signature', 403);
            }
        }
        // During pilot: if no signature present, we proceed and log.
        // Production should reject unsigned webhooks once format is confirmed.

        if (!$school) {
            Log::info('SchoolPay webhook: student payment code not found in KlassApp', [
                'studentPaymentCode' => $studentPaymentCode,
                'studentName'        => $studentName,
            ]);
            return response('OK — student not enrolled in KlassApp', 200);
        }

        if (!$school->school_pay_webhook_enabled) {
            Log::info('SchoolPay webhook: webhook not enabled for school', ['school_id' => $school->id]);
            return response('OK — webhook not enabled', 200);
        }

        // ── Record the transaction ───────────
        $transactionId = DB::table('schoolpay_transactions')->insertGetId([
            'school_id'             => $schoolId,
            'student_id'            => $studentUserId,
            'student_payment_code'  => $studentPaymentCode,
            'schoolpay_receipt'     => $receiptNumber,
            'amount'                => $amount ?? 0,
            'channel'               => $channel,
            'student_name'          => $studentName,
            'student_class'         => $studentClass,
            'source'                => 'webhook',
            'raw_payload'           => json_encode($all),
            'notification_status'   => 'pending',
            'paid_at'               => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // ── No KlassApp student match — still save, just can't send WhatsApp ──
        if (!$studentUserId) {
            DB::table('schoolpay_transactions')
                ->where('id', $transactionId)
                ->update(['notification_status' => 'skipped']);

            Log::info('SchoolPay webhook: student not in KlassApp', [
                'school_id'          => $schoolId,
                'studentPaymentCode' => $studentPaymentCode,
                'studentName'        => $studentName,
            ]);
            return response('OK — student not enrolled in KlassApp', 200);
        }

        // ── Find the parent ──────────────────
        $parentLink = DB::table('student_parent_links')
            ->where('student_id', $studentUserId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (!$parentLink) {
            DB::table('schoolpay_transactions')
                ->where('id', $transactionId)
                ->update(['notification_status' => 'skipped']);

            Log::info('SchoolPay webhook: no parent linked', [
                'student_id' => $studentUserId,
                'studentPaymentCode' => $studentPaymentCode,
            ]);
            return response('OK — no parent linked to student', 200);
        }

        // ── Find the parent's WhatsApp number ──
        $whatsappUser = WhatsAppUser::where('user_id', $parentLink->parent_id)
            ->where('opted_in', true)
            ->first();

        if (!$whatsappUser) {
            DB::table('schoolpay_transactions')
                ->where('id', $transactionId)
                ->update(['notification_status' => 'skipped']);

            Log::info('SchoolPay webhook: parent has no WhatsApp linked', [
                'parent_id' => $parentLink->parent_id,
                'studentPaymentCode' => $studentPaymentCode,
            ]);
            return response('OK — parent has no WhatsApp', 200);
        }

        // ── Send the WhatsApp receipt ────────
        $displayName = $studentName ?? 'Your child';
        $classLine = $studentClass ? " — {$studentClass}" : '';
        $amountFormatted = number_format((float) ($amount ?? 0));

        $description = "*{$school->name}*\n"
            . "{$displayName}{$classLine}\n\n"
            . "UGX {$amountFormatted} received via {$channel}\n"
            . "Receipt: {$receiptNumber}";

        try {
            $result = $whatsAppService->sendList(
                phone: $whatsappUser->phone,
                title: '✅ Payment Received',
                sections: [
                    [
                        'title' => 'Quick Actions',
                        'rows' => [
                            ['title' => '💰 Fee Balance', 'description' => 'Check what you owe'],
                            ['title' => '📊 Exam Results', 'description' => 'View latest grades'],
                            ['title' => '📋 Attendance', 'description' => 'See attendance records'],
                            ['title' => '🔗 Link Another Student', 'description' => 'Add another child using payment code'],
                            ['title' => '❓ Help & Options', 'description' => 'Full menu and support'],
                        ],
                    ],
                ],
                description: $description,
                footerText: 'Tap an option for more',
                flowType: 'fee_receipt',
                userId: $whatsappUser->user_id,
            );

            $notificationStatus = $result['success'] ? 'sent' : 'failed';

            DB::table('schoolpay_transactions')
                ->where('id', $transactionId)
                ->update(['notification_status' => $notificationStatus]);

            Log::info('SchoolPay webhook: WhatsApp receipt ' . $notificationStatus, [
                'phone'               => $whatsappUser->phone,
                'studentPaymentCode'  => $studentPaymentCode,
                'receipt'             => $receiptNumber,
                'message_id'          => $result['message_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            DB::table('schoolpay_transactions')
                ->where('id', $transactionId)
                ->update(['notification_status' => 'failed']);

            Log::error('SchoolPay webhook: WhatsApp send exception', [
                'phone'  => $whatsappUser->phone,
                'error'  => $e->getMessage(),
                'receipt' => $receiptNumber,
            ]);
        }

        return response('OK', 200);
    }
}
