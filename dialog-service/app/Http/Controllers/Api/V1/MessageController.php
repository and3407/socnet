<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dialog;
use App\Models\DialogMessage;
use App\Models\DialogUser;
use App\Services\RabbitMQPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Send a message to a user (create or use existing dialog)
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_user_id' => 'required|integer|min:1',
            'content' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $authorUserId = $request->header('X-User-Id');
        if (!$authorUserId) {
            return response()->json(['error' => 'Missing X-User-Id header'], 401);
        }

        $authorUserId = (int) $authorUserId;
        $recipientUserId = $request->input('recipient_user_id');
        $content = $request->input('content');

        // Find or create dialog
        $dialog = $this->findOrCreateDialog($authorUserId, $recipientUserId);

        // Create message
        $message = DialogMessage::create([
            'dialog_id' => $dialog->id,
            'author_user_id' => $authorUserId,
            'content' => $content,
        ]);

        Log::info('Message sent', [
            'message_id' => $message->id,
            'dialog_id' => $dialog->id,
            'author_user_id' => $authorUserId,
            'recipient_user_id' => $recipientUserId,
            'request_id' => $request->header('X-Request-Id'),
        ]);

        // Publish event for counter service
        try {
            $publisher = new RabbitMQPublisher();
            $publisher->publish('message.sent', [
                'dialog_id' => $dialog->id,
                'author_user_id' => $authorUserId,
                'recipient_user_id' => $recipientUserId,
                'message_id' => $message->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish RabbitMQ event: ' . $e->getMessage());
        }

        return response()->json([
            'messageId' => $message->id,
            'dialogId' => $dialog->id,
        ], 201);
    }

    private function findOrCreateDialog(int $user1, int $user2): Dialog
    {
        // Ensure user1 < user2 for consistent ordering
        $minUserId = min($user1, $user2);
        $maxUserId = max($user1, $user2);

        // Look for existing dialog via dialog_users
        $dialog = Dialog::whereHas('dialogUsers', function ($query) use ($minUserId) {
            $query->where('user_id', $minUserId)->whereNull('deleted_at');
        })->whereHas('dialogUsers', function ($query) use ($maxUserId) {
            $query->where('user_id', $maxUserId)->whereNull('deleted_at');
        })->first();

        if ($dialog) {
            return $dialog;
        }

        // Create new dialog
        return DB::transaction(function () use ($user1, $user2) {
            $dialog = Dialog::create([
                'name' => null,
                'creater_user_id' => $user1,
            ]);

            DB::table('dialog_users')->insert([
                ['dialog_id' => $dialog->id, 'user_id' => $user1],
                ['dialog_id' => $dialog->id, 'user_id' => $user2],
            ]);

            return $dialog;
        });
    }

    /**
     * Get dialog messages between current user and another user
     */
    public function getDialog(Request $request, $userId)
    {
        $currentUserId = $request->header('X-User-Id');
        if (!$currentUserId) {
            return response()->json(['error' => 'Missing X-User-Id header'], 401);
        }

        $currentUserId = (int) $currentUserId;
        $otherUserId = (int) $userId;

        $dialog = $this->findOrCreateDialog($currentUserId, $otherUserId);

        // Publish event that user opened dialog (read messages)
        try {
            $publisher = new RabbitMQPublisher();
            $publisher->publish('dialog.opened', [
                'dialog_id' => $dialog->id,
                'user_id' => $currentUserId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish RabbitMQ event: ' . $e->getMessage());
        }

        $messages = DialogMessage::where('dialog_id', $dialog->id)
            ->orderBy('id', 'desc')
            ->get(['id', 'author_user_id', 'content', 'created_at']);

        Log::info('Dialog retrieved', [
            'dialog_id' => $dialog->id,
            'user_ids' => [$currentUserId, $otherUserId],
            'message_count' => $messages->count(),
            'request_id' => $request->header('X-Request-Id'),
        ]);

        return response()->json($messages);
    }

    /**
     * Mark dialog as read for current user
     */
    public function markAsRead(Request $request, $dialogId)
    {
        $currentUserId = $request->header('X-User-Id');
        if (!$currentUserId) {
            return response()->json(['error' => 'Missing X-User-Id header'], 401);
        }

        // Check if dialog exists and user is a participant
        $dialog = Dialog::find($dialogId);
        if (!$dialog) {
            return response()->json(['error' => 'Dialog not found'], 404);
        }

        $isParticipant = DB::table('dialog_users')
            ->where('dialog_id', $dialogId)
            ->where('user_id', $currentUserId)
            ->whereNull('deleted_at')
            ->exists();

        if (!$isParticipant) {
            return response()->json(['error' => 'User is not a participant of this dialog'], 403);
        }

        // Update readed_at timestamp
        DB::table('dialog_users')
            ->where('dialog_id', $dialogId)
            ->where('user_id', $currentUserId)
            ->update(['readed_at' => DB::raw('NOW()')]);

        // Publish dialog.opened event for counter service
        try {
            $publisher = new RabbitMQPublisher();
            $publisher->publish('dialog.opened', [
                'dialog_id' => $dialogId,
                'user_id' => $currentUserId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish RabbitMQ event: ' . $e->getMessage());
        }

        Log::info('Dialog marked as read', [
            'dialog_id' => $dialogId,
            'user_id' => $currentUserId,
            'request_id' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['success' => true]);
    }
}
