<?php

namespace App\Requests\Post;

use App\Requests\Request;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class PostCreateRequest extends Request
{
    private array $validatedData = [];
    private array $errors = [];

    public function validation(): array
    {
        $data = $this->getJsonData();

        $this->validateText($data['text'] ?? null);

        if (!empty($this->errors)) {
            ErrorResponse::createJson('Invalid parameters', HttpCode::BAD_REQUEST, $this->errors);
        }

        $user = $this->getAuthUser();

        return [
            'text' => $this->validatedData['text'],
            'user' => $user,
        ];
    }

    private function validateText(?string $text): void
    {
        if (empty($text)) {
            $this->errors['text'] = 'Text is required';
            return;
        }

        if (!is_string($text)) {
            $this->errors['text'] = 'Text must be a string';
            return;
        }

        if (strlen($text) < 1) {
            $this->errors['text'] = 'Text must be at least 1 character long';
            return;
        }

        if (strlen($text) > 1000) {
            $this->errors['text'] = 'Text must not exceed 1000 characters';
            return;
        }

        $this->validatedData['text'] = trim($text);
    }
}