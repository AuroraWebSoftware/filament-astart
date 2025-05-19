<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Chat;

use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Result;

class ChatResult implements Result
{

    public string $response;

    public function __construct($response)
    {
        $this->response = $response;
    }
}
