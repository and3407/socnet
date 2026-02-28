<?php

namespace App\Responses\DIalog;

use App\Responses\Response;

class GetDialogByUserResponse extends Response
{
    protected array $data;
    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function create(array $data): self
    {
        return new self($data);
    }

    public function json(): void
    {
        $this->successResponse($this->data);
    }
}