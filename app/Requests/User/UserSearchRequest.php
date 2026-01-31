<?php

namespace App\Requests\User;

use App\Requests\Request;

class UserSearchRequest extends Request
{
    public function validation(): array
    {
        $data = $this->getQueryParams();

        return [
            'first_name' => $data['first_name'],
            'second_name' => $data['last_name'],
        ];
    }
}