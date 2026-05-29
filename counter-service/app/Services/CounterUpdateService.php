<?php

namespace App\Services;

use App\Models\UserUnreadCount;
use App\Models\DialogUnreadCount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CounterUpdateService
{
    /**
     * Handle message.sent event
     *
     * @param array $event
     * @return void
     */
    public function handleMessageSent(array $event): void
    {
        $dialogId = $event['dialog_id'] ?? null;
        $authorUserId = $event['author_user_id'] ?? null;
        $recipientUserId = $event['recipient_user_id'] ?? null;

        if (!$dialogId || !$authorUserId || !$recipientUserId) {
            Log::warning('Invalid message.sent event', $event);
            return;
        }

        DB::transaction(function () use ($dialogId, $recipientUserId) {
            // Update dialog unread count for recipient
            $dialogCount = DialogUnreadCount::firstOrNew([
                'dialog_id' => $dialogId,
                'user_id' => $recipientUserId,
            ]);
            // Ensure unread_count is not null
            $current = $dialogCount->unread_count ?? 0;
            $dialogCount->unread_count = $current + 1;
            $dialogCount->save();

            // Update total unread count for recipient
            $userCount = UserUnreadCount::firstOrNew(['user_id' => $recipientUserId]);
            $currentTotal = $userCount->total_unread ?? 0;
            $userCount->total_unread = $currentTotal + 1;
            $userCount->save();

            // Invalidate cache
            Cache::forget("user:{$recipientUserId}:total_unread");
            Cache::forget("user:{$recipientUserId}:dialog_unreads");
            Cache::forget("user:{$recipientUserId}:dialog:{$dialogId}:unread");

            Log::debug('Counters updated', [
                'dialog_id' => $dialogId,
                'user_id' => $recipientUserId,
                'dialog_unread' => $dialogCount->unread_count,
                'total_unread' => $userCount->total_unread,
            ]);
        });

        Log::info('Counter updated for message.sent', [
            'dialog_id' => $dialogId,
            'recipient_user_id' => $recipientUserId,
        ]);
    }

    /**
     * Handle dialog.opened event (user read messages in dialog)
     *
     * @param array $event
     * @return void
     */
    public function handleDialogOpened(array $event): void
    {
        $dialogId = $event['dialog_id'] ?? null;
        $userId = $event['user_id'] ?? null;

        if (!$dialogId || !$userId) {
            Log::warning('Invalid dialog.opened event', $event);
            return;
        }

        DB::transaction(function () use ($dialogId, $userId) {
            // Get current unread count for this dialog
            $dialogCount = DialogUnreadCount::where('dialog_id', $dialogId)
                ->where('user_id', $userId)
                ->first();

            if (!$dialogCount || $dialogCount->unread_count == 0) {
                return;
            }

            $decrement = $dialogCount->unread_count;

            // Reset dialog unread count to zero
            $dialogCount->unread_count = 0;
            $dialogCount->save();

            // Decrease total unread count
            $userCount = UserUnreadCount::where('user_id', $userId)->first();
            if ($userCount) {
                $userCount->total_unread = max(0, $userCount->total_unread - $decrement);
                $userCount->save();
            }

            // Invalidate cache
            Cache::forget("user:{$userId}:total_unread");
            Cache::forget("user:{$userId}:dialog_unreads");
            Cache::forget("user:{$userId}:dialog:{$dialogId}:unread");
        });

        Log::info('Counter reset for dialog.opened', [
            'dialog_id' => $dialogId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Handle compensation event (rollback counter increment)
     *
     * @param array $event
     * @return void
     */
    public function handleCompensation(array $event): void
    {
        $dialogId = $event['dialog_id'] ?? null;
        $recipientUserId = $event['recipient_user_id'] ?? null;

        if (!$dialogId || !$recipientUserId) {
            Log::warning('Invalid compensation event', $event);
            return;
        }

        DB::transaction(function () use ($dialogId, $recipientUserId) {
            // Decrease dialog unread count for recipient
            $dialogCount = DialogUnreadCount::where('dialog_id', $dialogId)
                ->where('user_id', $recipientUserId)
                ->first();

            if ($dialogCount && $dialogCount->unread_count > 0) {
                $dialogCount->unread_count = max(0, $dialogCount->unread_count - 1);
                $dialogCount->save();
            }

            // Decrease total unread count for recipient
            $userCount = UserUnreadCount::where('user_id', $recipientUserId)->first();
            if ($userCount && $userCount->total_unread > 0) {
                $userCount->total_unread = max(0, $userCount->total_unread - 1);
                $userCount->save();
            }

            // Invalidate cache
            Cache::forget("user:{$recipientUserId}:total_unread");
            Cache::forget("user:{$recipientUserId}:dialog_unreads");
            Cache::forget("user:{$recipientUserId}:dialog:{$dialogId}:unread");

            Log::debug('Counters compensated', [
                'dialog_id' => $dialogId,
                'user_id' => $recipientUserId,
                'dialog_unread' => $dialogCount ? $dialogCount->unread_count : 0,
                'total_unread' => $userCount ? $userCount->total_unread : 0,
            ]);
        });

        Log::info('Counter compensated', [
            'dialog_id' => $dialogId,
            'recipient_user_id' => $recipientUserId,
        ]);
    }
}
