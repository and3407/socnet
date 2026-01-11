<?php

namespace App\Requests;

abstract class Request
{
    public function validation(): array
    {
        return [];
    }

    public function getJsonData(): array
    {
        $jsonData = file_get_contents('php://input');

        return json_decode($jsonData, true);
    }

    public function getQueryParams(): array
    {
        return $_GET;
    }
}