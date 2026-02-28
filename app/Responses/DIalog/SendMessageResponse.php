<?php

namespace App\Responses\DIalog;

use App\Responses\Response;

class SendMessageResponse extends Response
{
    public function __construct(
        private readonly int $messageId,
        private readonly int $dialogId,
    ) { }

    public static function create(int $messageId, int $dialogId): self
    {
        return new self($messageId, $dialogId);
    }

    public function json(): void
    {
        $result = [
            'messageId' => $this->messageId,
            'dialogId' => $this->dialogId,
        ];

        $this->successResponse($result);
    }
}