<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserUnreadCount;
use App\Models\DialogUnreadCount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CounterController extends Controller
{
    /**
     * Get total unread messages count for authenticated user
     */
    public function total(Request $request)
    {
        $userId = $request->header('X-User-Id');
        if (!$userId) {
            return response()->json(['error' => 'Missing X-User-Id header'], 401);
        }

        // Try cache first
        $cacheKey = "user:{$userId}:total_unread";
        $total = Cache::remember($cacheKey, 60, function () use ($userId) {
            $record = UserUnreadCount::where('user_id', $userId)->first();
            return $record ? $record->total_unread : 0;
        });

        return response()->json(['total_unread' => (int) $total]);
    }

    /**
     * Get unread counts for all dialogs of authenticated user
     */
    public function dialogs(Request $request)
    {
        $userId = $request->header('X-User-Id');
        if (!$userId) {
            return response()->json(['error' => 'Missing X-User-Id header'], 401);
        }

        $cacheKey = "user:{$userId}:dialog_unreads";
        $counts = Cache::remember($cacheKey, 60, function () use ($userId) {
            return DialogUnreadCount::where('user_id', $userId)
                ->select('dialog_id', 'unread_count')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->dialog_id => $item->unread_count];
                })
                ->toArray();
        });

        return response()->json(['dialogs' => $counts]);
    }

    /**
     * Get unread count for specific dialog
     */
    public function dialog(Request $request, $dialogId)
    {
        $userId = $request->header('X-User-Id');
        if (!$userId) {
            return response()->json(['error' => 'Missing X-User-Id header'], 401);
        }

        $cacheKey = "user:{$userId}:dialog:{$dialogId}:unread";
        $count = Cache::remember($cacheKey, 60, function () use ($userId, $dialogId) {
            $record = DialogUnreadCount::where('user_id', $userId)
                ->where('dialog_id', $dialogId)
                ->first();
            return $record ? $record->unread_count : 0;
        });

        return response()->json([
            'dialog_id' => (int) $dialogId,
            'unread_count' => (int) $count,
        ]);
    }

    /**
     * Get total unread count for user by ID (public endpoint)
     */
    public function user(Request $request, $userId)
    {
        $cacheKey = "user:{$userId}:total_unread";
        $total = Cache::remember($cacheKey, 60, function () use ($userId) {
            $record = UserUnreadCount::where('user_id', $userId)->first();
            return $record ? $record->total_unread : 0;
        });

        return response()->json(['total_unread' => (int) $total]);
    }
}
